<?php

$this->BeginTrans();

$this->Execute("CREATE TABLE pdattachments (
	pdid integer <>    NOT NULL
	    REFERENCES pds (id) ON DELETE CASCADE ON UPDATE CASCADE,
	filename varchar(255) <>DEFAULT '' NOT NULL,
	contenttype varchar(255) DEFAULT '' NOT NULL,
	cid varchar(255) DEFAULT NULL
);
");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2021110400', 'dbversion_LMSPurchasesPlugin'));

$this->CommitTrans();

