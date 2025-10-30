<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/errorlogging.php';


class DatabaseConnector
{
    public $servername = "[Server]";
    public $database = "[Database]";
    public $dbusername = "[Username]";
    public $dbpassword = "[Password]";

    public $conn;

    function __construct()
    {
        try {
            $this->conn = mysqli_connect($this->servername, $this->dbusername, $this->dbpassword, $this->database);
            $this->conn->set_charset("utf8");
        } catch (\Throwable $th) {
            echo $th;
            logError($th->getMessage());
        }
    }
}
