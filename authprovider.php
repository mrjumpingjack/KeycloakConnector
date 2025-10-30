<?php
require './ClientInfo.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/errorlogging.php';
ini_set('session.cookie_domain', $CookieDomain);

require 'vendor/autoload.php';

use League\OAuth2\Client\Provider\GenericProvider;

// Starte die Sitzung
//session_start();

if (!isset($_SESSION)) {
    session_start();
}


// Erstelle den OpenID Connect Provider
$provider = new GenericProvider([
    'clientId'                => $clientId,    // Aus ClientInfo.php
    'clientSecret'            => $clientSecret, // Aus ClientInfo.php
    'redirectUri'             => $redirectUri,  // Aus ClientInfo.php
    'urlAuthorize'            => $issuer . '/protocol/openid-connect/auth',
    'urlAccessToken'          => $issuer . '/protocol/openid-connect/token',
    'urlResourceOwnerDetails' => $issuer . '/protocol/openid-connect/userinfo',
    'scopes'                  => 'openid profile email', // Füge 'email' hinzu, falls benötigt
]);

// Logging der GET-Parameter
$ParametersString = '';
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        $ParametersString .= "Key: " . htmlspecialchars($key) . " - Value: " . htmlspecialchars($value) . "\n";
    }
} else {
    $ParametersString = "No GET parameters found.";
}
// logError($ParametersString);

// Logging der SESSION-Parameter
$ParametersString = '';
if (!empty($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        $ParametersString .= "Key: " . htmlspecialchars($key) . " - Value: " . htmlspecialchars($value) . "\n";
    }
} else {
    $ParametersString = "No SESSION parameters found.";
}
// logError($ParametersString);

if (!isset($_SESSION['access_token']) && !isset($_GET['code'])) {
    // Der Benutzer ist nicht authentifiziert, leite ihn zur Keycloak-Authentifizierung weiter
    // logError("Der Benutzer ist nicht authentifiziert, leite ihn zur Keycloak-Authentifizierung weiter");

    $authorizationUrl = $provider->getAuthorizationUrl();
    header('Location: ' . $authorizationUrl);
    exit;
} elseif (isset($_GET['code'])) {
    try {
        // Tausche den Autorisierungscode gegen einen Access Token ein
        // logError("Der Benutzer ist zurückgekehrt und wir haben einen Autorisierungscode erhalten, tausche diesen gegen einen Access Token ein.");

        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code'],
        ]);

        // Speichere den Access Token und den Refresh Token in der Sitzung
        $_SESSION['code'] = $_GET['code'];
        $_SESSION['access_token'] = $accessToken->getToken();
        $_SESSION['refresh_token'] = $accessToken->getRefreshToken();
        $_SESSION['id_token'] = $accessToken->getValues()['id_token'];

        // Extrahiere Benutzerinformationen aus dem Access Token
        $_SESSION["username"] = getUsernameFromAccessToken($_SESSION['access_token']);
        $_SESSION["sub"] = getUserIDFromAccessToken($_SESSION['access_token']);

        // logError("Access Token erfolgreich erhalten und in der Sitzung gespeichert.");

    } catch (Exception $e) {
        // Fehlerbehandlung
        $msg = $e->getMessage();
        // logError("Fehler beim Token-Austausch: " . $msg);
        echo 'Error: ' . htmlspecialchars($msg);
        exit;
    }
} elseif (isset($_SESSION['access_token'])) {
    // Überprüfe, ob das Access Token noch gültig ist
    $accessToken = $_SESSION['access_token'];
    if (validateAccessToken($issuer, $accessToken, $clientId, $clientSecret)) {
        // Aktualisiere Benutzerinformationen in der Sitzung
        $_SESSION["username"] = getUsernameFromAccessToken($accessToken);
        $_SESSION["sub"] = getUserIDFromAccessToken($accessToken);

        // logError("Die Session ist noch gültig.");
    } else {
        // Das Access Token ist nicht mehr gültig, versuche es zu erneuern
        // logError("Access Token ist abgelaufen oder ungültig, versuche es zu erneuern.");

        if (isset($_SESSION['refresh_token'])) {
            $newAccessToken = refreshAccessToken($provider, $_SESSION['refresh_token']);
            if ($newAccessToken !== false) {
                // Aktualisiere das Access Token und den Refresh Token in der Sitzung
                $_SESSION['access_token'] = $newAccessToken->getToken();
                $_SESSION['refresh_token'] = $newAccessToken->getRefreshToken();
                $_SESSION['id_token'] = $newAccessToken->getValues()['id_token'];

                // Aktualisiere Benutzerinformationen
                $_SESSION["username"] = getUsernameFromAccessToken($_SESSION['access_token']);
                $_SESSION["sub"] = getUserIDFromAccessToken($_SESSION['access_token']);

                // logError("Access Token erfolgreich erneuert.");
            } else {
                // logError("Konnte Access Token nicht erneuern, Benutzer muss sich erneut authentifizieren.");
                logoutAndRedirect();
            }
        } else {
            // logError("Kein Refresh Token verfügbar, Benutzer muss sich erneut authentifizieren.");
            logoutAndRedirect();
        }
    }
} else {
    // Weder Access Token noch Code vorhanden, leite zur Startseite weiter
    logoutAndRedirect();
}


