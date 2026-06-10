<?php
// Initialize Vtiger engine
chdir(__DIR__ . '/../..');
require_once 'config.php';
require_once 'vendor/autoload.php';
require_once 'include/utils/utils.php';
require_once 'includes/Loader.php';
require_once 'includes/runtime/BaseModel.php';
require_once 'includes/runtime/Controller.php';
require_once 'includes/runtime/Globals.php';
require_once 'includes/runtime/LanguageHandler.php';
require_once 'modules/Users/models/Record.php';
require_once 'modules/Office365/connectors/Oauth2.php';

$user = Users_Record_Model::getInstanceById(6);
$oauth2 = new Office365_Oauth2_Connector('Calendar', 6);
$oauth2->authorize();

$baseUrl = 'https://graph.microsoft.com/v1.0';
$headers = [
    'Authorization: Bearer ' . $oauth2->getAccessToken(),
    'Content-Type: application/json',
    'Accept: application/json'
];

echo "Fetching calendar folders...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/me/calendars");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$data = json_decode($response, true);
if (isset($data['value'])) {
    echo "Found " . count($data['value']) . " calendars:\n";
    foreach ($data['value'] as $cal) {
        echo " - Name: " . $cal['name'] . "\n";
        echo "   ID: " . $cal['id'] . "\n";
        echo "   Is Default: " . ($cal['isDefaultCalendar'] ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "Error: " . print_r($data, true) . "\n";
}
