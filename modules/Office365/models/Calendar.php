<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

vimport('~~/modules/WSAPP/synclib/models/SyncRecordModel.php');

class Office365_Calendar_Model extends WSAPP_SyncRecordModel {

    function getId() {
        return $this->data['entity']['id'];
    }

    public function getModifiedTime() {
        return date('Y-m-d H:i:s', strtotime($this->data['entity']['lastModifiedDateTime']));
    }

    function getSubject() {
        return $this->data['entity']['subject'];
    }

    public static function getInstanceFromValues($recordValues) {
        return new Office365_Calendar_Model($recordValues);
    }
}
