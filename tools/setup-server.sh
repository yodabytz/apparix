#!/usr/bin/env bash
# Opt-in host preparation. Never called by the web installer.
set -euo pipefail
export LC_ALL=C
die() { printf 'Error: %s\n' "$*" >&2; exit 1; }
usage() {
    printf '%s\n' 'Usage: bash tools/setup-server.sh [options]' \
        'Default: read-only dependency preview; no sudo required.' \
        '  --apply              Ask for confirmation, then install missing packages' \
        '  --php=X.Y            Match the PHP version used by this site (minimum 8.3)' \
        '  --owner=USER         Non-root application/PHP worker user (default www-data)' \
        '  --fix-permissions    Repair ownership/access inside this installation only' \
        '  --with-nginx         Include Nginx if absent (does not configure a vhost)' \
        '  --with-mariadb       Include MariaDB server if absent (not for remote DBs)' \
        '  --with-cron          Include the cron daemon; does not install any jobs' \
        '  --help               Show this help'
}
apply=0; permissions=0; nginx=0; mariadb=0; cron=0; version=''; owner=www-data
for arg in "$@"; do
    case "$arg" in
        --apply) apply=1;;
        --php=*) version=${arg#*=};;
        --owner=*) owner=${arg#*=};;
        --fix-permissions) permissions=1;;
        --with-nginx) nginx=1;;
        --with-mariadb) mariadb=1;;
        --with-cron) cron=1;;
        --help) usage; exit 0;;
        *) die "Unknown option: $arg";;
    esac
done
root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd -P)
[[ "$root" != / && -f "$root/version.php" && -f "$root/composer.lock" ]] || die 'Run the tool from an intact Apparix installation.'
[[ "$owner" =~ ^[a-z_][a-z0-9_-]*\$?$ ]] || die 'Invalid application user.'
[[ -r /etc/os-release ]] || die 'Unsupported host. See docs/server-setup.md.'
. /etc/os-release
case "${ID:-}" in ubuntu|debian) ;; *) die 'Automatic package installation supports Debian and Ubuntu only. See docs/server-setup.md.';; esac
[[ ! -e /.dockerenv && ! -e /run/.containerenv ]] || die 'Use the Docker image dependencies; do not modify a running container with this tool.'
for command in dpkg-query apt-cache apt-get; do command -v "$command" >/dev/null || die "Missing package manager command: $command"; done
if [[ -z "$version" ]]; then
    if command -v php >/dev/null; then
        version=$(php -r 'echo PHP_MAJOR_VERSION,".",PHP_MINOR_VERSION;')
    else
        version=$(apt-cache policy php-cli | awk '/Candidate:/ {split($2,a,":"); v=a[length(a)]; split(v,b,"."); print b[1]"."b[2]; exit}')
    fi
fi
[[ "$version" =~ ^[0-9]+\.[0-9]+$ ]] || die 'Cannot detect PHP version. Supply --php=X.Y after configuring trusted OS repositories.'
dpkg --compare-versions "$version" ge 8.3 || die 'PHP 8.3+ is required. Configure a supported OS repository and select --php=X.Y; no repositories are added automatically.'
phpbin="php$version"
packages=()
installed() { [[ $(dpkg-query -W -f='${Status}' "$1" 2>/dev/null || true) == 'install ok installed' ]]; }
need() { if ! installed "$1"; then packages+=("$1"); fi; }
need "$phpbin-cli"
need "$phpbin-common"
disabled=()
for pair in pdo_mysql:mysql mbstring:mbstring gd:gd curl:curl zip:zip; do
    extension=${pair%:*}; package="$phpbin-${pair#*:}"
    if command -v "$phpbin" >/dev/null && "$phpbin" -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$extension"; then continue; fi
    if installed "$package"; then disabled+=("$extension"); else need "$package"; fi
done
if ((nginx)); then need nginx; need "$phpbin-fpm"; fi
if ((mariadb)); then need mariadb-server; fi
if ((cron)); then need cron; fi
composer_needed=0
if [[ ! -f "$root/vendor/autoload.php" ]]; then
    composer_needed=1
    command -v composer >/dev/null || need composer
