SET @schema_name := DATABASE();
SET @has_provider_reference := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'email_logs'
      AND COLUMN_NAME = 'provider_reference'
);

SET @provider_reference_sql := IF(
    @has_provider_reference = 0,
    'ALTER TABLE email_logs ADD COLUMN provider_reference VARCHAR(255) NULL AFTER status',
    'SELECT 1'
);

PREPARE provider_reference_stmt FROM @provider_reference_sql;
EXECUTE provider_reference_stmt;
DEALLOCATE PREPARE provider_reference_stmt;
