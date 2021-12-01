<?php

$id = intval($_GET['id']);

$PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();
$firstfile = array_shift(array_values($PURCHASES->GetPurchaseDocumentFiles($id)));

if (!empty($firstfile)) {
    $SESSION->redirect($firstfile['fullpath']);
}

?>
