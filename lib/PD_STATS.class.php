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
        global $PDSTATUSES;
        $sql = '';
        foreach ($PDSTATUSES as $statusidx => $status) {
            $sql .= ' COUNT(CASE WHEN status = ' . $statusidx . ' THEN 1 END) AS ' . $status['alias'] . ',';
        }
        $result = $this->db->GetRow(
            'SELECT ' . $sql . ' COUNT(id) AS total
            FROM pds
            '
        );

        $tmp = $this->db->GetRow(
            'SELECT
                SUM(a.value) * -1 AS debtvalue,
                COUNT(*) AS debt,
                SUM(CASE WHEN a.status = ? THEN a.value ELSE 0 END) * -1 AS debtcollectionvalue
            FROM (
                SELECT status, balance AS value
                FROM pds
            ) a',
            array(
                PD_DEBT_COLLECTION,
            )
        );

        if (is_array($tmp)) {
            $result = array_merge($result, $tmp);
        }

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

}