// Funktion zum Überprüfen der Gültigkeit des Zugriffstokens bei Keycloak
function validateAccessToken($issuer, $accessToken, $clientId, $clientSecret)
{
    $tokenEndpoint = $issuer . '/protocol/openid-connect/token/introspect';

    $data = http_build_query([
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'token' => $accessToken,
    ]);

    // Führe den Introspect-Aufruf durch
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $data,
        ],
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($tokenEndpoint, false, $context);

    // Verarbeite die Antwort
    if ($result !== false) {
        $response = json_decode($result, true);
        // logError("Introspect Response: " . print_r($response, true));

        if (isset($response['active']) && $response['active'] === true) {
            return true;
        } else {
            // logError("Token ist nicht aktiv oder ungültig.");
            return false;
        }
    } else {
        $error = error_get_last();
        // logError("Failed to introspect token: " . $error['message']);
        return false;
    }
}

// Funktion zum Erneuern des Access Tokens mit dem Refresh Token
function refreshAccessToken($provider, $refreshToken)
{
    try {
        $newAccessToken = $provider->getAccessToken('refresh_token', [
            'refresh_token' => $refreshToken,
        ]);
        // logError("Refresh Token erfolgreich verwendet.");
        return $newAccessToken;
    } catch (Exception $e) {
        // logError("Fehler beim Verwenden des Refresh Tokens: " . $e->getMessage());
        return false;
    }
}

// Funktion zum Ausloggen und Weiterleiten
function logoutAndRedirect()
{
    global $CookieDomain;
    // Entferne spezifische Session-Daten
    unset($_SESSION['access_token']);
    unset($_SESSION['id_token']);
    unset($_SESSION['refresh_token']);
    unset($_SESSION['code']);
    unset($_SESSION['username']);
    unset($_SESSION['sub']);

    // Session zerstören
    session_destroy();

    // Cookies entfernen, falls vorhanden
    if (isset($_COOKIE['domainauth'])) {
        setcookie('domainauth', '', time() - 3600, '/', $CookieDomain);
    }

    // Benutzer zurück zur Startseite leiten
    global $BaseURL;
    header('Location: ' . $BaseURL);
    exit;
}

// Funktion zum Extrahieren des Benutzernamens aus dem Zugriffstoken
function getUsernameFromAccessToken($accessToken)
{
    $tokenData = getTokenPayload($accessToken);
    // logError("Token Payload for Username: " . print_r($tokenData, true));

    if (isset($tokenData['name'])) {
        return $tokenData['name'];
    } else {
        return null;
    }
}

// Funktion zum Extrahieren der Benutzer-ID aus dem Zugriffstoken
function getUserIDFromAccessToken($accessToken)
{
    $tokenData = getTokenPayload($accessToken);
    // logError("Token Payload for UserID: " . print_r($tokenData, true));

    if (isset($tokenData['sub'])) {
        return $tokenData['sub'];
    } else {
        return null;
    }
}

// Hilfsfunktion zum Extrahieren des Payloads aus dem JWT
function getTokenPayload($accessToken)
{
    $tokenParts = explode('.', $accessToken);
    if (count($tokenParts) !== 3) {
        return null;
    }

    $tokenPayload = base64_decode(strtr($tokenParts[1], '-_', '+/'));
    return json_decode($tokenPayload, true);
}
