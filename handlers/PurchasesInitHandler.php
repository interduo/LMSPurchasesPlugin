<?php

/**
 * InitHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class PurchasesInitHandler {
    /**
     * Sets plugin Smarty templates directory
     * 
     * @param Smarty $hook_data Hook data
     * @return \Smarty Hook data
     */
	public function smartyInit(Smarty $hook_data) {
		$template_dirs = $hook_data->getTemplateDir();
		$plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSPurchasesPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'templates';
		array_unshift($template_dirs, $plugin_templates);
		$hook_data->setTemplateDir($template_dirs);
		return $hook_data;
	}

    /**
     * Sets plugin modules directory
     * 
     * @param array $hook_data Hook data
     * @return array Hook data
     */
	public function ModulesDirInit(array $hook_data = array()) {
		$plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSPurchasesPlugin::plugin_directory_name . DIRECTORY_SEPARATOR . 'modules';
		array_unshift($hook_data, $plugin_modules);
		return $hook_data;
	}

    /**
     * Sets plugin menu entries
     *.
     * @param array $hook_data Hook data
     * @return array Hook data
     */
	public function menuInit(array $hook_data = array()) {
		$hook_data['finances']['submenu'][] = array(
			'name' => trans('Purchase document list'),
			'link' => '?m=pdlist',
			'tip' => trans('Purchase document list'),
			'prio' => 180,
		);
		$hook_data['finances']['submenu'][] = array(
			'name' => trans('Purchase document types'),
			'link' => '?m=pdtlist',
			'tip' => trans('Purchase document types'),
			'prio' => 181,
		);
	return $hook_data;
	}


    /**
     * Modifies access table
     * 
     */
	public function accessTableInit() {
		$access = AccessRights::getInstance();

		$access->insertPermission(new Permission('Purchases', trans('Purchases'), '^Purchases$'),
			AccessRights::FIRST_FORBIDDEN_PERMISSION);
	}
}

?>
