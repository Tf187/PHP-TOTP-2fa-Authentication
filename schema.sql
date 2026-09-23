-- Example database fields for TOTP two-factor authentication.
-- Adapt table/column names to your own application.

ALTER TABLE users
    ADD COLUMN two_factor_enabled BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN two_factor_secret TEXT NULL,
    ADD COLUMN two_factor_confirmed_at DATETIME NULL,
    ADD COLUMN two_factor_recovery_codes TEXT NULL;

-- Optional audit log table.

CREATE TABLE security_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    event VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_security_logs_user_id (user_id),
    INDEX idx_security_logs_event (event),
    INDEX idx_security_logs_created_at (created_at)
);
