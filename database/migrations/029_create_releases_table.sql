-- Software releases/versions table for update system
CREATE TABLE IF NOT EXISTS releases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(20) NOT NULL,
    version_major INT NOT NULL DEFAULT 1,
    version_minor INT NOT NULL DEFAULT 0,
    version_patch INT NOT NULL DEFAULT 0,
    release_type ENUM('stable', 'beta', 'alpha') DEFAULT 'stable',
    release_notes TEXT,
    changelog TEXT,
    min_php_version VARCHAR(10) DEFAULT '8.0',
    min_edition CHAR(1) DEFAULT 'S',
    update_file VARCHAR(255) NOT NULL,
    file_hash VARCHAR(64) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    download_count INT DEFAULT 0,
    released_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_version (version),
    INDEX idx_active_type (is_active, release_type)
);

-- Track update downloads/installs
CREATE TABLE IF NOT EXISTS update_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_key VARCHAR(100) NOT NULL,
    domain VARCHAR(255),
    from_version VARCHAR(20),
    to_version VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('downloaded', 'installed', 'failed') DEFAULT 'downloaded',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_license (license_key),
    INDEX idx_domain (domain)
);

-- Insert initial version
INSERT INTO releases (version, version_major, version_minor, version_patch, release_type, release_notes, changelog, update_file, file_hash, file_size)
VALUES (
    '1.0.0',
    1, 0, 0,
    'stable',
    'Initial release of Apparix E-Commerce Platform',
    '## Version 1.0.0 - Initial Release\n\n### Features\n- Complete e-commerce platform\n- License key activation\n- Theme customization\n- Digital downloads support\n- Stripe payment integration\n- Admin dashboard',
    'apparix-1.0.0.tar.gz',
    'pending',
    0
);
