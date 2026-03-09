-- =============================================
-- MASTER DEPLOYMENT FILE
-- Database: abiturent_v2
-- Created: 2026-03-09
-- =============================================

-- SETTINGS 
SOURCE 000_settings.sql;

-- TABLES 
SOURCE tables/001_tables_admin.sql;
SOURCE tables/002_foreign_keys.sql;
SOURCE tables/002_tables_attributes.sql;
SOURCE tables/003_tables_clusters.sql;
SOURCE tables/004_tables_directions.sql;
SOURCE tables/005_tables_establishments.sql;
SOURCE tables/006_tables_programs.sql;
SOURCE tables/007_tables_bundles.sql;
SOURCE tables/008_tables_metrics.sql;
SOURCE tables/009_tables_addresses.sql;
SOURCE tables/010_tables_phones.sql;

-- DATAS 
SOURCE datas/001_datas_admins.sql;
SOURCE datas/002_datas_atributes.sql;
SOURCE datas/003_datas_bundles.sql;
SOURCE datas/004_datas_clusters.sql;
SOURCE datas/005_datas_directions.sql;
-- SOURCE datas/006_datas_establishments.sql;
SOURCE datas/007_datas_metrics.sql;
SOURCE datas/008_datas_programs.sql;
SOURCE datas/new_006_datas_establishments.sql;
SOURCE datas/new_009_datas_addresses.sql;
SOURCE datas/new_010_datas_phones.sql;

-- INDEXES 
900_indexes_constraints.sql;
901_auto_increment.sql;

-- FINALS
SOURCE 999_finalize.sql;

SELECT '========================================' AS 'STATUS';
SELECT 'DEPLOYMENT COMPLETED SUCCESSFULLY!' AS 'STATUS';
SELECT '========================================' AS 'STATUS';

-- Показать статистику
SELECT 'Tables count:' AS 'Info', COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT 'Total records in bundles:' AS 'Info', COUNT(*) FROM bundles;
