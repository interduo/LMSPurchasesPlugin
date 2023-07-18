<?php

/*
 * LMS version 1.11-git
 *
 *  (C) Copyright 2001-2020 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

$PURCHASES = LMSPurchasesPlugin::getPurchasesInstance();

if (!empty($_GET['catid'])) {
    print_r(json_encode($PURCHASES->getPurchaseCategoryInfo(intval($_GET['catid']))));
    die();
}

$action = $_GET['action'];
$id = $_GET['id'];

$addpdc = $_POST['addpdc'];
$layout['pagetitle'] = trans('Purchase categories');

$params['orderby'] = $_GET['orderby'];

$pdclist = $PURCHASES->getPurchaseCategoryList($params);

switch ($action) {
    case 'add':
        $PURCHASES->addPurchaseCategory($addpdc);
        $SESSION->redirect('?m=pdcategorylist');
        break;
    case 'modify':
        $pdcinfo = $PURCHASES->getPurchaseCategoryInfo($id);
        $SMARTY->assign('pdcinfo', $pdcinfo);
        if (isset($pdcinfo)) {
            $addpdc['id'] = $id;
            $PURCHASES->updatePurchaseCategory($addpdc);
            $SESSION->redirect('?m=pdcategorylist');
        }
        break;
    case 'delete':
        if (!empty($id)) {
            $PURCHASES->deletePurchaseCategory($id);
            $SESSION->redirect('?m=pdcategorylist');
        }
        break;
    default:
        break;
}

$SMARTY->assign(array(
        'action' => $action,
        'pdclist' => $pdclist,
        'pagetitle' => $layout['pagetitle'],
        'pluginusers' => $LMS->GetUserList(array('short' => true)),
    )
);

$SMARTY->display('pdcategorylist.html');
