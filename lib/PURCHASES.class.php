<?php

class PURCHASES
{
private $db;            // database object

    public function __construct()
    {
        // class variables setting
        $this->db = LMSDB::getInstance();
    }

    public function GetPurchaseDocumentList($params = array())
    {
        if (!empty($params)) {
            extract($params);
        }

        switch ($orderby) {
            case 'supplierid':
                $orderby = ' ORDER BY pds.supplierid';
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
            case 'grossvalue':
                $orderby = ' ORDER BY pds.grossvalue';
                break;
            case 'description':
                $orderby = ' ORDER BY pds.description';
                break;
            case 'id':
            default:
                $orderby = ' ORDER BY pds.id';
                break;
        }

        // PAYMENT FILTERS
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
                    $periodfilter = ' AND sdate BETWEEN ' . $currentweek_firstday . ' AND ' . $currentweek_lastday;
                    break;
                case '2':
                    $previousweek_firstday = strtotime("last week monday");
                    $previousweek_lastday = strtotime("last week sunday")+604799;
                    $periodfilter = ' AND sdate BETWEEN ' . $previousweek_firstday . ' AND ' . $previousweek_lastday;
                    break;
                case '3':
                    $currentmonth_firstday = date_to_timestamp(date('Y/m/01', strtotime("now")));
                    $currentmonth_lastday = date_to_timestamp(date('Y/m/t', strtotime("now")));
                    $periodfilter = ' AND sdate BETWEEN ' . $currentmonth_firstday . ' AND ' . $currentmonth_lastday;
                    break;
                case '4':
                    $previousmonth_firstday = date_to_timestamp(date('Y/m/01', strtotime("last month")));
                    $previousmonth_lastday = date_to_timestamp(date('Y/m/t', strtotime("last month")));
                    $periodfilter = ' AND sdate BETWEEN ' . $previousmonth_firstday . ' AND ' . $previousmonth_lastday;
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
                    $periodfilter = ' AND sdate BETWEEN ' . $quarter_start . ' AND ' . $quarter_end;
                    break;
                case '6':
                    $currentyear_firstday = date_to_timestamp(date('Y/01/01', strtotime("now")));
                    $currentyear_lastday = date_to_timestamp(date('Y/12/31', strtotime("now")));
                    $periodfilter = ' AND sdate BETWEEN ' . $currentyear_firstday . ' AND ' . $currentyear_lastday;
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
            $valuefromfilter = ' AND grossvalue >= ' . $valuefrom;
        }
        $valueto = intval($valueto);
        if (!empty($valueto)) {
            $valuetofilter = ' AND grossvalue <= ' . $valueto;
        }

