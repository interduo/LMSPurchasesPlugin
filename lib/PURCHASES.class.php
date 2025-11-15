<?php

declare(strict_types=1);

class PURCHASES
{
    private $db;

    public function __construct()
    {
        $this->db = LMSDB::getInstance();
    }

    /**
     * Set confirmation flag on purchase(s)
     *
     * @param int|array $ids
     * @param bool $state
     */
    public function setConfirmationFlag($ids, bool $state): void
    {
        $flag = $state ? 'true' : 'false';

        if (empty($ids) || !ConfigHelper::checkPrivilege('purchases_mark_purchase_as_confirmed')) {
            return;
        }

        $idList = is_array($ids) ? implode(",", array_map('intval', $ids)) : intval($ids);

        $this->db->Execute('UPDATE pds SET confirmflag = ? WHERE id IN (' . $idList . ')', array($flag));
    }

    /**
     * Export helper functions (moved from deep inside getPurchaseList)
     */
    private static function array2csv(array $data, string $delimiter = ',', string $enclosure = '"', string $escape_char = "\\"): string
    {
        $f = fopen('php://memory', 'r+');
        foreach ($data as $item) {
            fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
        }
        rewind($f);
        return stream_get_contents($f);
    }

    private static function splitPaymentCheck($doc_flag, $doc_grosscurrencyvalue, $currency): bool
    {
        if ($currency == 'PLN' && $doc_grosscurrencyvalue >= 15000) {
            return true;
        }
        if ($currency == 'PLN' && !$doc_flag) {
            return false;
        }
        if ($currency != 'PLN') {
            return false;
        }
        return true;
    }

    /**
     * Get purchase list with all filters
     *
     * @param array $params
     * @return array|null
     */
    public function getPurchaseList(array $params = array()): ?array
    {
        // Read config values only once.
        $src_iban = preg_replace('/\D/', '', ConfigHelper::getConfig('pd.source_iban', '0'));
        $export_filename = ConfigHelper::getConfig('pd.export_filename', ('pdexport-' . date('Y-m-d') . '.txt'));
        $export_privileges = ConfigHelper::checkPrivilege('purchases_export_purchases');
        $expences = $params['expences'] ?? null;

        // Extract and sanitize parameters.
        $divisionid = $params['divisionid'] ?? null;
        $supplier = $params['supplier'] ?? null;
        $payments = $params['payments'] ?? null;
        $docnumber = $params['docnumber'] ?? null;
        $confirm = $params['confirm'] ?? null;
        $catids = $params['catids'] ?? null;
        $invprojectids = $params['invprojectids'] ?? null;
        $datefrom = $params['datefrom'] ?? null;
        $dateto = $params['dateto'] ?? null;
        $netcurrencyvaluefrom = $params['netcurrencyvaluefrom'] ?? null;
        $netcurrencyvalueto = $params['netcurrencyvalueto'] ?? null;
        $grosscurrencyvaluefrom = $params['grosscurrencyvaluefrom'] ?? null;
        $grosscurrencyvalueto = $params['grosscurrencyvalueto'] ?? null;
        $description = $params['description'] ?? null;
        $orderby = $params['orderby'] ?? '';

        // Build ORDER BY clause
        if ($orderby) {
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
                case 'grosscurrencyvalue':
                    $orderby = ' ORDER BY pds.grosscurrencyvalue';
                    break;
                case 'description':
                    $orderby = ' ORDER BY pdc.description';
                    break;
                default:
                    $orderby = ' ORDER BY pds.id';
                    break;
            }
        }

        // Filters
        $divisionfilter = $divisionid ? ' AND pds.divisionid IN (' . implode(',', Utils::filterIntegers((array)$divisionid)) . ')' : '';
        $supplierfilter = $supplier ? ' AND pds.supplierid = ' . intval($supplier) : '';
        $docnumberfilter = $docnumber ? ' AND pds.fullnumber LIKE '\%' . $this->db->Escape($docnumber) . '\%'' : '';
        $categoriesfilter = $catids ? ' AND pdcc.categoryid IN (' . implode(',', Utils::filterIntegers((array)$catids)) . ')' : '';
        $invprojectsfilter = $invprojectids ? ' AND pdci.invprojectid IN (' . implode(',', Utils::filterIntegers((array)$invprojectids)) . ')' : '';
        $expencedescriptionfilter = $description ? ' AND pdc.description ILIKE '\%' . $this->db->Escape($description) . '\%'' : '';

