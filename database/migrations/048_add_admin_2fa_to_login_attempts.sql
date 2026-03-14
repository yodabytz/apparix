ALTER TABLE login_attempts MODIFY COLUMN attempt_type ENUM('admin','user','admin_2fa') NOT NULL DEFAULT 'user';
