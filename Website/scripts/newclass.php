<?php
if(!$LoggedIn) {
    throw new Exception("User has to be logged in in order to access this page.", ERR_PERMISSION_NOTAUTH);
}

//TODO: Form token

if(isset($_POST['newClassForm'])) {
    $errorArray = array();

    // Check form integrity
    $formFields = array(
        'name',
    );

    $entireForm = true;
    foreach($formFields as $field)
        if(!isset($_POST[$field])) {
            $entireForm = false;
            $errorArray[] = ERR_NEWCLASS_INTEGRITYCHECK_FAILED;
        }

    $Class = new StudentClass($DBCon);

    if($entireForm) {
        // Check for invalid inputs
        if(empty($_POST['name']))
            $errorArray[] = ERR_NEWCLASS_NAME_NOTPROVIDED;

        if(strlen($_POST['name']) > 32)
            $errorArray[] = ERR_NEWCLASS_NAME_TOOLONG;

        if(empty($errorArray)) {
            // SQL Escaping
            $Name = $DBCon->real_escape_string($_POST['name']);

            $Class->Create($Name);
            $Class->AddTeacher($User);
        }
    }

    if(empty($errorArray)) {
        $scriptOut['CreateStatus'] = 'OK';
        $scriptOut['ClassID'] = $Class->GetID();
        $scriptOut['ClassAccessKey'] = $Class->GetAccessKey();
    } else {
        $scriptOut['CreateStatus'] = 'FAIL';
        $scriptOut['CreateErrorArray'] = $errorArray;
    }
}