
DROP SEQUENCE IF EXISTS pdtypes_id_seq;
DROP TABLE IF EXISTS pdtypes CASCADE;
DROP SEQUENCE IF EXISTS pdcategories_id_seq;
DROP TABLE IF EXISTS pdcategories CASCADE;
DROP SEQUENCE IF EXISTS pds_id_seq;
DROP TABLE IF EXISTS pds CASCADE;
DROP SEQUENCE IF EXISTS pdcontentcat_id_seq;
DROP TABLE IF EXISTS pdcontentcat CASCADE;
DROP SEQUENCE IF EXISTS pdinvprojects_id_seq;
DROP TABLE IF EXISTS pdinvprojects CASCADE;
DROP SEQUENCE IF EXISTS pdattachments_id_seq;
DROP TABLE IF EXISTS pdattachments CASCADE;
DROP SEQUENCE IF EXISTS pdcontents_id_seq;
DROP TABLE IF EXISTS pdcontents CASCADE;

DELETE FROM dbinfo WHERE keytype='dbversion_LMSPurchasesPlugin';
DELETE FROM uiconfig WHERE section='pd';
UPDATE uiconfig SET value = REPLACE(value,'LMSPurchasesPlugin','') WHERE section='phpui' AND var='plugins';