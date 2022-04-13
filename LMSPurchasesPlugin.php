<?php

/**
 * LMSPurchasesPlugin
 *
 * @author Jarosław Kłopotek <jkl@interduo.pl>
 * @author Grzegorz Cichowski <gc@ptlanet.pl>
 */

class LMSPurchasesPlugin extends LMSPlugin
{
    const PLUGIN_DIRECTORY_NAME = 'LMSPurchasesPlugin';
    const PLUGIN_DB_VERSION = '2022040100';
    const PLUGIN_SOFTWARE_VERSION = '1.0-beta';
    const PLUGIN_NAME = 'Purchase Documents';
    const PLUGIN_DESCRIPTION = 'Ewidencja dokumentów kosztowych.';
    const PLUGIN_AUTHOR = 'Jarosław Kłopotek &lt;jkl@interduo.pl&gt;<br>Grzegorz Cichowski &lt;gc@ptlanet.pl&gt;';
    const PLUGIN_DOC_URL = 'https://raw.githubusercontent.com/interduo/LMSPurchasesPlugin/main/README.md';
    const PLUGIN_REPO_URL = 'https://github.com/interduo/LMSPurchasesPlugin';

    private static $purchases = null;

    public static function getPurchasesInstance()
    {
        if (empty(self::$purchases)) {
            self::$purchases = new PURCHASES();
        }
        return self::$purchases;
    }

    public function registerHandlers()
    {
        $this->handlers = array(
            'smarty_initialized' => array(
                'class' => 'PurchasesInitHandler',
                'method' => 'smartyInit'
            ),
            'modules_dir_initialized' => array(
                'class' => 'PurchasesInitHandler',
                'method' => 'ModulesDirInit',
            ),
            'menu_initialized' => array(
                'class' => 'PurchasesInitHandler',
                'method' => 'menuInit',
            ),
            'access_table_initialized' => array(
                'class' => 'PurchasesInitHandler',
                'method' => 'accessTableInit'
            ),
        );
    }
}
