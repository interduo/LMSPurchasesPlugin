
DROP SEQUENCE IF EXISTS pdtypes_id_seq;
DROP TABLE IF EXISTS pdtypes CASCADE;
DROP SEQUENCE IF EXISTS pds_id_seq;
DROP TABLE IF EXISTS pds CASCADE;
DROP SEQUENCE IF EXISTS pdprojects_id_seq;
DROP TABLE IF EXISTS pdprojects CASCADE;
DROP SEQUENCE IF EXISTS pdattachments_id_seq;
DROP TABLE IF EXISTS pdattachments CASCADE;
DELETE FROM dbinfo WHERE keytype='dbversion_LMSPurchasesPlugin';
DELETE FROM uiconfig WHERE section='pd';
DELETE FROM uiconfig WHERE section='phpui' AND var='plugins';