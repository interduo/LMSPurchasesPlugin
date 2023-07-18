<?php
if (ConfigHelper::checkPrivilege('purchases')) {
    $PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();
} else {
    access_denied();
}

if (isset($_GET['documentexist'])) {
    $supplierid = intval($_POST['supplierid']);
    $fullnumber = htmlspecialchars($_POST['fullnumber']);

    if (empty($supplierid) || empty($fullnumber)) {
        die();
    }

    $duplicate_found = $PURCHASES->documentExist($supplierid, $fullnumber);
    print_r(json_encode((empty($duplicate_found)) ? false : $duplicate_found));
    die();
}

$default_taxrate = ConfigHelper::getConfig('phpui.default_taxrate', '23.00');
$default_divisionid = ConfigHelper::getConfig('pd.default_divisionid');
$default_period_filter = ConfigHelper::getConfig('pd.default_period_filter', false);
$pagelimit = ConfigHelper::getConfig('pd.pagelimit', 50);
$force_global_division_context = ConfigHelper::getConfig('phpui.force_global_division_context', false);

check_file_uploads();

if (!empty($_GET['pdid'])) {
    print_r(json_encode($PURCHASES->getPurchaseDocumentInfo(intval($_GET['pdid']))));
    die();
}

if (!empty($_GET['get_customer_ten'])) {
    print_r(json_encode($PURCHASES->getCustomerTen(intval($_GET['get_customer_ten']))));
    die();
}

if (isset($_POST['addpd'])) {
    $addpd = $_POST['addpd'];

    $addpd['preferred_splitpayment'] = ($_POST['addpd']['preferred_splitpayment'] != 'on'
        || empty($_POST['addpd']['preferred_splitpayment'])) ? 0 : 1;

    $result = handle_file_uploads('files', $error);

    extract($result);

    $attachments = null;

    if (!empty($files)) {
        $SMARTY->assign('files', $files);
        foreach ($files as &$file) {
            $attachments[] = array(
                'content_type' => $file['type'],
                'filename' => $file['name'],
                'data' => file_get_contents($tmppath . DIRECTORY_SEPARATOR . $file['name']),
            );
            $file['fullpath'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
        }
        unset($file);
    }
}

$layout['pagetitle'] = trans('Purchase document list');

$params = array();

// division filter
if ($force_global_division_context) {
    $params['divisionid'] = $layout['division'];
} else {
    if (!empty($_GET['divisionid'])) {
        if ($_GET['divisionid'] == 'all') {
            unset($params['divisionid']);
        } else {
            $params['divisionid'] = intval($_GET['divisionid']);
        }
    } else {
        $params['divisionid'] = null;
    }
}

// supplier filter
if (!empty($_GET['supplier'])) {
    if ($_GET['supplier'] == 'all') {
        unset($params['supplier']);
    } else {
        $params['supplier'] = intval($_GET['supplier']);
    }
} else {
    $params['supplier'] = null;
}

// payments filter
if (!empty($_GET['payments'])) {
    if ($_GET['payments'] == 'all') {
        unset($params['payments']);
    } else {
        $params['payments'] = intval($_GET['payments']);
    }
} else {
    $params['payments'] = null;
}

// datefrom filter
$params['datefrom'] = empty($_GET['datefrom']) ? null : intval(date_to_timestamp($_GET['datefrom']));

// dateto filter
$params['dateto'] = empty($_GET['dateto']) ? null : intval(date_to_timestamp($_GET['dateto']));

//default period filter set
if (empty($params['datefrom']) && empty($params['dateto']) && !empty($default_period_filter)) {
    switch ($default_period_filter) {
        case 6:
            $params['datefrom'] = strtotime('first day of january this year');
            $params['dateto'] = strtotime('first day of january next year')-1;
            break;
        case 3:
            $params['datefrom'] = strtotime('first day of this month');
            $params['dateto'] = strtotime('first day of next month')-1;
            break;
        default:
            break;
    }
}

// net currency valuefrom filter
if (isset($_GET['netcurrencyvaluefrom'])) {
    if (empty($_GET['netcurrencyvaluefrom'])) {
        $params['netcurrencyvaluefrom'] = '';
    } else {
        $params['netcurrencyvaluefrom'] = ($_GET['netcurrencyvaluefrom'] == 'all') ? array() : intval($_GET['netcurrencyvaluefrom']);
    }
} else {
    $params['netcurrencyvaluefrom'] = null;
}

// net currency valueto filter
if (isset($_GET['netcurrencyvalueto'])) {
    if (empty($_GET['netcurrencyvalueto'])) {
        $params['netcurrencyvalueto'] = '';
    } else {
        $params['netcurrencyvalueto'] = ($_GET['netcurrencyvalueto'] == 'all') ? null : intval($_GET['netcurrencyvalueto']);
    }
} else {
    $params['netcurrencyvalueto'] = null;
}

// gross currency valuefrom filter
if (isset($_GET['grosscurrencyvaluefrom'])) {
    if (empty($_GET['grosscurrencyvaluefrom'])) {
        $params['grosscurrencyvaluefrom'] = '';
    } else {
        $params['grosscurrencyvaluefrom'] = ($_GET['grosscurrencyvaluefrom'] == 'all') ? array() : intval($_GET['grosscurrencyvaluefrom']);
    }
} else {
    $params['grosscurrencyvaluefrom'] = null;
}

// gross currency valueto filter
if (isset($_GET['grosscurrencyvalueto'])) {
    if (empty($_GET['grosscurrencyvalueto'])) {
        $params['grosscurrencyvalueto'] = '';
    } else {
        $params['grosscurrencyvalueto'] = ($_GET['grosscurrencyvalueto'] == 'all') ? null : intval($_GET['grosscurrencyvalueto']);
    }
} else {
    $params['grosscurrencyvalueto'] = null;
}

// document number filter
if (!empty($_GET['docnumber'])) {
    $params['docnumber'] = htmlspecialchars($_GET['docnumber']);
}

// confirmation flag filter
if (isset($_GET['confirm'])) {
    switch ($_GET['confirm']) {
        case "0":
        case "1":
            $params['confirm'] = intval($_GET['confirm']);
            break;
        default:
            break;
    }
} else {
    $params['confirm'] = null;
}

// categories filter
if (!empty($_GET['catid'])) {
    if (!is_array($_GET['catid'])) {
        $_GET['catid'] = array($_GET['catid']);
    }

    $params['catids'] = (in_array('all', $_GET['catid'])) ? null : Utils::filterIntegers($_GET['catid']);
}

// invproject filter
if (!empty($_GET['invprojectids'])) {
    if (!is_array($_GET['invprojectids'])) {
        $_GET['invprojectids'] = array($_GET['invprojectids']);
    }

    $params['invprojectids'] = (in_array('all', $_GET['invprojectids'])) ? null : Utils::filterIntegers($_GET['invprojectids']);
}

// filters: expence description
$params['description'] = empty($_GET['description'])) ? null : htmlspecialchars($_GET['description']);

