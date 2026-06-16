<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

vimport('~~/modules/WSAPP/synclib/connectors/TargetConnector.php');

class Office365_Calendar_Connector extends WSAPP_TargetConnector {

    protected $apiConnection;
    protected $baseUrl = 'https://graph.microsoft.com/v1.0';
    protected $totalRecords;
    protected $maxResults = 50;
    protected $createdRecords;

    public function __construct($oauth2Connection) {
        $this->apiConnection = $oauth2Connection;
    }

    public function getName() {
        return 'Office365Calendar';
    }

    protected function makeGraphRequest($endpoint, $method = 'GET', $data = null) {
        if (strpos($endpoint, 'http') === 0) {
            $url = $endpoint;
        } else {
            $url = $this->baseUrl . $endpoint;
        }
        // Final safety check for spaces in the final URL
        $url = str_replace(' ', '%20', $url);
        $headers = [
            'Authorization: Bearer ' . $this->apiConnection->getAccessToken(),
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        file_put_contents('logs/sync_debug.log', "Graph Request to $endpoint: HTTP $httpCode, Error: $curlError\n", FILE_APPEND);
        if ($response) {
            file_put_contents('logs/sync_debug.log', "Graph Response Body: " . substr($response, 0, 500) . "\n", FILE_APPEND);
        }
        curl_close($ch);
        if ($httpCode >= 400) return false;
        return json_decode($response, true);
    }

    public function pull($SyncState, $user = false) {
        if (!$user) $user = Users_Record_Model::getCurrentUserModel();
        $lastSyncTime = Office365_Utils_Helper::getSyncTime('Calendar', $user);
        
        $syncStartFrom = Office365_Utils_Helper::getSyncStartFrom('Calendar', $user);
        if ($syncStartFrom) {
            $startDateTime = date('Y-m-d\TH:i:s\Z', strtotime($syncStartFrom));
        } else {
            $startDateTime = date('Y-m-d\TH:i:s\Z', strtotime('-30 days'));
        }
        $endDateTime = date('Y-m-d\TH:i:s\Z', strtotime('+90 days'));
        
        $url = '/me/calendar/calendarView?startDateTime=' . $startDateTime . '&endDateTime=' . $endDateTime;
        $allEvents = array();
        $pageLimit = 100; // safety limit to prevent infinite loops
        
        while ($url && $pageLimit > 0) {
            $response = $this->makeGraphRequest($url);
            $count = (is_array($response) && isset($response['value'])) ? count($response['value']) : 0;
            file_put_contents('logs/sync_debug.log', "Graph API calendarView Response count: $count\n", FILE_APPEND);
            if (!$response || !isset($response['value'])) break;
            
            $allEvents = array_merge($allEvents, $response['value']);
            
            if (isset($response['@odata.nextLink'])) {
                $url = $response['@odata.nextLink'];
                $pageLimit--;
            } else {
                $url = null;
            }
        }
        
        if (empty($allEvents)) return array();

        $lastSyncTimestamp = 0;
        if ($lastSyncTime) {
            $lastSyncTimestamp = strtotime($lastSyncTime);
            file_put_contents('logs/sync_debug.log', "Filtering events modified since last sync: $lastSyncTime (timestamp $lastSyncTimestamp)\n", FILE_APPEND);
        }

        // Fetch mapped Office365 client IDs for this user only, to skip modification filtering for already-synced events
        $db = PearDatabase::getInstance();
        $userId = $user->id;
        $mappingQuery = $db->pquery(
            "SELECT rm.clientid FROM vtiger_wsapp_recordmapping rm
             INNER JOIN vtiger_wsapp app ON rm.appid = app.appid
             INNER JOIN vtiger_wsapp_sync_state ss ON app.appkey = JSON_UNQUOTE(JSON_EXTRACT(ss.stateencodedvalues, '$.synctrackerid'))
             WHERE ss.userid = ? AND ss.name = 'Vtiger_Office365Calendar'",
            array($userId)
        );
        $mappedIds = array();
        while ($row = $db->fetchByAssoc($mappingQuery)) {
            if (!empty($row['clientid'])) {
                $mappedIds[$row['clientid']] = true;
            }
        }

        $records = array();
        foreach ($allEvents as $event) {
            $eventId = $event['id'];
            
            // If the event is already mapped, only pull if modified since the last sync time
            if (isset($mappedIds[$eventId])) {
                if ($lastSyncTimestamp > 0 && isset($event['lastModifiedDateTime'])) {
                    $eventModifiedTime = strtotime($event['lastModifiedDateTime']);
                    if ($eventModifiedTime < $lastSyncTimestamp) {
                        continue; // Skip unmodified event
                    }
                }
            }
            
            $recordModel = Office365_Calendar_Model::getInstanceFromValues(array('entity' => $event));
            $mode = WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE;
            // Microsoft doesn't usually return 'cancelled' in a simple list unless we use delta sync
            // For now we assume update.
            $recordModel->setType($this->getSynchronizeController()->getSourceType())->setMode($mode);
            $records[$eventId] = $recordModel;
        }
        
        $this->createdRecords = count($records);
        $this->totalRecords = count($records); // Simple paging for now

        file_put_contents('logs/sync_debug.log', "Pulled and filtered Office365 records to sync: " . count($records) . "\n", FILE_APPEND);
        return $records;
    }

    public function push($records, $user) {
        file_put_contents('logs/sync_debug.log', "Office365 Push: Pushing " . count($records) . " records to Office365\n", FILE_APPEND);
        foreach ($records as $record) {
            $entity = $record->get('entity');
            $mode = $record->getMode();
            file_put_contents('logs/sync_debug.log', "Office365 Push Record: Mode=$mode, Subject=" . ($entity['subject'] ?? 'N/A') . "\n", FILE_APPEND);
            
            try {
                if ($mode == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE) {
                    $this->makeGraphRequest('/me/calendar/events/' . $entity['id'], 'PATCH', $entity);
                } else if ($mode == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                    $this->makeGraphRequest('/me/calendar/events/' . $entity['id'], 'DELETE');
                } else {
                    $newEntity = $this->makeGraphRequest('/me/calendar/events', 'POST', $entity);
                    if ($newEntity) $record->set('entity', $newEntity);
                }
            } catch (Exception $e) { 
                file_put_contents('logs/sync_debug.log', "Office365 Push Error: " . $e->getMessage() . "\n", FILE_APPEND);
                continue; 
            }
        }
        return $records;
    }

    public function transformToSourceRecord($targetRecords, $user = false) {
        $calendarArray = array();
        if (!$user) $user = Users_Record_Model::getCurrentUserModel();
        
        foreach ($targetRecords as $officeRecord) {
            $data = $officeRecord->get('entity');
            file_put_contents('logs/sync_debug.log', "Transforming Office365 event: " . $data['subject'] . "\n", FILE_APPEND);
            $entity = array();
            
            $entity['assigned_user_id'] = vtws_getWebserviceEntityId('Users', $user->getId());
            $entity['subject'] = $this->cleanEmoji($data['subject'] ?? '');
            $entity['description'] = $this->cleanEmoji($data['bodyPreview'] ?? '');
            $entity['location'] = $this->cleanEmoji($data['location']['displayName'] ?? '');
            
            // Format dates
            $start = new DateTime($data['start']['dateTime'], new DateTimeZone($data['start']['timeZone']));
            $start->setTimezone(new DateTimeZone('UTC'));
            $entity['date_start'] = $start->format('Y-m-d');
            $entity['time_start'] = $start->format('H:i:s');
            
            $end = new DateTime($data['end']['dateTime'], new DateTimeZone($data['end']['timeZone']));
            $end->setTimezone(new DateTimeZone('UTC'));
            $entity['due_date'] = $end->format('Y-m-d');
            $entity['time_end'] = $end->format('H:i:s');
            
            $entity['eventstatus'] = "Planned";
            $entity['activitytype'] = "Meeting";
            $entity['visibility'] = (isset($data['sensitivity']) && $data['sensitivity'] == 'private') ? 'Private' : 'Public';

            $calendar = $this->getSynchronizeController()->getSourceRecordModel($entity);
            $calendar = $this->performBasicTransformations($officeRecord, $calendar);
            $calendar = $this->performBasicTransformationsToSourceRecords($calendar, $officeRecord);
            $calendarArray[] = $calendar;
        }
        return $calendarArray;
    }

    public function transformToTargetRecord($vtEvents, $user = false) {
        file_put_contents('logs/sync_debug.log', "Office365 Transform: Transforming " . count($vtEvents) . " Vtiger events to Office365 format\n", FILE_APPEND);
        $records = array();
        foreach ($vtEvents as $vtEvent) {
            $event = array();
            if ($vtEvent->getMode() == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE || $vtEvent->getMode() == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                $event['id'] = $vtEvent->get('_id');
            }

            $event['subject'] = $vtEvent->get('subject');
            file_put_contents('logs/sync_debug.log', "Office365 Transform Record: Subject=" . $event['subject'] . ", Mode=" . $vtEvent->getMode() . "\n", FILE_APPEND);
            $event['body'] = array('contentType' => 'text', 'content' => $vtEvent->get('description'));
            $event['location'] = array('displayName' => $vtEvent->get('location'));
            
            $event['start'] = array(
                'dateTime' => $vtEvent->get('date_start') . 'T' . $vtEvent->get('time_start'),
                'timeZone' => 'UTC'
            );
            $event['end'] = array(
                'dateTime' => $vtEvent->get('due_date') . 'T' . $vtEvent->get('time_end'),
                'timeZone' => 'UTC'
            );
            $event['sensitivity'] = (strtolower($vtEvent->get('visibility')) == 'private') ? 'private' : 'normal';

            // Recurrence Handling disabled to prevent duplicate occurrence pushes
            // if ($vtEvent->get('recurringtype') && $vtEvent->get('recurringtype') != '--None--') {
            //     $event['recurrence'] = $this->getGraphRecurrence($vtEvent);
            // }

            $recordModel = Office365_Calendar_Model::getInstanceFromValues(array('entity' => $event));
            $recordModel->setType($this->getSynchronizeController()->getSourceType())->setMode($vtEvent->getMode());
            $recordModel = $this->performBasicTransformations($vtEvent, $recordModel);
            $records[] = $recordModel;
        }
        return $records;
    }

    private function getGraphRecurrence($vtEvent) {
        $type = $vtEvent->get('recurringtype');
        $freq = $vtEvent->get('repeat_frequency');
        $pattern = array('type' => strtolower($type), 'interval' => intval($freq));
        
        if ($type == 'Weekly') {
            $days = array();
            // Vtiger stores days in a specific way, usually we need to check the request or related fields
            // For now, assume current day of week if not specified
            $pattern['daysOfWeek'] = array(date('l', strtotime($vtEvent->get('date_start'))));
        }
        
        return array(
            'pattern' => $pattern,
            'range' => array(
                'type' => 'endDate',
                'startDate' => $vtEvent->get('date_start'),
                'endDate' => $vtEvent->get('calendar_repeat_limit_date')
            )
        );
    }

    protected function cleanEmoji($string) {
        if (empty($string)) return '';
        // Strip 4-byte UTF-8 characters (emojis, etc.)
        return preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $string);
    }

    public function moreRecordsExits() {
        return false; // Simplified
    }
}
