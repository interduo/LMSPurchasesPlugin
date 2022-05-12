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

$action = $_GET['action'];
$id = intval($_GET['id']);

$addpdt = $_POST['addpdt'];
$layout['pagetitle'] = trans('Purchase document types');

$params['orderby'] = $_GET['orderby'];

$pdtlist = $PURCHASES->GetPurchaseDocumentTypesList($params);

switch ($action) {
    case 'add':
            $PURCHASES->AddPurchaseDocumentType($addpdt);
            $SESSION->redirect('?m=pdtlist');
        break;
    case 'modify':
        $pdtinfo = $PURCHASES->GetPurchaseTypeInfo($id);
        $SMARTY->assign('pdtinfo', $pdtinfo);
        if (isset($pdtinfo)) {
            $addpdt['id'] = $id;
            $PURCHASES->UpdatePurchaseDocumentType($addpdt);
            $SESSION->redirect('?m=pdtlist');
        }
        break;
    case 'delete':
        if (!empty($id)) {
            $PURCHASES->DeletePurchaseDocumentType($id);
            $SESSION->redirect('?m=pdtlist');
        }
        break;
    default:
        break;
}

$SMARTY->assign('action', $action);
$SMARTY->assign('pdtlist', $pdtlist);
$SMARTY->assign('pagetitle', $layout['pagetitle']);
$SMARTY->display('pdtlist.html');