        $result = $this->db->GetAllByKey(
            'SELECT pds.id, pds.typeid, pt.name AS typename, pds.fullnumber, pds.netvalue, 
                    pds.grossvalue, pds.cdate, pds.sdate, pds.deadline, pds.paydate, pds.description, 
                    pds.supplierid, pds.userid, u.name AS username, '
                    . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS suppliername
                FROM pds
                    LEFT JOIN customers cv ON (pds.supplierid = cv.id)
                    LEFT JOIN pdtypes pt ON (pds.typeid = pt.id)
                    LEFT JOIN vusers u ON (pds.userid = u.id)
                WHERE 1=1'
            . $paymentsfilter
            . $periodfilter
            . $valuefromfilter
            . $valuetofilter
            . $orderby,
            'id'
        );
        foreach ($result as $idx=>$val) {
            $result[$idx]['projects'] = $this->GetAssignedProjects($idx);
        }
        return $result;
    }

    public function GetAssignedProjects($pdid) {
        return $this->db->GetAll(
            'SELECT inv.id AS id, inv.name AS name
                FROM pdprojects AS pdp
                    LEFT JOIN invprojects inv ON (pdp.projectid = inv.id)
                WHERE pdid = ?',
            array($pdid)
        );
    }

    public function SetAssignedProjects($params) {
        if (!empty($params['pdid'])) {
            $this->db->Execute(
                'DELETE FROM pdprojects WHERE pdid = ?',
                array($params['pdid'])
            );

            foreach ($params['invprojects'] as $p)
                $this->db->Execute(
                    'INSERT INTO pdprojects (pdid, projectid) VALUES (?, ?)',
                    array($params['pdid'], $p)
                );
            }

        return null;
    }

    public function GetPurchaseDocumentInfo($id)
    {
        $result = $this->db->GetRow('SELECT pds.id, pds.typeid, pds.fullnumber, pds.netvalue, pds.grossvalue, pds.cdate, 
            pds.sdate, pds.deadline, pds.paydate, pds.description,
            pds.supplierid, ' . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS suppliername
            FROM pds
                LEFT JOIN customers cv ON (pds.supplierid = cv.id)
            WHERE pds.id = ?',
            array($id)
        );

        if ($result) {
            $result['projects'] = $this->GetAssignedProjects($id);
        }

        return $result;
    }

    private function SavePDAttachments($ticketid, $messageid, $files, $cleanup = false)
    {
        $pd_dir = ConfigHelper::getConfig('pd.mail_dir', STORAGE_DIR . DIRECTORY_SEPARATOR . 'pd');
        $storage_dir_permission = intval(ConfigHelper::getConfig('storage.dir_permission', ConfigHelper::getConfig('pd.mail_dir_permission', '0700')), 8);
        $storage_dir_owneruid = ConfigHelper::getConfig('storage.dir_owneruid', 'root');
        $storage_dir_ownergid = ConfigHelper::getConfig('storage.dir_ownergid', 'root');

        if (!empty($files) && $pd_dir) {
            $ticket_dir = $pd_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $ticketid);
            $message_dir = $ticket_dir . DIRECTORY_SEPARATOR . sprintf('%06d', $messageid);

            @umask(0007);

            @mkdir($ticket_dir, $storage_dir_permission);
            @chown($ticket_dir, $storage_dir_owneruid);
            @chgrp($ticket_dir, $storage_dir_ownergid);
            @mkdir($message_dir, $storage_dir_permission);
            @chown($message_dir, $storage_dir_owneruid);
            @chgrp($message_dir, $storage_dir_ownergid);

            $dirs_to_be_deleted = array();
            foreach ($files as $file) {
                // handle spaces and unknown characters in filename
                // on systems having problems with that
                $filename = preg_replace('/[^\w\.-_]/', '_', basename($file['name']));
                $dstfile = $message_dir . DIRECTORY_SEPARATOR . $filename;
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
                    'INSERT INTO pdattachments (pdid, filename, contenttype)
                    VALUES (?, ?, ?)',
                    array($messageid, $filename, $file['type'])
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

    public function AddPurchaseDocument($args, $files = null)
    {
        $invprojects = empty($args['invprojects']) ? null : $args['invprojects'];

        $args = array(
            'typeid' => empty($args['typeid']) ? null : $args['typeid'],
            'fullnumber' => $args['fullnumber'],
            'netvalue' => str_replace(",",".",$args['netvalue']),
            'grossvalue' => str_replace(",",".",$args['grossvalue']),
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'description' => empty($args['description']) ? null : $args['description'],
            'supplierid' => $args['supplierid'],
            'userid' => Auth::GetCurrentUser(),
        );

        $result = $this->db->Execute(
            'INSERT INTO pds (typeid, fullnumber, netvalue, grossvalue, cdate, sdate, deadline, paydate, description, supplierid, userid) 
                    VALUES (?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?, ?)', $args
        );

        if (!empty($invprojects)) {
            $params['invprojects'] = $invprojects;
            $params['pdid'] = $this->db->GetLastInsertID('pds');
            $this->SetAssignedProjects($params);
        }

        $this->SavePDAttachments($params['pdid'], $params['pdid'], $files);

        return $result;
    }

    public function DeletePurchaseDocument($id)
    {
        return $this->db->Execute('DELETE FROM pds WHERE id = ?', array($id));
    }

    public function MarkAsPaid($id)
    {
        return $this->db->Execute('UPDATE pds SET paydate = ?NOW? WHERE id = ?', array($id));
    }

    public function UpdatePurchaseDocument($args)
    {
        ///porównać to co jest aktualnie w projekcie i to co wybieramy i warunkowo odpalić ten kod -
        $params['pdid'] = $args['id'];
        $params['invprojects'] = !empty($args['invprojects']) ? $args['invprojects'] : null;
        $this->SetAssignedProjects($params);
        ///

        $args = array(
            'typeid' => empty($args['typeid']) ? null : $args['typeid'],
            'fullnumber' => $args['fullnumber'],
            'netvalue' => str_replace(",",".",$args['netvalue']),
            'grossvalue' => str_replace(",",".",$args['grossvalue']),
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'description' => empty($args['description']) ? null : $args['description'],
            'supplierid' => $args['supplierid'],
            'id' => $args['id'],
        );

        $result = $this->db->Execute(
            'UPDATE pds SET typeid = ?, fullnumber = ?, netvalue = ?, grossvalue = ?, sdate = ?, deadline = ?,
                    paydate = ? , description = ?, supplierid = ? WHERE id = ?', $args
            );

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
            'SELECT pdtypes.id, pdtypes.name, pdtypes.description
                FROM pdtypes '
            . $orderby,
            'id'
        );
    }
    public function GetPurchaseTypeInfo($id)
    {
        $result = $this->db->GetAll('SELECT pdtypes.id, pdtypes.name, pdtypes.description
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
            'description' => empty($args['description']) ? null : $args['description']
        );

        $result = $this->db->Execute(
            'INSERT INTO pdtypes (name, description) VALUES (?, ?)', 
            $args
        );

        return $result;
    }

    public function DeletePurchaseTypeDocument($id)
    {
        return $this->db->Execute('DELETE FROM pdtypes WHERE id = ?', array($id));
    }

    public function UpdatePurchaseTypeDocument($args)
    {
        $args = array(
            'name' => $args['name'],
            'description' => empty($args['description']) ? null : $args['description'],
            'id' => $args['id'],
        );

        $result = $this->db->Execute(
            'UPDATE pdtypes SET name = ?, description = ? WHERE id = ?', $args
        );

        return $result;
    }
}
