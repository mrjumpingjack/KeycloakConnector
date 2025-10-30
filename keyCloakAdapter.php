<?php

header("Access-Control-Allow-Origin: *");


require './authprovider.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/errorlogging.php';

require './keycloakConnector.php';
require_once './ClientInfo.php';

if (!isset($_SESSION)) {
    session_start();
}

if (isset($_POST['Mode'])) {
    $Mode = $_POST['Mode'];
    $args = $_POST;
    unset($args['Mode']);

    $cleanargs = [];
    foreach ($args as $arg) {
        $cleanargs[] = filter_var($arg, FILTER_SANITIZE_STRING);
    }

    $args = $cleanargs;

    try {
        call_user_func_array($Mode, $args);
    } catch (\Throwable $th) {
        echo json_encode(array("status" => "error", "message" => $th->getMessage()));
    }
}
else
{
    echo json_encode(array("status" => "error", "message" => "No Mode specified"));
}

function CheckLoggedIn()
{
    echo json_encode(["Status" => "1"]);
}

function GetUserInfo()
{
    global $KeyCloakLoginURL;
    global $KeyCloakRealm;
    $realm = $KeyCloakRealm;

    $Domain = $KeyCloakLoginURL;
    $URL = $Domain . "realms/" . $realm . "/";

    $result = GetKeyCloakUserDetails($URL, $_SESSION['access_token']);
    echo json_encode(["UserInfo" => $result]);
}
