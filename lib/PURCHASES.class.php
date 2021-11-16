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
            case 'customerid':
                $orderby = ' ORDER BY pds.customerid';
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

        // PAYMENT FILTERS
        if ($payments) {
            switch ($payments) {
                case '-1':
                    $paymentsfilter = ' AND paydate IS NULL';
                    break;
                case '-2':
                    $paymentsfilter = ' AND paydate IS NULL AND (deadline - ?NOW? > 259200)';
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
                case 'all':
                default:
                    $periodfilter = '';
                    break;
            }
        }

        return $this->db->GetAllByKey(
            'SELECT pds.id, pds.typeid, ' . $this->db->Concat('pt.name') . ' AS pdtypename, pds.fullnumber, pds.netvalue, pds.grossvalue, pds.cdate, pds.sdate, pds.deadline, pds.paydate,
                    pds.description, pds.customerid, ' . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS customername
                FROM pds
                    LEFT JOIN customers cv ON (pds.customerid = cv.id)
                    LEFT JOIN pdtypes pt ON (pds.pdtypeid = pt.id)
                WHERE 1=1'
            . $paymentsfilter
            . $periodfilter
            . $orderby,
            'id'
        );
    }

    public function GetPurchaseDocumentInfo($id)
    {
        $result = $this->db->GetAll('SELECT pds.id, pds.typeid, pds.fullnumber, pds.netvalue, pds.grossvalue, pds.cdate, 
            pds.sdate, pds.deadline, pds.paydate, pds.description,
            pds.customerid, ' . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS customername
            FROM pds
                LEFT JOIN customers cv ON (pds.customerid = cv.id)
            WHERE pds.id = ?',
            array($id)
        );

        return $result;
    }

    public function AddPurchaseDocument($args)
    {
        $args = array(
            'typeid' => $args['typeid'],
            'fullnumber' => $args['fullnumber'],
            'netvalue' => str_replace(",",".",$args['netvalue']),
            'grossvalue' => str_replace(",",".",$args['grossvalue']),
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'description' => empty($args['description']) ? null : $args['description'],
            'customerid' => $args['customerid'],
        );

        $result = $this->db->Execute(
            'INSERT INTO pds (typeid, fullnumber, netvalue, grossvalue, cdate, sdate, deadline, paydate, description, customerid) 
                    VALUES (?, ?, ?, ?, ?NOW?, ?, ?, ?, ?, ?)', $args
        );

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
        $args = array(
            'typeid' => $args['typeid'],
            'fullnumber' => $args['fullnumber'],
            'netvalue' => str_replace(",",".",$args['netvalue']),
            'grossvalue' => str_replace(",",".",$args['grossvalue']),
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'description' => empty($args['description']) ? null : $args['description'],
            'customerid' => $args['customerid'],
            'id' => $args['id'],
        );

        $result = $this->db->Execute(
            'UPDATE pds SET typeid = ?, fullnumber = ?, netvalue = ?, grossvalue = ?, sdate = ?, deadline = ?,
                    paydate = ? , description = ?, customerid = ? WHERE id = ?', $args
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
