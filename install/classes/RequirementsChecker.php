<?php

/**
 * Checks system requirements for the Apparix platform
 */
class RequirementsChecker
{
    private array $requirements = [];
    private bool $allPassed = true;

    /**
     * Run all requirement checks
     */
    public function check(): array
    {
        $this->checkPhpVersion();
        $this->checkExtensions();
        $this->checkWritableDirectories();

        return [
            'requirements' => $this->requirements,
            'passed' => $this->allPassed
        ];
    }

    /**
     * Check PHP version
     */
    private function checkPhpVersion(): void
    {
        $required = '8.1.0';
        $current = PHP_VERSION;
        $passed = version_compare($current, $required, '>=');

        $this->requirements['php'] = [
            'name' => 'PHP Version',
            'required' => '>= ' . $required,
            'current' => $current,
            'passed' => $passed
        ];

        if (!$passed) {
            $this->allPassed = false;
        }
    }

    /**
     * Check required PHP extensions
     */
    private function checkExtensions(): void
    {
        $extensions = [
            'pdo' => 'PDO',
            'pdo_mysql' => 'PDO MySQL',
            'mbstring' => 'Multibyte String',
            'openssl' => 'OpenSSL',
            'json' => 'JSON',
            'fileinfo' => 'Fileinfo',
            'gd' => 'GD Image Library',
            'curl' => 'cURL'
        ];

        foreach ($extensions as $ext => $name) {
            $loaded = extension_loaded($ext);

            $this->requirements['ext_' . $ext] = [
                'name' => $name . ' Extension',
                'required' => 'Enabled',
                'current' => $loaded ? 'Enabled' : 'Not installed',
                'passed' => $loaded
            ];

            if (!$loaded) {
                $this->allPassed = false;
            }
        }
    }

    /**
     * Check writable directories
     */
    private function checkWritableDirectories(): void
    {
        $directories = [
            BASE_PATH . '/storage' => 'storage/',
            BASE_PATH . '/storage/logs' => 'storage/logs/',
            BASE_PATH . '/storage/sessions' => 'storage/sessions/',
            PUBLIC_PATH . '/assets/images' => 'public/assets/images/',
            BASE_PATH => '.env file location'
        ];

        foreach ($directories as $path => $name) {
            $writable = is_writable($path) || (is_writable(dirname($path)) && !file_exists($path));

            $this->requirements['dir_' . md5($path)] = [
                'name' => $name,
                'required' => 'Writable',
                'current' => $writable ? 'Writable' : 'Not writable',
                'passed' => $writable
            ];

            if (!$writable) {
                $this->allPassed = false;
            }
        }
    }

    /**
     * Check if all requirements passed
     */
    public function allPassed(): bool
    {
        return $this->allPassed;
    }
}
