/* --------------------------------------------------------
Structure of table "pdtypes"
-------------------------------------------------------- */

DROP TABLE IF EXISTS pdtypes CASCADE;
CREATE TABLE pdtypes (
    id serial PRIMARY KEY,
    name varchar(50) NOT NULL,
    description varchar(254),
    defaultflag boolean DEFAULT false,
    CONSTRAINT pdtypes_name_ukey UNIQUE (name)
);

/* --------------------------------------------------------
Structure of table "pdcategories"
-------------------------------------------------------- */

DROP TABLE IF EXISTS pdcategories CASCADE;
CREATE TABLE pdcategories (
    id serial PRIMARY KEY,
    name varchar(50) NOT NULL,
    description varchar(254),
    CONSTRAINT pdcategories_name_ukey UNIQUE (name)
);

/* --------------------------------------------------------
Structure of table "pds"
-------------------------------------------------------- */

DROP TABLE IF EXISTS pds CASCADE;
CREATE TABLE pds (
    id serial PRIMARY KEY,
    currency varchar(3) NOT NULL DEFAULT 'PLN',
    vatplnvalue integer,
    fullnumber varchar(50) NOT NULL,
    cdate integer NOT NULL,
    sdate integer NOT NULL,
    deadline integer,
    paydate integer,
    paytype smallint NOT NULL,
    supplierid integer NOT NULL
        CONSTRAINT pds_supplierid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    divisionid smallint NOT NULL,
    iban varchar(26),
    typeid integer
        CONSTRAINT pds_typeid_fkey REFERENCES pdtypes (id) ON DELETE SET NULL ON UPDATE CASCADE,
    userid integer
        CONSTRAINT pds_userid_fkey REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT pds_supplierid_fullnumber_ukey UNIQUE (supplierid, fullnumber)
);

/* --------------------------------------------------------
Structure of table "pdcontents"
-------------------------------------------------------- */

DROP TABLE IF EXISTS pdcontents CASCADE;
CREATE TABLE pdcontents (
    id serial PRIMARY KEY,
    pdid integer NOT NULL
        CONSTRAINT pdcontents_pdid_fkey REFERENCES pds (id) ON DELETE CASCADE ON UPDATE CASCADE,
    netcurrencyvalue numeric(17,10) NOT NULL,
    amount variable NOT NULL DEFAULT 1,
    taxid integer NOT NULL
        CONSTRAINT pds_taxid_fkey REFERENCES taxes (id) ON DELETE SET NULL ON UPDATE CASCADE,
    description varchar(254)
);

/* --------------------------------------------------------
Structure of table "pdcontentcat"
-------------------------------------------------------- */

DROP TABLE IF EXISTS pdcontentcat CASCADE;
CREATE TABLE pdcontentcat (
    id serial PRIMARY KEY,
    contentid integer NOT NULL
        CONSTRAINT pdcontentcat_contentid_fkey REFERENCES pdcontents (id) ON DELETE CASCADE ON UPDATE CASCADE,
    categoryid integer
        CONSTRAINT pdcontentcat_categoryid_fkey REFERENCES pdcategories (id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT pdcontentcat_contentid_categoryid_ukey UNIQUE (contentid, categoryid)
);

/* --------------------------------------------------------
Structure of table "pdcontentinvprojects"
-------------------------------------------------------- */

DROP TABLE IF EXISTS pdcontentinvprojects CASCADE;
CREATE TABLE pdcontentinvprojects (
    id serial PRIMARY KEY,
    contentid integer NOT NULL,
    invprojectid integer NOT NULL
        CONSTRAINT pdcontentinvprojects_invprojectid_fkey REFERENCES invprojects (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT pdcontentinvprojects_contentid_invprojectid_ukey UNIQUE (contentid, invprojectid)
);

/* --------------------------------------------------------
Structure of table "pdattachments"
-------------------------------------------------------- */

DROP TABLE IF EXISTS pdattachments CASCADE;
CREATE TABLE pdattachments (
    id serial PRIMARY KEY,
    pdid integer
        CONSTRAINT pdattachments_pdid_fkey REFERENCES pds (id) ON DELETE CASCADE ON UPDATE CASCADE,
    anteroom boolean NOT NULL,
    filename varchar(255) DEFAULT '' NOT NULL,
    filepath varchar(255) DEFAULT '' NOT NULL,
    contenttype varchar(255) DEFAULT '' NOT NULL,
    createtime integer NOT NULL,
    sender varchar(255),
    sender_mail varchar(255),
    comment varchar(255),
    CONSTRAINT pdattachments_fullpatch_ukey UNIQUE (filepath)
);

INSERT INTO pdtypes (id, name, description, defaultflag) VALUES (1, 'faktura VAT', NULL, true);
INSERT INTO pdtypes (id, name, description, defaultflag) VALUES (2, 'faktura VAT-marża', NULL, false);
INSERT INTO pdtypes (id, name, description, defaultflag) VALUES (3, 'korekta', NULL, false);
INSERT INTO pdtypes (id, name, description, defaultflag) VALUES (4, 'rachunek', NULL, false);
INSERT INTO pdtypes (id, name, description, defaultflag) VALUES (5, 'decyzja płatnicza', NULL, false);
INSERT INTO pdtypes (id, name, description, defaultflag) VALUES (6, 'opłata za rachunek bankowy', NULL, false);
INSERT INTO pdtypes (id, name, description, defaultflag) VALUES (7, 'proforma', NULL, false);
INSERT INTO pdtypes (id, name, description, defaultflag) VALUES (8, 'nota księgowa', NULL, false);

INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'filter_default_period', '6', 'Domyślny filtr okresu wartości: -1, 1-6', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'storage_dir', 'storage/pd', 'Katalog ze skanami dokumentów kosztowych', 1) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'default_divisionid', '1', 'ID domyślnego oddziału', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'default_currency', 'PLN', 'domyślna waluta', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'pagelimit', '50', 'ilość pozycji na stronie', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;

INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'imap_server', 'mail.domain.pl', 'adres serwera IMAP', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'imap_port', '993', 'port serwera IMAP', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'imap_options', '/imap/ssl', 'opcje połączenia IMAP', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'imap_username', 'username_not_set1', 'login skrzynki imap', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'imap_password', 'password_not_set1', 'login skrzynki imap', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'imap_use_seen_flag', true, 'oznacz wiadomosc jako odczytaną po pobraniu zamiast kasować', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'imap_folder', 'INBOX', 'folder IMAP z którego pobieramy wiadomości', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'move_to_trashbin', true, 'przenieś maila do kosza', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'replace_spaces_in_attachment_names', true, 'zamień spacje w nazwach plików', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'import_only_file_extension', 'pdf', 'zaczytuj tylko załączniki z rozszerzenami zdefiniowanymi tą zmienną - rozszerzenia oddzielone przecinkami', 0) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;
INSERT INTO uiconfig (section, var, value, description, disabled) VALUES ('pd', 'allowed_sender_emails', 'mail1@domain.pl,mail2@domain.pl', 'zaczytuj tylko maile z podanych adresów zdefiniowanych tą zmienną - maile oddzielone przecinkami, pusta wartość lub wyłączona listę dostępu', 1) ON CONFLICT (section, var, userid, divisionid) DO NOTHING;

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSPurchasesPlugin', '2022041400') ON CONFLICT (keytype) DO UPDATE SET keyvalue='2022041400';
