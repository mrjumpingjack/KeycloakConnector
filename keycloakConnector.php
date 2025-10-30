<?php
header("Access-Control-Allow-Origin: *");

function GetKeyCloakAuthToken($URL, $Username, $Password)
{
    $curl = curl_init();

    $Username = urlencode($Username);
    $Password = urlencode($Password);



    $Data = array(
        CURLOPT_URL => $URL . 'protocol/openid-connect/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'client_id=admin-cli&username=' . $Username . '&password=' . $Password . '&grant_type=password',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
    );



    curl_setopt_array($curl, $Data);

    $response = curl_exec($curl);

    curl_close($curl);

    $responseObj = json_decode($response);

    return $responseObj->access_token;
}


function GetKeyCloakGroup($URL, $GroupID, $authToken)
{
    $curl = curl_init();

    $Data = array(
        CURLOPT_URL => $URL . 'groups/' . $GroupID . '/members',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $authToken
        ),
    );


    curl_setopt_array($curl, $Data);

    $response = curl_exec($curl);

    curl_close($curl);
    echo $response;
}



function GetKeyCloakGroups($Domain, $Realm, $authToken)
{
    $curl = curl_init();

    $Data = array(
        CURLOPT_URL => $Domain . '/admin/realms/' . $Realm . '/groups',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $authToken
        ),
    );


    curl_setopt_array($curl, $Data);

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}


function GetKeyCloakGroupUsers($Domain, $Realm, $GroupID, $authToken)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $Domain . '/admin/realms/' . $Realm . "/groups/" . $GroupID . "/members",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $authToken
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    return $response;
}



function GetGroupWhereNameIs($Groups, $Name)
{
    $Groups = json_decode($Groups, true);

    $desiredElement = null;

    foreach ($Groups as $element) {
        if ($element['name'] === $Name) {
            $desiredElement = $element;
            break;
        }
    }

    if ($desiredElement !== null) {
        return json_encode($desiredElement);
    } else {
        echo "";
    }
}


function GetAllUsersFromGroup($URL, $Domain, $Realm, $GroupName)
{
    $authToken = GetKeyCloakAuthToken($URL, "robot", "Jphjc6raCp2KVCZ@");


    $Groups = GetKeyCloakGroups($Domain, $Realm, $authToken);

    $Group = GetGroupWhereNameIs($Groups, $GroupName);

    $GroupID = json_decode($Group)->id;

    $users = GetKeyCloakGroupUsers($Domain, $Realm, $GroupID, $authToken);

    return json_decode($users);
}


function GetKeyCloakUserDetails($URL, $AccessToken) {
    $curl = curl_init();

    $Data = array(
        CURLOPT_URL => $URL . 'protocol/openid-connect/userinfo',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $AccessToken
        ),
    );

    curl_setopt_array($curl, $Data);

    $response = curl_exec($curl);

    curl_close($curl);

    $userDetails = json_decode($response);

    return $userDetails;
}

