<?php

class PURCHASES
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    public function GetPurchaseList($params = array())
    {
        if (!empty($params)) {
            extract($params);
        }
        if (isset($orderby)) {
            switch ($orderby) {
                case 'supplierid':
                    $orderby = ' ORDER BY pds.supplierid';
                    break;
                case 'cdate':
                    $orderby = ' ORDER BY pds.cdate';
                    break;
                case 'sdate':
                    $orderby = ' ORDER BY pds.sdate';
                    break;
                case 'fullnumber':
                    $orderby = ' ORDER BY pds.fullnumber';
                    break;
                case 'netcurrencyvalue':
                    $orderby = ' ORDER BY pds.netcurrencyvalue';
                    break;
                case 'description':
                    $orderby = ' ORDER BY pdc.description';
                    break;
                case 'id':
                default:
                    $orderby = ' ORDER BY pds.id';
                    break;
            }
        } else {
            $orderby = '';
        }

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

        // CATEGORY FILTER
        if (!empty($catids)) {
            $categoriesfilter = ' AND pdcc.categoryid IN (' . implode(',', $catids) . ')';
        } else {
            $categoriesfilter = '';
        }

        // PERIOD FILTER
        if ($period) {
            switch ($period) {
                case '1':
                    $currentweek_firstday = strtotime("monday");
                    $currentweek_lastday = strtotime("monday")+604799;
                    $periodfilter = ' AND (sdate BETWEEN ' . $currentweek_firstday . ' AND ' . $currentweek_lastday . ')';
                    break;
                case '2':
                    $previousweek_firstday = strtotime("last week monday");
                    $previousweek_lastday = strtotime("last week sunday")+604799;
                    $periodfilter = ' AND (sdate BETWEEN ' . $previousweek_firstday . ' AND ' . $previousweek_lastday . ')';
                    break;
                case '3':
                    $currentmonth_firstday = date_to_timestamp(date('Y/m/01', strtotime("now")));
                    $currentmonth_lastday = date_to_timestamp(date('Y/m/t', strtotime("now")));
                    $periodfilter = ' AND (sdate BETWEEN ' . $currentmonth_firstday . ' AND ' . $currentmonth_lastday . ')';
                    break;
                case '4':
                    $previousmonth_firstday = date_to_timestamp(date('Y/m/01', strtotime("last month")));
                    $previousmonth_lastday = date_to_timestamp(date('Y/m/t', strtotime("last month")));
                    $periodfilter = ' AND (sdate BETWEEN ' . $previousmonth_firstday . ' AND ' . $previousmonth_lastday . ')';
                    break;
                case '5':
                    $currentmonth = date('n');
                    switch ($currentmonth) {
                        case 1:
                        case 2:
                        case 3:
                            $startq = 1;
                            break;
                        case 4:
                        case 5:
                        case 6:
                            $startq = 4;
                            break;
                        case 7:
                        case 8:
                        case 9:
                            $startq = 7;
                            break;
                        case 10:
                        case 11:
                        case 12:
                            $startq = 10;
                            break;
                        default:
                            break;
                    }
                    $quarter_start = mktime(0, 0, 0, $startq, 1, date('Y'));
                    $quarter_end = mktime(0, 0, 0, $startq + 3, 1, date('Y'))-1;
                    $periodfilter = ' AND (sdate BETWEEN ' . $quarter_start . ' AND ' . $quarter_end . ')';
                    break;
                case '6':
                    $currentyear_firstday = date_to_timestamp(date('Y/01/01', strtotime("now")));
                    $currentyear_lastday = date_to_timestamp(date('Y/12/31', strtotime("now")));
                    $periodfilter = ' AND (sdate BETWEEN ' . $currentyear_firstday . ' AND ' . $currentyear_lastday . ')';
                    break;
                case 'all':
                default:
                    $periodfilter = '';
                    break;
            }
        } else {
            $periodfilter = '';
        }

        // VALUE FROM FILTER
        if (isset($valuefrom)) {
            $valuefrom = intval($valuefrom);
            if (!empty($valuefrom)) {
                $valuefromhavingfilter = ' SUM((pdc.netcurrencyvalue*tx.value/100)+pdc.netcurrencyvalue) >= ' . $valuefrom;
            } else {
                $valuefromhavingfilter = '';
            }
        } else {
            $valuefromhavingfilter = '';
        }

        // VALUE TO FILTER
        if (isset($valueto)) {
            $valueto = intval($valueto);
            if (!empty($valueto)) {
                $valuetohavingfilter = ' SUM((pdc.netcurrencyvalue*tx.value/100)+pdc.netcurrencyvalue) <= ' . $valueto;
            } else {
                $valuetohavingfilter = '';
            }
        } else {
            $valuetohavingfilter = '';
        }

        if (!empty($description)) {
            $expencedescriptionfilter = ' AND pdc.description ILIKE \'%' . $description . '%\'';
        } else {
            $expencedescriptionfilter = '';
        }

        if (empty($expences)) {
            $split = ' SUM(pdc.netcurrencyvalue) AS netcurrencyvalue,
                SUM(pdc.netcurrencyvalue*tx.value/100) AS vatcurrencyvalue,
                SUM(pdc.netcurrencyvalue*tx.value/100)+SUM(pdc.netcurrencyvalue) AS grosscurrencyvalue';
            $groupby = ' GROUP BY pt.name, vu.name, tx.value, tx.label, cv.lastname, cv.name, pds.id';
        } else {
            $split = ' pdc.netcurrencyvalue AS netcurrencyvalue,
                pdc.netcurrencyvalue*tx.value/100 AS vatcurrencyvalue,
                pdc.netcurrencyvalue*tx.value/100+pdc.netcurrencyvalue AS grosscurrencyvalue,
                pdc.description, pdc.id AS expenceid';
            $groupby = ' GROUP BY pds.id, pt.name, vu.name, tx.value, tx.label, cv.lastname, cv.name, pdc.description, pdc.id';
        }

        $result = $this->db->GetAll(
            'SELECT pds.id, pds.typeid, pt.name AS typename, fullnumber, currency, vatplnvalue,
                    cdate, sdate, deadline, pds.paytype, paydate, COUNT(pdc.netcurrencyvalue) AS expencescount,
                    supplierid, pds.userid, vu.name AS username, tx.value AS tax_value, tx.label AS tax_label,'
                    . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS supplier_name,'
                    . $split
                    . ' FROM pds
                    LEFT JOIN pdcontents pdc ON (pdc.pdid = pds.id)
                    LEFT JOIN pdcontentcat pdcc ON (pdcc.contentid = pdc.id)
                    LEFT JOIN customers cv ON (cv.id = pds.supplierid)
                    LEFT JOIN taxes tx ON (tx.id = pdc.taxid)
                    LEFT JOIN pdtypes pt ON (pt.id = pds.typeid)
                    LEFT JOIN vusers vu ON (vu.id = pds.userid)
                    LEFT JOIN pdattachments pda ON (pda.pdid = pds.id)
                WHERE 1=1'
            . $divisionfilter
            . $docnumberfilter
            . $categoriesfilter
            . $supplierfilter
            . $expencedescriptionfilter
            . $paymentsfilter
            . $periodfilter
            . $groupby
            . ((!empty($valuefromhavingfilter) || !empty($valuetohavingfilter)) ? ' HAVING' : '' )
            . $valuefromhavingfilter
            . ((!empty($valuefromhavingfilter) && !empty($valuetohavingfilter)) ? ' AND ' : '')
            . $valuetohavingfilter
            . $orderby
        );

        if (!empty($result)) {
            foreach ($result as $idx => $r) {
                $params['pdid'] = $r['id'];
                $docfiles = $this->GetPurchaseFiles($params);
                if (!empty($docfiles)) {
                    $result[$idx]['files'] = $docfiles;
                }
                if (empty($expences)) {
                    $docexpencecategory = $this->GetCategoriesUsingDocumentId($r['id']);
                    if (!empty($docexpencecategory)) {
                        $result[$idx]['categories'] = $docexpencecategory;
                    }
                    $docexpenceinvprojects = $this->GetInvProjectsUsingDocumentId($r['id']);
                    if (!empty($docexpenceinvprojects)) {
                        $result[$idx]['invprojects'] = $docexpenceinvprojects;
                    }
                } else {
                    $result[$idx]['expences'] = $this->GetPurchaseDocumentExpences($r['id']);
                    $expencecategories = $this->GetCategoriesUsingExpenceId($r['expenceid']);
                    if (!empty($expencecategories)) {
                        $result[$idx]['categories'] = $expencecategories;
                    }
                    $expenceinvprojects = $this->GetInvProjectsUsingExpenceId($r['expenceid']);
                    if (!empty($expenceinvprojects)) {
                        $result[$idx]['invprojects'] = $expenceinvprojects;
                    }
                }
            }
        }

        if (isset($export) && $export == 1) {
            if (!ConfigHelper::checkPrivilege('purchases_export_purchases')) {
                die();
            }

            $src_iban = ConfigHelper::getConfig('pd.source_iban');
            $exportfilename = ConfigHelper::getConfig('pd.export_filename', ('pdexport-' . date('Y-m-d')));

            $exported = '';
            foreach ($result as $r) {
                $title = $r['typename'] . $r['fullnumber'];
                $exported .= $r['id'] . ';' . $src_iban . ';' . $r['supplier_name'] . ';;;;' . $r['iban'] . ';'
                    . $r['doc_grosscurrnecyvalue'] . ';' . $title . ';;;' . date("Y-m-d") . PHP_EOL;
            }
            header('Content-Disposition: attachment; filename=' . $exportfilename);
            print_r($exported);
            die();
        }

        return $result;
    }

    public function GetCategoriesUsingDocumentId($id)
    {
        return $this->db->GetAll(
            'SELECT DISTINCT pcc.categoryid, pdc.name
                FROM pdcontentcat pcc
                    LEFT JOIN pdcategories pdc ON (pdc.id = pcc.categoryid)
                    LEFT JOIN pdcontents pc ON (pc.id = pcc.contentid)
                    LEFT JOIN pds pd ON (pd.id = pc.pdid)
                WHERE pd.id = ?',
            array($id)
        );
    }

    public function GetCategoriesUsingExpenceId($expenceid)
    {
        return $this->db->GetAll(
            'SELECT categoryid, pdc.name 
                FROM pdcontentcat pcc
                    LEFT JOIN pdcategories pdc ON (pdc.id = pcc.categoryid)
                WHERE contentid = ?',
            array($expenceid)
        );
    }

    public function GetInvProjectsUsingDocumentId($pdid)
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

    public function GetInvProjectsUsingExpenceId($id)
    {
        return $this->db->GetAll(
            'SELECT pdp.invprojectid, inv.name 
                FROM pdcontentinvprojects pdp
                    LEFT JOIN invprojects inv ON (inv.id = pdp.invprojectid)
                WHERE contentid = ?',
            array($id)
        );
    }

    public function GetPurchaseFiles($params)
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

    public function GetDefaultDocumentTypeid()
    {
        return $this->db->GetOne('SELECT id FROM pdtypes WHERE defaultflag IS TRUE');
    }

    public function GetPurchaseDocumentExpences($pdid)
    {
        $result = $this->db->GetAll(
            'SELECT pdid, pdc.id AS expenceid, pdc.netcurrencyvalue, pdc.taxid,
                tx.value AS tax_value, pdc.description, pds.currency, pdc.amount
            FROM pdcontents pdc
                LEFT JOIN taxes tx ON (pdc.taxid = tx.id)
                LEFT JOIN pds ON (pdc.pdid = pds.id)
            WHERE pdid = ?',
            array($pdid)
        );

        foreach ($result as $idx => $r) {
            $result[$idx]['categories'] = $this->GetCategoriesUsingExpenceId($r['expenceid']);
            $result[$idx]['invprojects'] = $this->GetInvProjectsUsingExpenceId($r['expenceid']);

            ////round money values depending on document currency
            switch ($result[$idx]['currency']) {
                case 'PLN':
                default:
                    $precision = 2;
                    break;
            }
            $result[$idx]['netcurrencyvalue'] = round($result[$idx]['netcurrencyvalue'], $precision);
        }

        return $result;
    }

    public function GetCustomerTen($customerid)
    {
        $customerten = $this->db->GetOne('SELECT ten FROM customers WHERE id = ?', array($customerid));
        return (int) filter_var($customerten, FILTER_SANITIZE_NUMBER_INT);
    }

    public function GetPurchaseDocumentInfo($id)
    {
        $result = $this->db->GetRow(
            'SELECT pds.id, pds.typeid, pds.fullnumber, pds.currency, pds.vatplnvalue,
            pds.cdate, to_char(TO_TIMESTAMP(pds.cdate), \'YYYY/MM/DD\') AS cdate_formatted, 
            pds.sdate, to_char(TO_TIMESTAMP(pds.sdate), \'YYYY/MM/DD\') AS sdate_formatted, 
            pds.deadline, to_char(TO_TIMESTAMP(pds.deadline), \'YYYY/MM/DD\') AS deadline_formatted, 
            pds.paydate, to_char(TO_TIMESTAMP(pds.paydate), \'YYYY/MM/DD\') AS paydate_formatted,
            pds.paytype, pds.supplierid, pds.divisionid, pds.iban, cv.ten AS supplier_ten,'
            . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS supplier_name,
            SUM(pd.netcurrencyvalue*pd.amount) AS doc_netcurrencyvalue, 
            SUM(pd.netcurrencyvalue*pd.amount)+(SUM(pd.netcurrencyvalue*pd.amount)*tx.value/100) AS doc_grosscurrencyvalue,
            COUNT(pd.pdid) AS expences_count
            FROM pds
                LEFT JOIN customers cv ON (cv.id = pds.supplierid)
                LEFT JOIN pdcontents pd ON (pd.pdid = pds.id)
                LEFT JOIN taxes tx ON (tx.id = pd.taxid)
            WHERE pds.id = ?
            GROUP BY pds.id, cv.lastname, cv.name, cv.ten, tx.value',
            array($id)
        );
        $result['iban'] = format_bankaccount($result['iban']);
        $result['expences'] = $this->GetPurchaseDocumentExpences($id);
        $result['fileupload'] = $this->GetPurchaseFiles(array('pdid' => $id));

        return $result;
    }

    public function AddPurchaseFiles($params)
    {
        extract($params);

        if (empty($files)) {
            return;
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

            if ($file['content']) {
                $i = 1;
                while (file_exists($dstfile)) {
                    $pathinfo = pathinfo($dstfile);
                    $dstfile = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['basename'] . '-' . $i . '.' . $pathinfo['extension'];
                    $i++;
                }
                file_put_contents($dstfile, $file['content'], LOCK_EX);
            } else {
                rename($file['fullpath'], $dstfile);
            }

            @chown($dstfile, $storage_dir_owneruid);
            @chgrp($dstfile, $storage_dir_ownergid);

            $result = $this->db->Execute(
                'INSERT INTO pdattachments (pdid, filename, contenttype, anteroom, filepath, createtime, sender, sender_mail, comment)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                array(
                    empty($pdid) ? null : $pdid,
                    $dstfilename,
                    $file['type'],
                    empty($anteroom) ? 'false' : 'true',
                    $attdir,
                    time(),
                    empty($sender) ? null : $sender,
                    empty($sender_mail) ? null : $sender_mail,
                    empty($comment) ? null : $comment,
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

    public function MovePurchaseFileFromAnteroom($params)
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

    public function DeleteAttachementFile($attid)
    {
        $file = $this->db->GetOne(
            'SELECT filepath FROM pdattachments WHERE id = ?',
            array($attid)
        );

        if (file_exists($file)) {
            unlink($file);
        }

        $attachment_dir = ConfigHelper::getConfig('pd.storage_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'pd')
            . DIRECTORY_SEPARATOR . $file['filepath'];
        if (is_dir($attachment_dir) && count(scandir($attachment_dir)) == 2) {
            rmdir($attachment_dir);
        }

        return $this->db->Execute(
            'DELETE FROM pdattachments WHERE id = ?',
            array($attid)
        );
    }

    public function AddPurchase($args, $files = null)
    {
        $params = array(
            'typeid' => empty($args['typeid']) ? null : $args['typeid'],
            'currency' => empty($args['currency']) ? 'PLN' : $args['currency'],
            'vatplnvalue' => empty($args['vatplnvalue']) ? null : $args['vatplnvalue'],
            'fullnumber' => $args['fullnumber'],
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paytype' => empty($args['paytype']) ? ConfigHelper::getConfig('pd.default_paytype', 2) : $args['paytype'],
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'supplierid' => $args['supplierid'],
            'iban' => empty($args['iban']) ? null : str_replace(' ', '', $args['iban']),
            'divisionid' => intval($args['divisionid']),
            'userid' => Auth::GetCurrentUser(),
            'attid' => empty($args['attid']) ? null : $args['attid'],
        );

        $this->db->Execute(
            'INSERT INTO pds (typeid, currency, vatplnvalue, fullnumber, cdate, sdate, deadline, paytype, paydate, supplierid, divisionid, iban, userid)
                    VALUES (?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array($params['typeid'], $params['currency'], $params['vatplnvalue'], $params['fullnumber'], $params['sdate'], $params['deadline'], $params['paytype'],
                    $params['paydate'], $params['supplierid'], $params['divisionid'], $params['iban'], $params['userid'])
        );

        $params['pdid'] = $this->db->GetLastInsertID('pds');

        $this->AddExpences($params['pdid'], $args['expenses']);

        if (!empty($files)) {
            $argv = array(
                'pdid' => $params['pdid'],
                'files'=> $files
            );
            $this->AddPurchaseFiles($argv);
        } elseif (!empty($attid)) {
            $argv = array(
                'pdid' => $params['pdid'],
                'attid' => $params['attid'],
            );
            $this->MovePurchaseFileFromAnteroom($argv);
        }

        return null;
    }

    public function DeletePurchaseDocument($id)
    {
        if (empty($id)) {
            exit;
        }

        $pd_dir = ConfigHelper::getConfig('pd.storage_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'pd');
        @rrmdir($pd_dir . DIRECTORY_SEPARATOR . $id);

        return $this->db->Execute(
            'DELETE FROM pds WHERE id = ?',
            array($id)
        );
    }

    public function MarkAsPaid($id)
    {
        if (empty($id)) {
            exit;
        }

        return $this->db->Execute(
            'UPDATE pds SET paydate = ?NOW?
                    WHERE id = ?',
            array($id)
        );
    }

    public function UpdatePurchaseDocument($args)
    {
        $params = array(
            'id' => $args['id'],
            'typeid' => empty($args['typeid']) ? null : $args['typeid'],
            'currency' => empty($args['currency']) ? 'PLN' : $args['currency'],
            'vatplnvalue' => empty($args['vatplnvalue']) ? null : $args['vatplnvalue'],
            'fullnumber' => $args['fullnumber'],
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paytype' => empty($args['paytype']) ? ConfigHelper::getConfig('pd.default_paytype', 2) : $args['paytype'],
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'supplierid' => $args['supplierid'],
            'divisionid' => intval($args['divisionid']),
            'iban' => empty($args['iban']) ? null : str_replace(' ', '', $args['iban']),
        );

        $this->db->Execute(
            'UPDATE pds SET typeid = ?, currency = ?, vatplnvalue = ?, fullnumber = ?, sdate = ?, deadline = ?, paytype = ?,
                    paydate = ?, supplierid = ?, divisionid = ?, iban = ? WHERE id = ?',
            array($params['typeid'], $params['currency'], $params['vatplnvalue'], $params['fullnumber'], $params['sdate'], $params['deadline'],
                    $params['paytype'], $params['paydate'], $params['supplierid'], $params['divisionid'],
                    $params['iban'], $params['id'])
        );

        $this->db->Execute(
            'DELETE FROM pdcontents WHERE pdid = ?',
            array($args['id'])
        );

        $this->AddExpences($args['id'], $args['expenses']);

        return null;
    }
    public function GetSuppliers()
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

    public function GetPurchaseDocumentTypesList($params = array())
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

    public function GetPurchaseTypeInfo($id)
    {
        return $this->db->GetAll(
            'SELECT pdtypes.id, pdtypes.name, pdtypes.description, pdtypes.defaultflag :: int
            FROM pdtypes
            WHERE pdtypes.id = ?',
            array($id)
        );
    }

    public function AddPurchaseDocumentType($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ? null : $args['description'],
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

    public function DeletePurchaseDocumentType($id)
    {
        return $this->db->Execute('DELETE FROM pdtypes WHERE id = ?', array($id));
    }

    public function UpdatePurchaseDocumentType($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ? null : $args['description'],
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

    public function GetPurchaseCategoryList($params = array())
    {
        if (!empty($params)) {
            extract($params);
        }

        if (isset($orderby)) {
            switch ($orderby) {
                case 'name':
                    $orderby = ' ORDER BY pdcategories.name';
                    break;
                case 'description':
                    $orderby = ' ORDER BY pdcategories.description';
                    break;
                case 'id':
                default:
                    $orderby = ' ORDER BY pdcategories.id';
                    break;
            }
        } else {
            $orderby = ' ORDER BY pdcategories.id';
        }

        return $this->db->GetAllByKey(
            'SELECT pdcategories.id, pdcategories.name, pdcategories.description
                FROM pdcategories '
            . $orderby,
            'id'
        );
    }

    public function GetPurchaseCategoryInfo($id)
    {
        return $this->db->GetAll(
            'SELECT pdcategories.id, pdcategories.name, pdcategories.description
            FROM pdcategories
            WHERE pdcategories.id = ?',
            array($id)
        );
    }

    public function AddPurchaseCategory($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ? null : $args['description']
        );

        return $this->db->Execute(
            'INSERT INTO pdcategories (name, description) VALUES (?, ?)',
            $args
        );
    }

    public function DeletePurchaseCategory($id)
    {
        return $this->db->Execute('DELETE FROM pdcategories WHERE id = ?', array($id));
    }

    public function UpdatePurchaseCategory($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ? null : $args['description'],
            'id' => $args['id'],
        );

        return $this->db->Execute(
            'UPDATE pdcategories SET name = ?, description = ? WHERE id = ?',
            $args
        );
    }

    public function AddExpences($pdid, $expenses)
    {
        foreach ($expenses as $idx => $e) {
            $expenses[$idx] = array(
                'netcurrencyvalue' => str_replace(",", ".", $e['netcurrencyvalue']),
                'amount' => $e['amount'],
                'taxid' => intval($e['taxid']),
                'description' => empty($args['description']) ? null : $e['description'],
                'invprojects' => empty($args['invprojects']) ? null : $e['invprojects'],
                'categories' => empty($args['categories']) ? null : $e['categories'],
            );

            $this->db->Execute(
                'INSERT INTO pdcontents (pdid, netcurrencyvalue, amount, taxid, description)
                    VALUES (?, ?, ?, ?, ?)',
                array($pdid, $e['netcurrencyvalue'], $e['amount'], $e['taxid'], $e['description'])
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
}
