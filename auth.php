<?php

require 'vendor/autoload.php';

use League\OAuth2\Client\Provider\GenericProvider;

require './ClientInfo.php';

// Erstelle den OpenID Connect Provider
$provider = new GenericProvider([
	'clientId'                => $clientId,
	'clientSecret'            => $clientSecret,
	'redirectUri'             => $redirectUri,
	'urlAuthorize'            => $issuer . '/protocol/openid-connect/auth',
	'urlAccessToken'          => $issuer . '/protocol/openid-connect/token',
	'urlResourceOwnerDetails' => $issuer . '/protocol/openid-connect/userinfo',
	'scopes'                  => 'openid profile', // Scopes, die du anfordern möchtest
]);

// Überprüfe, ob der Benutzer bereits authentifiziert ist
//session_start();

if (!isset($_SESSION)) {
    session_start();
}



if (!isset($_SESSION['access_token']) && !isset($_GET['code'])) {
	// Der Benutzer ist nicht authentifiziert, leite ihn zur Keycloak-Authentifizierung weiter
	$authorizationUrl = $provider->getAuthorizationUrl();
	header('Location: ' . $authorizationUrl);
	exit;
} elseif (isset($_GET['code'])) {
	try {
		// Der Benutzer ist zurückgekehrt und wir haben einen Autorisierungscode erhalten, tausche diesen gegen einen Access Token ein
		$accessToken = $provider->getAccessToken('authorization_code', [
			'code' => $_GET['code'],
		]);

		// Speichere den Access Token in der Sitzung
		$_SESSION['access_token'] = $accessToken->getToken();
		$_SESSION['id_token']= $accessToken->getValues()['id_token'];
		$_SESSION['refresh_token'] = $accessToken->getRefreshToken();
		
		
		// Leite den Benutzer auf die Startseite weiter oder führe andere Aktionen aus
		header('Location: '. $BaseURL);


		exit;
	} catch (Exception $e) {
		// Fehlerbehandlung
		echo 'Error: ' . $e->getMessage();
		exit;
	}
}