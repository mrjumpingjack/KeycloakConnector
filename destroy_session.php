<?php

require './authprovider.php';
require './ClientInfo.php';

$url = urldecode($PostLogOutRedirectUri);
$backurl = $KeyCloakLoginURL."realms/".$KeyCloakRealm."/protocol/openid-connect/logout?post_logout_redirect_uri=" . $url .
    "&id_token_hint=" .  $_SESSION["id_token"];

header('Location: ' . $backurl);
