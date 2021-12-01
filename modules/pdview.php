<?php

$id = intval($_GET['id']);

$PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();
$firstfile = array_shift(array_values($PURCHASES->GetPurchaseFiles($id)));

if (!empty($firstfile)) {
    $SESSION->redirect($firstfile['fullpath']);
}
