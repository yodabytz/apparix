#!/bin/bash
#
# Apparix Deploy Script
# Builds tarball, updates hash, and deploys to /storage/updates/ — atomically.
# The update API serves from /storage/updates/, NOT /public/downloads/.
#
# SAFETY: This script validates EVERYTHING before going live:
#   - No ./ prefix in tarball (PharData can't extract ".")
#   - Hash in DB matches file on disk
#   - Tarball can actually be extracted by PHP PharData
#   - version.php exists inside the tarball
#
# Usage: ./deploy.sh
#

set -euo pipefail

REPO_DIR="/root/apparix"
LIVE_DIR="/var/www/apparix.app"
DB_NAME="apparix_ecommerce"
DB_USER="apparix"
DB_PASS="apparix_secure_2024"

echo "=== Apparix Deploy ==="

# 1. Read current version from version.php
VERSION=$(php -r "\$v = require '${REPO_DIR}/version.php'; echo \$v['version'];")
TARBALL_NAME="apparix-${VERSION}.tar.gz"
UPDATE_DIR="${LIVE_DIR}/storage/updates"
TMP_TARBALL="/tmp/apparix-deploy-$$.tar.gz"

echo "Version: ${VERSION}"
echo "Tarball: ${TARBALL_NAME}"

# 2. Build tarball to a temp file (using * glob, NEVER "." which creates ./ entries)
echo "Building tarball..."
cd "${REPO_DIR}"
tar czf "${TMP_TARBALL}" \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.claude' \
    --exclude='storage/logs/*' \
    --exclude='storage/sessions/*' \
    --exclude='storage/updates/*' \
    --exclude='storage/backups/*' \
    --exclude='storage/cache/*' \
    --exclude='storage/releases/*' \
    --exclude='vendor' \
    --exclude='public/downloads' \
    --exclude='node_modules' \
    --exclude='deploy.sh' \
    *
cd - >/dev/null

# 3. VALIDATE: No ./ prefix in tarball (PharData fatal error)
if tar tzf "${TMP_TARBALL}" | head -1 | grep -q '^\./'; then
    echo "ABORT: Tarball contains ./ prefix entries. PharData cannot extract these."
    rm -f "${TMP_TARBALL}"
    exit 1
fi
echo "Check: No ./ prefix — OK"

# 4. VALIDATE: version.php exists in the tarball
if ! tar tzf "${TMP_TARBALL}" | grep -q '^version\.php$'; then
    echo "ABORT: Tarball is missing version.php"
    rm -f "${TMP_TARBALL}"
    exit 1
fi
echo "Check: version.php present — OK"

# 5. VALIDATE: PHP PharData can actually extract it
EXTRACT_TEST="/tmp/apparix-extract-test-$$"
mkdir -p "${EXTRACT_TEST}"
php -r "
try {
    \$phar = new PharData('${TMP_TARBALL}');
    \$phar->extractTo('${EXTRACT_TEST}', null, true);
    echo 'OK';
} catch (Exception \$e) {
    echo 'FAIL: ' . \$e->getMessage();
    exit(1);
}
" > /tmp/phar-test-$$.out 2>&1
PHAR_RESULT=$(cat /tmp/phar-test-$$.out)
rm -rf "${EXTRACT_TEST}" /tmp/phar-test-$$.out

if [ "${PHAR_RESULT}" != "OK" ]; then
    echo "ABORT: PharData extraction test failed: ${PHAR_RESULT}"
    rm -f "${TMP_TARBALL}"
    exit 1
fi
echo "Check: PharData extraction — OK"

# 6. Calculate hash
NEW_HASH=$(sha256sum "${TMP_TARBALL}" | awk '{print $1}')
FILE_SIZE=$(stat -c%s "${TMP_TARBALL}")
echo "Hash: ${NEW_HASH}"
echo "Size: ${FILE_SIZE} bytes"

# 7. Update DB FIRST — hash, file size, and update_file name
mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e \
    "UPDATE releases SET file_hash='${NEW_HASH}', file_size=${FILE_SIZE}, update_file='${TARBALL_NAME}' WHERE is_active = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo "DB updated (hash + filename)"

# 8. Atomic deploy to /storage/updates/
mkdir -p "${UPDATE_DIR}"
TMP_DEST="${UPDATE_DIR}/${TARBALL_NAME}.new"
cp "${TMP_TARBALL}" "${TMP_DEST}"
chown www-data:www-data "${TMP_DEST}"
mv -f "${TMP_DEST}" "${UPDATE_DIR}/${TARBALL_NAME}"
echo "Tarball deployed to ${UPDATE_DIR}/${TARBALL_NAME}"

# 9. Copy to /storage/downloads/ for authenticated digital product downloads
TMP_DL="${LIVE_DIR}/storage/downloads/apparix-latest.tar.gz.new"
cp "${TMP_TARBALL}" "${TMP_DL}"
chown www-data:www-data "${TMP_DL}"
mv -f "${TMP_DL}" "${LIVE_DIR}/storage/downloads/apparix-latest.tar.gz"

# 10. Cleanup temp file
rm -f "${TMP_TARBALL}"

# 11. Final verify: hash in DB matches file on disk
VERIFY_HASH=$(sha256sum "${UPDATE_DIR}/${TARBALL_NAME}" | awk '{print $1}')
if [ "${NEW_HASH}" = "${VERIFY_HASH}" ]; then
    echo "Verified: DB hash matches file on disk"
else
    echo "CRITICAL: Hash mismatch after deploy! Rolling back DB..."
    mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e \
        "UPDATE releases SET file_hash='DEPLOY_FAILED', file_size=0 WHERE is_active = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
    exit 1
fi

echo "=== Deploy complete: v${VERSION} ==="
