<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Office365_Utils_Helper {

    public static function getSyncTime($module, $user = false) {
        $db = PearDatabase::getInstance();
        if (!$user) $user = Users_Record_Model::getCurrentUserModel();
        $result = $db->pquery("SELECT lastsynctime FROM vtiger_office365_sync WHERE user=? AND office365module=?", array($user->getId(), $module));
        if ($db->num_rows($result) > 0) {
            return $db->query_result($result, 0, 'lastsynctime');
        }
        return false;
    }


    public static function updateSyncTime($module, $time, $user = false) {
        $db = PearDatabase::getInstance();
        if (!$user) $user = Users_Record_Model::getCurrentUserModel();
        $check = $db->pquery("SELECT 1 FROM vtiger_office365_sync WHERE user=? AND office365module=?", array($user->getId(), $module));
        if ($db->num_rows($check) > 0) {
            $db->pquery("UPDATE vtiger_office365_sync SET lastsynctime=? WHERE user=? AND office365module=?", array($time, $user->getId(), $module));
        } else {
            $db->pquery("INSERT INTO vtiger_office365_sync (user, office365module, lastsynctime) VALUES (?,?,?)", array($user->getId(), $module, $time));
        }
    }


    public static function getLastSyncTime($module) {
        $time = self::getSyncTime($module);
        if ($time) {
            return Vtiger_Util_Helper::formatDateTimeIntoDayString($time);
        }
        return false;
    }

    public static function checkSyncEnabled($module, $user = false) {
        $db = PearDatabase::getInstance();
        if (!$user) $user = Users_Record_Model::getCurrentUserModel();
        $result = $db->pquery("SELECT enable_cron FROM vtiger_office365_sync_settings WHERE user=? AND module=?", array($user->getId(), $module));
        if ($db->num_rows($result) > 0) {
            return $db->query_result($result, 0, 'enable_cron');
        }
        return true;
    }


    public static function getSyncDirectionForUser($user, $module = 'Calendar') {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT direction FROM vtiger_office365_sync_settings WHERE user=? AND module=?", array($user->getId(), $module));
        if ($db->num_rows($result) > 0) {
            $direction = $db->query_result($result, 0, 'direction');
            if ($direction == '11') return array('push', 'pull');
            if ($direction == '10') return array('push');
            if ($direction == '01') return array('pull');
        }
        return array('push', 'pull');
    }
}
