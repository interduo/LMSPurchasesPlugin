<?php

!empty($_GET['id']) ? $id = intval($_GET['id']) : '';
!empty($_GET['attid']) ? $attid = intval($_GET['attid']) : '';

if (empty($attid) && empty($id)) {
    access_denied();
}

$PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();

if (!empty($id)) {
    $files = $PURCHASES->GetPurchaseFiles(array('pdid' => $id));
} else {
    $files = $PURCHASES->GetPurchaseFiles(array('attid' => $attid));
}

if (!empty($files)) {
    $firstfile = array_shift(array_values($files));
}

if (!empty($firstfile)) {
    $SESSION->redirect($firstfile['fullpath']);
} else {
    die("No attachment for this purchase. Please go back.");
}