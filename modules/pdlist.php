<?php

check_file_uploads();
$PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();

$action = $_GET['action'];
$id = $_GET['id'];

if (isset($_POST['addpd'])) {
    $addpd = $_POST['addpd'];

    $result = handle_file_uploads('files', $error);
    extract($result);
    $SMARTY->assign('fileupload', $fileupload);

        $attachments = null;

        if (!empty($files)) {
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

// payments filter
if (empty($_GET['payments']) || $_GET['payments'] == 'all') {
    unset($params['payments']);
} else {
    $params['payments'] = intval($_GET['payments']);
}

// period filter
if (empty($_GET['period']) || $_GET['period'] == 'all') {
    unset($params['period']);
} else {
    $params['period'] = intval($_GET['period']);
}

// valuefrom filter
if (empty($_GET['valuefrom'])) {
    unset($params['valuefrom']);
} else {
    if ($_GET['valuefrom'] == 'all') {
        $params['valuefrom'] = array();
    } else {
        $params['valuefrom'] = intval($_GET['valuefrom']);
    }
}

// valueto filter
if (empty($_GET['valueto'])) {
    unset($params['valueto']);
} else {
    if ($_GET['valueto'] == 'all') {
        $params['valueto'] = null;
    } else {
        $params['valueto'] = intval($_GET['valueto']);
    }
}

$pdlist = $PURCHASES->GetPurchaseDocumentList($params);

switch ($action) {
    case 'add':
            $PURCHASES->AddPurchaseDocument($addpd, $files);
            $SESSION->redirect('?m=pdlist');
        break;
    case 'modify':
        $pdinfo = $PURCHASES->GetPurchaseDocumentInfo($id);
        $SMARTY->assign('pdinfo', $pdinfo);
        if (isset($addpd)) {
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
$SMARTY->assign('projectslist', $LMS->GetProjects());
$SMARTY->assign('typeslist', $PURCHASES->GetPurchaseDocumentTypesList());
$SMARTY->assign('action', $action);
$SMARTY->assign('params', $params);
$SMARTY->assign('pdlist', $pdlist);
$SMARTY->assign('pagetitle', $layout['pagetitle']);
$SMARTY->display('pdlist.html');
