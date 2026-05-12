SET @schema_name := DATABASE();
SET @has_timezone := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'business_profiles'
      AND COLUMN_NAME = 'timezone'
);

SET @timezone_sql := IF(
    @has_timezone = 0,
    'ALTER TABLE business_profiles ADD COLUMN timezone VARCHAR(100) NOT NULL DEFAULT ''Africa/Douala'' AFTER daily_send_limit',
    'ALTER TABLE business_profiles MODIFY COLUMN timezone VARCHAR(100) NOT NULL DEFAULT ''Africa/Douala'''
);

PREPARE timezone_stmt FROM @timezone_sql;
EXECUTE timezone_stmt;
DEALLOCATE PREPARE timezone_stmt;

UPDATE business_profiles
SET timezone = 'Africa/Douala'
WHERE timezone IS NULL
   OR TRIM(timezone) = ''
   OR timezone = 'UTC';
