-- EduBot Pro Database Health Check SQL Script
-- Run this in phpMyAdmin or MySQL client to verify all FK constraints

-- 1. Check MySQL Version and Settings
SELECT 'MySQL Version' as Check_Type, VERSION() as Result;
SELECT 'FK Checks Enabled' as Check_Type, @@FOREIGN_KEY_CHECKS as Result;
SELECT 'InnoDB Available' as Check_Type, COUNT(*) as Result FROM INFORMATION_SCHEMA.ENGINES WHERE ENGINE = 'InnoDB' AND SUPPORT != 'NO';

-- 2. List All EduBot Tables
SELECT CONCAT('Table: ', TABLE_NAME, ' (', TABLE_ROWS, ' rows)') as EduBot_Tables
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE 'wp_edubot_%'
ORDER BY TABLE_NAME;

-- 3. Verify Parent Table (Enquiries)
SELECT 
    'Parent Table: wp_edubot_enquiries' as Table_Info,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'wp_edubot_enquiries'
AND COLUMN_NAME IN ('id')
ORDER BY ORDINAL_POSITION;

-- 4. Verify Child Table Data Types (should all match parent BIGINT UNSIGNED)
SELECT 
    'Child Tables FK Columns' as Table_Info,
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN (
    'wp_edubot_attribution_sessions',
    'wp_edubot_attribution_touchpoints',
    'wp_edubot_attribution_journeys',
    'wp_edubot_conversions',
    'wp_edubot_api_logs'
)
AND COLUMN_NAME IN ('enquiry_id', 'session_id')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- 5. List All Foreign Key Constraints
SELECT 
    'Foreign Key Constraints' as Constraint_Info,
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'wp_edubot_%'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, CONSTRAINT_NAME;

-- 6. Table Statistics
SELECT 
    CONCAT(TABLE_NAME, ' - ', TABLE_ROWS, ' rows, ', ROUND(DATA_LENGTH/1024/1024, 2), ' MB') as Table_Stats,
    TABLE_COLLATION,
    ENGINE
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME LIKE 'wp_edubot_%'
ORDER BY TABLE_NAME;

-- 7. Verify No Data Type Mismatches
SELECT 
    CONCAT('MISMATCH FOUND: ', t1.TABLE_NAME, '.', t1.COLUMN_NAME, ' -> ', 
           t2.TABLE_NAME, '.', t2.COLUMN_NAME) as Issue,
    t1.COLUMN_TYPE as Child_Type,
    t2.COLUMN_TYPE as Parent_Type
FROM INFORMATION_SCHEMA.COLUMNS t1
JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON 
    kcu.TABLE_SCHEMA = t1.TABLE_SCHEMA
    AND kcu.TABLE_NAME = t1.TABLE_NAME
    AND kcu.COLUMN_NAME = t1.COLUMN_NAME
    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
JOIN INFORMATION_SCHEMA.COLUMNS t2 ON 
    t2.TABLE_SCHEMA = kcu.TABLE_SCHEMA
    AND t2.TABLE_NAME = kcu.REFERENCED_TABLE_NAME
    AND t2.COLUMN_NAME = kcu.REFERENCED_COLUMN_NAME
WHERE t1.TABLE_SCHEMA = DATABASE()
AND t1.COLUMN_TYPE != t2.COLUMN_TYPE
AND t1.TABLE_NAME LIKE 'wp_edubot_%';

-- If above query returns no rows, all data types match correctly

-- 8. Test FK Enforcement (these should show the constraints working)
-- First verify a valid insert works:
-- INSERT INTO wp_edubot_attribution_sessions (enquiry_id, user_session_key, attribution_model)
-- SELECT id, CONCAT('test_', id, '_', UNIX_TIMESTAMP()), 'last-click' 
-- FROM wp_edubot_enquiries LIMIT 1;

-- Then verify an invalid insert fails:
-- INSERT INTO wp_edubot_attribution_sessions (enquiry_id, user_session_key, attribution_model)
-- VALUES (999999999, 'invalid_test', 'last-click');
-- ^ This should fail with: Foreign key constraint fails

-- View current counts
SELECT 'Record Counts' as Metric,
    (SELECT COUNT(*) FROM wp_edubot_enquiries) as enquiries,
    (SELECT COUNT(*) FROM wp_edubot_attribution_sessions) as sessions,
    (SELECT COUNT(*) FROM wp_edubot_attribution_touchpoints) as touchpoints,
    (SELECT COUNT(*) FROM wp_edubot_attribution_journeys) as journeys,
    (SELECT COUNT(*) FROM wp_edubot_api_logs) as api_logs;
