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

// pdstats definitions
// todo:
// - wynieść ponad funkcję, klasę, może nawet w osobny plik i korzystać równeiż w filtrach
// - porobic odpowiednie filtry po zakonczeniu templ i okresleniu ktore dane sa potrzebne

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

// bazuje na https://github.com/kyob/LMSIncomePlugin
    public function SalePerMonth($only_year)
    {
        $income = $this->db->GetAll(
               'SELECT EXTRACT(MONTH FROM to_timestamp(time)) AS month, SUM(value)* (-1) AS suma
                   FROM cash
                   WHERE value<0 AND EXTRACT(YEAR FROM to_timestamp(time))=' . $only_year . '
                   GROUP BY EXTRACT(MONTH FROM to_timestamp(time)) ORDER BY month
        ');
        return $income;
    }

// bazuje na https://github.com/kyob/LMSIncomePlugin
    public function IncomePerMonth($only_year)
    {
        $income = $this->db->GetAll(
               'SELECT EXTRACT(MONTH FROM to_timestamp(time)) AS month, SUM(value) AS suma
                   FROM cash
                   WHERE importid IS NOT NULL AND value>0 AND EXTRACT(YEAR FROM to_timestamp(time))=' . $only_year . '
                   GROUP BY EXTRACT(MONTH FROM to_timestamp(time)) ORDER BY month
        ');
        return $income;
    }

}
