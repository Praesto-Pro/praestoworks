<?php

class Office365_Module_Model extends Vtiger_Module_Model {
    
    public static function removeSync($module, $id) {
        $db = PearDatabase::getInstance();
        $query = "DELETE FROM vtiger_office365_oauth WHERE service = ? AND userid = ?";
        $db->pquery($query, array($module, $id));
    }
    
    /**
     * Function to delete office365 synchronization completely. Deletes all mapping information stored.
     * @param <string> $module - Module Name
     * @param <integer> $user - User Id
     */
    public static function deleteSync($module, $user) {
        $module = str_replace("Office365", '', $module);
        if($module == 'Contacts' || $module == 'Calendar') {
            $name = 'Vtiger_Office365'.$module;
        }
        else {
            return;
        }
        $db = PearDatabase::getInstance();
        $db->pquery("DELETE FROM vtiger_office365_oauth2 WHERE service = ? AND userid = ?", array('Office365'.$module, $user));
        //$db->pquery("DELETE FROM vtiger_office365_sync WHERE module = ? AND userid = ?", array($module, $user));
        //$db->pquery("DELETE FROM vtiger_office365_sync_settings WHERE userid = ? AND module = ?", array($user,$module));
        return;
    }
    
    /*
     * Function to get supported utility actions for a module
     */
    function getUtilityActionsNames() {
        return array();
    }
    
    public static function saveSyncSettings($request) {
       
        $contactsSettings = $request->get('Contacts');
        $calendarSettings = $request->get('Calendar');
        $sourceModule = $request->get('sourceModule');
        
        if($sourceModule == 'Contacts' && !empty($contactsSettings)){
            $contactRequest = new Vtiger_Request($contactsSettings);
            $contactRequest->set('sourcemodule', 'Contacts');
            Office365_Utils_Helper::saveSyncSettings($contactRequest);
        }
        
        if($sourceModule == 'Calendar' && !empty($calendarSettings)){
            $calendarRequest = new Vtiger_Request($calendarSettings);
            $calendarRequest->set('sourcemodule', 'Calendar');
            Office365_Utils_Helper::saveSyncSettings($calendarRequest);
        }
        
        return true;
    }
}

?>