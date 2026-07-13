<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Calendar_Office365Sync_View extends Vtiger_PopupAjax_View {

    public function __construct() {
        parent::__construct();
        $this->exposeMethod('sync');
    }

    public function requiresPermission(\Vtiger_Request $request) {
        return array();
    }

    public function validateRequest(\Vtiger_Request $request) {
        return true;
    }

    function process(Vtiger_Request $request) {
        $operation = $request->get('operation');
        if ($operation === 'sync') {
            $this->renderSyncUI($request);
            return;
        }
        echo "Invalid operation";
    }

    function renderSyncUI(Vtiger_Request $request) {
        // Force include the module's controller and helpers
        require_once 'modules/Office365/helpers/Utils.php';
        require_once 'modules/Office365/controllers/Calendar.php';
        require_once 'modules/Office365/connectors/Oauth2.php';
        require_once 'modules/Office365/connectors/Calendar.php';
        require_once 'modules/Office365/models/SyncRecord.php';
        
        $sourceModule = $request->get('sourcemodule');
        if (!$sourceModule) $sourceModule = 'Calendar';
        
        $viewer = $this->getViewer($request);
        
        // Use the sync logic
        $user = Users_Record_Model::getCurrentUserModel();
        $controller = new Office365_Calendar_Controller($user);
        $syncDirection = Office365_Utils_Helper::getSyncDirectionForUser($user, 'Calendar');
        
        $records = array();
        if (Office365_Utils_Helper::checkSyncEnabled('Calendar', $user)) {
            $records = $controller->synchronize(true, $syncDirection[0], $syncDirection[1] ?? null);
        }
        
        $countRecords = $this->getSyncRecordsCount($records);
        
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

        $viewer->assign('MODULE_NAME', 'Office365');
        $viewer->assign('RECORDS', $countRecords);
        $viewer->assign('SYNCTIME', Office365_Utils_Helper::getLastSyncTime($sourceModule));
        $viewer->assign('SOURCEMODULE', $sourceModule);
        $viewer->assign('LOG_ID', $logId);
        
        // Render from Office365 layout directory
        echo $viewer->view('ContentDetails.tpl', 'Office365', true);
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
                            if ($data->getMode() == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE) $countRecords['vtiger']['update']++;
                            elseif ($data->getMode() == WSAPP_SyncRecordModel::WSAPP_CREATE_MODE) $countRecords['vtiger']['create']++;
                            elseif ($data->getMode() == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) $countRecords['vtiger']['delete']++;
                        }
                    }
                }
            } else if ($key == 'pull') {
                foreach ($records as $type => $record) {
                    foreach ($record as $type => $data) {
                        if ($type == 'target') {
                            if ($data->getMode() == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE) $countRecords['office365']['update']++;
                            elseif ($data->getMode() == WSAPP_SyncRecordModel::WSAPP_CREATE_MODE) $countRecords['office365']['create']++;
                            elseif ($data->getMode() == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) $countRecords['office365']['delete']++;
                        }
                    }
                }
            }
        }
        return $countRecords;
    }
}
