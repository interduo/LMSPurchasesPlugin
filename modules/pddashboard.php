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
$PD_STATS = LMSPurchasesPlugin::getPurchasesStats();

$layout['pagetitle'] = trans('Finances dashboard');

$date['date'] = date("Y-m-d");
$date['day'] = strftime("%A");

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

if (ConfigHelper::checkConfig('privileges.superuser') || !ConfigHelper::checkConfig('privileges.hide_sysinfo')) {
    $SI = new Sysinfo;
    $SMARTY->assign('pdstats', $PD_STATS->PDStats());
}

$SMARTY->assign('IncomePerMonth', $PD_STATS->IncomePerMonth(date("Y")));
$SMARTY->assign('SalePerMonth', $PD_STATS->SalePerMonth(date("Y")));
$SMARTY->assign('date', $date);
$SMARTY->assign('supplierslist', $PURCHASES->GetSuppliers());
$SMARTY->assign('pagetitle', $layout['pagetitle']);
$SMARTY->assign('dashboard_sortable_order', json_encode($SESSION->get_persistent_setting('dashboard-sortable-order')));
$SMARTY->display('dashboard/pddashboard.html');
