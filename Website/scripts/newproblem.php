<?php
	//TODO: Form token
	
	if(isset($_POST['newProblemForm'])) {
		$errorArray = array();
		
		// Check form integrity
		$formFields = array(
			'name',
			'name_clean',
			'author'
		);
		
		$entireForm = true;
		foreach($formFields as $field)
			if(!isset($_POST[$field])) {
				$entireForm = false;
				$errorArray[] = ERR_NEWPROBLEM_INTEGRITYCHECK_FAILED;
			}
		
		$problem = new Problem($DBCon);
		
		if($entireForm) {
			// Check for invalid inputs
			if(empty($_POST['name']))
				$errorArray[] = ERR_NEWPROBLEM_NAME_NOTPROVIDED;
			
			if(empty($_POST['name_clean']))
				$errorArray[] = ERR_NEWPROBLEM_NAME_CLEAN_NOTPROVIDED;
				
			if(empty($_POST['author']))
				$errorArray[] = ERR_NEWPROBLEM_AUTHOR_NOTPROVIDED;
			
			if(strlen($_POST['name']) > 32)
				$errorArray[] = ERR_NEWPROBLEM_NAME_TOOLONG;

			if(strlen($_POST['name_clean']) > 32)
				$errorArray[] = ERR_NEWPROBLEM_NAME_CLEAN_TOOLONG;

			if(!preg_match('/^[a-z0-9_]+$/u', $_POST['name_clean']))
				$errorArray[] = ERR_NEWPROBLEM_NAME_CLEAN_NOTVALID;

			if(strlen($_POST['author']) > 32)
				$errorArray[] = ERR_NEWPROBLEM_AUTHOR_TOOLONG;
			
			// SQL Escaping
			$name = $DBCon -> real_escape_string($_POST['name']);
			$name_clean = $DBCon -> real_escape_string($_POST['name_clean']);
			$author = $DBCon -> real_escape_string($_POST['author']);

			$problem -> create($name, $name_clean, $author);
		}

		if(empty($errorArray)) {
			$scriptOut['CreateStatus'] = 'OK';
			$scriptOut['ProblemID'] = $problem -> getProblemID();
		} else {
			$scriptOut['CreateStatus'] = 'FAIL';
			$scriptOut['CreateErrorArray'] = $errorArray;
		}
	}