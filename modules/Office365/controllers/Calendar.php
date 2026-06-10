<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

vimport('~~/modules/WSAPP/synclib/controllers/SynchronizeController.php');

class Office365_Calendar_Controller extends WSAPP_SynchronizeController {

    public function getTargetConnector() {
        $oauth2Connector = new Office365_Oauth2_Connector("Calendar", $this->user->getId());
        $oauth2Connection = $oauth2Connector->authorize();
        $connector = new Office365_Calendar_Connector($oauth2Connection);
        $connector->setSynchronizeController($this);
        return $connector;
    }

    public function getSourceConnector() {
        vimport('~~/modules/Office365/connectors/Vtiger.php');
        $connector = new Office365_Vtiger_Connector();
        $connector->setSynchronizeController($this);
        $targetName = $this->targetConnector->getName();
        return $connector->setName('Vtiger_' . $targetName);
    }

    public function getSyncType() {
        return WSAPP_SynchronizeController::WSAPP_SYNCHRONIZECONTROLLER_USER_SYNCTYPE;
    }

    public function getSourceType() {
        return 'Events';
    }
}
