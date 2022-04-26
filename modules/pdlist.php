<?php
if (ConfigHelper::checkPrivilege('purchases') || ConfigHelper::checkPrivilege('superuser')) {
    $PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();
} else {
    access_denied();
}

$default_taxid = ConfigHelper::getConfig('pd.default_taxid');
$default_divisionid = ConfigHelper::getConfig('pd.default_divisionid');
$default_period_filter = ConfigHelper::getConfig('pd.filter_default_period', 6);
$pagelimit = ConfigHelper::getConfig('pd.pagelimit', 50);

check_file_uploads();

if (!empty($_GET['pdid'])) {
    $pdid = intval($_GET['pdid']);
    print_r(json_encode($PURCHASES->GetPurchaseDocumentInfo($pdid)));
    die();
}

if (!empty($_GET['get_customer_ten'])) {
    $customerid = intval($_GET['get_customer_ten']);
    print_r(json_encode($PURCHASES->GetCustomerTen($customerid)));
    die();
}

if (isset($_POST['addpd'])) {
    $addpd = $_POST['addpd'];

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
            $file['name'] = $tmppath . DIRECTORY_SEPARATOR . $file['name'];
        }
        unset($file);
    }
}

$layout['pagetitle'] = trans('Purchase document list');

$params = array();
// division filter
if (!empty($_GET['divisionid'])) {
    if ($_GET['divisionid'] == 'all') {
        unset($params['divisionid']);
    } else {
        $params['divisionid'] = intval($_GET['divisionid']);
    }
} else {
    $params['divisionid'] = null;
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

// period filter
if (!empty($_GET['period'])) {
    if ($_GET['period'] == 'all') {
        unset($params['period']);
    } else {
        $params['period'] = intval($_GET['period']);
    }
} else {
    $params['period'] = $default_period_filter;
}

// valuefrom filter
if (isset($_GET['valuefrom'])) {
    if (empty($_GET['valuefrom'])) {
        $params['valuefrom'] = '';
    } else {
        if ($_GET['valuefrom'] == 'all') {
            $params['valuefrom'] = array();
        } else {
            $params['valuefrom'] = intval($_GET['valuefrom']);
        }
    }
} else {
    $params['valuefrom'] = null;
}

// valueto filter
if (isset($_GET['valueto'])) {
    if (empty($_GET['valueto'])) {
        $params['valueto'] = '';
    } else {
        if ($_GET['valueto'] == 'all') {
            $params['valueto'] = null;
        } else {
            $params['valueto'] = intval($_GET['valueto']);
        }
    }
} else {
    $params['valueto'] = null;
}

// document number filter
if (!empty($_GET['docnumber'])) {
    $params['docnumber'] = htmlspecialchars($_GET['docnumber']);
}

// categories filter
if (!empty($_GET['catid'])) {
    if (!is_array($_GET['catid'])) {
        $_GET['catid'] = array($_GET['catid']);
    }

    if (in_array('all', $_GET['catid'])) {
        $params['catids'] = null;
    } else {
        $params['catids'] = Utils::filterIntegers($_GET['catid']);
    }
}

// filters: expence description
if (!empty($_GET['description'])) {
    $params['description'] = htmlspecialchars($_GET['description']);
} else {
    $params['description'] = null;
}

// filters: expences or documents
if (isset($_GET['expences'])) {
    $params['expences'] = intval($_GET['expences']);
}

if (isset($_GET['export'])) {
    $params['export'] = intval($_GET['export']);
}

$pdlist = $PURCHASES->GetPurchaseList($params);

if (!isset($pdinfo['taxid'])) {
    $pdinfo['taxid'] = $default_taxid;
}

if (!empty($_GET['action'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : '';
    $attid = isset($_GET['attid']) ? intval($_GET['attid']) : '';

    $action = $_GET['action'];
    switch ($action) {
        case 'add':
            if (!empty($addpd) && ConfigHelper::checkPrivilege('purchase_add_purchase')) {
                $PURCHASES->AddPurchase($addpd, $files);
                $docid = $DB->GetLastInsertID('pds');
                if (!empty($attid)) {
                    $argv = array(
                        'attid' => $attid,
                        'pdid' => $docid
                    );
                    $PURCHASES->MovePurchaseFileFromAnteroom($argv);
                }
            }
            break;
        case 'modify':
            $pdinfo = $PURCHASES->GetPurchaseDocumentInfo($id);
            $SMARTY->assign('pdinfo', $pdinfo);
            if (isset($addpd) && ConfigHelper::checkPrivilege('purchase_modify_purchase')) {
                $addpd['id'] = $id;
                $PURCHASES->UpdatePurchaseDocument($addpd);
            }
            break;
        case 'delete':
            if (!empty($id) && ConfigHelper::checkPrivilege('purchases_delete_purchase')) {
                $PURCHASES->DeletePurchaseDocument($id);
            }
            break;
        case 'delete-attachment':
            if (!empty($attid) && ConfigHelper::checkPrivilege('purchases_delete_purchase')) {
                $PURCHASES->DeleteAttachementFile($attid);
            }
            break;
        case 'markaspaid':
            if (!empty($id) && ConfigHelper::checkPrivilege('purchases_mark_purchase_as_paid')) {
                $PURCHASES->MarkAsPaid($id);
            }
            break;
        default:
            break;
    }
    if (!empty($id) || !empty($attid) || !empty($addpd)) {
        $SESSION->redirect('?m=pdlist');
    }
    $SMARTY->assign('action', $action);
}

$SMARTY->assign('anteroom', $PURCHASES->GetPurchaseFiles(array('anteroom' => true)));
if (!empty($_GET['attid'])) {
    $SMARTY->assign('attid', intval($_GET['attid']));
}
$SMARTY->assign('supplierslist', $PURCHASES->GetSuppliers());
$SMARTY->assign('projectslist', $LMS->GetProjects());
$SMARTY->assign('typeslist', $PURCHASES->GetPurchaseDocumentTypesList());
$SMARTY->assign('categorylist', $PURCHASES->GetPurchaseCategoryList());
$SMARTY->assign('taxrates', $LMS->GetTaxes());
$SMARTY->assign('default_taxid', $default_taxid);
$SMARTY->assign('default_document_typeid', $PURCHASES->GetDefaultDocumentTypeid());

$SMARTY->assign('params', $params);
$SMARTY->assign('pdlist', $pdlist);
$SMARTY->assign('pagetitle', $layout['pagetitle']);
$SMARTY->assign('pagelimit', $pagelimit);

$SMARTY->display('pdlist.html');
