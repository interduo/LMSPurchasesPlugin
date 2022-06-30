<?php

if (ConfigHelper::checkPrivilege('purchases')) {
    $PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();
} else {
    access_denied();
}

check_file_uploads();

$SMARTY->assign(
    'allowed_mime_types',
    ConfigHelper::getConfig('phpui.allowed_mime_types', 'application/pdf')
);

if (isset($_GET['ajax']) && isset($_GET['fileupload'])) {
    $result = handle_file_uploads('files', $error);

    extract($result);
    $SMARTY->assign('files', $files);

    $attachments = null;

    //TODO: sprawdz czy są duplikaty w poczekalni - jeśli tak wyrzuć błąd lub zmien nazwe pliku,

    if (!empty($files)) {
        $SMARTY->assign('files', $files);
        foreach ($files as &$file) {
            $file['fullpath'] = $_POST['fileupload']['files-tmpdir'] . DIRECTORY_SEPARATOR . $file['name'];
            $attachments[] = array(
                'content_type' => $file['type'],
                'filename' => $file['name'],
                'data' => file_get_contents($file['fullpath']),
            );
        }
        unset($file);
    }
}

if (isset($_POST['pduploads'])) {
    $tmp_dir = sys_get_temp_dir() ?? '/tmp';
    foreach ($_POST['fileupload']['files'] as &$file) {
        $file['fullpath'] = $tmp_dir . DIRECTORY_SEPARATOR . $_POST['fileupload']['files-tmpdir'] . DIRECTORY_SEPARATOR . $file['name'];
    }
    $params = array(
        'pdid' => null,
        'files' => $_POST['fileupload']['files'],
        'anteroom' => true,
        'cleanup' => false,
    );
    $PURCHASES->AddPurchaseFiles($params);
    $SESSION->redirect('?m=pdlist');
} else {
    $SMARTY->assign('pagetitle', $layout['pagetitle']);
    $SMARTY->display('pduploads.html');
}
