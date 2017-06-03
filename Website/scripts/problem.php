<?php
	if(isset($_GET['action'])) {
		switch($_GET['action']) {
			case 'default':
			case 'answers':
			case 'myanswers':
			case 'viewsolution':
			case 'sendsolution':
			case 'viewtest':
			case 'edit':
				$scriptOut['pageAction'] = $_GET['action'];
				break;
			default:
				$scriptOut['pageAction'] = 'default';
				break;
		}
	} else {
		$scriptOut['pageAction'] = 'default';
	}
	
	if(isset($_GET['id'])) {
		$problem = new Problem($DBCon);
		$problem -> setProblemID($DBCon -> real_escape_string($_GET['id']));
		
		$retrieve = $problem -> retrieveFromDB();
		if($retrieve === true) {
			$scriptOut['problemID'] = $problem -> getProblemID();
			$scriptOut['problemInfo'] = $problem -> getProblemInfoArray();
			$scriptOut['problemText'] = $problem -> getProblemTextArray();
			$scriptOut['problemEditPermission'] = false;
			$scriptOut['problemVisible'] = $problem -> isVisible();
			
			if($LoggedIn) {
				$permissionManager = new UserPermissionManager($DBCon);
				$permissionManager -> setUser($User);
				
				$scriptOut['problemEditPermission'] = $permissionManager -> canEditProblem($problem -> getProblemID());
				
				if(!$permissionManager -> canViewProblem($problem -> getProblemID()))
					throw new Exception("This user cannot see this problem!");
			} else {
				if(!$problem -> isVisible())
					throw new Exception("This problem is not visible!");
			}
			
			switch($scriptOut['pageAction']) {
				case 'answers':
				{
					$resultsOnPage = 10;
					
					$solutionCount = $problem -> countProblemSolutions();
					$pageCount = floor($solutionCount / $resultsOnPage) + ($solutionCount % $resultsOnPage != 0);
					
					$solutionPage = 1;
					if(isset($_GET['page']) && $_GET['page'] >= 1 && $_GET['page'] <= $pageCount)
						$solutionPage = $_GET['page'];
					
					$scriptOut['problemSolutionCurrentPage'] = $solutionPage;
					$scriptOut['problemSolutionCount'] = $solutionCount;
					$scriptOut['problemSolutionPageCount'] = $pageCount;
					$scriptOut['problemSolutions'] = $problem -> getProblemSolutions($solutionPage, $resultsOnPage);
					
					$scriptOut['problemSolutionsShowStart'] = ($solutionPage - 1) * $resultsOnPage + 1;
					$scriptOut['problemSolutionsShowEnd'] = min(
							$solutionPage * $resultsOnPage, $solutionCount);
					break;
				}
				case 'myanswers':
				{
					if(!$LoggedIn) {
						throw new Exception("User has to be logged in in order to access this page.", ERR_PERMISSION_NOTAUTH);
					}
					
					$resultsOnPage = 10;
					
					$solutionCount = $problem -> countProblemSolutions($_SESSION['id']);
					$pageCount = floor($solutionCount / $resultsOnPage) + ($solutionCount % $resultsOnPage != 0);
					
					$solutionPage = 1;
					if(isset($_GET['page']) && $_GET['page'] >= 1 && $_GET['page'] <= $pageCount)
						$solutionPage = $_GET['page'];
					
					$scriptOut['problemSolutionCurrentPage'] = $solutionPage;
					$scriptOut['problemSolutionCount'] = $solutionCount;
					$scriptOut['problemSolutionPageCount'] = $pageCount;
					$scriptOut['problemSolutions'] = $problem -> getProblemSolutions($solutionPage, $resultsOnPage, $_SESSION['id']);
					
					$scriptOut['problemSolutionsShowStart'] = ($solutionPage - 1) * $resultsOnPage + 1;
					$scriptOut['problemSolutionsShowEnd'] = min(
							$solutionPage * $resultsOnPage, $solutionCount);
					break;
				}
				case 'sendsolution':
				{
					if(!$LoggedIn) {
						throw new Exception("User has to be logged in in order to access this page.", ERR_PERMISSION_NOTAUTH);
					}
					
					if(isset($_FILES['solution'])) {
						$scriptOut['fileSent'] = true;
						
						try {
							// Undefined | Multiple Files | $_FILES Corruption Attack
							// If this request falls under any of them, treat it invalid.
							if (
								!isset($_FILES['solution']['error']) ||
								is_array($_FILES['solution']['error'])
							) {
								throw new RuntimeException('Invalid parameters.', ERR_PROBLEM_SOLUTION_UPLOAD_INVALID_PARAM);
							}

							// Check $_FILES['solution']['error'] value.
							switch ($_FILES['solution']['error']) {
								case UPLOAD_ERR_OK:
									break;
								case UPLOAD_ERR_NO_FILE:
									throw new RuntimeException('No file sent.', ERR_PROBLEM_SOLUTION_UPLOAD_NOFILE);
								case UPLOAD_ERR_INI_SIZE:
								case UPLOAD_ERR_FORM_SIZE:
									throw new RuntimeException('Exceeded filesize limit.', ERR_PROBLEM_SOLUTION_UPLOAD_SIZE_EXCEEDED);
								default:
									throw new RuntimeException('Unknown errors.', ERR_PROBLEM_SOLUTION_UPLOAD_UNKNOWN_ERR);
							}

							// You should also check filesize here. 
							if ($_FILES['solution']['size'] > 1000000) {
								throw new RuntimeException('Exceeded filesize limit.', ERR_PROBLEM_SOLUTION_UPLOAD_SIZE_EXCEEDED);
							}
							
							if (!isset($_POST['language'])) {
								throw new RuntimeException('Preferred language not set.', ERR_PROBLEM_SOLUTION_UPLOAD_LANGUAGE_NOTSET);
							}

							$binaryData = file_get_contents($_FILES['solution']['tmp_name']);
							$fileName = $_FILES['solution']['name'];
							
							$uploadHandler = new FileHandler($DBCon);
							$uploadHandler -> setFileName($fileName);
							$uploadHandler -> setBinaryData($binaryData);
							
							$fileID = $uploadHandler -> pushToDatabase();
							$solutionID = $problem -> addUserSolution($_SESSION['id'], $fileID, $_POST['language']);
							
							$scriptOut['solutionID'] = $solutionID;
						} catch (RuntimeException $e) {
							$scriptOut['sendError'] = true;
							if(isset($WebsiteInfo['debug']) && $WebsiteInfo['debug'] == true)
								$scriptOut['uploadError'] = $e->getMessage();
							$scriptOut['uploadErrorCode'] = $e->getCode();
						}
					}

					break;
				}
				
				case 'viewsolution':
				{
					if(!isset($_GET['solutionid']))
						throw new Exception("Solution ID not set while accessing the page!", ERR_PROBLEM_SOLUTION_ID_NOTPROVIDED);
					
					$SolutionID = $DBCon -> real_escape_string($_GET['solutionid']);
					
					$scriptOut['solutionID'] = $SolutionID;
					$scriptOut['solution'] = $problem -> getSolution($SolutionID);

					break;
				}
				
				case 'edit':
				{
					if(!$LoggedIn) {
						throw new Exception("User has to be logged in in order to access this page.", ERR_PERMISSION_NOTAUTH);
					}
					
					if(!$permissionManager -> canEditProblem($problem -> getProblemID())) {
						throw new Exception("User does not have permission to edit this page.");
					}
					
					if(isset($_POST['editForm'])) {
						// Form integrity check
						$FormFields = array(
							'memory',
							'time',
							'io',
							'statement',
							'input',
							'output',
							'notes',
							'exampleInput',
							'exampleOutput',
						);
						
						foreach($FormFields as $Field) {
							if(!isset($_POST[$Field]))
								throw new Exception("Form integrity check failed!");
						}
						
						// TODO: validate user input
						// TODO: prevent XSS
						// TODO: prevent CSRF
						
						$problem -> setMaxMemory($_POST['memory']);
						$problem -> setMaxTime($_POST['time']);
						$problem -> setIOMethod($_POST['io']);
						$problem -> setStatement($_POST['statement']);
						$problem -> setInput($_POST['input']);
						$problem -> setOutput($_POST['output']);
						$problem -> setNotes($_POST['notes']);
						$problem -> setExampleInput($_POST['exampleInput']);
						$problem -> setExampleOutput($_POST['exampleOutput']);
						
						if(isset($_POST['visible']))
							$problem -> Show();
						else
							$problem -> Hide();
						
						// Update the output array
						$scriptOut['problemInfo'] = $problem -> getProblemInfoArray();
						$scriptOut['problemText'] = $problem -> getProblemTextArray();
						$scriptOut['problemVisible'] = $problem -> isVisible();
						
						// Update the database
						$problem -> updateInDB();
					}
					
					break;
				}
				
				case 'viewtest':
				{
					if(!$LoggedIn) {
						throw new Exception("User has to be logged in in order to access this page.", ERR_PERMISSION_NOTAUTH);
					}

					if(!isset($_GET['testid']) || empty($_GET['testid']))
						throw new Exception("Test ID not set while accessing the page!", ERR_PROBLEM_SOLUTION_ID_NOTPROVIDED);
					
					$TestID = $DBCon -> real_escape_string($_GET['testid']);
					
					$scriptOut['testID'] = $TestID;
					$scriptOut['test'] = $problem -> getProblemTest($TestID);
					
					$CanViewTest = ($permissionManager -> canViewFile($scriptOut['test']['input_file_id'], false) &&
						$permissionManager -> canViewFile($scriptOut['test']['output_file_id'], false));
					
					if(isset($_GET['unlock']) && !$CanViewTest) {
						if($User -> GetCoins() - 5 < 0)
							throw new Exception("Not enough coins!");
						
						$User -> ModifyCoins(-5);
						
						$permissionManager -> addPermission(UserPermissionManager::PERM_VIEW_FILE, $scriptOut['test']['input_file_id']);
						$permissionManager -> addPermission(UserPermissionManager::PERM_VIEW_FILE, $scriptOut['test']['output_file_id']);
						
						$CanViewTest = true;
					}
					
					$scriptOut['canViewTest'] = $CanViewTest;
					
					if($CanViewTest) {
						$inputFile = new FileHandler($DBCon);
						$outputFile = new FileHandler($DBCon);
						
						$inputFile -> setID($scriptOut['test']['input_file_id']);
						$outputFile -> setID($scriptOut['test']['output_file_id']);
						
						$inputFile -> pullFromDatabase();
						$outputFile -> pullFromDatabase();
						
						$scriptOut['inputFile'] = $inputFile -> getBinaryData();
						$scriptOut['outputFile'] = $outputFile -> getBinaryData();
					}

					break;
				}
				
				case 'default':
				default:
				{
					// Solution statistics
					if($LoggedIn) {
						$solutionStats = $problem -> getUserSolutionStats($_SESSION['id']);
						
						$scriptOut['problemSolutionCount'] = $solutionStats['solutionCount'];
						$scriptOut['problemSolutionDoneCount'] = $solutionStats['solutionDoneCount'];
						$scriptOut['problemMaxScore'] = $solutionStats['maxScore'];
					}
					
					break;
				}
			}
		} else {
			throw new Exception("Error while retrieving problem from the database!", $retrieve);
		}
	} else {
		throw new Exception("Problem ID not set while accessing the page!", ERR_PROBLEM_ID_NOTPROVIDED);
	}
	
