<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

vimport('~~/modules/WSAPP/synclib/connectors/VtigerConnector.php');
vimport('~~/modules/WSAPP/SyncServer.php');
include_once 'include/Webservices/Query.php';
include_once 'include/Webservices/Create.php';
include_once 'include/Webservices/Retrieve.php';

class Office365_Vtiger_Connector extends WSAPP_VtigerConnector {

    /**
     * Returns the sync tracker handler name for Office365.
     * This determines the key used to store sync state in vtiger_wsapp_sync_state.
     */
    public function getSyncTrackerHandlerName() {
        return 'Office365_vtigerSyncHandler';
    }
}
