<?php

$this->BeginTrans();

$this->Execute("ALTER TABLE pds ADD COLUMN grossvalue numeric(9,2) NOT NULL");

$this->Execute("UPDATE dbinfo SET keyvalue = ? WHERE keytype = ?", array('2021110401', 'dbversion_LMSPurchasesPlugin'));

$this->CommitTrans();

