<?php

$id = intval($_GET['id']) ?? '';
$attid = intval($_GET['attid']) ?? '';

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
    if (!empty($attid)) {
        $content = file_get_contents($firstfile['fullpath']);
        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($content));
        header('Content-Disposition: inline; filename=' . $firstfile['name'] . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        ini_set('zlib.output_compression', '0');
        die($content);
    } else {
        $SESSION->redirect($firstfile['fullpath']);
    }
} else {
    die("No attachment for this purchase. Please go back.");
}
