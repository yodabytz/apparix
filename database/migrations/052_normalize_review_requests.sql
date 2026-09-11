-- Normalize review request columns created by historical Apparix schemas.
-- Legacy columns remain in place so older application files can roll back safely.

ALTER TABLE review_requests
    ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER product_id,
    ADD COLUMN IF NOT EXISTS email VARCHAR(255) NULL AFTER user_id,
    ADD COLUMN IF NOT EXISTS customer_email VARCHAR(255) NULL AFTER email,
    ADD COLUMN IF NOT EXISTS reminded_at TIMESTAMP NULL DEFAULT NULL AFTER sent_at,
    ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL DEFAULT NULL AFTER reminded_at,
    ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL DEFAULT NULL AFTER reviewed_at;

ALTER TABLE review_requests
    MODIFY COLUMN status ENUM('pending', 'sent', 'completed', 'reviewed', 'expired') NOT NULL DEFAULT 'pending';

UPDATE review_requests rr
JOIN orders o ON o.id = rr.order_id
SET rr.user_id = COALESCE(rr.user_id, o.user_id),
    rr.email = COALESCE(NULLIF(rr.email, ''), NULLIF(rr.customer_email, ''), o.customer_email),
    rr.customer_email = COALESCE(NULLIF(rr.customer_email, ''), NULLIF(rr.email, ''), o.customer_email)
WHERE rr.user_id IS NULL
   OR rr.email IS NULL OR rr.email = ''
   OR rr.customer_email IS NULL OR rr.customer_email = '';

UPDATE review_requests
SET status = 'reviewed',
    reviewed_at = COALESCE(reviewed_at, completed_at)
WHERE status = 'completed';

ALTER TABLE review_requests
    MODIFY COLUMN status ENUM('pending', 'sent', 'reviewed', 'expired') NOT NULL DEFAULT 'pending';
