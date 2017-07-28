<?php
/**
 * Created by PhpStorm.
 * User: Tiberiu
 * Date: 7/25/2017
 * Time: 4:45 PM
 */

if(!$LoggedIn) {
    throw new Exception("User has to be logged in in order to access this page.", ERR_PERMISSION_NOTAUTH);
}

function getClasses($DBCon, $User) {
    $stmt = $DBCon -> stmt_init();

    $stmt -> prepare('
            SELECT
                `classes`.`id`,
                `classes`.`name`,
                `class_members`.`status`
            FROM
                `classes`
            LEFT JOIN `class_members` ON `classes`.`id` = `class_members`.`class_id`
            WHERE
                `class_members`.`user_id` = ?;
        ');

    if(!$stmt -> bind_param('i', $User->getID()))
        throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

    if(!$stmt -> execute())
        throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);

    $ClassesArray = array();

    $ClassID = 0;
    $ClassName = "";
    $UserStatus = "";

    $stmt -> bind_result(
        $ClassID,
        $ClassName,
        $UserStatus);

    while($stmt -> fetch()) {
        $ClassesArray[] = array(
            'ID'				=> $ClassID,
            'Name'				=> $ClassName,
            'Status'			=> $UserStatus
        );
    }

    $stmt -> close();

    return $ClassesArray;
}

$scriptOut['UserClasses'] = getClasses($DBCon, $User);