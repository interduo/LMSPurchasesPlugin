/* --------------------------------------------------------
Structure of table "pds"
-------------------------------------------------------- */

DROP SEQUENCE IF EXISTS pds_id_seq;
CREATE SEQUENCE pds_id_seq;
DROP TABLE IF EXISTS pds CASCADE;
CREATE TABLE pds (
    id smallint DEFAULT nextval('pds_id_seq'::text) NOT NULL,
    fullnumber varchar(50) NOT NULL,
    value numeric(9,2) NOT NULL,
    grossvalue numeric(9,2) NOT NULL,
    cdate integer NOT NULL,
    sdate integer NOT NULL,
    deadline integer DEFAULT NULL,
    paydate integer DEFAULT NULL,
    description varchar(254) DEFAULT NULL,
    customerid integer NOT NULL
        CONSTRAINT pds_customerid_fkey REFERENCES customers (id) ON DELETE SET NULL ON UPDATE CASCADE,
    PRIMARY KEY (id),
    CONSTRAINT pds_customerid_ukey UNIQUE (fullnumber, customerid)
);

INSERT INTO dbinfo (keytype, keyvalue) VALUES ('dbversion_LMSPurchasesPlugin', '2021110401');

