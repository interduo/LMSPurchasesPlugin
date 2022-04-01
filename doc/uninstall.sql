
DROP TABLE IF EXISTS pdtypes CASCADE;
DROP TABLE IF EXISTS pdcategories CASCADE;
DROP TABLE IF EXISTS pds CASCADE;
DROP TABLE IF EXISTS pdcontentcat CASCADE;
DROP TABLE IF EXISTS pdcontentinvprojects CASCADE;
DROP TABLE IF EXISTS pdattachments CASCADE;
DROP TABLE IF EXISTS pdcontents CASCADE;

DELETE FROM dbinfo WHERE keytype='dbversion_LMSPurchasesPlugin';
DELETE FROM uiconfig WHERE section='pd';
UPDATE uiconfig SET value = REPLACE(value, 'LMSPurchasesPlugin', '') WHERE section='phpui' AND var='plugins';