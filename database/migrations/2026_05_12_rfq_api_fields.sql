SET @schema_name := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'rfqs' AND COLUMN_NAME = 'timeline') = 0,
  'ALTER TABLE rfqs ADD COLUMN timeline VARCHAR(190) NULL AFTER estimated_quantity',
  'SET @noop := 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'rfqs' AND COLUMN_NAME = 'items_json') = 0,
  'ALTER TABLE rfqs ADD COLUMN items_json MEDIUMTEXT NULL AFTER message',
  'SET @noop := 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'rfqs' AND COLUMN_NAME = 'page_url') = 0,
  'ALTER TABLE rfqs ADD COLUMN page_url VARCHAR(500) NULL AFTER items_json',
  'SET @noop := 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @schema_name AND TABLE_NAME = 'rfqs' AND COLUMN_NAME = 'user_agent') = 0,
  'ALTER TABLE rfqs ADD COLUMN user_agent VARCHAR(500) NULL AFTER page_url',
  'SET @noop := 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
