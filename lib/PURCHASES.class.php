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
            case 'netvalue':
                $orderby = ' ORDER BY pds.netvalue';
                break;
            case 'description':
                $orderby = ' ORDER BY pds.description';
                break;
            case 'id':
            default:
                $orderby = ' ORDER BY pds.id';
                break;
        }

        // SUPPLIER FILTER
        if (!empty($supplier)) {
            $supplierfilter = ' AND supplierid = ' . intval($supplier);
        }

        // PAYMENT FILTER
        if ($payments) {
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
                    $paymentsfilter = '';
                    break;
            }
        }

        // VALUE FROM FILTER
        $valuefrom = intval($valuefrom);
        if (!empty($valuefrom)) {
            $valuefromhavingfilter = ' SUM((pdc.netvalue*tx.value/100)+pdc.netvalue) >= ' . $valuefrom;
        }

        // VALUE TO FILTER
        $valueto = intval($valueto);
        if (!empty($valueto)) {
            $valuetohavingfilter = ' SUM((pdc.netvalue*tx.value/100)+pdc.netvalue) <= ' . $valueto;
        }

        if (empty($expences)) {
            $split = 'SUM(pdc.netvalue) AS netvalue, SUM(pdc.netvalue*tx.value/100) AS vatvalue, (SUM(pdc.netvalue*tx.value/100)+SUM(pdc.netvalue)) AS grossvalue';
            $groupby = ' GROUP BY pds.id, pt.name, vu.name, tx.value, tx.label, cv.lastname, cv.name';
        } else {
            $split = ' pdc.netvalue, (pdc.netvalue*tx.value/100)+pdc.netvalue AS grossvalue, pdc.description, pdc.id AS expenceid';
            $groupby = ' GROUP BY pds.id, pt.name, vu.name, tx.value, tx.label, cv.lastname, cv.name, pdc.netvalue, pdc.id, pdc.description';
        }

        $result = $this->db->GetAll(
            'SELECT pds.id, pds.typeid, pt.name AS typename, pds.fullnumber,
                    pds.cdate, pds.sdate, pds.deadline, pds.paytype, pds.paydate, COUNT(pdc.netvalue) AS expencescount,
                    pds.supplierid, pds.userid, vu.name AS username, tx.value AS tax_value, tx.label AS tax_label,'
                    . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS suppliername,'
                    . $split
                    . ' FROM pds
                    LEFT JOIN pdcontents pdc ON (pdc.pdid = pds.id)
                    LEFT JOIN customers cv ON (cv.id = pds.supplierid)
                    LEFT JOIN taxes tx ON (tx.id = pdc.taxid)
                    LEFT JOIN pdtypes pt ON (pt.id = pds.typeid)
                    LEFT JOIN vusers vu ON (vu.id = pds.userid)
                    LEFT JOIN pdattachments pda ON (pda.pdid = pds.id)
                WHERE 1=1'
            . $supplierfilter
            . $paymentsfilter
            . $periodfilter
            . $groupby
            . ((!empty($valuefromhavingfilter) || !empty($valuetohavingfilter)) ? ' HAVING' : '' )
            . $valuefromhavingfilter
            . ((!empty($valuefromhavingfilter) && !empty($valuetohavingfilter)) ? ' AND ' : '')
            . $valuetohavingfilter
            . $orderby
        );

        if (empty($expences)) {
            foreach ($result as $idx => $r) {
                $docexpencecategory = $this->GetCategoriesUsingDocumentId($r['id']);
                (!empty($docexpencecategory) ? $result[$idx]['categories'] = $docexpencecategory : '' );
                $docexpenceinvprojects = $this->GetInvProjectsUsingDocumentId($r['id']);
                (!empty($docexpenceinvprojects) ? $result[$idx]['invprojects'] = $docexpenceinvprojects : '' );
            }
        } else {
            foreach ($result as $idx => $r) {
                $expencecategories = $this->GetCategoriesUsingExpenceId($r['expenceid']);
                (!empty($expencecategories) ? $result[$idx]['categories'] = $expencecategories : '');
                $expenceinvprojects = $this->GetInvProjectsUsingExpenceId($r['expenceid']);
                (!empty($expenceinvprojects) ? $result[$idx]['invprojects'] = $expenceinvprojects : '');
            }
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

    public function GetPurchaseFiles($pdid)
    {
        $storage_dir = ConfigHelper::GetConfig("pd.storage_dir", 'storage' . DIRECTORY_SEPARATOR . 'pd');

        return $this->db->GetAll(
            'SELECT filename, contenttype, CONCAT_WS(\'' . DIRECTORY_SEPARATOR . '\', \'' . $storage_dir . '\', ?, filename) AS fullpath
            FROM pdattachments
            WHERE pdid = ?',
            array($pdid, $pdid)
        );
    }

    public function GetPurchaseDocumentExpences($pdid)
    {

        $result = $this->db->GetAll(
            'SELECT pdid, pdc.id AS expenceid, pdc.netvalue, pdc.taxid, tx.value AS tax_value, pdc.description
            FROM pdcontents pdc
                LEFT JOIN taxes tx ON (pdc.taxid = tx.id)
            WHERE pdid = ?',
            array($pdid)
        );

        foreach ($result as $idx => $r) {
            $result[$idx]['categories'] = $this->GetCategoriesUsingExpenceId($r['expenceid']);
            $result[$idx]['invprojects'] = $this->GetInvProjectsUsingExpenceId($r['expenceid']);
        }
        return $result;
    }

    public function GetPurchaseDocumentInfo($id)
    {
        $result = $this->db->GetRow(
            'SELECT pds.id, pds.typeid, pds.fullnumber, 
            pds.cdate, to_char(TO_TIMESTAMP(pds.cdate), \'YYYY/MM/DD\') AS cdate_formatted, 
            pds.sdate, to_char(TO_TIMESTAMP(pds.sdate), \'YYYY/MM/DD\') AS sdate_formatted, 
            pds.deadline, to_char(TO_TIMESTAMP(pds.deadline), \'YYYY/MM/DD\') AS deadline_formatted, 
            pds.paydate, to_char(TO_TIMESTAMP(pds.paydate), \'YYYY/MM/DD\') AS paydate_formatted,
            pds.paytype, pds.supplierid, '
            . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS suppliername,
            SUM(pd.netvalue) AS doc_netvalue, (SUM(pd.netvalue*tx.value/100)+SUM(pd.netvalue)) AS doc_grossvalue,
            COUNT(pd.pdid) AS expences_count
            FROM pds
                LEFT JOIN customers cv ON (cv.id = pds.supplierid)
                LEFT JOIN pdcontents pd ON (pd.pdid = pds.id)
                LEFT JOIN taxes tx ON (tx.id = pd.taxid)
            WHERE pds.id = ?
            GROUP BY pds.id, cv.lastname, cv.name',
            array($id)
        );

        $result['expences'] = $this->GetPurchaseDocumentExpences($id);

        return $result;
    }

    private function AddPurchaseFiles($pdid, $files, $cleanup = false)
    {
        $pd_dir = ConfigHelper::getConfig('pd.storage_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'pd');
        $storage_dir_permission = intval(ConfigHelper::getConfig('storage.dir_permission', '0700'), 8);
        $storage_dir_owneruid = ConfigHelper::getConfig('storage.dir_owneruid', 'www-data');
        $storage_dir_ownergid = ConfigHelper::getConfig('storage.dir_ownergid', 'www-data');

        if (!empty($files) && $pd_dir) {
            $pdid_dir = $pd_dir . DIRECTORY_SEPARATOR . $pdid;

            @umask(0007);

            @mkdir($pdid_dir, $storage_dir_permission);
            @chown($pdid_dir, $storage_dir_owneruid);
            @chgrp($pdid_dir, $storage_dir_ownergid);

            $dirs_to_be_deleted = array();
            foreach ($files as $file) {
                $filename = preg_replace('/[^\w\.-_]/', '_', basename($file['name']));
                $dstfile = $pdid_dir . DIRECTORY_SEPARATOR . $filename;
                if (isset($file['content'])) {
                    $fh = @fopen($dstfile, 'w');
                    if (empty($fh)) {
                        continue;
                    }
                    fwrite($fh, $file['content'], strlen($file['content']));
                    fclose($fh);
                } else {
                    if ($cleanup) {
                        $dirs_to_be_deleted[] = dirname($file['name']);
                    }
                    if (!@rename(isset($file['tmp_name']) ? $file['tmp_name'] : $file['name'], $dstfile)) {
                        continue;
                    }
                }

                @chown($dstfile, $storage_dir_owneruid);
                @chgrp($dstfile, $storage_dir_ownergid);

                $this->db->Execute(
                    'INSERT INTO pdattachments (pdid, filename, contenttype) VALUES (?, ?, ?)',
                    array($pdid, $filename, $file['type'])
                );
            }
            if (!empty($dirs_to_be_deleted)) {
                $dirs_to_be_deleted = array_unique($dirs_to_be_deleted);
                foreach ($dirs_to_be_deleted as $dir) {
                    rrmdir($dir);
                }
            }
        }
    }

    public function AddPurchase($args, $files = null)
    {
        $params = array(
            'typeid' => empty($args['typeid']) ? null : $args['typeid'],
            'fullnumber' => $args['fullnumber'],
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paytype' => empty($args['paytype']) ? ConfigHelper::getConfig('pd.default_paytype', 2) : $args['paytype'],
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'supplierid' => $args['supplierid'],
            'userid' => Auth::GetCurrentUser(),
        );

        $result = $this->db->Execute(
            'INSERT INTO pds (typeid, fullnumber, cdate, sdate, deadline, paytype, paydate, supplierid, userid)
                    VALUES (?, ?, ?NOW?, ?, ?, ?, ?, ?, ?)',
            array($params['typeid'], $params['fullnumber'], $params['sdate'], $params['deadline'], $params['paytype'],
                    $params['paydate'], $params['supplierid'], $params['userid'])
        );

        $params['pdid'] = $this->db->GetLastInsertID('pds');

        foreach ($args['expenses'] as $idx => $e) {
            $args['expenses'][$idx] = array(
                'netvalue' => str_replace(",", ".", $e['netvalue']),
                'taxid' => $e['taxid'],
                'description' => empty($args['description']) ? null : $e['description'],
                'invprojects' => empty($args['invprojects']) ? null : $e['invprojects'],
                'categories' => empty($args['categories']) ? null : $e['categories'],
            );

            $this->db->Execute(
                'INSERT INTO pdcontents (pdid, netvalue, taxid, description) VALUES (?, ?, ?, ?)',
                array($params['pdid'], $e['netvalue'], $e['taxid'], $e['description'])
            );
            $args['contentid'] = $this->db->GetLastInsertID('pdcontents');
            if (!empty($e['invprojects'])) {
                foreach ($e['invprojects'] as $p) {
                    $this->db->Execute(
                        'INSERT INTO pdcontentinvprojects (contentid, invprojectid) VALUES (?, ?)',
                        array($args['contentid'], $p)
                    );
                }
            }
            if (!empty($e['categories'])) {
                foreach ($e['categories'] as $c) {
                    $this->db->Execute(
                        'INSERT INTO pdcontentcat (contentid, categoryid) VALUES (?, ?)',
                        array($args['contentid'], $c)
                    );
                }
            }
        }

        if (!empty($files)) {
            $this->AddPurchaseFiles($params['pdid'], $files);
        }

        return $result;
    }

    public function DeletePurchaseDocument($id)
    {
        return $this->db->Execute('DELETE FROM pds WHERE id = ?',
            array($id)
        );
    }

    public function MarkAsPaid($id)
    {
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
            'fullnumber' => $args['fullnumber'],
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paytype' => empty($args['paytype']) ? ConfigHelper::getConfig('pd.default_paytype', 2) : $args['paytype'],
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'supplierid' => $args['supplierid'],
        );

        $this->db->Execute(
            'UPDATE pds SET typeid = ?, fullnumber = ?, sdate = ?, deadline = ?, paytype = ?,
                    paydate = ?, supplierid = ? WHERE id = ?',
            array($params['typeid'], $params['fullnumber'], $params['sdate'], $params['deadline'],
                    $params['paytype'], $params['paydate'], $params['supplierid'], $params['id'])
        );

        $this->db->Execute(
            'DELETE FROM pdcontents WHERE pdid = ?',
            array($args['id'])
        );

        foreach ($args['expenses'] as $e) {
            $expence = array(
                'netvalue' => str_replace(",", ".", $e['netvalue']),
                'taxid' => $e['taxid'],
                'description' => empty($e['description']) ? null : $e['description'],
                'invprojects' => !empty($e['invprojects']) ? $e['invprojects'] : null,
                'categories' => !empty($e['categories']) ? $e['categories'] : null,
            );
            $this->db->Execute(
                'INSERT INTO pdcontents (pdid, netvalue, taxid, description) VALUES (?, ?, ?, ?)',
                array($args['id'], $expence['netvalue'], $expence['taxid'], $expence['description'])
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

        return $result;
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

        return $this->db->GetAllByKey(
            'SELECT pdtypes.id, pdtypes.name, pdtypes.description, pdtypes.defaultflag
                FROM pdtypes '
            . $orderby,
            'id'
        );
    }

    public function GetPurchaseTypeInfo($id)
    {
        $result = $this->db->GetAll(
            'SELECT pdtypes.id, pdtypes.name, pdtypes.description, pdtypes.defaultflag
            FROM pdtypes
            WHERE pdtypes.id = ?',
            array($id)
        );

        return $result;
    }

    public function AddPurchaseType($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ? null : $args['description'],
            'defaultflag' => empty($args['defaultflag']) ? 'false' : 'true'
        );

        $result = $this->db->Execute(
            'INSERT INTO pdtypes (name, description, defaultflag) VALUES (?, ?, ?)',
            $args
        );

        $lastinserted = $this->db->GetLastInsertID('pdtypes');

        if (!empty($args['defaultflag'])) {
            $result = $this->db->Execute('UPDATE pdtypes SET defaultflag = false WHERE id != ?', $lastinserted);
        }

        return $result;
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

        if (!empty($args['defaultflag'])) {
            $result = $this->db->Execute('UPDATE pdtypes SET defaultflag = false');
        }

        $result = $this->db->Execute(
            'UPDATE pdtypes SET name = ?, description = ?, defaultflag = ? WHERE id = ?',
            $args
        );

        return $result;
    }

    public function GetPurchaseCategoryList($params = array())
    {
        if (!empty($params)) {
            extract($params);
        }

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

        return $this->db->GetAllByKey(
            'SELECT pdcategories.id, pdcategories.name, pdcategories.description
                FROM pdcategories '
            . $orderby,
            'id'
        );
    }

    public function GetPurchaseCategoryInfo($id)
    {
        $result = $this->db->GetAll(
            'SELECT pdcategories.id, pdcategories.name, pdcategories.description
            FROM pdcategories
            WHERE pdcategories.id = ?',
            array($id)
        );

        return $result;
    }

    public function AddPurchaseCategory($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ? null : $args['description']
        );

        $result = $this->db->Execute(
            'INSERT INTO pdcategories (name, description) VALUES (?, ?)',
            $args
        );

        return $result;
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

        $result = $this->db->Execute(
            'UPDATE pdcategories SET name = ?, description = ? WHERE id = ?',
            $args
        );

        return $result;
    }

    public function PDStats()
    {
        define('PD_PAID', 0);
        define('PD_OVERDUE', 1);
        define('PD_TODAY', 2);
        define('PD_TOMORROW', 3);
        define('PD_IN3DAYS', 4);
        define('PD_IN7DAYS', 5);
        define('PD_IN14DAYS', 6);


        $PDSTATS = array(
            PD_PAID => array(
                'summarylabel' => trans('Paid'),
                'filter' => 'paydate IS NOT NULL',
                'alias' => 'paid'
            ),
            PD_OVERDUE => array(
                'summarylabel' => trans('Overdue'),
                'filter' => 'paydate IS NULL AND (deadline+86399 < ?NOW?)',
                'alias' => 'overdue'
            ),
            PD_TODAY => array(
                'summarylabel' => trans('Today'),
                'filter' => 'paydate IS NULL AND (deadline+86399 > ?NOW?) AND (deadline - ?NOW? < 86399)',
                'alias' => 'today'
            ),
            PD_TOMORROW => array(
                'summarylabel' => trans('Tomorrow'),
                'filter' => 'paydate IS NULL AND (deadline+2*86399 > ?NOW?) AND (deadline - ?NOW? < 2*86399)',
                'alias' => 'tomorrow'
            ),
            PD_IN3DAYS => array(
                'summarylabel' => trans('In 3 days:'),
                'filter' => 'paydate IS NULL AND deadline - ?NOW? < 3*86400',
                'alias' => 'in3days'
            ),
            PD_IN7DAYS => array(
                'summarylabel' => trans('In 7 days:'),
                'filter' => 'paydate IS NULL AND deadline - ?NOW? < 7*86400',
                'alias' => 'in7days'
            ),
            PD_IN14DAYS => array(
                'summarylabel' => trans('In 14 days:'),
                'filter' => 'paydate IS NULL AND deadline - ?NOW? < 14*86400',
                'alias' => 'in14days'
            ),
        );

        $sql = '';
        foreach ($PDSTATS as $statusidx => $status) {
            $sql .= ' COUNT(CASE WHEN ' . $status['filter'] . ' THEN 1 END) AS ' . $status['alias'] . ',
            SUM(CASE WHEN ' . $status['filter'] . ' THEN grossvalue END) AS '.$status['alias'].'value,
            ';
        }
        $result = $this->db->GetRow(
            'SELECT
            ' . $sql . ' COUNT(id) AS unpaid
            FROM pds
            '
        );
        return $result;
    }

    public function SupplierStats()
    {
        global $CSTATUSES;
        $sql = '';
        foreach ($CSTATUSES as $statusidx => $status) {
            $sql .= ' COUNT(CASE WHEN status = ' . $statusidx . ' THEN 1 END) AS ' . $status['alias'] . ',';
        }
        $result = $this->db->GetRow(
            'SELECT ' . $sql . ' COUNT(id) AS total
            FROM customerview
            WHERE deleted=0'
        );

        $tmp = $this->db->GetRow(
            'SELECT
                SUM(a.value) * -1 AS debtvalue,
                COUNT(*) AS debt,
                SUM(CASE WHEN a.status = ? THEN a.value ELSE 0 END) * -1 AS debtcollectionvalue
            FROM (
                SELECT c.status, b.balance AS value
                FROM customerbalances b
                LEFT JOIN customerview c ON (customerid = c.id)
                WHERE c.deleted = 0 AND b.balance < 0
            ) a',
            array(
                CSTATUS_DEBT_COLLECTION,
            )
        );

        if (is_array($tmp)) {
            $result = array_merge($result, $tmp);
        }

        return $result;
    }

// bazuje na https://github.com/kyob/LMSIncomePlugin
    public function SalePerMonth($only_year)
    {
        $income = $this->db->GetAll(
            'SELECT EXTRACT(MONTH FROM to_timestamp(time)) AS month, SUM(value)* (-1) AS suma
                   FROM cash
                   WHERE value<0
                     AND EXTRACT(YEAR FROM to_timestamp(time))=' . $only_year . '
                   GROUP BY EXTRACT(MONTH FROM to_timestamp(time))
                   ORDER BY month
        '
        );
        return $income;
    }

    public function SalePerMonthType($only_year, $servicetype = 'all')
    {
        switch ($servicetype) {
            case '-1':
                $inv = ' AND servicetype=-1 ';
                break;
            case '1':
                $inv = ' AND servicetype=1 ';
                break;
            case '2':
                $inv = ' AND servicetype=2 ';
                break;
            case '3':
                $inv = ' AND servicetype=3 ';
                break;
            case '4':
                $inv = ' AND servicetype=4 ';
                break;
            case '5':
                $inv = ' AND servicetype=5 ';
                break;
            case '6':
                $inv = ' AND servicetype=6 ';
            case 'all':
            default:
                break;
        }
        $income = $this->db->GetAll(
            'SELECT EXTRACT(MONTH FROM to_timestamp(time)) AS month, SUM(value)* (-1) AS suma
                   FROM cash
                   WHERE value<0
                     AND EXTRACT(YEAR FROM to_timestamp(time))=' . $only_year . '
                     ' . $inv . '
                   GROUP BY EXTRACT(MONTH FROM to_timestamp(time))
                   ORDER BY month
        '
        );
        return $income;
    }

    // bazuje na https://github.com/kyob/LMSIncomePlugin
    public function IncomePerMonth($only_year)
    {
        $income = $this->db->GetAll(
            'SELECT EXTRACT(MONTH FROM to_timestamp(time)) AS month, SUM(value) AS suma
                   FROM cash
                   WHERE importid IS NOT NULL
                     AND value>0
                     AND EXTRACT(YEAR FROM to_timestamp(time))=' . $only_year . '
                   GROUP BY EXTRACT(MONTH FROM to_timestamp(time))
                   ORDER BY month
        '
        );
        return $income;
    }
}
