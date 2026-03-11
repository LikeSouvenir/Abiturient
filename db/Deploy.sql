-- =============================================
-- MASTER DEPLOYMENT FILE
-- Database: abiturent_v3
-- Created: 2026-03-09
-- =============================================
USE abiturent_v3;
-- SETTINGS 
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/000_settings.sql;

-- TABLES 
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/001_tables_admin.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/002_tables_attributes.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/003_tables_clusters.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/004_tables_directions.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/005_tables_establishments.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/006_tables_programs.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/007_tables_bundles.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/008_tables_metrics.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/009_tables_addresses.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/tables/010_tables_phones.sql;

-- DATAS 
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/001_datas_admins.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/002_datas_atributes.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/003_datas_bundles.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/004_datas_clusters.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/005_datas_directions.sql;
-- SOURCE datas/006_datas_establishments.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/007_datas_metrics.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/008_datas_programs.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/new_006_datas_establishments.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/new_009_datas_addresses.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/datas/new_010_datas_phones.sql;

-- INDEXES 
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/constraints/000_indexes_constraints.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/constraints/001_auto_increment.sql;
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/constraints/002_foreign_keys.sql;

-- FINALS
SOURCE /home/dmitri/WebstormProjects/abiturient/Abiturient/db/999_finalize.sql;

SELECT '========================================' AS 'STATUS';
SELECT 'DEPLOYMENT COMPLETED SUCCESSFULLY!' AS 'STATUS';
SELECT '========================================' AS 'STATUS';

-- Показать статистику
SELECT 'Tables count:' AS 'Info', COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT 'Total records in bundles:' AS 'Info', COUNT(*) FROM bundles;