// filters: expences or documents
if (!empty($_GET['expences'])) {
    $params['expences'] = intval($_GET['expences']);
}

if (isset($_GET['export'])) {
    $params['export'] = intval($_GET['export']);
}

$pdlist = $PURCHASES->getPurchaseList($params);

if (!empty($_GET['action'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : '';
    $attid = isset($_GET['attid']) ? intval($_GET['attid']) : '';

    $action = $_GET['action'];
    switch ($action) {
        case 'add':
            if (!empty($addpd) && ConfigHelper::checkPrivilege('purchases_add_purchase')) {
                $PURCHASES->addPurchase($addpd, $files);
            }
            break;
        case 'acceptfile':
            if (!empty($addpd) && ConfigHelper::checkPrivilege('purchases_add_purchase')) {
                $addedid = $PURCHASES->addPurchase($addpd, $files);
                $PURCHASES->movePurchaseFileFromAnteroom(array('attid' => $attid, 'pdid' => $addedid));
            }
            break;
        case 'modify':
            $pdinfo = $PURCHASES->getPurchaseDocumentInfo($id);
            $SMARTY->assign('pdinfo', $pdinfo);
            if (isset($addpd) && ConfigHelper::checkPrivilege('purchases_modify_purchase')) {
                $addpd['id'] = $id;
                $PURCHASES->updatePurchaseDocument($addpd);
            }
            break;
        case 'delete':
            if (!empty($id) && ConfigHelper::checkPrivilege('purchases_delete_purchase')) {
                $PURCHASES->deletePurchaseDocument($id);
            }
            break;
        case 'delete-attachment':
            if (!empty($attid) && ConfigHelper::checkPrivilege('purchases_delete_purchase')) {
                $PURCHASES->deleteAttachementFile($attid);
            }
            break;
        case 'markAsPaid':
            if (!empty($id) && ConfigHelper::checkPrivilege('purchases_mark_purchase_as_paid')) {
                $PURCHASES->markAsPaid($id);
            }
            break;
        case 'markasconfirmed':
            if (!empty($id) && ConfigHelper::checkPrivilege('purchases_mark_purchase_as_confirmed')) {
                $PURCHASES->setConfirmationFlag($id, true);
            }
            break;
        default:
            break;
    }
    if (!empty($id) || !empty($attid) || !empty($addpd)) {
        $SESSION->redirect_to_history_entry('?m=pdlist');
    }
    $SMARTY->assign('action', $action);
}

if (!empty($_GET['attid'])) {
    $SMARTY->assign('attid', intval($_GET['attid']));
}

$SMARTY->assign(
    array(
        'anteroom' => $PURCHASES->getPurchaseFiles(array('anteroom' => true)),
        'supplierslist' => $PURCHASES->getSuppliers(),
        'projectslist' => $LMS->GetProjects(),
        'typeslist' => $PURCHASES->getPurchaseDocumentTypesList(),
        'categorylist' => $PURCHASES->getPurchaseCategoryList(),
        'taxrates' => $LMS->GetTaxes(),
        'default_taxrate' => $default_taxrate,
        'default_document_typeid' => $PURCHASES->getDefaultDocumentTypeid(),
        'params' => $params,
        'pdlist' => $pdlist,
        'pagetitle' => $layout['pagetitle'],
        'pagelimit' => $pagelimit,
    )
);

$SESSION->add_history_entry();
$SMARTY->display('pdlist.html');
