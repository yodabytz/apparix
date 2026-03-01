#!/bin/bash
set -euo pipefail
REPO_DIR="/root/apparix"
LIVE_DIR="/var/www/apparix.app"
DB_NAME="apparix_ecommerce"
DB_USER="apparix"
DB_PASS="apparix_secure_2024"
echo "=== Apparix Deploy ==="
VERSION=$(php -r "\$v = require '${REPO_DIR}/version.php'; echo \$v['version'];")
TARBALL_NAME="apparix-${VERSION}.tar.gz"
UPDATE_DIR="${LIVE_DIR}/storage/updates"
TMP_TARBALL="/tmp/apparix-deploy-$$.tar.gz"
echo "Version: ${VERSION}"
echo "Tarball: ${TARBALL_NAME}"
echo "Building tarball..."
cd "${REPO_DIR}"
tar czf "${TMP_TARBALL}" --exclude='.git' --exclude='.env' --exclude='.claude' --exclude='storage/logs/*' --exclude='storage/sessions/*' --exclude='storage/updates/*' --exclude='storage/backups/*' --exclude='storage/cache/*' --exclude='storage/releases/*' --exclude='vendor' --exclude='public/downloads' --exclude='node_modules' --exclude='deploy.sh' *
cd - >/dev/null
if tar tzf "${TMP_TARBALL}" | head -1 | grep -q '^\./'; then
    echo "ABORT: Tarball contains ./ prefix"; rm -f "${TMP_TARBALL}"; exit 1
fi
echo "Check: No ./ prefix — OK"
if ! tar tzf "${TMP_TARBALL}" | grep -q '^version\.php$'; then
    echo "ABORT: Missing version.php"; rm -f "${TMP_TARBALL}"; exit 1
fi
echo "Check: version.php present — OK"
EXTRACT_TEST="/tmp/apparix-extract-test-$$"
mkdir -p "${EXTRACT_TEST}"
php -r "try { \$p = new PharData('${TMP_TARBALL}'); \$p->extractTo('${EXTRACT_TEST}', null, true); echo 'OK'; } catch (Exception \$e) { echo 'FAIL: '.\$e->getMessage(); exit(1); }" > /tmp/phar-test-$$.out 2>&1
PHAR_RESULT=$(cat /tmp/phar-test-$$.out)
rm -rf "${EXTRACT_TEST}" /tmp/phar-test-$$.out
if [ "${PHAR_RESULT}" != "OK" ]; then
    echo "ABORT: PharData extraction failed: ${PHAR_RESULT}"; rm -f "${TMP_TARBALL}"; exit 1
fi
echo "Check: PharData extraction — OK"
NEW_HASH=$(sha256sum "${TMP_TARBALL}" | awk '{print $1}')
FILE_SIZE=$(stat -c%s "${TMP_TARBALL}")
echo "Hash: ${NEW_HASH}"
echo "Size: ${FILE_SIZE} bytes"
mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e "UPDATE releases SET file_hash='${NEW_HASH}', file_size=${FILE_SIZE}, update_file='${TARBALL_NAME}' WHERE is_active = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null
echo "DB updated (hash + filename)"
mkdir -p "${UPDATE_DIR}"
TMP_DEST="${UPDATE_DIR}/${TARBALL_NAME}.new"
cp "${TMP_TARBALL}" "${TMP_DEST}"
chown www-data:www-data "${TMP_DEST}"
mv -f "${TMP_DEST}" "${UPDATE_DIR}/${TARBALL_NAME}"
echo "Tarball deployed to ${UPDATE_DIR}/${TARBALL_NAME}"
TMP_DL="${LIVE_DIR}/storage/downloads/apparix-latest.tar.gz.new"
cp "${TMP_TARBALL}" "${TMP_DL}"
chown www-data:www-data "${TMP_DL}"
mv -f "${TMP_DL}" "${LIVE_DIR}/storage/downloads/apparix-latest.tar.gz"
rm -f "${TMP_TARBALL}"
VERIFY_HASH=$(sha256sum "${UPDATE_DIR}/${TARBALL_NAME}" | awk '{print $1}')
if [ "${NEW_HASH}" = "${VERIFY_HASH}" ]; then
    echo "Verified: DB hash matches file on disk"
else
    echo "CRITICAL: Hash mismatch!"; mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -e "UPDATE releases SET file_hash='DEPLOY_FAILED', file_size=0 WHERE is_active = 1 ORDER BY id DESC LIMIT 1;" 2>/dev/null; exit 1
fi
echo "=== Deploy complete: v${VERSION} ==="
