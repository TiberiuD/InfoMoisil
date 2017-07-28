<?php
	// Include scripts needed at run-time
	$filesToInclude = array(
	    'const',
		'config',
		'functions',
		'file.class',
		'user.class',
		'permissions.class',
		'problem.class',
		'studentclass.class',
		'homework.class',
		'Twig/Autoloader',
	);
	
	foreach($filesToInclude as $curFile) {
		$file = 'include/' . $curFile . '.php';
		if(file_exists($file)) {
			include($file);
		} else {
			die('Failed to include file "' . $curFile . '".');
		}
	}
	
	// Connect to the database
	$DBCon = new mysqli($DBInfo['host'], $DBInfo['username'], $DBInfo['password'], $DBInfo['database']); 
	if($DBCon->connect_error) {
		// TODO: Handle this nicer
		die('Connect Error (' . $DBCon->connect_errno . ') ' . $DBCon->connect_error);
	}
	
	if (!$DBCon -> set_charset("utf8")) {
		printf("Error loading character set utf8: %s\n", $DBCon -> error);
		exit();
	}
		
	// Start the session
	session_start();
	
	if(isset($_GET['ajax'])) {
		$LoggedIn = ($_SESSION != array());
		
		$allowedTypes = array(
			'get_notifications',
			'read_notification',
		);
		
		if(isset($_GET['type']) && in_array($_GET['type'], $allowedTypes)) {
			$type = $_GET['type'];
			include("ajax/$type.php");
		}

		exit();
	}
	
    // Declare output data holder as an array
    $scriptOut = array();

    $User = new User($DBCon);

	// TODO: Move this elsewhere
    // Log in the user if needed
    if(isset($_POST['loginForm']) && isset($_POST['email']) && isset($_POST['password'])) {
		$_SESSION = array();
		$scriptOut['isLogin'] = true;

		$User -> setEmail($DBCon -> real_escape_string($_POST['email']));
		$User -> setPassword($DBCon -> real_escape_string($_POST['password']));
		
		$Login = $User -> retrieveUserData();
		
		if($Login === true) {
			$_SESSION = array_merge($_SESSION, $User -> getDataArray());

			// Set the session cookie
			if(isset($_POST['remember'])) {
			    $CookieToken = $User->getAuthToken();
			    setcookie("UserCookie", $CookieToken, time() + 30 * 24 * 60 * 60,
                    "/", $WebsiteInfo['domain'], true);
            }
		} elseif($Login === ERR_LOGIN_NOTFOUND)
			$scriptOut['loginError'] = ERR_LOGIN_NOTFOUND;
	}

    // Log in by cookie
    if($_SESSION == array() && isset($_COOKIE['UserCookie'])) {
        $stmt = $DBCon -> stmt_init();

        $stmt -> prepare('
				SELECT		`user_id`
				FROM		`authentication_tokens` 
				WHERE		`token` = ?
				AND			`expire_date` > NOW()
			');

        if(!$stmt -> bind_param('s', $_COOKIE['UserCookie']))
            throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

        if(!$stmt -> execute())
            throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);

        $stmt->store_result();

        if($stmt -> num_rows == 0)
            throw new Exception("Invalid cookie token!");

        $UserID = 0;
        $stmt->bind_result($UserID);
        $stmt->fetch();

        $User->setID($UserID);

        $Login = $User->retrieveUserData(true);
        if($Login !== true)
            die('User cookie login failed!');

        $_SESSION = array_merge($_SESSION, $User -> getDataArray());
    }

	// Log out
	if(isset($_GET["logout"])) {
        $_SESSION = array();

        if (isset($_COOKIE['UserCookie'])) {
            unset($_COOKIE['UserCookie']);
            setcookie('UserCookie', '', time() - 3600,
                '/', $WebsiteInfo['domain'], true);
        }
    }
	
	$LoggedIn = ($_SESSION != array());
	
	if($LoggedIn) {
		$scriptOut['avatarURL'] = get_gravatar($_SESSION['email'], 160);
		
		$User -> setID($_SESSION['id']);
		
		$retrieve = $User -> retrieveUserData(true);
		
		if($retrieve !== true)
			die('User update failed!');
		
		$_SESSION = array_merge($_SESSION, $User -> getDataArray());
	}
		
	// Include required script & template
	
	// Is a certain page requested?
	if(isset($_GET['p']) && $_GET['p'] != "index") {
		$page = $_GET['p'];
	} else {
		//$page = ($LoggedIn) ? 'home' : 'info';
		$page = 'home';
	}
	
	// Append the website information to the output array
	$scriptOut['WebsiteInfo'] = $WebsiteInfo;
	$scriptOut['LoggedIn'] = $LoggedIn;
	$scriptOut['Session'] = $_SESSION;

	try {
		// Include required PHP script, if any
		// TODO: Prevent accesing access to sysfiles
		if(file_exists("scripts/$page.php")) {
            include("scripts/$page.php");
        } if(file_exists("scripts/$page/default.php")) {
            include("scripts/$page/default.php");
            $page .= "/default";
		} elseif(!file_exists("templates/$page.twig") && !file_exists("templates/$page/default.twig")) {
			throw new Exception("Script $page not found!", ERR_PAGE_NOTFOUND);
		}

        $template = $page;
	} catch(Exception $e) {
		if(isset($WebsiteInfo['debug']) && $WebsiteInfo['debug'] == true)
			$scriptOut['DebugMessage'] = $e->getMessage();
		$scriptOut['ErrorCode'] = $e->getCode();
		$template = 'error';
	}
	
	// Refresh the output info, in case of updates in the scripts
	if($LoggedIn) {
		$retrieve = $User -> retrieveUserData(true);
		
		if($retrieve !== true)
			die('User update failed!');
		
		$_SESSION = array_merge($_SESSION, $User -> getDataArray());
		$scriptOut['Session'] = $_SESSION;

        // Get various homework information
        $UnpublishedHomeworks = GetUnpublishedHomeworks($User);
        foreach($UnpublishedHomeworks as $Key => $Homework) {
            $HomeworkObject = new Homework($DBCon);
            $HomeworkObject->SetID($Homework['ID']);

            $ProblemObjects = $HomeworkObject->GetProblems();
            $Problems = array();

            foreach($ProblemObjects as $Problem) {
                $Info = $Problem->getProblemInfoArray();

                $Problems[] = array(
                    "ID" => $Problem->getProblemID(),
                    "Name" => $Info['name']
                );
            }

            $UnpublishedHomeworks[$Key]['Problems'] = $Problems;
        }
        $scriptOut['UnpublishedHomeworks'] = $UnpublishedHomeworks;

        $Homeworks = GetHomeworks($User);
        foreach($Homeworks as $Key => $Homework) {
            $HomeworkObject = new Homework($DBCon);
            $HomeworkObject->SetID($Homework['ID']);

            $ProblemObjects = $HomeworkObject->GetProblems();
            $Problems = array();

            foreach($ProblemObjects as $Problem) {
                $Info = $Problem->getProblemInfoArray();
                $Stats = $Problem->getUserSolutionStats($User->getID());

                $Problems[] = array(
                    "ID" => $Problem->getProblemID(),
                    "Name" => $Info['name'],
                    "Solutions" => $Stats['solutionDoneCount'],
                    "Solved" => ($Stats['maxScore'] == 100)
                );
            }

            $Homeworks[$Key]['Problems'] = $Problems;
        }
        $scriptOut['Homeworks'] = $Homeworks;

        // Is the current user a teacher?
        $scriptOut['IsTeacher'] = IsTeacher($User);
	}
	
	// Include required Twig template
	if(file_exists('templates/' . $template . '.twig')) {
		Twig_Autoloader::register();
		$loader = new Twig_Loader_Filesystem('templates/');
		$twig = new Twig_Environment($loader, array(
			'debug' => true,
			//'cache' => 'templates_c/',
		));
		$twig->addExtension(new Twig_Extension_Debug());
		
		echo $twig->render($template . '.twig', $scriptOut);
	} else {
		die('Template "' . $template . '" not found!');
	}
?>