#!/usr/bin/env php
<?php
/**
 * Apparix License Key Generator
 *
 * Usage:
 *   php generate-license.php [options]
 *
 * Options:
 *   --edition=X    Edition code: S=Standard, P=Professional, E=Enterprise, D=Developer, U=Unlimited
 *   --domain=xxx   Lock to specific domain (optional, omit for wildcard)
 *   --count=N      Generate N keys (default: 1)
 *   --validate=KEY Validate an existing key
 *
 * Examples:
 *   php generate-license.php --edition=P
 *   php generate-license.php --edition=E --domain=example.com
 *   php generate-license.php --edition=S --count=10
 *   php generate-license.php --validate=APX-XXXXX-XXXXX-XXXXX-XXXXX
 */

// Change to project root
chdir(dirname(__DIR__));

// Load autoloader
require_once 'vendor/autoload.php';

use App\Core\License;

// Parse command line arguments
$options = getopt('', ['edition:', 'domain:', 'count:', 'validate:', 'help']);

// Show help
if (isset($options['help']) || (empty($options) && $argc === 1)) {
    echo <<<HELP
╔══════════════════════════════════════════════════════════════╗
║           APPARIX LICENSE KEY GENERATOR                      ║
╚══════════════════════════════════════════════════════════════╝

Usage: php generate-license.php [options]

Options:
  --edition=X     Edition code (default: S)
                  S = Standard
                  P = Professional
                  E = Enterprise
                  D = Developer
                  U = Unlimited

  --domain=xxx    Lock license to specific domain
                  Omit for wildcard (any domain)

  --count=N       Generate N license keys (default: 1)

  --validate=KEY  Validate an existing license key

  --help          Show this help message

Examples:
  Generate a Standard wildcard license:
    php generate-license.php --edition=S

  Generate a Professional license for specific domain:
    php generate-license.php --edition=P --domain=mystore.com

  Generate 5 Enterprise wildcard licenses:
    php generate-license.php --edition=E --count=5

  Validate a license key:
    php generate-license.php --validate=APX-SXXXX-AAAAA-XXXXX-XXXXX


HELP;
    exit(0);
}

// Validate mode
if (isset($options['validate'])) {
    $key = $options['validate'];
    echo "\n";
    echo "Validating: $key\n";
    echo str_repeat('-', 60) . "\n";

    $result = License::validateKey($key);

    if ($result['valid']) {
        echo "✓ License is VALID\n\n";
        echo "  Edition:       " . ucfirst($result['edition']) . " ({$result['edition_code']})\n";
        echo "  Domain Lock:   " . ($result['domain_locked'] ? 'Yes (locked to specific domain)' : 'No (wildcard)') . "\n";
    } else {
        echo "✗ License is INVALID\n\n";
        echo "  Error:  {$result['error']}\n";
        echo "  Code:   {$result['code']}\n";

        if (isset($result['expected_domain_hash'])) {
            echo "  Expected Hash: {$result['expected_domain_hash']}\n";
            echo "  Current Hash:  {$result['current_domain_hash']}\n";
        }
    }
    echo "\n";
    exit($result['valid'] ? 0 : 1);
}

// Generation mode
$edition = strtoupper($options['edition'] ?? 'S');
$domain = $options['domain'] ?? null;
$count = max(1, min(100, (int)($options['count'] ?? 1)));

$editions = [
    'S' => 'Standard',
    'P' => 'Professional',
    'E' => 'Enterprise',
    'D' => 'Developer',
    'U' => 'Unlimited'
];

if (!isset($editions[$edition])) {
    echo "Error: Invalid edition code '$edition'\n";
    echo "Valid codes: S, P, E, D, U\n";
    exit(1);
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           APPARIX LICENSE KEY GENERATOR                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Generating $count " . $editions[$edition] . " license(s)";
if ($domain) {
    echo " for domain: $domain";
} else {
    echo " (wildcard - any domain)";
}
echo "\n\n";

echo str_repeat('-', 60) . "\n";

$keys = [];
for ($i = 0; $i < $count; $i++) {
    $key = License::generate($edition, $domain);
    $keys[] = $key;
    echo "  $key\n";
}

echo str_repeat('-', 60) . "\n\n";

if ($count === 1) {
    echo "Add this to your .env file:\n\n";
    echo "  LICENSE_KEY={$keys[0]}\n\n";
}

echo "Edition: {$editions[$edition]}\n";
echo "Domain:  " . ($domain ?? 'Any (wildcard)') . "\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";
