ALTER TABLE email_logs ADD COLUMN IF NOT EXISTS provider_reference VARCHAR(255) NULL AFTER status;
