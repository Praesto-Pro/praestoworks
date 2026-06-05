<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once "modules/Oauth2/Config.php";

class Oauth2_Usercallback_Callbacks
{

    protected static function ensureLogin($requireAdmin = false)
    {
        if (!isset($_SESSION['authenticated_user_id']) || empty($_SESSION['authenticated_user_id'])) {
            static::redirectToCRM();
            return;
        } else {
            global $current_user;
            $current_user = CRMEntity::getInstance('Users');
            $current_user->retrieveCurrentUserInfoFromFile($_SESSION['authenticated_user_id']);

            if ($requireAdmin) {
                if (!filter_var($current_user->is_admin, FILTER_VALIDATE_BOOLEAN)) {
                    static::redirectToCRM();
                    return;
                }
            }
        }
    }

    protected static function redirectToCRM()
    {
        global $site_URL;
        header(sprintf("Location: %s/index.php", trim($site_URL, '/')));
        exit;
    }

    public static function handleRequest($config, $req)
    {
        if (isset($req["error"]) && !empty($req["error"])) {
            echo htmlspecialchars($_REQUEST['error'], ENT_QUOTES, 'UTF-8');
            exit;
        }

        session_start();
        vglobal('log')->fatal("Oauth2 Session ID: " . session_id());
        vglobal('log')->fatal("Oauth2 Cookies: " . print_r($_COOKIE, true));

        if (isset($req['state'])) {
            $decodedState = (base64_decode($req['state'], true) === false) ? $req['state'] : base64_decode($req['state']);
            vglobal('log')->fatal("Oauth2 Decoded State: $decodedState");
            global $site_URL;
            $parsed_url = parse_url($site_URL);
            $stateData = preg_split('/\|\||\|/', $decodedState);
            if (count($stateData) >= 4) {
                if (empty($req['authfor'])) {
                    $req['authfor'] = $stateData[2];
                }
                if (empty($req['authservice'])) {
                    $req['authservice'] = $stateData[3];
                }
                if (empty($req['scannername']) && isset($stateData[4])) {
                    $req['scannername'] = $stateData[4];
                }
                if (empty($req['record']) && isset($stateData[5])) {
                    $req['record'] = $stateData[5];
                }
                if (empty($req['scannerOldName']) && isset($stateData[6])) {
                    $req['scannerOldName'] = $stateData[6];
                }
                if (empty($req['create']) && isset($stateData[7])) {
                    $req['create'] = $stateData[7];
                }
                $_SESSION['oauth2for'] = $req['authfor'];
                $_SESSION['oauth2svc'] = $req['authservice'];
            } else if ($decodedState === $parsed_url['host']) {
                // Host-only state, use session
                if (empty($req['authfor'])) {
                    $req['authfor'] = $_SESSION['oauth2for'];
                }
                if (empty($req['authservice'])) {
                    $req['authservice'] = $_SESSION['oauth2svc'];
                }
                vglobal('log')->fatal("Oauth2 State Decoded (Host-only): " . $req['authfor'] . " / " . $req['authservice']);
            }
        }

        // Cache parameters in session so they are available to updateTokensFor
        if (isset($req['scannername'])) {
            $_SESSION['oauth2_scannername'] = $req['scannername'];
        }
        if (isset($req['record'])) {
            $_SESSION['oauth2_record'] = $req['record'];
        }
        if (isset($req['scannerOldName'])) {
            $_SESSION['oauth2_scannerOldName'] = $req['scannerOldName'];
        }
        if (isset($req['create'])) {
            $_SESSION['oauth2_create'] = $req['create'];
        }

        error_log("Oauth2_Usercallback_Callbacks::handleRequest: " . print_r($req, true));

        // Ensure Right User.
        // callback is done against externally service. Successfully login does not mean rights to change CRM state.
        // Example: Non-admin should not be able alter unexpected system configuration
        // with direct visit to the oauth2callback when auth is for specifically CRM admin only.
        $authfor = (isset($req['authfor'])) ? $req['authfor'] : (isset($_SESSION['oauth2for']) ? $_SESSION['oauth2for'] : "");
        switch ($authfor) {
            case "OutgoingServer":
                static::ensureLogin(true);
                break;
            case "MailConverter":
                static::ensureLogin(true);
                break;
            case "MailManager":
                static::ensureLogin();
                break;
            case "Calendar":
                static::ensureLogin();
                break;
            default:
                static::ensureLogin(true);
                break;
        }

        $authsvc = (isset($req['authservice'])) ? $req['authservice'] : (isset($_SESSION['oauth2svc']) ? $_SESSION['oauth2svc'] : "");
        $authcfg = $config->getProviderConfig($authsvc);
        if (!$authcfg) {
            echo "Unknown service provider";
            exit;
        }

        if (empty($authcfg["clientId"]) || empty($authcfg["clientSecret"])) {
            echo "Please setup configuration.";
            exit;
        }

        $provider = new \League\OAuth2\Client\Provider\GenericProvider($authcfg);

        if (!isset($req['code'])) {
            // step 1

            global $site_URL, $current_user;

            $parsed_url = parse_url($site_URL);
            $scannername = isset($req['scannername']) ? $req['scannername'] : "";
            $record = isset($req['record']) ? $req['record'] : "";
            $scannerOldName = isset($req['scannerOldName']) ? $req['scannerOldName'] : "";
            $create = isset($req['create']) ? $req['create'] : "";

            $payload  = $parsed_url['host'] . "||" . $current_user->id . "||" . $authfor . "||" . $authsvc . "||" . $scannername . "||" . $record . "||" . $scannerOldName . "||" . $create;
            vglobal('log')->fatal("Oauth2 Step 1 Payload: $payload");
            $state = base64_encode($payload);

            $authParams = [
                'access_type' => 'offline',
                'state' => $state,
            ];
            // For Office365, select_account is enough to avoid repetitive consent prompts
            // while still allowing account selection if multiple accounts are logged in.
            if ($authsvc === 'Office365') {
                $authParams['prompt'] = 'select_account';
            } else {
                // Retain existing behavior for Google and other services
                $authParams['prompt'] = 'consent';
            }

            $authurl = $provider->getAuthorizationUrl($authParams);

            $_SESSION['oauth2state'] = $provider->getState();
            $_SESSION['oauth2for'] = isset($req['authfor']) ? $req['authfor'] : "";
            $_SESSION['oauth2svc'] = isset($req['authservice']) ? $req['authservice'] : "";
            $_SESSION['oauth2_account_id'] = isset($req['account_id']) ? $req['account_id'] : "";
            $_SESSION['oauth2_scannername'] = isset($req['scannername']) ? $req['scannername'] : "";
            $_SESSION['oauth2_record'] = isset($req['record']) ? $req['record'] : "";
            $_SESSION['oauth2_scannerOldName'] = isset($req['scannerOldName']) ? $req['scannerOldName'] : "";
            $_SESSION['oauth2_create'] = isset($req['create']) ? $req['create'] : "";

            // For Google oAuth (prompt is used instead of approval_prompt) which otherwise
            // will end up with bad-request due to conflict.
            $authurl = str_replace("approval_prompt=auto", "", $authurl);

            header("Location: $authurl");
            exit;
        } else if (isset($req['state']) && isset($_SESSION['oauth2state']) && $req['state'] != $_SESSION['oauth2state']) {
            // something wrong
            unset($_SESSION['oauth2state']);
            echo ("Invalid state");
            exit;
        } else {
            // state is good, use code
            try {

                $accessToken = $provider->getAccessToken(
                    "authorization_code",
                    ["code" => $req["code"]]
                );

                // We have an access token, which we may use in authenticated
                // requests against the service provider's API.
                $accessTokenValue = $accessToken->getToken();
                $refreshTokenValue = $accessToken->getRefreshToken();
                $accessTokenExpiresOn = $accessToken->getExpires();

                 $userinfo = null;
                 $values = $accessToken->getValues();
                 if (isset($values['id_token'])) {
                     $idTokenParts = explode('.', $values['id_token']);
                     if (count($idTokenParts) >= 2) {
                         $payload = json_decode(base64_decode(str_replace(array('-', '_'), array('+', '/'), $idTokenParts[1])), true);
                         if (is_array($payload)) {
                             $emailVal = isset($payload['email']) ? $payload['email'] : (isset($payload['preferred_username']) ? $payload['preferred_username'] : (isset($payload['upn']) ? $payload['upn'] : ''));
                             if (!empty($emailVal)) {
                                 $userinfo = array(
                                     'email' => $emailVal,
                                     'mail' => $emailVal,
                                     'userPrincipalName' => $emailVal
                                 );
                             }
                         }
                     }
                 }

                 if (!$userinfo) {
                     try {
                         $resourceOwner = $provider->getResourceOwner($accessToken);
                         $userinfo = $resourceOwner ? $resourceOwner->toArray() : null;
                     } catch (Exception $e) {
                         vglobal('log')->fatal("Oauth2 failed to getResourceOwner: " . $e->getMessage());
                     }
                 }

                $oauth2for = isset($_SESSION['oauth2for']) ? $_SESSION['oauth2for'] : (isset($req['authfor']) ? $req['authfor'] : "");
                $oauth2svc = isset($_SESSION['oauth2svc']) ? $_SESSION['oauth2svc'] : (isset($req['authservice']) ? $req['authservice'] : "");
                vglobal('log')->fatal("Oauth2 Final authfor: $oauth2for, svc: $oauth2svc");
                $oauth2_account_id = isset($_SESSION['oauth2_account_id']) ? $_SESSION['oauth2_account_id'] : "";

                $response = null;
                if (($oauth2for == 'OutgoingServer' || $oauth2for == 'MailConverter' || $oauth2for == 'MailManager') && $oauth2svc == 'Office365') {

                    $userinfo["email"] = $userinfo['mail'] ? $userinfo['mail'] : $userinfo['userPrincipalName'];

                    if ($userinfo["email"]) {
                        $tokens = array("access_token" => $accessTokenValue, "refresh_token" => $refreshTokenValue);
                        $response = static::updateTokensFor($config, $oauth2for, $oauth2svc, $userinfo, $tokens, $accessTokenExpiresOn, $oauth2_account_id);
                    } else {
                        error_log("Email was empty in userinfo for Office365.");
                    }
                } else if ($userinfo["email"] && (!isset($userinfo["email_verified"]) || $userinfo["email_verified"])) {
                    $tokens = array("access_token" => $accessTokenValue, "refresh_token" => $refreshTokenValue);
                    $response = static::updateTokensFor($config, $oauth2for, $oauth2svc, $userinfo, $tokens, $accessTokenExpiresOn, $oauth2_account_id);
                }


                unset($_SESSION['oauth2for']);
                unset($_SESSION['oauth2state']);
                unset($_SESSION['oauth2svc']);

                global $site_URL;
                $crmBaseUrl = trim($site_URL, '/');

                switch ($oauth2for) {
                    case "OutgoingServer":
                        header("Location: {$crmBaseUrl}/oauth2callback/redirect.php?authfor=OutgoingServer");
                        break;
                    case "MailConverter":
                        $create = (isset($_SESSION['oauth2_create']) && !empty($_SESSION['oauth2_create'])) ? $_SESSION['oauth2_create'] : "new";
                        unset($_SESSION['oauth2_create']);
                        $scannerId = ($response && is_object($response)) ? $response->scannerid : "";

                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            header('Content-Type: application/json');
                            echo json_encode(array('scannerid' => $scannerId));
                            exit;
                        } else {
                            if ($scannerId) {
                                header("Location: {$crmBaseUrl}/oauth2callback/redirect.php?authfor=MailConverter&id=" . $scannerId);
                            } else {
                                header("Location: {$crmBaseUrl}/oauth2callback/redirect.php?authfor=MailConverter");
                            }
                            exit;
                        }
                        break;
                    case "MailManager":
                        header("Location: {$crmBaseUrl}/oauth2callback/redirect.php?authfor=MailManager");
                        break;
                    case "Calendar":
                        header("Location: {$crmBaseUrl}/oauth2callback/redirect.php?authfor=Calendar");
                        break;
                }
            } catch (Exception $e) {
                unset($_SESSION['oauth2for']);
                unset($_SESSION['oauth2state']);
                unset($_SESSION['oauth2svc']);

                header('Content-type: text/plain');
                echo $e->getMessage();
                echo $e->getTraceAsString();
                exit;
            }
        }
    }

    public static function updateTokensFor($config, $oauth2for, $oauth2svc, $userinfo, $tokens, $expireson, $oauth2_account_id = "")
    {
        $db = PearDatabase::getInstance();

        if ($oauth2for == "OutgoingServer") {
            $checkRs = $db->pquery("select 1 from vtiger_systems where server_type = ? limit 1", array("email"));

            $server = "";
            $port = "";
            if (strcasecmp($oauth2svc, "Google") === 0) {
                $port = 465;
                $server = "ssl://smtp.gmail.com:$port";
            } else if (strcasecmp($oauth2svc, "Office365") === 0) {
                $port = 587;
                $server = "tls://smtp.office365.com:$port";
            }

            if ($db->num_rows($checkRs)) {
                // update
                    $db->pquery(
                    "update vtiger_systems set server = ?, server_port = ?, server_username = ?, server_password = ?, smtp_auth = ?, smtp_auth_type = ?, smtp_auth_expireson = ? where server_type = ?",
                    array(
                        $server,
                        $port,
                        $userinfo["email"],
                        Vtiger_Functions::toProtectedText(json_encode($tokens)),
                        1,
                        "XOAUTH2",
                        $expireson,
                        "email"
                    )
                );
            } else {
                $db->pquery(
                    "insert into vtiger_systems (id, server, server_port, server_username, server_password, smtp_auth, smtp_auth_type, smtp_auth_expireson, server_type) values (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    array(
                        $db->getUniqueID("vtiger_systems"),
                        $server,
                        $port,
                        $userinfo["email"],
                        Vtiger_Functions::toProtectedText(json_encode($tokens)),
                        1,
                        "XOAUTH2",
                        $expireson,
                        "email"
                    )
                );
            } return true;
        } else if ($oauth2for == "MailConverter") {
            require_once "modules/Settings/MailConverter/handlers/MailScannerInfo.php";

            $server = strcasecmp($oauth2svc, "Google") === 0 ? "imap.gmail.com" : (strcasecmp($oauth2svc, "Office365") === 0 ? "imap.office365.com" : "");
            $proxy  = $server && isset($config["Proxies"]) && isset($config["Proxies"][$server]) ? $config["Proxies"][$server] : "";

            $scannername = (isset($_SESSION['oauth2_scannername']) && !empty($_SESSION['oauth2_scannername'])) ? $_SESSION['oauth2_scannername'] : $server;
            $recordId = (isset($_SESSION['oauth2_record']) && !empty($_SESSION['oauth2_record'])) ? $_SESSION['oauth2_record'] : "";
            $scannerOldName = (isset($_SESSION['oauth2_scannerOldName']) && !empty($_SESSION['oauth2_scannerOldName'])) ? $_SESSION['oauth2_scannerOldName'] : "";

            unset($_SESSION['oauth2_scannername']);
            unset($_SESSION['oauth2_record']);
            unset($_SESSION['oauth2_scannerOldName']);

            $scanner = new Vtiger_MailScannerInfo(sprintf("%f", microtime(true)));
            $scanner->scannername = $scannername;
            $scanner->server = $server;
            $scanner->protocol = "imap4";
            $scanner->authtype = "XOAUTH2";
            $scanner->authexpireson = $expireson;
            $scanner->mailproxy = $proxy;
            $scanner->username = $userinfo["email"];
            $scanner->password = json_encode($tokens);
            $scanner->ssltype  = "ssl";
            $scanner->sslmethod = "validate-cert";
            $scanner->isvalid = 1;

            if (!empty($recordId)) {
                $scanner->scannerid = $recordId;
                $oldscanner = new Vtiger_MailScannerInfo($scannerOldName, true);
            } else {
                $oldscanner = new Vtiger_MailScannerInfo($scanner->scannername, true);
            }

            // Connect to verify and get connecturl
            require_once "modules/Settings/MailConverter/handlers/MailBox.php";
            $mailBox = new Vtiger_MailBox($scanner);
            if ($mailBox->connect()) {
                $scanner->connecturl = $mailBox->_imapurl;
            }

            $oldscanner->update($scanner); return $scanner;
        } else if ($oauth2for == "MailManager") {

            require_once "modules/MailManager/models/Mailbox.php";

            if (strcasecmp($oauth2svc, "Google") === 0) {
                $server = "imap.gmail.com";
            } else if (strcasecmp($oauth2svc, "Office365") === 0) {
                $server = "imap.office365.com";
            }

            $proxy  = $server && isset($config["Proxies"]) && isset($config["Proxies"][$server]) ? $config["Proxies"][$server] : "";

            if ($server) {
                if ($oauth2_account_id === "") {
                    $mailbox = new MailManager_Mailbox_Model();
                } else {
                    $mailbox = MailManager_Mailbox_Model::activeInstance($oauth2_account_id, 'edit');
                }
                $mailbox->setUsername($userinfo["email"]);
                $mailbox->setServer($server);
                $mailbox->setPassword(json_encode($tokens));
                $mailbox->setAuthType("XOAUTH2");
                $mailbox->setAuthExpiresOn($expireson);
                $mailbox->setProtocol("IMAP4");
                $mailbox->setFolder("INBOX");
                $mailbox->setCertValidate(false);
                $mailbox->setSSLType("SSL");
				
				if (strcasecmp($oauth2svc, "Office365") === 0) {
					$mailbox->setRefreshTimeOut("300000");
				}
				
                if ($proxy) $mailbox->setMailProxy($proxy);
                $mailbox->save();
             } return true;
        } else if ($oauth2for == "Calendar") {
            $log = vglobal('log');
            $log->fatal("Oauth2 updateTokensFor Calendar: " . $oauth2svc);
            if ($oauth2svc == 'Office365') {
                $serviceName = 'Office365Calendar';
                $tableName = 'vtiger_office365_oauth2';
                $userid = $_SESSION['authenticated_user_id'];
                
                $checkRs = $db->pquery("SELECT 1 FROM $tableName WHERE userid = ? AND service = ?", array($userid, $serviceName));
                if ($db->num_rows($checkRs)) {
                    $db->pquery("UPDATE $tableName SET access_token = ?, refresh_token = ? WHERE userid = ? AND service = ?",
                        array(json_encode($tokens), $tokens['refresh_token'], $userid, $serviceName));
                } else {
                    $db->pquery("INSERT INTO $tableName (service, access_token, refresh_token, userid) VALUES (?, ?, ?, ?)",
                        array($serviceName, json_encode($tokens), $tokens['refresh_token'], $userid));
                }
            }
            return true;
        }
    }
}
