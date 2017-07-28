<?php
	class UserPermissionManager {
		private $DBCon;
		
		private $User;
		
		const PERM_VIEW_FILE = 'view_file';
		const PERM_VIEW_PROBLEM = 'view_problem';
		
		public function __construct($DatabaseLink) {
			if($DatabaseLink instanceof MySQLi)
				$this -> DBCon = $DatabaseLink;
			else
				throw new Exception('Provided DatabaseLink is not an instance of MySQLi!');
		}
		
		public function setUser($User) {
			if($User instanceof User) {
				if(!$User -> isConnected())
					throw new Exception('Provided user is not connected!');
				
				$this -> User = $User;
			} else
				throw new Exception('Provided user is not an instance of the User class!');
		}
		
		public function addPermission($Permission, $Arg1) {
			if(empty($this -> User))
				throw new Exception('User not set!');
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				INSERT INTO `permissions`
				(
					`user_id`   ,
					`permission`,
					`arg1`
				)
				VALUES
				(
					?,
					?,
					?
				)
			');
						
			if(!$stmt -> bind_param('iss', $this -> User -> getID(), $Permission, $Arg1))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			if($stmt -> affected_rows != 1)
				throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);
			
			$stmt -> close();
		}
		
		public function removePermission($Permission, $Arg1) {
			if(empty($this -> User))
				throw new Exception('User not set!');
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				DELETE
				FROM		`permissions` 
				WHERE		`user_id` = ?
				AND 		`permission` = ?
				AND			`arg1` = ?
			');
						
			if(!$stmt -> bind_param('iss', $this -> User -> getID(), $Permission, $Arg1))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			if($stmt -> affected_rows != 1)
				throw new Exception('The number of affected rows is not 1: ' . $stmt -> affected_rows);
			
			$stmt -> close();
		}
		
		public function hasPermission($Permission, $Arg1) {
			if(empty($this -> User))
				throw new Exception('User not set!');
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT		`id`
				FROM		`permissions` 
				WHERE		`user_id` = ?
				AND 		`permission` = ?
				AND			`arg1` = ?
			');
						
			if(!$stmt -> bind_param('iss', $this -> User -> getID(), $Permission, $Arg1))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			$HasPermission = (($stmt -> num_rows) != 0);
			
			$stmt -> close();
			
			return $HasPermission;
		}
		
		public function canEditProblem($ProblemID) {
			return $this -> User -> isAdmin();
		}
		
		public function canViewProblem($ProblemID) {
			if($this -> User -> isAdmin())
				return true;
			
			if($this -> hasPermission(UserPermissionManager::PERM_VIEW_PROBLEM, $ProblemID))
				return true;
			
			$Problem = new Problem($this -> DBCon);
			$Problem -> setProblemID($ProblemID);
			$retrieve = $Problem -> retrieveFromDB();
			
			if($retrieve === true)
				return $Problem -> isVisible();
			else
				return false;
		}
		
		public function canViewFile($FileID, $AdminOverride = true) {
			if($AdminOverride && $this -> User -> isAdmin())
				return true;
			
			return $this -> hasPermission(UserPermissionManager::PERM_VIEW_FILE, $FileID);
		}

		public function canManageClass($ClassID) {
            if($this -> User -> isAdmin())
                return true;

            $Class = new StudentClass($this->DBCon);
            $Class->SetID($ClassID);

            return $Class->IsTeacher($this->User);
		}
	}