<?php

/**
 * LMSPurchasesPlugin
 *
 * @author Jarosław Kłopotek <jkl@interduo.pl>
 * @author Grzegorz Cichowski <gc@ptlanet.pl>
 */

class LMSPurchasesPlugin extends LMSPlugin {
	const plugin_directory_name = 'LMSPurchasesPlugin';
	const PLUGIN_DBVERSION = '2021111601';
	const PLUGIN_NAME = 'LMSPurchasesPlugin';
	const PLUGIN_DESCRIPTION = 'Wtyczka służąca do ewidencji dokumentów kosztowych.';
	const PLUGIN_AUTHOR = 'Jarosław Kłopotek &lt;jkl@interduo.pl&gt;';

	private static $purchases = null;
	private static $pd_stats = null;

    public static function getPurchasesInstance()
    {
        if (empty(self::$purchases)) {
            self::$purchases = new PURCHASES();
        }
        return self::$purchases;
    }
    public static function getPurchasesStats()
    {
        if (empty(self::$pd_stats)) {
            self::$pd_stats = new PD_STATS();
        }
        return self::$pd_stats;
    }

	public function registerHandlers() {
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

?>
