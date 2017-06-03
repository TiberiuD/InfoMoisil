<?php
	class Problem {
		private $DBCon;
		
		private $ProblemID;
		private $Name;
		private $CleanName;
		
		private $Author;
		private $Date;
		
		private $IOMethod;
		private $MaxMemory;
		private $MaxTime;
		private $SolutionFileID;
		
		private $Statement;
		private $Input;
		private $Output;
		private $Notes;
		private $ExampleInput;
		private $ExampleOutput;
		
		private $Visible;
		
		public function __construct($DatabaseLink) {
			if($DatabaseLink instanceof MySQLi)
				$this -> DBCon = $DatabaseLink;
			else
				throw new Exception('Provided DatabaseLink is not an instance of MySQLi!');
		}
		
		public function setProblemID($ProblemID) {
			$this -> ProblemID = $ProblemID;
		}
		
		public function getProblemID() {
			return $this -> ProblemID;
		}
		
		public function setIOMethod($IOMethod) {
			$this -> IOMethod = $IOMethod;
		}
		
		public function setMaxMemory($MaxMemory) {
			$this -> MaxMemory = $MaxMemory;
		}
		
		public function setMaxTime($MaxTime) {
			$this -> MaxTime = $MaxTime;
		}
		
		public function setStatement($Statement) {
			$this -> Statement = $Statement;
		}
		
		public function setInput($Input) {
			$this -> Input = $Input;
		}
		
		public function setOutput($Output) {
			$this -> Output = $Output;
		}
		
		public function setNotes($Notes) {
			$this -> Notes = $Notes;
		}
		
		public function setExampleInput($ExampleInput) {
			$this -> ExampleInput = $ExampleInput;
		}
		
		public function setExampleOutput($ExampleOutput) {
			$this -> ExampleOutput = $ExampleOutput;
		}
		
		public function create($Name, $CleanName, $Author) {
			$this -> Name = $Name;
			$this -> CleanName = $CleanName;
			$this -> Author = $Author;
			
			$this -> IOMethod = 'file';
			$this -> MaxMemory = 1;
			$this -> MaxTime = 1;
			
			$this -> Date = date("Y-m-d H:i:s");
			
			$this -> Visible = false;
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('INSERT INTO `problems` (`date_added`) VALUES (NOW());');
			
			if(!$stmt -> execute())
				throw new Exception('MySQL error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if($stmt -> affected_rows != 1)
				throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);
			
			$this -> ProblemID = $stmt -> insert_id;
			
			$stmt -> close();
			
			$this -> updateInDB();
		}
		
		public function updateInDB() {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			if(empty($this -> Name))
				throw new Exception('Problem name not set!');
			
			if(empty($this -> CleanName))
				throw new Exception('Problem clean name not set!');
			
			if(empty($this -> IOMethod))
				throw new Exception('Problem input method not set!');
			
			if(empty($this -> MaxMemory))
				throw new Exception('Problem max memory not set!');
			
			if(empty($this -> MaxTime))
				throw new Exception('Problem max time not set!');
			
			$stmt = $this -> DBCon -> prepare('
				UPDATE `problems`
				SET `name` = ?,
				 `name_clean` = ?,
				 `author` = ?,
				 `date_added` = ?,
				 `io_method` = ?,
				 `max_mem` = ?,
				 `max_time` = ?,
				 `solution_file_id` = ?,
				 `statement` = ?,
				 `input` = ?,
				 `output` = ?,
				 `notes` = ?,
				 `example_input` = ?,
				 `example_output` = ?,
				 `visible` = ?
				WHERE
					`id` = ?;
			');
			
			if(!$stmt -> bind_param('sssssddissssssii', 
					$this -> Name,
					$this -> CleanName,
					$this -> Author,
					$this -> Date,
					$this -> IOMethod,
					$this -> MaxMemory,
					$this -> MaxTime,
					$this -> SolutionFileID,
					$this -> Statement,
					$this -> Input,
					$this -> Output,
					$this -> Notes,
					$this -> ExampleInput,
					$this -> ExampleOutput,
					$this -> Visible,
					$this -> ProblemID
			))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL error ' . $stmt -> errno . ': ' . $stmt -> error);
						
			$stmt -> close();

			return true;
		}
		
		public function retrieveFromDB() {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');

			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT	`name`, 
						`name_clean`, 
						`author`, 
						`date_added`, 
						`io_method`, 
						`max_mem`, 
						`max_time`, 
						`solution_file_id`, 
						`statement`, 
						`input`, 
						`output`, 
						`notes`, 
						`example_input`, 
						`example_output`,
						`visible`
				FROM	`problems` 
				WHERE	`id` = ?
			');
			
			if(!$stmt -> bind_param('i', $this -> ProblemID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			if($stmt -> num_rows == 0) {
				$stmt -> close();
				return ERR_PROBLEM_NOTFOUND;
			} elseif($stmt -> num_rows == 1) {
				$stmt -> bind_result(
					$this -> Name,
					$this -> CleanName,
					$this -> Author,
					$this -> Date,
					$this -> IOMethod,
					$this -> MaxMemory,
					$this -> MaxTime,
					$this -> SolutionFileID,
					$this -> Statement,
					$this -> Input,
					$this -> Output,
					$this -> Notes,
					$this -> ExampleInput,
					$this -> ExampleOutput,
					$this -> Visible
				);
				$stmt -> fetch();
			} else
				throw new Exception('Multiple problems found in database!');
			
			$stmt -> close();
			return true;
		}
		
		public function getProblemTextArray() {
			return array(
				'statement' => $this -> Statement,
				'input' => $this -> Input,
				'output' => $this -> Output,
				'notes' => $this -> Notes,
				'exampleInput' => $this -> ExampleInput,
				'exampleOutput' => $this -> ExampleOutput
			);
		}
		
		public function getProblemInfoArray() {
			return array(
				'name' => $this -> Name,
				'cleanName' => $this -> CleanName,
				'author' => $this -> Author,
				'IOMethod' => $this -> IOMethod,
				'maxMemory' => $this -> MaxMemory,
				'maxTime' => $this -> MaxTime
			);
		}
		
		public function getUserSolutionStats($UserID) {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			// TODO: check if user exists and throw an error
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT	COUNT(`id`),
						COUNT(case `done` when 1 then 1 else null end),
						MAX(`score`)
				FROM	`jobs` 
				WHERE	`user_id` = ?
				AND		`problem_id` = ?
			');
			
			if(!$stmt -> bind_param('ii', $UserID, $this -> ProblemID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			$solutionCount = 0;
			$solutionDoneCount = 0;
			$maxScore = 0;
			
			$stmt -> bind_result($solutionCount, $solutionDoneCount, $maxScore);
			$stmt -> fetch();
			
			$stmt -> close();
			
			return array(
				'solutionCount' => $solutionCount,
				'solutionDoneCount' => $solutionDoneCount,
				'maxScore' => $maxScore
			);
		}
		
		public function countProblemSolutions($UserID = -1) {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			// TODO: check if user exists and throw an error
			
			$stmt = $this -> DBCon -> stmt_init();
			
			if($UserID == -1) {
				// Count all solutions
				$stmt -> prepare('
					SELECT		COUNT(`id`)
					FROM		`jobs` 
					WHERE		`problem_id` = ?
				');
				
				if(!$stmt -> bind_param('i', $this -> ProblemID))
					throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			} else {
				// Count only solutions for a user
				$stmt -> prepare('
					SELECT		COUNT(`id`)
					FROM		`jobs` 
					WHERE		`problem_id` = ?
					AND			`user_id` = ?
				');
				
				if(!$stmt -> bind_param('ii', $this -> ProblemID, $UserID))
					throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			}
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			$solutionCount = 0;
			
			$stmt -> bind_result($solutionCount);
			$stmt -> fetch();
			
			$stmt -> close();
			
			return $solutionCount;
		}
		
		public function getProblemSolutions($page, $resultsOnPage, $UserID = -1) {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			// TODO: check if user exists and throw an error
			
			$resultsOffset = ($page - 1) * $resultsOnPage;
			
			$stmt = $this -> DBCon -> stmt_init();
			
			if($UserID == -1) {
				// Get all solutions
				$stmt -> prepare('
					SELECT		`id`, `user_id`, `send_time`, `done`, `score`, `done_time`, `compiler_message`
					FROM		`jobs` 
					WHERE		`problem_id` = ?
					ORDER BY	`send_time` DESC
					LIMIT		?
					OFFSET		?
				');
				
				
				if(!$stmt -> bind_param('iii', $this -> ProblemID, $resultsOnPage, $resultsOffset))
					throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			} else {
				// Only get solutions for a specific user
				$stmt -> prepare('
					SELECT		`id`, `user_id`, `send_time`, `done`, `score`, `done_time`, `compiler_message`
					FROM		`jobs` 
					WHERE		`problem_id` = ?
					AND			`user_id` = ?
					ORDER BY	`send_time` DESC
					LIMIT		?
					OFFSET		?
				');
				
				
				if(!$stmt -> bind_param('iiii', $this -> ProblemID, $UserID, $resultsOnPage, $resultsOffset))
					throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);				
			}
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$solutionArray = array();
			$stmt -> store_result();
			
			$solutionID = 0;
			$solutionUserID = 0;
			$solutionSendTime = "";
			$solutionDone = false;
			$solutionScore = 0;
			$solutionDoneTime = "";
			$solutionCompilerMessage = "";
			
			$stmt -> bind_result(
				$solutionID,
				$solutionUserID,
				$solutionSendTime,
				$solutionDone,
				$solutionScore,
				$solutionDoneTime,
				$solutionCompilerMessage);
				
			while($stmt -> fetch()) {
				$user = new User($this -> DBCon);
				$user -> setID($solutionUserID);
				
				$login = $user -> retrieveUserData(true);
				if($login === ERR_LOGIN_NOTFOUND) {
					throw new Exception('Could not find user for solution ' . $solutionID . '.',
							ERR_PROBLEM_SOLUTION_USER_NOTFOUND);
				} else {
					$userDataArray = $user -> getDataArray();
					$solutionUserName = $userDataArray['name'];
					$solutionUserAvatar = get_gravatar($userDataArray['email'], 160);
				}
				
				$solutionArray[] = array(
					'id'				=> $solutionID,
					'user_name'			=> $solutionUserName,
					'user_avatar'		=> $solutionUserAvatar,
					'send_time'			=> $solutionSendTime,
					'done'				=> $solutionDone,
					'score'				=> $solutionScore,
					'done_time'			=> $solutionDoneTime,
					'compiler_message'	=> $solutionCompilerMessage
				);
			}
			
			$stmt -> close();
			
			return $solutionArray;
		}
		
		public function getSolutionTests($solutionID) {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
						
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT
					`job_tests`.`id`,
					`job_tests`.`test_id`,
					`job_tests`.`status`,
					`problem_tests`.`points`,
					`job_tests`.`memory_used`,
					`job_tests`.`execution_time`,
					`job_tests`.`message`
				FROM
					`job_tests`
				LEFT JOIN `problem_tests` ON `job_tests`.`test_id` = `problem_tests`.`id`
				WHERE
					`job_id` = ?;
			');
						
			if(!$stmt -> bind_param('i', $solutionID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
					
			$TestID = 0;
			$ProblemTestID = 0;
			$Status = "";
			$Points = 0;
			$MemoryUsed = 0.0;
			$ExecutionTime = 0.0;
			$Message = "";
			
			$TestsArray = array();
			
			$stmt -> bind_result(
				$TestID,
				$ProblemTestID,
				$Status,
				$Points,
				$MemoryUsed,
				$ExecutionTime,
				$Message);
				
			while($stmt -> fetch()) {
				$TestsArray[] = array(
					'test_id'			=> $TestID,
					'problem_test_id'	=> $ProblemTestID,
					'status'			=> $Status,
					'points'			=> $Points,
					'memory_used'		=> $MemoryUsed,
					'execution_time'	=> $ExecutionTime,
					'message'			=> $Message
				);
			}
			
			$stmt -> close();
			
			return $TestsArray;
		}
		
		public function getSolution($solutionID) {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
						
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT		`problem_id`,
							`user_id`,
							`language`,
							`send_time`,
							`done`,
							`score`,
							`done_time`,
							`compiler_message`
				FROM		`jobs` 
				WHERE		`id` = ?
			');
						
			if(!$stmt -> bind_param('i', $solutionID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			if($stmt -> num_rows == 0)
				throw new Exception('Solution ' . $solutionID . ' does not exist!', ERR_PROBLEM_SOLUTION_NOTFOUND);
		
			$solutionProblemID = 0;
			$solutionUserID = 0;
			$solutionUserName = "";
			$solutionLanguage = "";
			$solutionSendTime = "";
			$solutionDone = false;
			$solutionScore = 0;
			$solutionDoneTime = "";
			$solutionCompilerMessage = "";
			
			$stmt -> bind_result(
				$solutionProblemID,
				$solutionUserID,
				$solutionLanguage,
				$solutionSendTime,
				$solutionDone,
				$solutionScore,
				$solutionDoneTime,
				$solutionCompilerMessage);
				
			$stmt -> fetch();
			
			if($solutionProblemID != $this -> ProblemID)
				throw new Exception('Problem mismatch for solution ' . $solutionID .
						'. Expected ' . $this -> ProblemID . ', got  ' . $solutionProblemID . '.',
						ERR_PROBLEM_SOLUTION_MISMATCH);
			
			$user = new User($this -> DBCon);
			$user -> setID($solutionUserID);
			
			$login = $user -> retrieveUserData(true);
			if($login === ERR_LOGIN_NOTFOUND) {
				throw new Exception('Could not find user for solution ' . $solutionID . '.',
						ERR_PROBLEM_SOLUTION_USER_NOTFOUND);
			} else {
				$userDataArray = $user -> getDataArray();
				$solutionUserName = $userDataArray['name'];
			}
			
			$solutionTests = $this -> getSolutionTests($solutionID);
			
			$solutionArray = array(
				'id'				=> $solutionID,
				'user_id'			=> $solutionUserID,
				'user_name'			=> $solutionUserName,
				'language'			=> $solutionLanguage,
				'send_time'			=> $solutionSendTime,
				'done'				=> $solutionDone,
				'score'				=> $solutionScore,
				'done_time'			=> $solutionDoneTime,
				'compiler_message'	=> $solutionCompilerMessage,
				'solution_tests'	=> $solutionTests
			);
			
			$stmt -> close();
			
			return $solutionArray;
		}
		
		public function addUserSolution($UserID, $FileID, $Language) {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			$stmt = $this -> DBCon -> prepare('
				INSERT INTO `jobs`
					(
						`user_id`   ,
						`problem_id`,
						`file_id`   ,
						`language`
					)
					VALUES
					(
						?,
						?,
						?,
						?
					)
			');
			
			switch($Language) {
				case 'cpp':
				case 'c':
				case 'pas':
					break;
				default:
					throw new Exception('Unknown preferred language!');
			}
			
			if(!$stmt -> bind_param('iiis', $UserID, $this -> ProblemID, $FileID, $Language))
				throw new Exception('MySQL parameter bind ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if($stmt -> affected_rows != 1)
				throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);
			
			return $stmt -> insert_id;
		}
		
		function getProblemTest($TestID) {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT		`problem_id`,
							`input_file_id`,
							`output_file_id`,
							`points`
				FROM		`problem_tests` 
				WHERE		`id` = ?
			');
						
			if(!$stmt -> bind_param('i', $TestID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			if($stmt -> num_rows == 0)
				throw new Exception('Test ' . $TestID . ' does not exist!', ERR_PROBLEM_SOLUTION_NOTFOUND);
			
			$testProblemID = 0;
			$testInputFileID = 0;
			$testOutputFileID = 0;
			$testPoints = 0;
			
			$stmt -> bind_result(
				$testProblemID,
				$testInputFileID,
				$testOutputFileID,
				$testPoints);
				
			$stmt -> fetch();
			
			if($testProblemID != $this -> ProblemID)
				throw new Exception('Problem mismatch for test ' . $TestID .
						'. Expected ' . $this -> ProblemID . ', got  ' . $testProblemID . '.',
						ERR_PROBLEM_SOLUTION_MISMATCH);
			
			$testArray = array(
				'id'				=> $testProblemID,
				'input_file_id'		=> $testInputFileID,
				'output_file_id'	=> $testOutputFileID,
				'points'			=> $testPoints
			);
			
			$stmt -> close();
			
			return $testArray;
		}
		
		function isVisible() {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			return ($this -> Visible == true);
		}
		
		function Hide() {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			$this -> Visible = false;
		}
		
		function Show() {
			if(empty($this -> ProblemID))
				throw new Exception('Problem ID not set!');
			
			$this -> Visible = true;
		}
	}