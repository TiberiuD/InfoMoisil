<?php
if(!$LoggedIn) {
    throw new Exception("User has to be logged in in order to access this page.", ERR_PERMISSION_NOTAUTH);
}

//TODO: Form token

if(isset($_POST['joinClassForm'])) {
    $errorArray = array();

    // Check form integrity
    $formFields = array(
        'access_key',
    );

    $entireForm = true;
    foreach($formFields as $field)
        if(!isset($_POST[$field])) {
            $entireForm = false;
            $errorArray[] = ERR_JOINCLASS_INTEGRITYCHECK_FAILED;
        }

    $Class = new StudentClass($DBCon);

    if($entireForm) {
        // Check for invalid inputs
        if(empty($_POST['access_key']))
            $errorArray[] = ERR_JOINCLASS_ACCESSKEY_NOTPROVIDED;

        if(!preg_match("/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{4}$/", $_POST['access_key']))
            $errorArray[] = ERR_JOINCLASS_ACCESSKEY_NOTVALID;

        if(empty($errorArray)) {
            // SQL Escaping
            $AccessKey = $DBCon->real_escape_string($_POST['access_key']);

            $Join = $Class->JoinWithAccessKey($User, $AccessKey);
            if($Join === false)
                $errorArray[] = ERR_JOINCLASS_KEY_NOTEXISTS;
        }
    }

    if(empty($errorArray)) {
        $scriptOut['CreateStatus'] = 'OK';
        $scriptOut['ClassID'] = $Class->GetID();
    } else {
        $scriptOut['CreateStatus'] = 'FAIL';
        $scriptOut['CreateErrorArray'] = $errorArray;
    }
}