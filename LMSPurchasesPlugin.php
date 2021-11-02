<?php

/**
 * LMSPurchasesPlugin
 *
 * @author Jarosław Kłopotek <jkl@interduo.pl>
 * @author Grzegorz Cichowski <gc@ptlanet.pl>
 */

class LMSPurchasesPlugin extends LMSPlugin {
	const plugin_directory_name = 'LMSPurchasesPlugin';
	const PLUGIN_DBVERSION = '2021103100';
	const PLUGIN_NAME = 'LMSPurchases';
	const PLUGIN_DESCRIPTION = 'Purchases Plugin';
	const PLUGIN_AUTHOR = 'Jarosław Kłopotek &lt;jkl@interduo.pl&gt;';

	private static $purchases = null;

    public static function getPurchasesInstance()
    {
        if (empty(self::$purchases)) {
            self::$purchases = new PURCHASES();
        }
        return self::$purchases;
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
