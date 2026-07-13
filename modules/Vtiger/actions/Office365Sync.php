<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Vtiger_Office365Sync_Action extends Vtiger_Action_Controller {

    public function checkPermission(Vtiger_Request $request) {
        return true;
    }

    public function validateRequest(Vtiger_Request $request) {
        return true;
    }

    public function process(Vtiger_Request $request) {
        // Force include the module's controller and helpers
        require_once 'modules/Office365/helpers/Utils.php';
        require_once 'modules/Office365/models/SyncRecord.php';
        require_once 'modules/Office365/connectors/Oauth2.php';
        require_once 'modules/Office365/connectors/Calendar.php';
        require_once 'modules/Office365/controllers/Calendar.php';

        $operation = $request->get('operation');
        if ($operation === 'disconnect') {
            $db = PearDatabase::getInstance();
            $user = Users_Record_Model::getCurrentUserModel();
            $userId = $user->getId();
            
            // Delete token
            $db->pquery("DELETE FROM vtiger_office365_oauth2 WHERE userid = ?", array($userId));
            // Reset sync state
            $db->pquery("DELETE FROM vtiger_office365_sync WHERE user = ?", array($userId));
            
            $response = new Vtiger_Response();
            $response->setResult(array('success' => true));
            $response->emit();
            return;
        }

        if ($operation === 'save_settings') {
            $db = PearDatabase::getInstance();
            $user = Users_Record_Model::getCurrentUserModel();
            $userId = $user->getId();
            
            $syncEnabled = intval($request->get('sync_enabled'));
            $syncDirection = $request->get('sync_direction');
            $syncStartFrom = $request->get('sync_start_from');
            
            if (!empty($syncStartFrom)) {
                $syncStartFrom = date('Y-m-d', strtotime($syncStartFrom));
            } else {
                $syncStartFrom = null;
            }
            
            $sourceModule = $request->get('sourcemodule');
            if (!$sourceModule) $sourceModule = 'Calendar';
            
            $check = $db->pquery("SELECT 1 FROM vtiger_office365_sync_settings WHERE user=? AND module=?", array($userId, $sourceModule));
            if ($db->num_rows($check) > 0) {
                $db->pquery(
                    "UPDATE vtiger_office365_sync_settings SET sync_start_from=?, direction=?, enable_cron=? WHERE user=? AND module=?",
                    array($syncStartFrom, $syncDirection, $syncEnabled, $userId, $sourceModule)
                );
            } else {
                $db->pquery(
                    "INSERT INTO vtiger_office365_sync_settings (user, module, direction, sync_start_from, enable_cron) VALUES (?,?,?,?,?)",
                    array($userId, $sourceModule, $syncDirection, $syncStartFrom, $syncEnabled)
                );
            }
            
            $response = new Vtiger_Response();
            $response->setResult(array('success' => true));
            $response->emit();
            return;
        }

        $sourceModule = $request->get('sourcemodule');
        if (!$sourceModule) $sourceModule = 'Calendar';

        $user = Users_Record_Model::getCurrentUserModel();
        $controller = new Office365_Calendar_Controller($user);
        $syncDirection = Office365_Utils_Helper::getSyncDirectionForUser($user, 'Calendar');

        $records = array();
        if (Office365_Utils_Helper::checkSyncEnabled('Calendar', $user)) {
            $records = $controller->synchronize(true, $syncDirection[0], $syncDirection[1] ?? null);
            file_put_contents('logs/sync_debug.log', "Sync records found: " . count($records, COUNT_RECURSIVE) . "\n", FILE_APPEND);
            // Update the last sync time now that sync has completed
            Office365_Utils_Helper::updateSyncTime('Calendar', date('Y-m-d H:i:s'), $user);
        }

        $countRecords = $this->getSyncRecordsCount($records);
        file_put_contents('logs/sync_debug.log', "Sync count: " . print_r($countRecords, true) . "\n", FILE_APPEND);

        $db = PearDatabase::getInstance();
        $extensiontabid = getTabid('Office365');
        $result = $db->pquery(
            "SELECT id FROM vtiger_wsapp_logs_basic WHERE extensiontabid = ? AND userid = ? ORDER BY id DESC LIMIT 1",
            array($extensiontabid, $user->getId())
        );
        $logId = 0;
        if ($result && $db->num_rows($result) > 0) {
            $logId = $db->query_result($result, 0, 'id');
        }

        $viewer = Vtiger_Viewer::getInstance();
        $viewer->assign('MODULE_NAME', 'Office365');
        $viewer->assign('RECORDS', $countRecords);
        $viewer->assign('SYNCTIME', Office365_Utils_Helper::getLastSyncTime($sourceModule));
        $viewer->assign('SOURCEMODULE', $sourceModule);
        $viewer->assign('LOG_ID', $logId);

        // Get HTML content
        $html = $viewer->view('ContentDetails.tpl', 'Office365', true);

        $response = new Vtiger_Response();
        $response->setResult($html);
        $response->emit();
    }

    public function getSyncRecordsCount($syncRecords) {
        $countRecords = array(
            'vtiger' => array('update' => 0, 'create' => 0, 'delete' => 0),
            'office365' => array('update' => 0, 'create' => 0, 'delete' => 0)
        );
        foreach ($syncRecords as $key => $records) {
            if ($key == 'push') {
                foreach ($records as $record) {
                    foreach ($record as $type => $data) {
                        if ($type == 'source') {
                            $mode = $data->getMode();
                            if ($mode == Office365_SyncRecord_Model::UPDATE_MODE) $countRecords['vtiger']['update']++;
                            elseif ($mode == Office365_SyncRecord_Model::CREATE_MODE) $countRecords['vtiger']['create']++;
                            elseif ($mode == Office365_SyncRecord_Model::DELETE_MODE) $countRecords['vtiger']['delete']++;
                        }
                    }
                }
            } else if ($key == 'pull') {
                foreach ($records as $type => $record) {
                    foreach ($record as $type => $data) {
                        if ($type == 'target') {
                            $mode = $data->getMode();
                            if ($mode == Office365_SyncRecord_Model::UPDATE_MODE) $countRecords['office365']['update']++;
                            elseif ($mode == Office365_SyncRecord_Model::CREATE_MODE) $countRecords['office365']['create']++;
                            elseif ($mode == Office365_SyncRecord_Model::DELETE_MODE) $countRecords['office365']['delete']++;
                        }
                    }
                }
            }
        }
        return $countRecords;
    }
}
