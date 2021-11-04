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
            case 'value':
                $orderby = ' ORDER BY pds.value';
                break;
            case 'description':
                $orderby = ' ORDER BY pds.description';
                break;
            case 'id':
            default:
                $orderby = ' ORDER BY pds.id';
                break;
        }

        return $this->db->GetAllByKey(
            'SELECT pds.id, pds.fullnumber, pds.value, pds.grossvalue, pds.cdate, pds.sdate, pds.deadline, pds.paydate,
                    pds.description, pds.customerid, ' . $this->db->Concat('cv.lastname', "' '", 'cv.name') . ' AS customername
                FROM pds
                    LEFT JOIN customers cv ON (pds.customerid = cv.id) '
            . $orderby,
            'id'
        );
    }

    public function GetPurchaseDocumentInfo($id)
    {
        $result = $this->db->GetAll('SELECT pds.id, pds.fullnumber, pds.value, pds.grossvalue, pds.cdate, 
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
            'fullnumber' => $args['fullnumber'],
            'value' => str_replace(",",".",$args['value']),
            'grossvalue' => str_replace(",",".",$args['grossvalue']),
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'description' => empty($args['description']) ? null : $args['description'],
            'customerid' => $args['customerid'],
        );

        $result = $this->db->Execute(
            'INSERT INTO pds (fullnumber, value, grossvalue, cdate, sdate, deadline, paydate, description, customerid) 
                    VALUES (?, ?, ?, ?NOW?, ?, ?, ?, ?, ?)', $args
        );

        return $result;
    }

    public function DeletePurchaseDocument($id)
    {
        return $this->db->Execute('DELETE FROM pds WHERE id = ?', array($id));
    }

    public function UpdatePurchaseDocument($args)
    {
        $args = array(
            'fullnumber' => $args['fullnumber'],
            'value' => str_replace(",",".",$args['value']),
            'grossvalue' => str_replace(",",".",$args['grossvalue']),
            'sdate' => empty($args['sdate']) ? null : date_to_timestamp($args['sdate']),
            'deadline' => empty($args['deadline']) ? null : date_to_timestamp($args['deadline']),
            'paydate' => empty($args['paydate']) ? null : date_to_timestamp($args['paydate']),
            'description' => empty($args['description']) ? null : $args['description'],
            'customerid' => $args['customerid'],
            'id' => $args['id'],
        );

        $result = $this->db->Execute(
            'UPDATE pds SET fullnumber = ?, value = ?, grossvalue = ?, sdate = ?, deadline = ?,
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
}
