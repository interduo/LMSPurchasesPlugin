<?php

// rozkmina jak w osobnym pliku trzymac klase z funkcjami dla statystyk

class PD_STATS
{

private $db;
//    private $options = array();

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    public function PDStats()
    {

// pdstats definitions - todo: wynieść ponad funkcję, klasę, może nawet w osobny plik i korzystać równeiż w filtrach
define('PD_PAID', 0);
define('PD_OVERDUE', 1);
define('PD_TODAY', 2);
define('PD_IN3DAYS', 3);
define('PD_IN7DAYS', 4);
define('PD_IN14DAYS', 5);

$PDSTATS = array(
    PD_PAID => array(
        'summarylabel' => trans('Paid:'),
        'filter' => 'paydate IS NOT NULL',
        'alias' => 'paid'
    ),
    PD_OVERDUE => array(
        'summarylabel' => trans('Today:'),
        'filter' => 'paydate IS NULL AND deadline = ?NOW?',
        'alias' => 'today'
    ),
    PD_TODAY => array(
        'summarylabel' => trans('Overdue:'),
        'filter' => 'paydate IS NULL AND deadline+86399 < ?NOW?',
        'alias' => 'overdue'
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

//        global $PDSTATS;
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

// copied from CustomerStats:
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

}
