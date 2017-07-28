<?php

/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param boole $img True to return a complete IMG tag False for just the URL
 * @param array $atts Optional, additional key/value attributes to include in the IMG tag
 * @return String containing either just a URL or a complete image tag
 * @source http://gravatar.com/site/implement/images/php/
 */
function get_gravatar( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {

    return functiemisto("testerinonuammailulasta@debara.ro", $s, $d, $r, $img, $atts);
}

function functiemisto( $email, $s = 80, $d = 'mm', $r = 'g', $img = false, $atts = array() ) {
    $url = 'https://www.gravatar.com/avatar/';
    $url .= md5( strtolower( trim( $email ) ) );
    $url .= "?s=$s&d=$d&r=$r";
    if ( $img ) {
        $url = '<img src="' . $url . '"';
        foreach ( $atts as $key => $val )
            $url .= ' ' . $key . '="' . $val . '"';
        $url .= ' />';
    }
    return $url;
}


function RandomKey() {
    $DigitGroups[] = array();

    for($i = 0; $i < 4; $i++) {
        $RandomBytes = unpack("cchars/nint", openssl_random_pseudo_bytes(4));
        $DigitGroups[$i] = $RandomBytes['int'] % 10000;
    }

    $Key = sprintf("%04d-%04d-%04d-%04d",
        $DigitGroups[0],
        $DigitGroups[1],
        $DigitGroups[2],
        $DigitGroups[3]
        );

    return $Key;
}

function GetUnpublishedHomeworks($User) {
    if(!$User instanceof User)
        throw new Exception("Provided user is not an instance of User");

    global $DBCon;

    $stmt = $DBCon->stmt_init();

    $stmt->prepare("
        SELECT
            `homeworks`.`id`,
            `homeworks`.`name`,
            `classes`.`id` AS 'class_id',
            `classes`.`name` AS 'class_name'
        FROM
            `homeworks`
        LEFT JOIN `classes` ON `homeworks`.`class_id` = `classes`.`id`
        LEFT JOIN `class_members` ON `classes`.`id` = `class_members`.`class_id`
        WHERE
            `class_members`.`status` = '" . StudentClass::STATUS_TEACHER . "'
        AND `homeworks`.`published` = '" . Homework::STATUS_UNPUBLISHED . "'
        AND `homeworks`.`deadline` > NOW()
        AND `class_members`.`user_id` = ?;
    ");

    if (!$stmt->bind_param('i', $User->getID()))
        throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

    if (!$stmt->execute())
        throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

    $stmt->store_result();

    $Homeworks = array();

    $HomeworkID = 0;
    $HomeworkName = "";
    $ClassID = 0;
    $ClassName = "";

    $stmt->bind_result(
        $HomeworkID,
        $HomeworkName,
        $ClassID,
        $ClassName
    );

    while($stmt->fetch()) {
        $Homeworks[] = array(
            "ID" => $HomeworkID,
            "Name" => $HomeworkName,
            "ClassID" => $ClassID,
            "ClassName" => $ClassName
        );
    }

    $stmt->close();

    return $Homeworks;
}

function GetHomeworks($User) {
    if(!$User instanceof User)
        throw new Exception("Provided user is not an instance of User");

    global $DBCon;

    $stmt = $DBCon->stmt_init();

    $stmt->prepare("
        SELECT
            `homeworks`.`id`,
            `homeworks`.`name`,
            `homeworks`.`deadline`,
            `classes`.`id` AS 'class_id',
            `classes`.`name` AS 'class_name'
        FROM
            `homeworks`
        LEFT JOIN `classes` ON `homeworks`.`class_id` = `classes`.`id`
        LEFT JOIN `class_members` ON `classes`.`id` = `class_members`.`class_id`
        WHERE
            `class_members`.`status` = '" . StudentClass::STATUS_STUDENT . "'
        AND `homeworks`.`published` = '" . Homework::STATUS_PUBLISHED . "'
        AND `homeworks`.`deadline` > NOW()
        AND `class_members`.`user_id` = ?;
    ");

    if (!$stmt->bind_param('i', $User->getID()))
        throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

    if (!$stmt->execute())
        throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

    $stmt->store_result();

    $Homeworks = array();

    $HomeworkID = 0;
    $HomeworkName = "";
    $HomeworkDeadline = "";
    $ClassID = 0;
    $ClassName = "";

    $stmt->bind_result(
        $HomeworkID,
        $HomeworkName,
        $HomeworkDeadline,
        $ClassID,
        $ClassName
    );

    while($stmt->fetch()) {
        $Homeworks[] = array(
            "ID" => $HomeworkID,
            "Name" => $HomeworkName,
            "Deadline" => $HomeworkDeadline,
            "ClassID" => $ClassID,
            "ClassName" => $ClassName
        );
    }

    $stmt->close();

    return $Homeworks;
}

function IsTeacher($User) {
    global $DBCon;

    $stmt = $DBCon->stmt_init();

    $stmt->prepare("
				SELECT	COUNT(`user_id`)
				FROM	`class_members` 
				WHERE	`user_id` = ?
				AND     `status` = '" . StudentClass::STATUS_TEACHER . "';
			");

    if (!$stmt->bind_param('i', $User->getID()))
        throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

    if (!$stmt->execute())
        throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

    $stmt->store_result();

    if ($stmt->num_rows == 0)
        throw new Exception('Unexpected null result on COUNT query.');

    $CountResult = 0;

    $stmt->bind_result(
        $CountResult
    );

    $stmt->fetch();
    $stmt->close();

    return ($CountResult != 0);
}