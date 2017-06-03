<?php
	$outArray = array();
	
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
	
	$outArray = $User->GetNotifications();
	
	echo json_encode($outArray);