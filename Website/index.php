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
	
	// TODO: Move this elsewhere
    // Log in the user if needed
    if(isset($_POST['loginForm']) && isset($_POST['email']) && isset($_POST['password'])) {
		$_SESSION = array();
		$scriptOut['isLogin'] = true;
		
		$User = new User($DBCon);
		$User -> setEmail($DBCon -> real_escape_string($_POST['email']));
		$User -> setPassword($DBCon -> real_escape_string($_POST['password']));
		
		$Login = $User -> retrieveUserData();
		
		if($Login === true) {
			$_SESSION = array_merge($_SESSION, $User -> getDataArray());
		} elseif($Login === ERR_LOGIN_NOTFOUND)
			$scriptOut['loginError'] = ERR_LOGIN_NOTFOUND;
	}
	
	// Log out
	if(isset($_GET["logout"]))
	    $_SESSION = array();
	
	// TODO: Login by cookie
	
	$LoggedIn = ($_SESSION != array());
	
	if($LoggedIn) {
		$scriptOut['avatarURL'] = get_gravatar($_SESSION['email'], 160);
		
		$User = new User($DBCon);
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
		} elseif(!file_exists("templates/$page.php")) {
			throw new Exception("Script $page not found!", ERR_PAGE_NOTFOUND);
		}
		
		$template = $page;
	} catch(Exception $e) {
		if(isset($WebsiteInfo['debug']) && $WebsiteInfo['debug'] == true)
			$scriptOut['DebugMessage'] = $e->getMessage();
		$scriptOut['ErrorCode'] = $e->getCode();
		$template = 'error';
	}
	
	// Refresh the output session info, in case of updates in the scripts
	if($LoggedIn) {
		$retrieve = $User -> retrieveUserData(true);
		
		if($retrieve !== true)
			die('User update failed!');
		
		$_SESSION = array_merge($_SESSION, $User -> getDataArray());
		$scriptOut['Session'] = $_SESSION;
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