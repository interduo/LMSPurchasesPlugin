BEGIN;

/* --------------------------------------------------------
Structure of table "pdtypes"
-------------------------------------------------------- */

DROP SEQUENCE IF EXISTS pdtypes_id_seq;
CREATE SEQUENCE pdtypes_id_seq;

DROP TABLE IF EXISTS pdtypes CASCADE;
CREATE TABLE pdtypes (
    id integer DEFAULT nextval('pdtypes_id_seq'::text) NOT NULL,
    name varchar(50) NOT NULL,
    description varchar(254) DEFAULT NULL,
    PRIMARY KEY (id)
);

/* --------------------------------------------------------
Structure of table "pds"
-------------------------------------------------------- */

DROP SEQUENCE IF EXISTS pds_id_seq;
CREATE SEQUENCE pds_id_seq;

DROP TABLE IF EXISTS pds CASCADE;
CREATE TABLE pds (
    id integer DEFAULT nextval('pds_id_seq'::text) NOT NULL,
    fullnumber varchar(50) NOT NULL,
    netvalue numeric(9,2) NOT NULL,
    grossvalue numeric(9,2) NOT NULL,
    cdate integer NOT NULL,
    sdate integer NOT NULL,
    deadline integer DEFAULT NULL,
    paydate integer DEFAULT NULL,
    description varchar(254) DEFAULT NULL,
    supplierid integer NOT NULL
        CONSTRAINT pds_supplierid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    typeid integer DEFAULT NULL
        CONSTRAINT pds_typeid_fkey REFERENCES pdtypes (id) ON DELETE SET NULL ON UPDATE CASCADE,
    userid integer DEFAULT NULL
        CONSTRAINT pds_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    PRIMARY KEY (id),
    CONSTRAINT pds_supplierid_ukey UNIQUE (fullnumber, supplierid)
);

/* --------------------------------------------------------
Structure of table "pdprojects"
-------------------------------------------------------- */

DROP SEQUENCE IF EXISTS pdprojects_id_seq;
CREATE SEQUENCE pdprojects_id_seq;

DROP TABLE IF EXISTS pdprojects CASCADE;
CREATE TABLE pdprojects (
    id integer DEFAULT nextval('pdprojects_id_seq'::text) NOT NULL,
    pdid integer NOT NULL,
    projectid integer NOT NULL,
    PRIMARY KEY (id)
);

INSERT INTO pdtypes (id, name, description) VALUES (1, 'faktura VAT', NULL);
INSERT INTO pdtypes (id, name, description) VALUES (2, 'faktura VAT-marża', NULL);
INSERT INTO pdtypes (id, name, description) VALUES (3, 'korekta', NULL);
INSERT INTO pdtypes (id, name, description) VALUES (4, 'rachunek', NULL);
INSERT INTO pdtypes (id, name, description) VALUES (5, 'decyzja płatnicza', NULL);
INSERT INTO pdtypes (id, name, description) VALUES (6, 'opłata za rachunek bankowy', NULL);
INSERT INTO pdtypes (id, name, description) VALUES (7, 'proforma', NULL);
INSERT INTO pdtypes (id, name, description) VALUES (8, 'nota księgowa', NULL);

/* --------------------------------------------------------
Structure of table "pdattachments"
-------------------------------------------------------- */

DROP TABLE IF EXISTS pdattachments CASCADE;
CREATE TABLE pdattachments (
    pdid integer NOT NULL
        REFERENCES pds (id) ON DELETE CASCADE ON UPDATE CASCADE,
    filename varchar(255) DEFAULT '' NOT NULL,
    contenttype varchar(255) DEFAULT '' NOT NULL
);

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSPurchasesPlugin', '2021112700') 
ON CONFLICT (keytype) DO UPDATE SET keyvalue = '2021112700';

INSERT INTO uiconfig (section, var, value, description, disabled) VALUES
('pd', 'mail_dir', '/var/www/html/lms/pdattachements', 'Katalog skanów dokumentów kosztowych', false),
('pd', 'filter_default_period', '6', 'Domyślny filtr okresu wartości: -1, 1-6', false)
ON CONFLICT DO NOTHING;

COMMIT;
