<?php
	class User {
		private $DBCon;
		
		private $UserID;
		private $Email;
		private $Password;
		
		private $Name;
		private $Rank;
		private $Coins;
		private $PrefferedLanguage;
		
		private $IsConnected;
		
		public function __construct($DatabaseLink) {
			if($DatabaseLink instanceof MySQLi)
				$this -> DBCon = $DatabaseLink;
			else
				throw new Exception('Provided DatabaseLink is not an instance of MySQLi!');
		}
		
		public function setID($UserID) {
			$this -> UserID = $UserID;
		}
		
		public function getID() {
			return $this -> UserID;
		}
		
		public function setEmail($Email) {
			$this -> Email = $Email;
		}
		
		public function setPassword($Password) {
			$this -> Password = $Password;
		}
		
		public function setName($Name) {
			$this -> Name = $Name;
		}
		
		public function isConnected() {
			return $this -> IsConnected;
		}
		
		public function isAdmin() {
			return ($this -> Rank == 'admin');
		}
		
		public function isEmailInUse() {
			if(empty($this -> Email))
				throw new Exception('Email not set!');
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT	COUNT(`id`)
				FROM	`users` 
				WHERE	`email` = ?
			');
			
			if(!$stmt -> bind_param('s', $this -> Email))
				throw new Exception('MySQL parameter bind ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			$count = 0;
			$stmt -> bind_result($count);
			$stmt -> fetch();
			
			$stmt -> close();
			
			return ($count != 0);
		}
		
		public function getDataArray() {
			return array(
				'id'					=> $this -> UserID,
				'email'					=> $this -> Email,
				'name'					=> $this -> Name,
				'coins'					=> $this -> Coins,
				'rank'					=> $this -> Rank,
				'preffered_language'	=> $this -> PrefferedLanguage
			);
		}
		
		public function register() {
			if(empty($this -> Email))
				throw new Exception('Email not set!');
			
			if(empty($this -> Password))
				throw new Exception('Password not set!');
			
			if(empty($this -> Name))
				throw new Exception('Name not set!');
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				INSERT INTO `users`
					(
						`email`   ,
						`password`,
						`name`    ,
						`register_date`
					)
					VALUES
					(
						?          ,
						PASSWORD(?),
						?          ,
						NOW()
					)
			');
			
			if(!$stmt -> bind_param('sss', $this -> Email, $this -> Password, $this -> Name))
				throw new Exception('MySQL parameter bind ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if($stmt -> affected_rows != 1)
				throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);
			
			$stmt -> close();
			$this -> IsConnected = true;
			return true;
		}
		
		public function retrieveUserData($getUserByID = false) {	
			if($getUserByID && empty($this -> UserID))
				throw new Exception('User ID not set!');
			
			if(!$getUserByID && empty($this -> Email))
				throw new Exception('Email not set!');
			
			if(!$getUserByID && empty($this -> Password))
				throw new Exception('Password not set!');

			$stmt = $this -> DBCon -> stmt_init();	
			
			if($getUserByID) {
				$stmt -> prepare('
					SELECT	`id`, 
							`name`,
							`coins`,
							`email`,
							`rank`,
							`preffered_language`
					FROM	`users` 
					WHERE	`id` = ?
				');
				
				if(!$stmt -> bind_param('i', $this -> UserID))
					throw new Exception('MySQL parameter bind ' . $stmt -> errno . ': ' . $stmt -> error);
			} else {
				$stmt -> prepare('
					SELECT	`id`, 
							`name`,
							`coins`,
							`email`,
							`rank`,
							`preffered_language`
					FROM	`users` 
					WHERE	`email` = ? 
							AND `password` = PASSWORD(?) 
				');
				
				if(!$stmt -> bind_param('ss', $this -> Email, $this -> Password))
					throw new Exception('MySQL parameter bind ' . $stmt -> errno . ': ' . $stmt -> error);
			}
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			if($stmt -> num_rows == 0) {
				$stmt -> close();
				return ERR_LOGIN_NOTFOUND;
			} elseif($stmt -> num_rows == 1) {
				$stmt -> bind_result(
					$this -> UserID,
					$this -> Name,
					$this -> Coins,
					$this -> Email,
					$this -> Rank,
					$this -> PrefferedLanguage
				);
				$stmt -> fetch();
				
				$this -> IsConnected = true;
			} else
				throw new Exception('Multiple accounts found in database!');
			
			$stmt -> close();
			return true;
		}
		
		public function GetCoins() {
			if(!$this -> IsConnected)
				throw new Exception('User not connected!');
			
			return $this -> Coins;
		}
		
		public function ModifyCoins($Amount) {
			if(!$this -> IsConnected)
				throw new Exception('User not connected!');
			
			$this -> Coins += $Amount;
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				UPDATE `users`
				SET `coins` = `coins` + ?
				WHERE `id` = ?
			');
			
			if(!$stmt -> bind_param('ii', $Amount, $this -> UserID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if($stmt -> affected_rows != 1)
				throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);
			
			$stmt -> close();
		}
		
		public function GetNotifications() {
			if(!$this -> IsConnected)
				throw new Exception('User not connected!');
						
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT		`id`, `time`, `message`, `read`
				FROM		`notifications` 
				WHERE		`user_id` = ?
				ORDER BY	`time` DESC
				LIMIT		5
			');
			
			
			if(!$stmt -> bind_param('i', $this -> UserID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$notificationArray = array();
			$stmt -> store_result();
			
			$ID = 0;
			$Time = "";
			$Message = "";
			$Read = false;
			
			$stmt -> bind_result(
				$ID,
				$Time,
				$Message,
				$Read);
				
			while($stmt -> fetch()) {
				$notificationArray[] = array(
					'id'			=> $ID,
					'time'			=> $Time,
					'message'		=> $Message,
					'read'			=> $Read
				);
			}
			
			$stmt -> close();
			
			return $notificationArray;
		}
		
		public function ReadNotification($NotificationID) {
			if(!$this -> IsConnected)
				throw new Exception('User not connected!');
						
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT		*
				FROM		`notifications` 
				WHERE		`id` = ?
				AND			`user_id` = ?
			');
			
			if(!$stmt -> bind_param('ii', $NotificationID, $this -> UserID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt->store_result();
			
			if($stmt -> num_rows == 0)
				throw new Exception("ID pair doesn't exist!");
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				UPDATE `notifications`
				SET `read` = 1
				WHERE `id` = ?
			');
			
			if(!$stmt -> bind_param('i', $NotificationID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if($stmt -> affected_rows != 1)
				throw new Exception('The number of updated rows is not 1: ' . $stmt -> affected_rows);			
			
			$stmt -> close();
		}

		public function getAuthToken() {
            if(!$this -> IsConnected)
                throw new Exception('User not connected!');

            $stmt = $this -> DBCon -> stmt_init();

            $stmt -> prepare('
				INSERT INTO `authentication_tokens`
					(
						`user_id`,
						`token`,
						`expire_date`
					)
					VALUES
					(
						?,
						?,
						NOW() + INTERVAL 1 MONTH
					)
			');

            $RandomToken = md5(uniqid('', true));

            if(!$stmt -> bind_param('is', $this->UserID, $RandomToken))
                throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

            if(!$stmt -> execute())
                throw new Exception('MySQL error ' . $stmt -> errno . ': ' . $stmt -> error);

            if($stmt -> affected_rows != 1)
                throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);

            $stmt -> close();

            return $RandomToken;
        }
	}

