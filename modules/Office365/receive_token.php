<?php
file_put_contents('/var/www/sites/praestotest/cache/receive_token.log', date('Y-m-d H:i:s') . " - Reachable\n", FILE_APPEND);
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

chdir(dirname(__FILE__, 3));
$rootPath = getcwd();
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Switched to root: $rootPath\n", FILE_APPEND);

require_once $rootPath . '/vendor/autoload.php';
if (!function_exists('vglobal')) {
    require_once $rootPath . '/includes/runtime/Globals.php';
}
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Autoloaded\n", FILE_APPEND);

require_once $rootPath . '/config.php';
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Config loaded\n", FILE_APPEND);
require_once $rootPath . '/include/utils/utils.php';
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Utils loaded\n", FILE_APPEND);
require_once $rootPath . '/include/database/PearDatabase.php';
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - DB loaded\n", FILE_APPEND);
require_once $rootPath . '/modules/Oauth2/callbacks/Usercallback.php';
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Callbacks loaded\n", FILE_APPEND);
require_once $rootPath . '/modules/Oauth2/Config.php';
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Oauth2 Config loaded\n", FILE_APPEND);

$cfgdata = require $rootPath . '/oauth2callback/config.oauth2.php';
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Cfg data loaded\n", FILE_APPEND);
$config = Oauth2_Config::loadConfig($cfgdata);
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Oauth2_Config object created\n", FILE_APPEND);

// Initialize Vtiger globals
global $log;
$log = Logger::getLogger('VT');
vglobal('log', $log);
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Log initialized\n", FILE_APPEND);


// The proxy sends: userid, source, code, source_module
$userid = $_POST['userid'];
$code = $_POST['code'];
file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Received code for user $userid\n", FILE_APPEND);

// Reconstruct the request for Usercallback
$req = array(
    'code' => $code,
    'authfor' => 'Calendar',
    'authservice' => 'Office365',
    'state' => base64_encode($_SERVER['HTTP_HOST'] . "||" . $userid . "||Calendar||Office365")
);

// We need to set the authenticated user id in session because Usercallback might check it
session_start();
$_SESSION['authenticated_user_id'] = $userid;
$_SESSION['oauth2for'] = 'Calendar';
$_SESSION['oauth2svc'] = 'Office365';

try {
    file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Exchanging code\n", FILE_APPEND);
    $authcfg = $config->getProviderConfig('Office365');
    $provider = new \League\OAuth2\Client\Provider\GenericProvider($authcfg);
    
    $accessToken = $provider->getAccessToken("authorization_code", ["code" => $code]);
    file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Access token obtained\n", FILE_APPEND);
    
    $tokens = array(
        "access_token" => $accessToken->getToken(),
        "refresh_token" => $accessToken->getRefreshToken()
    );
    $expiresOn = $accessToken->getExpires();
    
    $resourceOwner = $provider->getResourceOwner($accessToken);
    $userinfo = $resourceOwner ? $resourceOwner->toArray() : array();
    file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Resource owner: " . ($userinfo['mail'] ?? 'unknown') . "\n", FILE_APPEND);
    
    // Save tokens
    Oauth2_Usercallback_Callbacks::updateTokensFor($config, 'Calendar', 'Office365', $userinfo, $tokens, $expiresOn);
    file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Tokens saved\n", FILE_APPEND);
    
    echo json_encode(array("success" => true));
} catch (Exception $e) {
    file_put_contents($rootPath . '/cache/receive_token.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(array("success" => false, "error" => $e->getMessage()));
}