        // Payment filters
        $paymentsfilter = '';
        if ($payments !== null) {
            switch ($payments) {
                case '-1':
                    $paymentsfilter = ' AND pds.paydate IS NULL';
                    break;
                case '-2':
                    $paymentsfilter = ' AND pds.paydate IS NULL AND (pds.deadline - EXTRACT(EPOCH FROM NOW()) < 3*86400)';
                    break;
                case '-3':
                    $paymentsfilter = ' AND pds.paydate IS NULL AND (pds.deadline - EXTRACT(EPOCH FROM NOW()) < 7*86400)';
                    break;
                case '-4':
                    $paymentsfilter = ' AND pds.paydate IS NULL AND (pds.deadline - EXTRACT(EPOCH FROM NOW()) < 14*86400)';
                    break;
                case '-5':
                    $paymentsfilter = ' AND pds.paydate IS NULL AND (pds.deadline+86399 < EXTRACT(EPOCH FROM NOW()))';
                    break;
                case '-6':
                    $paymentsfilter = ' AND pds.paydate IS NULL AND pds.deadline = ' . strtotime("today", time());
                    break;
                case 'all':
                default:
                    $paymentsfilter = '';
            }
        }

        // Confirm filter
        $confirmfilter = '';
        if ($confirm !== null) {
            switch ($confirm) {
                case '1':
                    $confirmfilter = ' AND pds.confirmflag IS TRUE';
                    break;
                case '0':
                    $confirmfilter = ' AND pds.confirmflag IS FALSE';
                    break;
                default:
                    break;
            }
        }

        // Date filters
        $datefromfilter = $datefrom ? ' AND pds.sdate >= ' . intval($datefrom) : '';
        $datetofilter = $dateto ? ' AND pds.sdate <= ' . intval($dateto) : '';

        // Net/gross currency value filters in HAVING
        // Only concatenate one HAVING clause!
        $having = [];
        $netcurrencyvaluefrom = intval($netcurrencyvaluefrom);
        if ($netcurrencyvaluefrom) {
            $having[] = ($expences
                ? 'pdc.netcurrencyvalue >= ' . $netcurrencyvaluefrom
                : 'SUM(pdc.netcurrencyvalue) >= ' . $netcurrencyvaluefrom
            );
        }
        $netcurrencyvalueto = intval($netcurrencyvalueto);
        if ($netcurrencyvalueto) {
            $having[] = ($expences
                ? 'pdc.netcurrencyvalue <= ' . $netcurrencyvalueto
                : 'SUM(pdc.netcurrencyvalue) <= ' . $netcurrencyvalueto
            );
        }
        $grosscurrencyvaluefrom = intval($grosscurrencyvaluefrom);
        if ($grosscurrencyvaluefrom) {
            $having[] = ($expences
                ? 'pdc.grosscurrencyvalue >= ' . $grosscurrencyvaluefrom
                : 'SUM(pdc.grosscurrencyvalue) >= ' . $grosscurrencyvaluefrom
            );
        }
        $grosscurrencyvalueto = intval($grosscurrencyvalueto);
        if ($grosscurrencyvalueto) {
            $having[] = ($expences
                ? 'pdc.grosscurrencyvalue <= ' . $grosscurrencyvalueto
                : 'SUM(pdc.grosscurrencyvalue) <= ' . $grosscurrencyvalueto
            );
        }
        $havingClause = count($having) ? (' HAVING ' . implode(' AND ', $having)) : '';

        // Split/group fields based on expenses
        if (empty($expences)) {
            $split = ', SUM(pdc.netcurrencyvalue*pdc.amount) AS doc_netcurrencyvalue,
                SUM(pdc.grosscurrencyvalue-pdc.netcurrencyvalue) AS doc_vatcurrencyvalue,
                SUM(pdc.grosscurrencyvalue) AS doc_grosscurrencyvalue';
            $groupby = ' GROUP BY pt.name, vu.name, tx.value, tx.label, pds.id, dv.name, va.location';
        } else {
            $split = ', pdc.netcurrencyvalue*pdc.amount AS expence_netcurrencyvalue,
                pdc.grosscurrencyvalue-pdc.netcurrencyvalue AS vatcurrencyvalue,
                pdc.grosscurrencyvalue*pdc.amount AS expence_grosscurrencyvalue, pdc.description, pdc.id AS expenceid';
            $groupby = ' GROUP BY pds.id, pt.name, vu.name, tx.value, tx.label, pdc.description, pdc.id, dv.name, va.location';
        }

