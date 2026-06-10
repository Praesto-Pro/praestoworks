<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Office365_Oauth2_Connector {

    protected $service_provider = 'Office365';
    protected $source_module;
    protected $user_id;
    protected $db;
    protected $table_name = 'vtiger_office365_oauth2';
    protected $service_name;
    
    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;

    public $token;

    public function __construct($module, $userId = false) {
        $this->source_module = $module;
        if ($userId) $this->user_id = $userId;
        if ($module == 'Events') $module = 'Calendar';
        $this->service_name = $this->service_provider . $module;
        
        $cfgdata = require "oauth2callback/config.oauth2.php";
        $this->client_id = $cfgdata['Office365']['clientId'];
        $this->client_secret = $cfgdata['Office365']['clientSecret'];
        
        $this->redirect_uri = $cfgdata['Office365']['redirectUri'];
    }

    public function getClientId() { return $this->client_id; }
    public function getClientSecret() { return $this->client_secret; }
    public function getRedirectUri() { return $this->redirect_uri; }

    public function hasStoredToken() {
        if (!isset($this->user_id)) $this->user_id = Users_Record_Model::getCurrentUserModel()->getId();
        if (!isset($this->db)) $this->db = PearDatabase::getInstance();
        $sql = "SELECT 1 FROM $this->table_name WHERE userid = ? AND service = ?";
        $res = $this->db->pquery($sql, array($this->user_id, $this->service_name));
        return $this->db->num_rows($res) > 0;
    }

    public function getAccessToken() {
        return $this->token['access_token']['access_token'];
    }

    public function isTokenExpired() {
        if (null == $this->token || !isset($this->token['access_token'])) return true;
        $accessToken = $this->token['access_token'];
        $expired = ($accessToken['created'] + ($accessToken['expires_in'] - 30)) < time();
        return $expired;
    }

    public function refreshToken() {
        if (empty($this->token['refresh_token'])) return false;

        $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $params = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->token['refresh_token'],
            'scope' => 'offline_access User.Read Calendars.ReadWrite'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $decodedToken = json_decode($response, true);
        if (isset($decodedToken['access_token'])) {
            $decodedToken['created'] = time();
            $this->token['access_token'] = $decodedToken;
            if (isset($decodedToken['refresh_token'])) {
                $this->token['refresh_token'] = $decodedToken['refresh_token'];
            }
            $this->updateAccessToken(json_encode($this->token['access_token']), $this->token['refresh_token']);
            return true;
        }
        return false;
    }

    protected function updateAccessToken($accesstoken, $refreshtoken) {
        if (!isset($this->db)) $this->db = PearDatabase::getInstance();
        $sql = "UPDATE $this->table_name SET access_token = ?, refresh_token = ? WHERE userid = ? AND service = ?";
        $this->db->pquery($sql, array($accesstoken, $refreshtoken, $this->user_id, $this->service_name));
    }

    protected function retreiveToken() {
        if (!$this->user_id) $this->user_id = Users_Record_Model::getCurrentUserModel()->getId();
        if (!isset($this->db)) $this->db = PearDatabase::getInstance();
        $query = "SELECT access_token, refresh_token FROM $this->table_name WHERE userid = ? AND service = ?";
        $result = $this->db->pquery($query, array($this->user_id, $this->service_name));
        $data = $this->db->fetch_array($result);
        return array(
            'access_token' => json_decode(decode_html($data['access_token']), true),
            'refresh_token' => decode_html($data['refresh_token'])
        );
    }

    public function authorize() {
        if ($this->hasStoredToken()) {
            $this->token = $this->retreiveToken();
            if ($this->isTokenExpired()) $this->refreshToken();
            return $this;
        } else {
            global $site_URL;
            $authUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
            $parsed_url = parse_url($site_URL);
            $payload = $parsed_url['host'] . "||" . $this->user_id . "||" . 'Calendar' . "||" . 'Office365';
            $state = base64_encode($payload);

            $authParams = array(
                'client_id' => $this->client_id,
                'response_type' => 'code',
                'redirect_uri' => $this->redirect_uri,
                'response_mode' => 'query',
                'scope' => 'offline_access User.Read Calendars.ReadWrite',
                'state' => $state,
                'prompt' => 'select_account'
            );
            $url = $authUrl . '?' . http_build_query($authParams);
            header("Location: $url");
            exit;
        }
    }

    public function getConnectedEmail() {
        if (!$this->hasStoredToken()) return false;
        if (!$this->token) $this->token = $this->retreiveToken();
        if ($this->isTokenExpired()) $this->refreshToken();

        $url = 'https://graph.microsoft.com/v1.0/me';
        $headers = [
            'Authorization: Bearer ' . $this->getAccessToken(),
            'Accept: application/json'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['mail'] ?? $data['userPrincipalName'] ?? false;
    }
}
