<?php
if (ConfigHelper::checkPrivilege('purchases') || ConfigHelper::checkPrivilege('superuser')) {
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
    if (empty($duplicate_found)) {
        print_r(json_encode(false));
    } else {
        print_r(json_encode($duplicate_found));
    }
    die();
}

$default_taxrate = ConfigHelper::getConfig('phpui.default_taxrate', '23.00');
$default_divisionid = ConfigHelper::getConfig('pd.default_divisionid');
$pagelimit = ConfigHelper::getConfig('pd.pagelimit', 50);
$force_global_division_context = ConfigHelper::getConfig('phpui.force_global_division_context', false);

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
if (!empty($_GET['datefrom'])) {
    $params['datefrom'] = intval(date_to_timestamp($_GET['datefrom']));
} else {
    $params['datefrom'] = null;
}

// dateto filter
if (!empty($_GET['dateto'])) {
    $params['dateto'] = intval(date_to_timestamp($_GET['dateto']));
} else {
    $params['dateto'] = null;
}

// net currency valuefrom filter
if (isset($_GET['netcurrencyvaluefrom'])) {
    if (empty($_GET['netcurrencyvaluefrom'])) {
        $params['netcurrencyvaluefrom'] = '';
    } else {
        if ($_GET['netcurrencyvaluefrom'] == 'all') {
            $params['netcurrencyvaluefrom'] = array();
        } else {
            $params['netcurrencyvaluefrom'] = intval($_GET['netcurrencyvaluefrom']);
        }
    }
} else {
    $params['netcurrencyvaluefrom'] = null;
}

// net currency valueto filter
if (isset($_GET['netcurrencyvalueto'])) {
    if (empty($_GET['netcurrencyvalueto'])) {
        $params['netcurrencyvalueto'] = '';
    } else {
        if ($_GET['netcurrencyvalueto'] == 'all') {
            $params['netcurrencyvalueto'] = null;
        } else {
            $params['netcurrencyvalueto'] = intval($_GET['netcurrencyvalueto']);
        }
    }
} else {
    $params['netcurrencyvalueto'] = null;
}

// gross currency valuefrom filter
if (isset($_GET['grosscurrencyvaluefrom'])) {
    if (empty($_GET['grosscurrencyvaluefrom'])) {
        $params['grosscurrencyvaluefrom'] = '';
    } else {
        if ($_GET['grosscurrencyvaluefrom'] == 'all') {
            $params['grosscurrencyvaluefrom'] = array();
        } else {
            $params['grosscurrencyvaluefrom'] = intval($_GET['grosscurrencyvaluefrom']);
        }
    }
} else {
    $params['grosscurrencyvaluefrom'] = null;
}

// gross currency valueto filter
if (isset($_GET['grosscurrencyvalueto'])) {
    if (empty($_GET['grosscurrencyvalueto'])) {
        $params['grosscurrencyvalueto'] = '';
    } else {
        if ($_GET['grosscurrencyvalueto'] == 'all') {
            $params['grosscurrencyvalueto'] = null;
        } else {
            $params['grosscurrencyvalueto'] = intval($_GET['grosscurrencyvalueto']);
        }
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
            $params['confirm'] = $_GET['confirm'];
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

    if (in_array('all', $_GET['catid'])) {
        $params['catids'] = null;
    } else {
        $params['catids'] = Utils::filterIntegers($_GET['catid']);
    }
}

// invproject filter
if (!empty($_GET['invprojectids'])) {
    if (!is_array($_GET['invprojectids'])) {
        $_GET['invprojectids'] = array($_GET['invprojectids']);
    }

    if (in_array('all', $_GET['invprojectids'])) {
        $params['invprojectids'] = null;
    } else {
        $params['invprojectids'] = Utils::filterIntegers($_GET['invprojectids']);
    }
}

// filters: expence description
if (!empty($_GET['description'])) {
    $params['description'] = htmlspecialchars($_GET['description']);
} else {
    $params['description'] = null;
}

// filters: expences or documents
if (!empty($_GET['expences'])) {
    $params['expences'] = intval($_GET['expences']);
}

if (isset($_GET['export'])) {
    $params['export'] = intval($_GET['export']);
}

$pdlist = $PURCHASES->GetPurchaseList($params);

if (!empty($_GET['action'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : '';
    $attid = isset($_GET['attid']) ? intval($_GET['attid']) : '';

    $action = $_GET['action'];
    switch ($action) {
        case 'add':
            if (!empty($addpd) && ConfigHelper::checkPrivilege('purchases_add_purchase')) {
                $PURCHASES->AddPurchase($addpd, $files);
            }
            break;
        case 'acceptfile':
            if (!empty($addpd) && ConfigHelper::checkPrivilege('purchases_add_purchase')) {
                $addedid = $PURCHASES->AddPurchase($addpd, $files);
                $PURCHASES->MovePurchaseFileFromAnteroom(array('attid' => $attid, 'pdid' => $addedid));
            }
            break;
        case 'modify':
            $pdinfo = $PURCHASES->GetPurchaseDocumentInfo($id);
            $SMARTY->assign('pdinfo', $pdinfo);
            if (isset($addpd) && ConfigHelper::checkPrivilege('purchases_modify_purchase')) {
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
        case 'markasconfirmed':
            if (!empty($id) && ConfigHelper::checkPrivilege('purchases_mark_purchase_as_confirmed')) {
                $PURCHASES->MarkAsConfirmed($id);
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

$SMARTY->assign('default_taxrate', $default_taxrate);
$SMARTY->assign('default_document_typeid', $PURCHASES->GetDefaultDocumentTypeid());

$SMARTY->assign('params', $params);
$SMARTY->assign('pdlist', $pdlist);
$SMARTY->assign('pagetitle', $layout['pagetitle']);
$SMARTY->assign('pagelimit', $pagelimit);

$SMARTY->display('pdlist.html');
