<?php

/**
 * InitHandler
 *
 * @author Tomasz Chiliński <tomasz.chilinski@chilan.com>
 */
class PurchasesInitHandler
{
    /**
     * Sets plugin Smarty templates directory
     *
     * @param Smarty $hook_data Hook data
     * @return \Smarty Hook data
     */
    public function smartyInit(Smarty $hook_data)
    {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSPurchasesPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'templates';
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
    public function ModulesDirInit(array $hook_data = array())
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSPurchasesPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }

    /**
     * Sets plugin menu entries
     *.
     * @param array $hook_data Hook data
     * @return array Hook data
     */
    public function menuInit(array $hook_data = array())
    {
        $menu_pd = array(
            'purchases' => array(
                'name' => trans('Purchases'),
                'css' => 'lms-ui-icon-stats',
                'link' => '?m=pddashboard',
                'tip' => trans('Purchase Documents'),
                'accesskey' => 'p',
                'submenu' => array(
                    /*
                    array(
                        'name' => trans('Dashboard'),
                        'link' => '?m=pddashboard',
                        'tip' => trans('Purchase documents dashboard'),
                        'prio' => 180,
                    ),
                    */
                    'pdlist' => array(
                        'name' => trans('Purchase document list'),
                        'link' => '?m=pdlist',
                        'tip' => trans('Purchase document list'),
                        'prio' => 10,
                    ),
                    'pduploads' => array(
                        'name' => trans('Document uploads'),
                        'link' => '?m=pduploads',
                        'tip' => trans('Document uploads'),
                        'prio' => 20,
                        'key' => 'pduploads',
                    ),
                    'pdtlist' => array(
                        'name' => trans('Purchase document types'),
                        'link' => '?m=pdtlist',
                        'tip' => trans('Purchase document types'),
                        'prio' => 30,
                        'key' => 'pdtlist',
                    ),
                    'pdcategorylist' => array(
                        'name' => trans('Purchase categories'),
                        'link' => '?m=pdcategorylist',
                        'tip' => trans('Purchase categories'),
                        'prio' => 40,
                        'key' => 'pdcategorylist',
                    ),
                ),
            ),
        );

        $menu_keys = array_keys($hook_data);
        $i = array_search('documentation', $menu_keys);

        return $hook_data = array_merge(
            array_slice($hook_data, 0, $i, true),
            $menu_pd,
            array_slice($hook_data, $i, null, true)
        );
    }

    /**
     * Modifies access table
     *
     */
    public function accessTableInit()
    {
        $access = AccessRights::getInstance();

        $permission = new Permission(
            'purchases',
            '(PURCHASES) Ewidencja dokumentów kosztowych',
            '^pd(list|uploads|view).*$',
            null,
            array('purchases' => array('pdlist', 'pduploads'))
        );

        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'purchases_mark_purchase_as_paid',
            '(PURCHASES) Oznaczanie dokumentów jako zapłacone',
        );

        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'purchases_delete_purchase',
            '(PURCHASES) Kasowanie dokumentów kosztowych',
        );

        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'purchase_modify_purchase',
            '(PURCHASES) Edycja zakupów/wydatków/dokumentów kosztowych',
        );

        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'purchase_add_purchase',
            '(PURCHASES) Księgowanie zakupów/wydatków/dokumentów kosztowych',
        );

        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'purchase_export_purchases',
            '(PURCHASES) Export zakupów CSV',
        );

        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'purchase_management_types',
            '(PURCHASES) Zarządzanie typami dokumentów kosztowych',
            '^pdtlist.*$',
            null,
            array('purchases' => array('pdtlist'))
        );

        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);

        $permission = new Permission(
            'purchase_management_categories',
            '(PURCHASES) Zarządzanie kategoriami wydatków',
            '^pdclist.*$',
            null,
            array('purchases' => array('pdcategorylist'))
        );

        $access->insertPermission($permission, AccessRights::FIRST_FORBIDDEN_PERMISSION);
    }
}
