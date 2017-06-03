<?php
	//TODO: Form token
	
	if(isset($_POST['registerForm'])) {
		$errorArray = array();
		
		// Check form integrity
		$formFields = array(
			'email',
			'password',
			'name'
		);
		
		$entireForm = true;
		foreach($formFields as $field)
			if(!isset($_POST[$field])) {
				$entireForm = false;
				$errorArray[] = ERR_REGISTER_INTEGRITYCHECK_FAILED;
			}
		
		if($entireForm) {
			// Check for invalid inputs
			if(empty($_POST['email']))
				$errorArray[] = ERR_REGISTER_EMAIL_NOTPROVIDED;
			
			if(empty($_POST['password']))
				$errorArray[] = ERR_REGISTER_PASSWORD_NOTPROVIDED;
				
			if(empty($_POST['name']))
				$errorArray[] = ERR_REGISTER_NAME_NOTPROVIDED;
			
			if(strlen($_POST['name']) > 32)
				$errorArray[] = ERR_REGISTER_NAME_TOOLONG;
			
			if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
				$errorArray[] = ERR_REGISTER_EMAIL_NOTVALID;
			
			// SQL Escaping
			$email = $DBCon -> real_escape_string($_POST['email']);
			$password = $DBCon -> real_escape_string($_POST['password']);
			$name = $DBCon -> real_escape_string($_POST['name']);
		}
		
		$user = new User($DBCon);
		$user -> setPassword($password);
		$user -> setEmail($email);
		$user -> setName($name);
		
		if($user -> isEmailInUse())
			$errorArray[] = ERR_REGISTER_EMAIL_INUSE;
		
		if(empty($errorArray)) {
			// Register
			
			if($user -> register())
				$scriptOut['RegisterStatus'] = 'OK';
			else {
				$scriptOut['RegisterStatus'] = 'FAIL';
				$errorArray[] = ERR_REGISTER_FAILED;
			}
		} else {
			$scriptOut['RegisterStatus'] = 'FAIL';
			$scriptOut['RegisterErrorArray'] = $errorArray;
		}
	}