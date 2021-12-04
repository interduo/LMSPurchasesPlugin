<?php

$id = intval($_GET['id']);

if (!empty($id)) {
    $PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();
    $files = $PURCHASES->GetPurchaseFiles($id);

    if (!empty($files)) {
        $firstfile = array_shift(array_values($files));
    }

    if (!empty($firstfile)) {
        $SESSION->redirect($firstfile['fullpath']);
    } else {
        die("No attachment for this purchase. Please go back.");
    }
}
