<?php
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/databaseConnector.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/errorlogging.php';
require './authprovider.php';
require './keycloakConnector.php';
// require_once './Mail.php';
// require_once './Push.php';
require_once './ClientInfo.php';


require_once './Handlers/UserHandler.php';
require_once './Handlers/Utils.php';
// require_once './Handlers/NotificationHandler.php';
// require_once './config/Translator.php';

// $config = require_once "./config/config.php";

if (!isset($_SESSION)) {
    session_start();
    logError("SESSION CREATED BY DATACONTROLLER WHICH IS MAYBE NOT GOOD");
}

if (!isset($_SESSION["lang"])) {
    SetSessionLang($config["default_lang"]);
    logError("SESSION LANGUAGE WAS SET TO " .  $config["default_lang"]);
}

if (!$_SESSION["UserID"]) {
    $UserID = GetUserIDFromAuth();
    $_SESSION["UserID"] = $UserID;

    //logError('$_SESSION["UserID"]: ' . $_SESSION["UserID"]);
}


if (isset($_POST['Mode'])) {
    $Mode = $_POST['Mode'];
    $args = $_POST;
    unset($args['Mode']);

    $cleanargs = [];
    foreach ($args as $arg) {
        $cleanargs[] = decodeHtmlEntities(filter_var($arg, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }

    $args = $cleanargs;

    try {
        call_user_func_array($Mode, $args);
    } catch (\Throwable $th) {
        echo json_encode(array("status" => "error", "message" => $th->getMessage()));
    }
}


function SetSessionLang($lang)
{
    $_SESSION["lang"] = $lang;
}
