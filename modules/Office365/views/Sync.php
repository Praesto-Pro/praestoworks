<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Office365_Sync_View extends Vtiger_PopupAjax_View {

    public function __construct() {
        parent::__construct();
        $this->exposeMethod('Calendar');
        $this->exposeMethod('Events');
    }

    public function getHeaderScripts(\Vtiger_Request $request) {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = array(
            "modules.$moduleName.resources.Popup",
        );
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }

    public function Events() {

        return $this->Calendar();
    }

    public function requiresPermission(\Vtiger_Request $request) {
        return array();
    }

    public function validateRequest(\Vtiger_Request $request) {
        return true;
    }


    public function preProcess(Vtiger_Request $request, $display = true) {
        if ($request->isAjax()) {
            return true;
        }
        $headerView = new Vtiger_Index_View();
        $headerView->preProcess($request, $display);
    }

    public function postProcess(Vtiger_Request $request) {
        if ($request->isAjax()) {
            return true;
        }
        $footerView = new Vtiger_Index_View();
        $footerView->postProcess($request);
    }

    function process(Vtiger_Request $request) {
        switch ($request->get('operation')) {
            case "sync": $this->renderSyncUI($request); break;
            case "authorize": $this->renderAuthorize($request); break;
            default: $this->renderWidgetUI($request); break;
        }
    }

    function renderAuthorize(Vtiger_Request $request) {
        global $site_URL;
        $crmBaseUrl = trim($site_URL, '/');
        setcookie('vtiger_oauth2for', 'Calendar', time() + 3600, '/');
        header("Location: {$crmBaseUrl}/oauth2callback/index.php?authfor=Calendar&authservice=Office365");
        exit;
    }

    function renderWidgetUI(Vtiger_Request $request) {
        $sourceModule = $request->get('sourcemodule');
        if (!$sourceModule) $sourceModule = 'Calendar';
        $viewer = $this->getViewer($request);
        $oauth2 = new Office365_Oauth2_Connector($sourceModule);
        $firsttime = $oauth2->hasStoredToken();
        
        $viewer->assign('MODULE_NAME', $request->getModule());
        $viewer->assign('FIRSTTIME', $firsttime);
        $viewer->assign('CONNECTED_EMAIL', $oauth2->getConnectedEmail());
        $viewer->assign('SYNCTIME', Office365_Utils_Helper::getLastSyncTime($sourceModule));
        $viewer->assign('SYNC_START_FROM', Office365_Utils_Helper::getSyncStartFrom($sourceModule));
        $viewer->assign('SYNC_ENABLED', Office365_Utils_Helper::checkSyncEnabled($sourceModule));
        $viewer->assign('SYNC_DIRECTION', Office365_Utils_Helper::getSyncDirectionValue($sourceModule));
        $viewer->assign('SOURCEMODULE', $sourceModule);
        $viewer->view('Contents.tpl', $request->getModule());
    }

    function renderSyncUI(Vtiger_Request $request) {
        $sourceModule = $request->get('sourcemodule');
        if (!$sourceModule) $sourceModule = 'Calendar';
        $viewer = $this->getViewer($request);
        
        error_log("Office365 Sync: renderSyncUI called for " . $sourceModule);
        $records = $this->invokeExposedMethod($sourceModule);
        error_log("Office365 Sync: Records synced: " . print_r($records, true));
        
        $db = PearDatabase::getInstance();
        $user = Users_Record_Model::getCurrentUserModel();
        $extensiontabid = getTabid('Office365');
        $result = $db->pquery(
            "SELECT id FROM vtiger_wsapp_logs_basic WHERE extensiontabid = ? AND userid = ? ORDER BY id DESC LIMIT 1",
            array($extensiontabid, $user->getId())
        );
        $logId = 0;
        if ($db->num_rows($result) > 0) {
            $logId = $db->query_result($result, 0, 'id');
        }
        file_put_contents(
            'cache/sync_debug.log',
            date('Y-m-d H:i:s') . " - extensiontabid=" . (string)$extensiontabid . ", userid=" . (string)$user->getId() . ", logId=" . (string)$logId . "\n",
            FILE_APPEND
        );
        
        $viewer->assign('MODULE_NAME', $request->getModule());
        $viewer->assign('RECORDS', $records);
        $viewer->assign('SYNCTIME', Office365_Utils_Helper::getLastSyncTime($sourceModule));
        $viewer->assign('SOURCEMODULE', $sourceModule);
        $viewer->assign('LOG_ID', $logId);
        $viewer->view('ContentDetails.tpl', $request->getModule());
    }

    public function Calendar() {
        $user = Users_Record_Model::getCurrentUserModel();
        $controller = new Office365_Calendar_Controller($user);
        $syncDirection = Office365_Utils_Helper::getSyncDirectionForUser($user, 'Calendar');
        
        $records = array();
        if (Office365_Utils_Helper::checkSyncEnabled('Calendar', $user)) {
            $records = $controller->synchronize(true, $syncDirection[0], $syncDirection[1] ?? null);
        }
        
        return $this->getSyncRecordsCount($records);
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