fi
printf 'Application: %s\nPHP target: %s (verify this matches the web installer)\nApplication user: %s\n' "$root" "$version" "$owner"
printf 'Missing OS packages:'; if ((${#packages[@]})); then printf ' %s' "${packages[@]}"; else printf ' none'; fi; printf '\n'
printf 'Install Composer dependencies: %s\nRepair scoped permissions: %s\n' "$composer_needed" "$permissions"
if ((${#disabled[@]})); then printf 'Installed but disabled extensions (enable in the correct PHP configuration): %s\n' "${disabled[*]}"; fi
printf '%s\n' 'No vhosts, databases, firewall rules, repositories, or crontabs are rewritten.' \
    'OS package hooks may start/restart services. Review apt changes before confirming.'
((apply)) || { printf '%s\n' 'Preview only. Rerun with sudo bash tools/setup-server.sh --apply and the same options to proceed.'; exit 0; }
((EUID == 0)) || die '--apply requires sudo; the web process must never have sudo access.'
uid=$(id -u "$owner" 2>/dev/null) || die 'Application user does not exist. Create/select the correct PHP worker user first.'
[[ "$uid" != 0 ]] || die 'The application user must not be root.'
if ((permissions)); then
    # Refuse symlinked data paths instead of changing permissions outside this installation.
    for item in storage vendor public content .env; do [[ ! -L "$root/$item" ]] || die "Symlinked $item: repair its ownership manually."; done
    printf 'Permission repair will assign application files to %s; .git is excluded and symlinks/mounts are not traversed.\n' "$owner"
fi
read -r -p 'Type INSTALL to approve this plan: ' confirmation
[[ "$confirmation" == INSTALL ]] || die 'Cancelled; no changes made.'
if ((${#packages[@]})); then
    apt-get update
    for package in "${packages[@]}"; do
        candidate=$(apt-cache policy "$package" | awk '/Candidate:/ {print $2; exit}')
        [[ -n "$candidate" && "$candidate" != '(none)' ]] || die "No trusted repository candidate for $package. No packages installed."
    done
    apt-get --simulate --no-install-recommends --no-remove install "${packages[@]}"
    # Apt prompts separately; never use -y or allow package removal/downgrades.
    apt-get --no-install-recommends --no-remove install "${packages[@]}"
fi
if ((permissions)); then
    group=$(id -gn "$owner")
    find -P "$root" -xdev -name .git -prune -o -type d -exec chown -- "$owner:$group" {} + -exec chmod u+rwx {} +
    find -P "$root" -xdev -name .git -prune -o -type f -exec chown -- "$owner:$group" {} + -exec chmod u+rw {} +
    for dir in storage storage/logs storage/cache storage/sessions; do
        install -d -o "$owner" -g "$group" -m 0750 "$root/$dir"
    done
    [[ ! -f "$root/.env" ]] || chmod 0600 "$root/.env"
fi
if ((composer_needed)); then
    composerbin=$(command -v composer) || die 'Composer is missing.'
    # A private temporary home avoids running Composer as root or leaving root-owned cache files.
    home=$(mktemp -d)
    trap 'rm -rf -- "$home"' EXIT
    chown "$owner:$(id -gn "$owner")" "$home"
    runuser -u "$owner" -- env COMPOSER_HOME="$home" "$phpbin" "$composerbin" --working-dir="$root" install --no-dev --prefer-dist --no-interaction --no-scripts --no-plugins --optimize-autoloader
fi
printf '\nRechecking application requirements as %s with %s...\n' "$owner" "$phpbin"
runuser -u "$owner" -- "$phpbin" -r '
define("BASE_PATH",$argv[1]); define("PUBLIC_PATH",BASE_PATH."/public");
require BASE_PATH."/install/classes/RequirementsChecker.php";
$result=(new RequirementsChecker())->check();
foreach($result["requirements"] as $r)echo ($r["passed"]?"PASS ":"FAIL "),$r["name"],": ",$r["current"],PHP_EOL;
exit($result["passed"]?0:1);' "$root" || die 'Some checks still fail. Review the messages and rerun the browser requirements check.'
printf '%s\n' 'CLI checks passed. Reload /install?step=2 to verify the actual web PHP runtime.' \
    'Configure HTTPS, database access, email/payment credentials, and scheduled jobs separately. See docs/server-setup.md.'
