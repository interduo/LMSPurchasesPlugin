<?php

class PURCHASES
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }
/*
    public function GetUserlistWithRight($right)
    {
        $right ? $right = '%' . $right . '%' : die();
        return $this->db->GetAllByKey(
            'SELECT id, login, ' . $this->db->Concat('lastname', "' '", 'firstname') . ' AS name FROM users'
            . ' WHERE rights LIKE ?',
            'id',
            array($right)
        );
    }*/

    public function setConfirmationFlag($ids, bool $state) : void
    {
        $state = empty($state) ? 'false' : 'true';

        if (empty($ids) || !ConfigHelper::checkPrivilege('purchases_mark_purchase_as_confirmed')) {
            return;
        }

        if (is_array($ids)) {
            $ids = implode(",", $ids);
        }

        $this->db->Execute('UPDATE pds SET confirmflag = ? WHERE id IN ( ? )', array($state, $ids));
    }

    public function getPurchaseList($params = array())
    {
        $src_iban = preg_replace('/\D/', '', ConfigHelper::getConfig('pd.source_iban', 0));
        $export_filename = ConfigHelper::getConfig('pd.export_filename', ('pdexport-' . date('Y-m-d') . '.txt'));
        $export_privileges = ConfigHelper::checkPrivilege('purchases_export_purchases');

        if (!empty($params)) {
            extract($params);
        }

/* PHP 8.0 fragment
        if (isset($orderby)) {
            $orderby = ' ORDER BY '
                . match ($orderby) {
                    'supplierid' => 'pds.supplierid',
                    'cdate' => 'pds.cdate',
                    'sdate' => 'pds.sdate',
                    'fullnumber' => 'pds.fullnumber',
                    'netcurrencyvalue' => 'pds.netcurrencyvalue',
                    'grosscurrencyvalue' => 'pds.grosscurrencyvalue',
                    'description' => 'pdc.description',
                    default => 'pds.id',
                };
        } else {
            $orderby = '';
        }
*/

        $orderby = '';

        // DIVISION FILTER
        if (!empty($divisionid)) {
            if (is_array($divisionid)) {
                $divisionfilter = ' AND pds.divisionid IN (' . implode(',', Utils::filterIntegers($divisionid)) . ')';
            } else {
                $divisionfilter = ' AND pds.divisionid = ' . intval($divisionid);
            }
        } else {
            $divisionfilter = '';
        }

        // SUPPLIER FILTER
        if (!empty($supplier)) {
            $supplierfilter = ' AND supplierid = ' . intval($supplier);
        } else {
            $supplierfilter = '';
        }

        // PAYMENT FILTER
        if (isset($payments)) {
            switch ($payments) {
                case '-1':
                    $paymentsfilter = ' AND paydate IS NULL';
                    break;
                case '-2':
                    $paymentsfilter = ' AND paydate IS NULL AND (deadline - ?NOW? < 3*86400)';
                    break;
                case '-3':
                    $paymentsfilter = ' AND paydate IS NULL AND (deadline - ?NOW? < 7*86400)';
                    break;
                case '-4':
                    $paymentsfilter = ' AND paydate IS NULL AND (deadline - ?NOW? < 14*86400)';
                    break;
                case '-5':
                    $paymentsfilter = ' AND paydate IS NULL AND (deadline+86399 < ?NOW?)';
                    break;
                case '-6':
                    $paymentsfilter = ' AND paydate IS NULL AND deadline = ' . strtotime("today", time());
                    break;
                case 'all':
                default:
                    $paymentsfilter = '';
                    break;
            }
        } else {
            $paymentsfilter = '';
        }

        // DOCNUMBER FILTER
        if (!empty($docnumber)) {
            $docnumberfilter = ' AND fullnumber LIKE \'%' . htmlspecialchars($docnumber) . '%\'';
        } else {
            $docnumberfilter = '';
        }

        // CONFIRM FLAG FILTER
        if (isset($confirm)) {
            switch ($confirm) {
                case '1':
                    $confirmfilter = ' AND confirmflag IS TRUE';
                    break;
                case '0':
                    $confirmfilter = ' AND confirmflag IS FALSE';
                    break;
                case '-1':
                default:
                    $confirmfilter = null;
                    break;
            }
        } else {
            $confirmfilter = null;
        }

        // CATEGORY FILTER
        if (!empty($catids)) {
            $categoriesfilter = ' AND pdcc.categoryid IN (' . implode(',', $catids) . ')';
        } else {
            $categoriesfilter = '';
        }

        // CATEGORY FILTER
        if (!empty($invprojectids)) {
            $invprojectsfilter = ' AND pdci.invprojectid IN (' . implode(',', $invprojectids) . ')';
        } else {
            $invprojectsfilter = '';
        }

        // DATE FROM FILTER
        $datefromfilter = $params['datefrom'] ?
            (!empty(intval($datefrom)) ? ' AND sdate >= ' . intval($datefrom) : null) : null;

        // DATE TO FILTER
        $datetofilter = $params['dateto'] ?
            (!empty(intval($dateto)) ? ' AND sdate >= ' . intval($dateto) : null) : null;


        // NET CURRENCY VALUE FROM FILTER
        $netcurrencyvaluefrom = intval($netcurrencyvaluefrom);
        if (empty($netcurrencyvaluefrom)) {
            $netcurrencyvaluefromhavingfilter = '';
        } else {
            if ($expences) {
                $netcurrencyvaluefromhavingfilter = ' pdc.netcurrencyvalue >= ' . $netcurrencyvaluefrom;
            } else {
                $netcurrencyvaluefromhavingfilter = ' SUM(pdc.netcurrencyvalue) >= ' . $netcurrencyvaluefrom;
            }
        }

        // NET CURRENCY VALUE TO FILTER
        $netcurrencyvalueto = intval($netcurrencyvalueto);
        if (empty($netcurrencyvalueto)) {
            $netcurrencyvaluetohavingfilter = '';
        } else {
            if ($expences) {
                $netcurrencyvaluetohavingfilter = ' pdc.netcurrencyvalue >= ' . $netcurrencyvalueto;
            } else {
                $netcurrencyvaluetohavingfilter = ' SUM(pdc.netcurrencyvalue) >= ' . $netcurrencyvalueto;
            }
        }

        // GROSS CURRENCY VALUE FROM FILTER
        $grosscurrencyvaluefrom = intval($grosscurrencyvaluefrom);
        if (empty($grosscurrencyvaluefrom)) {
            $grosscurrencyvaluefromhavingfilter = '';
        } else {
            if ($expences) {
                $grosscurrencyvaluefromhavingfilter = ' pdc.grosscurrencyvalue >= ' . $grosscurrencyvaluefrom;
            } else {
                $grosscurrencyvaluefromhavingfilter = ' SUM(pdc.grosscurrencyvalue) >= ' . $grosscurrencyvaluefrom;
            }
        }

        // GROSS CURRENCY VALUE TO FILTER
        $grosscurrencyvalueto = intval($grosscurrencyvalueto);
        if (empty($grosscurrencyvalueto)) {
            $grosscurrencyvaluetohavingfilter = '';
        } else {
            if ($expences) {
                $grosscurrencyvaluetohavingfilter = ' pdc.grosscurrencyvalue >= ' . $grosscurrencyvalueto;
            } else {
                $grosscurrencyvaluetohavingfilter = ' SUM(pdc.grosscurrencyvalue) >= ' . $grosscurrencyvalueto;
            }
        }

        if (!empty($description)) {
            $expencedescriptionfilter = ' AND pdc.description ILIKE \'%' . $description . '%\'';
        } else {
            $expencedescriptionfilter = '';
        }

        if (empty($expences)) {
            $split = ', SUM(pdc.netcurrencyvalue) AS doc_netcurrencyvalue,
                SUM(pdc.grosscurrencyvalue-pdc.netcurrencyvalue) AS doc_vatcurrencyvalue,
                SUM(pdc.grosscurrencyvalue) AS doc_grosscurrencyvalue';
            $groupby = ' GROUP BY pt.name, vu.name, tx.value, tx.label, pds.id, dv.name, va.location';
        } else {
            $split = ', pdc.netcurrencyvalue, pdc.grosscurrencyvalue-pdc.netcurrencyvalue AS vatcurrencyvalue,
                pdc.grosscurrencyvalue, pdc.description, pdc.id AS expenceid';
            $groupby = ' GROUP BY pds.id, pt.name, vu.name, tx.value, tx.label, pdc.description, pdc.id,
                dv.name, va.location';
        }

        $result = $this->db->GetAll(
            'SELECT pds.id, pds.typeid, pt.name AS typename, fullnumber, currency, vatplnvalue, confirmflag :: int,
                iban, cdate, sdate, deadline, pds.paytype, paydate, COUNT(pdc.netcurrencyvalue) AS expencescount,
                supplierid, supplier_fullname, supplier_ten, pds.userid, vu.name AS username, tx.value AS tax_value,
                tx.label AS tax_label, preferred_splitpayment :: int, dv.name AS division_name,
                va.location AS division_address'
                . $split
                . ' FROM pds
                LEFT JOIN pdcontents pdc ON (pdc.pdid = pds.id)
                LEFT JOIN pdcontentcat pdcc ON (pdcc.contentid = pdc.id)
                LEFT JOIN pdcontentinvprojects pdci ON (pdci.contentid = pdc.id)
                LEFT JOIN taxes tx ON (tx.id = pdc.taxid)
                LEFT JOIN pdtypes pt ON (pt.id = pds.typeid)
                LEFT JOIN vusers vu ON (vu.id = pds.userid)
                LEFT JOIN divisions dv ON (dv.id = pds.divisionid)
                LEFT JOIN vaddresses va ON (va.id = dv.address_id) 
                LEFT JOIN pdattachments pda ON (pda.pdid = pds.id)
                WHERE 1=1'
            . $divisionfilter
            . $docnumberfilter
            . $confirmfilter
            . $categoriesfilter
            . $invprojectsfilter
            . $supplierfilter
            . $expencedescriptionfilter
            . $paymentsfilter
            . $datefromfilter
            . $datetofilter
            . $groupby
            . ((!empty($grosscurrencyvaluefromhavingfilter) || !empty($grosscurrencyvaluetohavingfilter)) ?
                ' HAVING' : '' )
            . $grosscurrencyvaluefromhavingfilter
            . ((!empty($grosscurrencyvaluefromhavingfilter) && !empty($grosscurrencyvaluetohavingfilter)) ?
                ' AND ' : '')
            . $grosscurrencyvaluetohavingfilter
            . ((!empty($netcurrencyvaluefromhavingfilter) || !empty($netcurrencyvaluetohavingfilter)) ?
                ' HAVING' : '' )
            . $netcurrencyvaluefromhavingfilter
            . ((!empty($netcurrencyvaluefromhavingfilter) && !empty($netcurrencyvaluetohavingfilter)) ?
                ' AND ' : '')
            . $netcurrencyvaluetohavingfilter
            . $orderby
        );

        if (!empty($result)) {
            foreach ($result as $idx => $r) {
                $params['pdid'] = $r['id'];
                $docfiles = $this->getPurchaseFiles($params);
                if (!empty($docfiles)) {
                    $result[$idx]['files'] = $docfiles;
                }
                if (empty($expences)) {
                    $docexpencecategory = $this->getCategoriesUsingDocumentId($r['id']);
                    if (!empty($docexpencecategory)) {
                        $result[$idx]['categories'] = $docexpencecategory;
                    }
                    $docexpenceinvprojects = $this->getInvProjectsUsingDocumentId($r['id']);
                    if (!empty($docexpenceinvprojects)) {
                        $result[$idx]['invprojects'] = $docexpenceinvprojects;
                    }
                } else {
                    $result[$idx]['expences'] = $this->getPurchaseDocumentExpences($r['id']);
                    $expencecategories = $this->getCategoriesUsingExpenceId($r['expenceid']);
                    if (!empty($expencecategories)) {
                        $result[$idx]['categories'] = $expencecategories;
                    }
                    $expenceinvprojects = $this->getInvProjectsUsingExpenceId($r['expenceid']);
                    if (!empty($expenceinvprojects)) {
                        $result[$idx]['invprojects'] = $expenceinvprojects;
                    }
                }
            }

            function array2csv($data, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
            {
                $f = fopen('php://memory', 'r+');
                foreach ($data as $item) {
                    fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
                }
                rewind($f);
                return stream_get_contents($f);
            }

            if (!empty($export) && $export_privileges) {
                $exported = '';
                foreach ($result as $r) {
                    switch ($export) {
                        case '1': // Bank spółdzielczy - przelew zwykły
                            $exported .= $r['id'] . ';' . $src_iban . ';' . $r['supplier_fullname'] . ';;;;'
                                . $r['iban'] . ';' . $r['doc_grosscurrencyvalue'] . ';' . $r['typename'] . ' '
                                . $r['fullnumber'] . ';;;' . date("Y-m-d") . PHP_EOL;
                            break;
                        case '2': // MT103
                            $title = $r['typename'] . ' ' . $r['fullnumber'] . '|ID:' . $r['id'] .'|';
                            $sender = trim($r['division_name']) . '|' . trim($r['division_address']);
                            $receiver = trim($r['supplier_name']) . '|' . trim($r['supplier_address']);

                            $fields = array(
                                110, // (1) kod zlecenia
                                date("Ymd"), // (2) data wykonania
                                round(($r['doc_grosscurrencyvalue']*100), 2), // (3) kwota przelewu w groszach
                                substr($src_iban, 2, 4), // (4) nr rozliczeniowy banku zleceniodawcy
                                0, // (5) pole zerowe
                                preg_replace("/[^0-9]/", '', $src_iban), // (6) nr rachunku zleceniodawcy
                                preg_replace("/[^0-9]/", '', $r['iban']), // (7) nr rachunku odbiorcy
                                $sender, // (8) nazwa i adres zleceniodawcy
                                $receiver, // (9) nazwa i adres odbiorcy
                                0, // (10) pole zerowe
                                substr($r['iban'], 2, 4), // (11) nr rozliczeniowy banku odbiorcy
                                $title, // (12) title
                                null, // (13) empty
                                null, // (14) empty
                                51, // (15) klasyfikacja polecenia
                                ($r['doc_grosscurrnecyvalue'] > 15000) ? '1'
                                    : (empty($r['preferred_splitpayment']) ? '0' : '1'), // (16) split payment
                            );

                            $exported .= array2csv(array($fields));
                            break;
                        default:
                            break;
                    }
                }
                header("Content-type: application/octet-stream");
                header('Content-Disposition: attachment; filename=' . $export_filename);
                header('Content-Type: text/csv');

                die(iconv('UTF-8', 'CP1250', $exported));
            }
        }

        return $result;
    }

    public function getCategoriesUsingDocumentId($id)
    {
        return $this->db->GetAllByKey(
            'SELECT DISTINCT pcc.categoryid, pdc.name
                FROM pdcontentcat pcc
                    LEFT JOIN pdcategories pdc ON (pdc.id = pcc.categoryid)
                    LEFT JOIN pdcontents pc ON (pc.id = pcc.contentid)
                    LEFT JOIN pds pd ON (pd.id = pc.pdid)
                WHERE pd.id = ?',
            'categoryid',
            array($id)
        );
    }

    public function getCategoriesUsingExpenceId($expenceid)
    {
        return $this->db->GetAll(
            'SELECT categoryid, pdc.name 
                FROM pdcontentcat pcc
                    LEFT JOIN pdcategories pdc ON (pdc.id = pcc.categoryid)
                WHERE contentid = ?',
            array($expenceid)
        );
    }

    public function getInvProjectsUsingDocumentId($pdid)
    {
        return $this->db->GetAll(
            'SELECT DISTINCT invprojectid, inv.name
                FROM pdcontentinvprojects pdp
                    LEFT JOIN invprojects inv ON (inv.id = pdp.invprojectid)
                    LEFT JOIN pdcontents pc ON (pc.id = pdp.contentid)
                    LEFT JOIN pds pd ON (pd.id = pc.pdid)
                WHERE pd.id = ?',
            array($pdid)
        );
    }

    public function getInvProjectsUsingExpenceId($id)
    {
        return $this->db->GetAll(
            'SELECT pdp.invprojectid, inv.name 
                FROM pdcontentinvprojects pdp
                    LEFT JOIN invprojects inv ON (inv.id = pdp.invprojectid)
                WHERE contentid = ?',
            array($id)
        );
    }

    public function getPurchaseFiles($params)
    {
        if (!empty($params)) {
            extract($params);
        }

        if (!empty($anteroom)) {
            switch ($anteroom) {
                case '0':
                    $anteroomfilter = 'AND anteroom IS FALSE';
                    break;
                case '1':
                    $anteroomfilter = 'AND anteroom IS TRUE';
                    break;
                default:
                    $anteroomfilter = '';
            }
        } else {
            $anteroomfilter = '';
        }

        $pdidfilter = empty($pdid) ?  null : ' AND pdid = ' . intval($pdid);
        $attidfilter = empty($attid) ?  null : ' AND id = ' . intval($attid);

        return $this->db->GetAllByKey(
            'SELECT id, filename, filepath, contenttype AS type, createtime,
                sender, sender_mail, comment 
                FROM pdattachments
                WHERE 1=1 '
            . $anteroomfilter
            . $pdidfilter
            . $attidfilter,
            'id'
        );
    }

    public function getDefaultDocumentTypeid()
    {
        return $this->db->GetOne('SELECT id FROM pdtypes WHERE defaultflag IS TRUE');
    }

    public function getPurchaseDocumentExpences($pdid)
    {
        $result = $this->db->GetAll(
            'SELECT pdid, pdc.id AS expenceid, pdc.netcurrencyvalue, pdc.grosscurrencyvalue, pdc.taxid,
                tx.value AS tax_value, pdc.description, pds.currency, pdc.amount
            FROM pdcontents pdc
                LEFT JOIN taxes tx ON (pdc.taxid = tx.id)
                LEFT JOIN pds ON (pdc.pdid = pds.id)
            WHERE pdid = ?',
            array($pdid)
        );

        foreach ($result as $idx => $r) {
            $result[$idx]['categories'] = $this->getCategoriesUsingExpenceId($r['expenceid']);
            $result[$idx]['invprojects'] = $this->getInvProjectsUsingExpenceId($r['expenceid']);

            $result[$idx]['netcurrencyvalue'] = $this->roundByCurrency(
                $result[$idx]['currency'],
                $result[$idx]['netcurrencyvalue']
            );
            $result[$idx]['grosscurrencyvalue'] = $this->roundByCurrency(
                $result[$idx]['currency'],
                $result[$idx]['grosscurrencyvalue']
            );
        }

        return $result;
    }

    public function roundByCurrency($currency, $values)
    {
        ////round money values depending on currency
        switch ($currency) {
            case 'PLN':
            default:
                $precision = 2;
                break;
        }

        if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $idx => $val) {
            $result[$idx] = round($val, $precision);
        }

        return $result;
    }

    public function getCustomerTen($customerid)
    {
        $customerten = $this->db->GetOne('SELECT ten FROM customers WHERE id = ?', array($customerid));
        return (int) filter_var($customerten, FILTER_SANITIZE_NUMBER_INT);
    }

    public function getPurchaseDocumentInfo($id)
    {
        $result = $this->db->GetRow(
            'SELECT pds.id, pds.typeid, pds.fullnumber, pds.currency, pds.vatplnvalue,
            pds.supplier_fullname, pds.supplier_ten,
            pds.cdate, to_char(TO_TIMESTAMP(pds.cdate), \'YYYY/MM/DD\') AS cdate_formatted, 
            pds.sdate, to_char(TO_TIMESTAMP(pds.sdate), \'YYYY/MM/DD\') AS sdate_formatted, 
            pds.deadline, to_char(TO_TIMESTAMP(pds.deadline), \'YYYY/MM/DD\') AS deadline_formatted, 
            pds.paydate, to_char(TO_TIMESTAMP(pds.paydate), \'YYYY/MM/DD\') AS paydate_formatted,
            pds.paytype, pds.supplierid, pds.divisionid, pds.iban, pds.preferred_splitpayment :: int,
            SUM(pd.netcurrencyvalue*pd.amount) AS doc_netcurrencyvalue,
            SUM(pd.grosscurrencyvalue*pd.amount) AS doc_grosscurrencyvalue,
            COUNT(pd.pdid) AS expences_count, pds.confirmflag :: int
            FROM pds
                LEFT JOIN pdcontents pd ON (pd.pdid = pds.id)
                LEFT JOIN taxes tx ON (tx.id = pd.taxid)
            WHERE pds.id = ?
            GROUP BY pds.id, pds.supplier_fullname, pds.supplier_ten, tx.value',
            array($id)
        );
        $result['iban'] = format_bankaccount($result['iban']);
        $result['expences'] = $this->getPurchaseDocumentExpences($id);
        $result['fileupload'] = $this->getPurchaseFiles(array('pdid' => $id));

        return $result;
    }

    public function addPurchaseFiles($params)
    {
        extract($params);

        if (empty($files)) {
            return null;
        }

        $storage_dir_owneruid = ConfigHelper::getConfig('storage.dir_owneruid', 33);
        $storage_dir_ownergid = ConfigHelper::getConfig('storage.dir_ownergid', 33);
        $storage_dir_permission = intval(ConfigHelper::getConfig('storage.dir_permission', '0770'), 8);
        $plugin_storage_dir = ConfigHelper::getConfig('pd.storage_dir', STORAGE_DIR . DIRECTORY_SEPARATOR .'pd');
        $attdir = empty($pdid) ? 'anteroom' : $pdid;
        $pdid_dir = $plugin_storage_dir . DIRECTORY_SEPARATOR . $attdir;

        if (!is_dir($pdid_dir)) {
            mkdir($pdid_dir, $storage_dir_permission, true);
        }

        if (!is_readable($pdid_dir) || !is_writable($pdid_dir)) {
            die(trans("Bad permissions for plugin storage dir") . ': ' . $pdid_dir);
        }

        /*
        if (fileowner($plugin_storage_dir) != $storage_dir_owneruid) {
            die(trans("Bad owner for plugin storage dir") . ': ' . $plugin_storage_dir . '<br>'
                . 'chown ' . $storage_dir_owneruid . ' ' . $plugin_storage_dir);
        }

        if (filegroup($plugin_storage_dir) != $storage_dir_ownergid) {
            die(trans("Bad group for plugin storage dir") . ': ' . $plugin_storage_dir . '<br>'
                . 'chgrp ' . $storage_dir_ownergid . ' ' . $plugin_storage_dir);
        }
        */

        $dirs_to_be_deleted = array();

        foreach ($files as $file) {
            $dstfilename = preg_replace('/[^\w\.-_]/', '_', $file['name']);
            $dstfile = $pdid_dir . DIRECTORY_SEPARATOR . $dstfilename;

            // check file exists - if found change name
            $ix = 1;
            while (file_exists($dstfile)) {
                $oldname = pathinfo($file['name'], PATHINFO_FILENAME);
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $dstfilename = $oldname . '_' . $ix . '.' . $extension;
                $dstfile = $pdid_dir . DIRECTORY_SEPARATOR . $dstfilename;
                $ix++;
            }

            if ($file['content']) {
                $i = 1;
                while (file_exists($dstfile)) {
                    $pathinfo = pathinfo($dstfile);
                    $dstfile = $pathinfo['dirname'] . DIRECTORY_SEPARATOR
                        . $pathinfo['basename'] . '-' . $i . '.' . $pathinfo['extension'];
                    $i++;
                }
                file_put_contents($dstfile, $file['content'], LOCK_EX);
            } else {
                rename($file['fullpath'], $dstfile);
            }

            @chown($dstfile, $storage_dir_owneruid);
            @chgrp($dstfile, $storage_dir_ownergid);

            $result = $this->db->Execute(
                'INSERT INTO pdattachments (pdid, filename, contenttype, anteroom, filepath,
                    createtime, sender, sender_mail, comment)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array(
                    empty($pdid) ? null : $pdid,
                    $dstfilename,
                    $file['type'],
                    empty($anteroom) ? 'false' : 'true',
                    $attdir,
                    time(),
                    empty($sender) ?: $sender,
                    empty($sender_mail) ?: $sender_mail,
                    empty($comment) ?: $comment,
                )
            );
        }

        $dirs_to_be_deleted[] = dirname($file['fullpath']);
        if (!empty($cleanup) && empty($anteroom) && !empty($dirs_to_be_deleted)) {
            $dirs_to_be_deleted = array_unique($dirs_to_be_deleted);
            foreach ($dirs_to_be_deleted as $dir) {
                rrmdir($dir);
            }
        }

        return $result;
    }

    public function movePurchaseFileFromAnteroom($params)
    {
        extract($params);

        $pd_dir = ConfigHelper::getConfig('pd.storage_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'pd');
        $storage_dir_permission = intval(ConfigHelper::getConfig('storage.dir_permission', '0770'), 8);
        $storage_dir_owneruid = ConfigHelper::getConfig('storage.dir_owneruid', '33');
        $storage_dir_ownergid = ConfigHelper::getConfig('storage.dir_ownergid', '33');
        $pdid_dir = $pd_dir . DIRECTORY_SEPARATOR . $pdid;

        @umask(0007);
        if (!is_dir($pdid_dir)) {
            @mkdir($pdid_dir, $storage_dir_permission, true);
        }

        @chmod($pdid_dir, $storage_dir_permission);
        @chown($pdid_dir, $storage_dir_owneruid);
        @chgrp($pdid_dir, $storage_dir_ownergid);

        $filename = $this->db->GetOne(
            'SELECT filename FROM pdattachments WHERE id = ?',
            array($attid)
        );

        $srcfile = $pd_dir . DIRECTORY_SEPARATOR . 'anteroom' . DIRECTORY_SEPARATOR . $filename;
        $dstfile = $pd_dir . DIRECTORY_SEPARATOR . $pdid . DIRECTORY_SEPARATOR
            . preg_replace('/[^\w\.-_]/', '_', $filename);
        rename($srcfile, $dstfile);
        @chown($dstfile, $storage_dir_owneruid);
        @chgrp($dstfile, $storage_dir_ownergid);

        return $this->db->Execute(
            'UPDATE pdattachments SET anteroom = ?, pdid = ?, filepath = ? WHERE id = ?',
            array('false', $pdid, $pdid, $attid)
        );
    }

    public function deleteAttachementFile($attid)
    {
        if (empty($attid) || !ConfigHelper::checkPrivilege('purchases_delete_purchase')) {
            die();
        }

        $file = $this->db->GetRow(
            'SELECT filename, filepath FROM pdattachments WHERE id = ?',
            array($attid)
        );

        $attachment_dir = ConfigHelper::getConfig('pd.storage_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'pd')
            . DIRECTORY_SEPARATOR . $file['filepath'];

        $fullpath = $attachment_dir . DIRECTORY_SEPARATOR . $file['filename'];

        if (file_exists($fullpath)) {
            unlink($fullpath);
        }

        if (is_dir($attachment_dir) && count(scandir($attachment_dir)) == 2) {
            rmdir($attachment_dir);
        }

        return $this->db->Execute(
            'DELETE FROM pdattachments WHERE id = ?',
            array($attid)
        );
    }

    public function addPurchase($args, $files = null)
    {
        $allow_to_confirm_purchase = ConfigHelper::checkPrivilege('purchases_mark_purchase_as_confirmed');
        if (!empty($args['supplierid'])) {
            $LMS = LMS::getInstance();
            $supplierinfo = $LMS->GetCustomer($args['supplierid']);
        }
        $params = array(
            'typeid' => empty($args['typeid']) ?: $args['typeid'],
            'currency' => empty($args['currency']) ? 'PLN' : $args['currency'],
            'vatplnvalue' => empty($args['vatplnvalue']) ? null : $args['vatplnvalue'],
            'fullnumber' => empty($args['fullnumber']) ? null : $args['fullnumber'],
            'sdate' => empty($args['sdate']) ?: date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ?: date_to_timestamp($args['deadline']),
            'paytype' => empty($args['paytype']) ? ConfigHelper::getConfig('pd.default_paytype', 2) : $args['paytype'],
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'supplierid' => empty($args['supplierid']) ? null : $args['supplierid'],
            'supplier_fullname' => empty($supplierinfo['customername']) ? null : $supplierinfo['customername'],
            'supplier_ten' => empty($supplierinfo['ten']) ? null : $supplierinfo['ten'],
            'iban' => empty($args['iban']) ?: str_replace(' ', '', $args['iban']),
            'divisionid' => intval($args['divisionid']),
            'userid' => Auth::GetCurrentUser(),
            'preferred_splitpayment' => empty($args['preferred_splitpayment']) ? 'false' : 'true',
            'confirmflag' => empty($allow_to_confirm_purchase) ? 'false'
                : (empty($args['confirmflag']) ? 'false' : 'true'),
        );

        $this->db->Execute(
            'INSERT INTO pds (typeid, currency, vatplnvalue, fullnumber, cdate, sdate, deadline, paytype, paydate,
                 supplierid, supplier_fullname, supplier_ten, divisionid, iban,
                 preferred_splitpayment, confirmflag, userid)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array($params['typeid'], $params['currency'], $params['vatplnvalue'], $params['fullnumber'], time(),
                $params['sdate'], $params['deadline'],$params['paytype'], $params['paydate'], $params['supplierid'],
                $params['supplier_fullname'], $params['supplier_ten'], $params['divisionid'], $params['iban'],
                $params['preferred_splitpayment'], $params['confirmflag'], $params['userid'])
        );

        $params['pdid'] = $this->db->GetLastInsertID('pds');

        $this->addExpense($params['pdid'], $args['expenses']);

        if (!empty($files)) {
            $argv = array(
                'pdid' => $params['pdid'],
                'files' => $files
            );
            $this->addPurchaseFiles($argv);
        }

        return $params['pdid'];
    }

    public function deletePurchaseDocument($id)
    {
        if (empty($id) || !ConfigHelper::checkPrivilege('purchases_delete_purchase')) {
            die();
        }

        $pd_dir = ConfigHelper::getConfig(
            'pd.storage_dir',
            STORAGE_DIR . DIRECTORY_SEPARATOR . 'pd' . DIRECTORY_SEPARATOR . $id
        );
        if (file_exists($pd_dir)) {
            @rrmdir($pd_dir);
        }

        return $this->db->Execute(
            'DELETE FROM pds WHERE id = ?',
            array($id)
        );
    }

    public function markAsPaid($id)
    {
        if (empty($id) || !ConfigHelper::checkPrivilege('purchases_mark_purchase_as_paid')) {
            exit;
        }

        return $this->db->Execute(
            'UPDATE pds SET paydate = ? WHERE id = ?',
            array($id, time())
        );
    }

    public function updatePurchaseDocument($args)
    {
        global $LMS;
        $allow_to_confirm_purchase = ConfigHelper::checkPrivilege('purchases_mark_purchase_as_confirmed');

        $params = array(
            'id' => intval($args['id']),
            'typeid' => empty($args['typeid']) ?: $args['typeid'],
            'currency' => empty($args['currency']) ? 'PLN' : $args['currency'],
            'vatplnvalue' => empty($args['vatplnvalue']) ? null : $args['vatplnvalue'],
            'fullnumber' => $args['fullnumber'],
            'sdate' => empty($args['sdate']) ?: date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paytype' => empty($args['paytype']) ? ConfigHelper::getConfig('pd.default_paytype', 2) : $args['paytype'],
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'supplierid' => $args['supplierid'],
            'divisionid' => intval($args['divisionid']),
            'iban' => empty($args['iban']) ?: str_replace(' ', '', $args['iban']),
            'preferred_splitpayment' => empty($args['preferred_splitpayment']) ? 'false' : 'true',
            'confirmflag' => empty($args['confirmflag']) ? 'false' : 'true',
        );

        if (empty($params['id'])) {
            return null;
        }

        $this->db->Execute(
            'UPDATE pds SET typeid = ?, currency = ?, vatplnvalue = ?, fullnumber = ?, sdate = ?, deadline = ?,
               paytype = ?, paydate = ?, supplierid = ?, divisionid = ?, iban = ?, preferred_splitpayment = ?
            WHERE id = ?',
            array(
                $params['typeid'], $params['currency'], $params['vatplnvalue'], $params['fullnumber'],
                $params['sdate'], $params['deadline'], $params['paytype'], $params['paydate'],
                $params['supplierid'], $params['divisionid'], $params['iban'], $params['preferred_splitpayment'],
                $params['id']
            )
        );

        if ($args['use_current_supplier_info'] == 'on') {
            $supplierinfo = $LMS->GetCustomer($params['supplierid']);

            $this->db->Execute(
                'UPDATE pds SET supplier_fullname = ?, supplier_ten = ? WHERE id = ?',
                array($supplierinfo['customername'], $supplierinfo['ten'], $params['id'])
            );
        }

        if ($allow_to_confirm_purchase) {
            $this->setConfirmationFlag($params['id'], $params['confirmflag']);
        }

        $this->db->Execute(
            'DELETE FROM pdcontents WHERE pdid = ?',
            array($params['id'])
        );

        $this->addExpense($params['id'], $args['expenses']);

        return null;
    }
    public function getSuppliers()
    {
        return $this->db->GetAllByKey(
            'SELECT *
            FROM customerview
            WHERE (flags & ? = ?)',
            'id',
            array(
                CUSTOMER_FLAG_SUPPLIER,
                CUSTOMER_FLAG_SUPPLIER
            )
        );
    }

    public function getPurchaseDocumentTypesList($params = array())
    {
        if (!empty($params)) {
            extract($params);
        }

        if (isset($orderby)) {
            switch ($orderby) {
                case 'name':
                    $orderby = ' ORDER BY pdtypes.name';
                    break;
                case 'description':
                    $orderby = ' ORDER BY pdtypes.description';
                    break;
                case 'id':
                default:
                    $orderby = ' ORDER BY pdtypes.id';
                    break;
            }
        } else {
            $orderby = ' ORDER BY pdtypes.id';
        }

        return $this->db->GetAllByKey(
            'SELECT pdtypes.id, pdtypes.name, pdtypes.description, pdtypes.defaultflag :: int
                FROM pdtypes '
            . $orderby,
            'id'
        );
    }

    public function getPurchaseTypeInfo($id)
    {
        return $this->db->GetAll(
            'SELECT pdtypes.id, pdtypes.name, pdtypes.description, pdtypes.defaultflag :: int
            FROM pdtypes
            WHERE pdtypes.id = ?',
            array($id)
        );
    }

    public function addPurchaseDocumentType($args)
    {
        /// TODO: set propper serial - some day i will find better way
        $this->db->Execute(
            "SELECT setval('pdtypes_id_seq', (SELECT GREATEST(MAX(id)+1,nextval('pdtypes_id_seq'))-1 FROM pdtypes))"
        );

        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ?: $args['description'],
            'defaultflag' => empty($args['defaultflag']) ? 'false' : 'true',
        );

        if ($args['defaultflag'] === 'true') {
            $this->db->Execute('UPDATE pdtypes SET defaultflag = false');
        }

        return $this->db->Execute(
            'INSERT INTO pdtypes (name, description, defaultflag) VALUES (?, ?, ?)',
            array($args['name'], $args['description'], $args['defaultflag'])
        );
    }

    public function deletePurchaseDocumentType($id)
    {
        return $this->db->Execute('DELETE FROM pdtypes WHERE id = ?', array($id));
    }

    public function updatePurchaseDocumentType($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ?: $args['description'],
            'defaultflag' => empty($args['defaultflag']) ? 'false' : 'true',
            'id' => $args['id'],
        );

        if ($args['defaultflag'] === 'true') {
            $this->db->Execute('UPDATE pdtypes SET defaultflag = false');
        }

        return $this->db->Execute(
            'UPDATE pdtypes SET name = ?, description = ?, defaultflag = ? WHERE id = ?',
            $args
        );
    }

    public function getPurchaseCategoryList($params = array())
    {
        if (!empty($params)) {
            extract($params);
        }

        if (isset($orderby)) {
            switch ($orderby) {
                case 'name':
                    $orderby = ' ORDER BY c.name';
                    break;
                case 'description':
                    $orderby = ' ORDER BY c.description';
                    break;
                case 'id':
                default:
                    $orderby = ' ORDER BY c.id';
                    break;
            }
        } else {
            $orderby = ' ORDER BY c.id';
        }

        $results = $this->db->GetAllByKey(
            'SELECT c.id, c.name, c.description
            FROM pdcategories c
                LEFT JOIN pdusercategories uc ON (uc.categoryid = c.id)
            '
            . $orderby,
            'id'
        );

        if (!empty($results)) {
            if (!is_array($results)) {
                $results = array($results);
            }
            foreach ($results as &$r) {
                $r['userids'] = $this->getUsersForCategory($r['id']);
            }
        }

        return $results;
    }

    private function getUsersForCategory($categoryid)
    {
        return $this->db->GetAllByKey(
            'SELECT ' . $this->db->Concat('u.lastname', "' '", 'u.firstname')
            . ' AS username, uc.id, u.id
            FROM pdusercategories uc
                LEFT JOIN users u ON (uc.userid = u.id)
            WHERE uc.categoryid = ?',
            'id',
            array($categoryid)
        );
    }

    public function getPurchaseCategoryInfo($categoryid)
    {
        $results = $this->db->GetRow('SELECT id, name, description FROM pdcategories WHERE id = ?', array($categoryid));

        if (!empty($results)) {
            $results['userids'] = $this->getUsersForCategory($categoryid);
        }

        return $results;
    }

    public function addPurchaseCategory($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ?: $args['description'],
            'userids' => empty($args['userids']) ?: $args['userids'],
        );

        $this->db->Execute(
            'INSERT INTO pdcategories (name, description) VALUES (?, ?)',
            $args
        );

        if ($args['userids']) {
            $catid = $this->db->GetLastInsertID('pdcategories');
            $this->replaceUserPdCategories($args['userids'], $catid, true);
        }

        return null;
    }

    private function replaceUserPdCategories($userids, $categoryid, $nodelete)
    {
        if (!empty($nodelete)) {
            $this->db->Execute(
                'DELETE FROM pdusercategories WHERE categoryid = ?',
                array($categoryid)
            );
        }

        if (empty($userids)) {
            return;
        }

        foreach ($userids as $uid) {
            $this->db->Execute(
                'INSERT INTO pdusercategories (userid, categoryid) VALUES (?, ?)',
                array($uid, $categoryid)
            );
        }

        return null;
    }

    private function getUserPurchaseCategories($userid)
    {
        $result = $this->db->GetCol(
            'SELECT DISTINCT categoryid
            FROM pdusercategories
            WHERE userid = ?',
            array($userid)
        );
        return is_array($result) ? $result : array($result);
    }

    public function isLoggedUserAllowedToViewThisAttachment($params)
    {
        extract($params);

        if (empty($pdid) && empty($attid)) {
            return false;
        }

        ///dostep gdy faktura nie ma kategorii
        ///użytkownik musi mieć uprawnienia do co najmniej jednej kategorii z wydatku faktury
        if (empty($pdid)) {
            $doccategories = $this->getCategoriesUsingDocumentId($this->getPurchaseDocumentIdUsingAttid($attid));
        } else {
            $doccategories = $this->getCategoriesUsingDocumentId($pdid);
        }

        $usercategories = $this->getUserPurchaseCategories(Auth::GetCurrentUser());

        if (empty($doccategories) || ConfigHelper::checkPrivilege('superuser')) {
            return true;
        }

        if (empty($usercategories) && !empty($doccategories)) {
            return false;
        }

        return !empty(array_intersect($doccategories, $usercategories));
    }

    public function getPurchaseDocumentIdUsingAttid($attid)
    {
        return $this->db->GetOne(
            'SELECT pdid FROM pdattachments WHERE id = ?',
            array($attid)
        );
    }

    public function deletePurchaseCategory($id)
    {
        return $this->db->Execute(
            'DELETE FROM pdcategories WHERE id = ?',
            array($id)
        );
    }

    public function updatePurchaseCategory($params)
    {
        if (empty($params['id'])) {
            die();
        }

        $args = array(
            'name' => $params['name'],
            'description' => empty($params['description']) ?: $params['description'],
            'id' => intval($params['id']),
        );

        $this->db->Execute(
            'UPDATE pdcategories SET name = ?, description = ? WHERE id = ?',
            $args
        );

        if (isset($params['userids'])) {
            $this->replaceUserPdCategories($params['userids'], $args['id'], true);
        }

        return null;
    }

    public function addExpense($pdid, $expenses)
    {
        foreach ($expenses as $idx => $e) {
            $expenses[$idx] = array(
                'netcurrencyvalue' => str_replace(",", ".", $e['netcurrencyvalue']),
                'grosscurrencyvalue' => str_replace(",", ".", $e['grosscurrencyvalue']),
                'amount' => $e['amount'],
                'taxid' => intval($e['taxid']),
                'description' => empty($args['description']) ?: $e['description'],
                'invprojects' => empty($args['invprojects']) ?: $e['invprojects'],
                'categories' => empty($args['categories']) ?: $e['categories'],
            );

            $this->db->Execute(
                'INSERT INTO pdcontents (pdid, netcurrencyvalue, grosscurrencyvalue, amount, taxid, description)
                    VALUES (?, ?, ?, ?, ?, ?)',
                array($pdid, $e['netcurrencyvalue'], $e['grosscurrencyvalue'],
                    $e['amount'], $e['taxid'], $e['description'])
            );

            $contentid = $this->db->GetLastInsertID('pdcontents');

            if (!empty($e['invprojects'])) {
                foreach ($e['invprojects'] as $p) {
                    $this->db->Execute(
                        'INSERT INTO pdcontentinvprojects (contentid, invprojectid) VALUES (?, ?)',
                        array($contentid, $p)
                    );
                }
            }

            if (!empty($e['categories'])) {
                foreach ($e['categories'] as $c) {
                    $this->db->Execute(
                        'INSERT INTO pdcontentcat (contentid, categoryid) VALUES (?, ?)',
                        array($contentid, $c)
                    );
                }
            }
        }
    }

    public function documentExist($supplierid, $fullnumber)
    {
        $supplierid = intval($supplierid);
        $fullnumber = strtoupper(htmlspecialchars($fullnumber));

        return $this->db->GetOne(
            'SELECT id 
              FROM pds 
              WHERE supplierid = ? AND UPPER(fullnumber) = ?',
            array($supplierid, $fullnumber)
        );
    }
}
