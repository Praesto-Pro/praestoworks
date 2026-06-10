<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once 'vtlib/Vtiger/Module.php';

class Office365 {

    const module = 'Office365';

    function vtlib_handler($moduleName, $eventType) {
        $adb = PearDatabase::getInstance();
        $syncModules = array('Calendar' => 'Office365 Calendar', 'Events' => 'Office365 Calendar');

        if ($eventType == 'module.postinstall') {
            $adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', array($moduleName));
            $this->addWidgetforSync($syncModules);
        } else if ($eventType == 'module.disabled') {
            $this->removeWidgetforSync($syncModules);
        } else if ($eventType == 'module.enabled') {
            $this->addWidgetforSync($syncModules);
        } else if ($eventType == 'module.preuninstall') {
            $this->removeWidgetforSync($syncModules);
        }
    }

    function addWidgetforSync($moduleNames, $widgetType = 'LISTVIEWSIDEBARWIDGET') {
        if (empty($moduleNames)) return;
        if (is_string($moduleNames)) $moduleNames = array($moduleNames);

        foreach ($moduleNames as $moduleName => $widgetName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->addLink($widgetType, $widgetName, "index.php?module=Office365&view=List&sourcemodule=$moduleName", '', '', '');
                $module->addLink('EXTENSIONLINK', 'Office365', "index.php?module=Office365&view=List&sourcemodule=$moduleName", '', '', '');
            }
        }
    }

    function removeWidgetforSync($moduleNames, $widgetType = 'LISTVIEWSIDEBARWIDGET') {
        if (empty($moduleNames)) return;
        if (is_string($moduleNames)) $moduleNames = array($moduleNames);

        foreach ($moduleNames as $moduleName => $widgetName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->deleteLink($widgetType, $widgetName);
                $module->deleteLink('EXTENSIONLINK', 'Office365');
            }
        }
    }
}