        // Compose the full SQL query (be explicit with table prefixes)
        $sql = '
            SELECT pds.id, pds.typeid, pt.name AS typename, pds.fullnumber, pds.currency, pds.vatplnvalue, pds.confirmflag::int,
                pds.iban, pds.cdate, pds.sdate, pds.deadline, pds.paytype, pds.paydate, COUNT(pdc.netcurrencyvalue) AS expencescount,
                pds.supplierid, pds.supplier_fullname, pds.supplier_ten, pds.userid, vu.name AS username, tx.value AS tax_value,
                tx.label AS tax_label, pds.preferred_splitpayment::int, dv.name AS division_name, va.location AS division_address
                ' . $split . '
            FROM pds
            LEFT JOIN pdcontents pdc ON (pdc.pdid = pds.id)
            LEFT JOIN pdcontentcat pdcc ON (pdcc.contentid = pdc.id)
            LEFT JOIN pdcontentinvprojects pdci ON (pdci.contentid = pdc.id)
            LEFT JOIN taxes tx ON (tx.id = pdc.taxid)
            LEFT JOIN pdtypes pt ON (pt.id = pds.typeid)
            LEFT JOIN vusers vu ON (vu.id = pds.userid)
            LEFT JOIN divisions dv ON (dv.id = pds.divisionid)
            LEFT JOIN vaddresses va ON (va.id = dv.address_id)
            LEFT JOIN pdattachments pda ON (pda.pdid = pds.id)
            WHERE 1=1
            ' . $divisionfilter
              . $supplierfilter
              . $docnumberfilter
              . $confirmfilter
              . $categoriesfilter
              . $invprojectsfilter
              . $expencedescriptionfilter
              . $paymentsfilter
              . $datefromfilter
              . $datetofilter
            . $groupby
            . $havingClause
            . $orderby;

        $result = $this->db->GetAll($sql);

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

            // Export logic
            if (!empty($params['export']) && $export_privileges) {
                $dst_charset = 'UTF-8';
                $exported = '';
                foreach ($result as $r) {
                    $grosscurrencyvalue = number_format((float)$r['doc_grosscurrencyvalue'], 2, ',', '');
                    $vatinplnvalue = number_format((float)$r['doc_vatcurrencyvalue'], 2, ',', '');
                    $splitPayment = self::splitPaymentCheck($r['preferred_splitpayment'], $r['doc_grosscurrencyvalue'], $r['currency']);
                    $title = $splitPayment
                        ? '/VAT/' . $vatinplnvalue
                            . '/IDC/' . $r['supplier_ten']
                            . '/INV/' . $r['fullnumber']
                            . '/TXT/' . $r['typename'] . '|ID:' . $r['id']
                        : $r['typename'] . '|' . $r['fullnumber'] . '|ID:' . $r['id'];

                    switch ($params['export']) {
                        case '1': // Bank spółdzielczy - przelew zwykły
                            $dst_charset = 'CP1252';
                            $exported .= $r['id'] . ';' . $src_iban . ';' . $r['supplier_fullname'] . ';;;;'
                                . $r['iban'] . ';' . ($grosscurrencyvalue * 100) . ';' . $title . ';;;' . date('Y-m-d') . PHP_EOL;
                            break;
                        case '2': // MT103
                            $sender = trim($r['division_name']) . '|' . trim($r['division_address']);
                            $receiver = trim($r['supplier_fullname']) . '|' . (isset($r['supplier_address']) ? trim($r['supplier_address']) : '');
                            $fields = array(
                                110,
                                date('Ymd'),
                                round(($r['doc_grosscurrencyvalue'] * 100), 2),
                                substr($src_iban, 2, 4),
                                0,
                                preg_replace('/[^0-9]/', '', $src_iban),
                                preg_replace('/[^0-9]/', '', $r['iban']),
                                $sender,
                                $receiver,
                                0,
                                substr($r['iban'], 2, 4),
                                $title,
                                null,
                                null,
                                51,
                                $splitPayment,
                            );
                            $exported .= self::array2csv(array($fields));
                            break;
                        default:
                            break;
                    }
                }
                header("Content-type: application/octet-stream");
                header('Content-Disposition: attachment; filename=' . $export_filename);
                header('Content-Type: text/csv');
                exit(iconv('UTF-8', $dst_charset, $exported));
            }
        }

        return $result;
    }

    ...
