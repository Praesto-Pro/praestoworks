<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
vimport('~~/modules/WSAPP/synclib/handlers/VtigerSyncEventHandler.php');

class Office365_VtigerSync_Handler extends WSAPP_VtigerSyncEventHandler {
    public function getSyncServerInstance() {
        vimport('~~/modules/Office365/controllers/SyncServer.php');
        return new Office365_SyncServer_Controller();
    }
}
