<?php

$PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();

$action = $_GET['action'];
$id = $_GET['id'];

$addpd = $_POST['addpd'];
$layout['pagetitle'] = trans('Purchase document list');

// payments filter
if (empty($_GET['payments'])) {
    unset($params['payments']);
} else {
    if ($_GET['payments'] == 'all') {
        $params['payments'] = array();
    } else {
        $params['payments'] = intval($_GET['payments']);
    }
}

// period filter
if (empty($_GET['period'])) {
    unset($params['period']);
} else {
    if ($_GET['period'] == 'all') {
        $params['period'] = array();
    } else {
        $params['period'] = intval($_GET['period']);
    }
}

$pdlist = $PURCHASES->GetPurchaseDocumentList($params);

switch ($action) {
    case 'add':
            $PURCHASES->AddPurchaseDocument($addpd);
            $SESSION->redirect('?m=pdlist');
        break;
    case 'modify':
        $pdinfo = $PURCHASES->GetPurchaseDocumentInfo($id);
        $SMARTY->assign('pdinfo', $pdinfo);
        if (isset($pdinfo)) {
            $addpd['id'] = $id;
            $PURCHASES->UpdatePurchaseDocument($addpd);
            $SESSION->redirect('?m=pdlist');
        }
        break;
    case 'delete':
        if (!empty($id)) {
            $PURCHASES->DeletePurchaseDocument($id);
            $SESSION->redirect('?m=pdlist');
        }
        break;
    case 'markaspaid':
        if (!empty($id)) {
            $PURCHASES->MarkAsPaid($id);
            $SESSION->redirect('?m=pdlist');
        }
        break;
    default:
        break;
}

$SMARTY->assign('supplierslist', $PURCHASES->GetSuppliers());
$SMARTY->assign('typeslist', $PURCHASES->GetPurchaseDocumentTypesList());
$SMARTY->assign('action', $action);
$SMARTY->assign('params', $params);
$SMARTY->assign('pdlist', $pdlist);
$SMARTY->assign('pagetitle', $layout['pagetitle']);
$SMARTY->display('pdlist.html');
