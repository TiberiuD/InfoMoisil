<?php
	$outArray = array();
	$outArray['status'] = 'error';
	
	if(!$LoggedIn) {
		echo json_encode($outArray);
		exit();
	}
	
	$User = new User($DBCon);
	$User -> setID($_SESSION['id']);
	
	$retrieve = $User -> retrieveUserData(true);
	
	if($retrieve !== true) {
		echo json_encode($outArray);
		exit();
	}
	
	$_SESSION = array_merge($_SESSION, $User -> getDataArray());
	
	try {
		$outArray = $User->ReadNotification($_GET["id"]);
		$outArray['status'] = "success";
	} catch(Exception $e) {
		echo json_encode($outArray);
		exit();
	}

	echo json_encode($outArray);