<?php
    class StudentClass {
        private $DBCon;

        private $ClassID;
        private $Name;
        private $AccessKey;

        const STATUS_STUDENT = 'student';
        const STATUS_TEACHER = 'teacher';

        public function __construct($DatabaseLink) {
            if($DatabaseLink instanceof MySQLi)
                $this -> DBCon = $DatabaseLink;
            else
                throw new Exception('Provided DatabaseLink is not an instance of MySQLi!');
        }

        private function CheckIfInitialised() {
            if(empty($this->ClassID))
                throw new Exception("Student class object not initialised.");
        }

        public function Create($Name) {
            $this->AccessKey = RandomKey();
            $this->Name = $Name;

            $stmt = $this->DBCon->stmt_init();

            $stmt -> prepare('
				INSERT INTO `classes`
				(
					`name`,
					`access_key`,
					`creation_date`
				)
				VALUES
				(
					?,
					?,
					NOW()
				)
			');

            if(!$stmt -> bind_param('ss', $this->Name, $this->AccessKey))
                throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

            if(!$stmt -> execute())
                throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);

            $stmt -> store_result();

            if($stmt -> affected_rows != 1)
                throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);

            $this->ClassID = $this->DBCon->insert_id;

            $stmt -> close();
        }

        private function PullFromDatabase()
        {
            $this->CheckIfInitialised();

            $stmt = $this->DBCon->stmt_init();

            $stmt->prepare('
				SELECT	`name`, 
				        `access_key`
				FROM	`classes` 
				WHERE	`id` = ?
			');

            if (!$stmt->bind_param('i', $this->ClassID))
                throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

            if (!$stmt->execute())
                throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

            $stmt->store_result();

            if ($stmt->num_rows == 0)
                throw new Exception('Class not found with required ID!');

            $stmt->bind_result(
                $this->Name,
                $this->AccessKey
            );

            $stmt->fetch();
            $stmt->close();
        }

        public function SetID($ClassID) {
            $this->ClassID = $ClassID;
            $this->PullFromDatabase();
        }

        public function GetID() {
            $this->CheckIfInitialised();
            return $this->ClassID;
        }

        public function GetAccessKey() {
            $this->CheckIfInitialised();
            return $this->AccessKey;
        }

        public function GetName() {
            $this->CheckIfInitialised();
            return $this->Name;
        }

        public function AddUser($User, $Status = self::STATUS_STUDENT) {
            $this->CheckIfInitialised();

            if($this->IsUserInClass($User))
                throw new Exception("User is already a member of this class.");

            $stmt = $this->DBCon->stmt_init();

            $stmt -> prepare('
				INSERT INTO `class_members`
				(
					`class_id`,
					`user_id`,
					`status`
				)
				VALUES
				(
					?,
					?,
					?
				)
			');

            if(!$stmt -> bind_param('iis', $this->ClassID, $User->getID(), $Status))
                throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

            if(!$stmt -> execute())
                throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);

            $stmt -> store_result();

            if($stmt -> affected_rows != 1)
                throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);

            $stmt -> close();
        }

        public function AddTeacher($User) {
            $this->CheckIfInitialised();

            $this->AddUser($User, self::STATUS_TEACHER);
        }

        public function IsTeacher($User) {
            $this->CheckIfInitialised();

            $stmt = $this->DBCon->stmt_init();

            $stmt->prepare("
				SELECT	COUNT(`user_id`)
				FROM	`class_members` 
				WHERE	`user_id` = ?
				AND     `class_id` = ?
				AND     `status` = '" . self::STATUS_TEACHER . "';
			");

            if (!$stmt->bind_param('ii', $User->getID(), $this->ClassID))
                throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

            if (!$stmt->execute())
                throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

            $stmt->store_result();

            if ($stmt->num_rows == 0)
                throw new Exception('Unexpected null result on COUNT query.');

            $CountResult = 0;

            $stmt->bind_result(
                $CountResult
            );

            $stmt->fetch();
            $stmt->close();

            return ($CountResult != 0);
        }

        public function IsUserInClass($User) {
            $this->CheckIfInitialised();

            $stmt = $this->DBCon->stmt_init();

            $stmt->prepare('
				SELECT	COUNT(`user_id`)
				FROM	`class_members` 
				WHERE	`user_id` = ?
				AND     `class_id` = ?;
			');

            if (!$stmt->bind_param('ii', $User->getID(), $this->ClassID))
                throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

            if (!$stmt->execute())
                throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

            $stmt->store_result();

            if ($stmt->num_rows == 0)
                throw new Exception('Unexpected null result on COUNT query.');

            $CountResult = 0;

            $stmt->bind_result(
                $CountResult
            );

            $stmt->fetch();
            $stmt->close();

            return ($CountResult != 0);
        }

        public function JoinWithAccessKey($User, $AccessKey) {
            $stmt = $this->DBCon->stmt_init();

            $stmt->prepare('
				SELECT	`id`
				FROM	`classes` 
				WHERE	`access_key` = ?
			');

            if (!$stmt->bind_param('s', $AccessKey))
                throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

            if (!$stmt->execute())
                throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

            $stmt->store_result();

            if ($stmt->num_rows == 0)
                return false;
                //throw new Exception('Class not found with required access key!');

            $ClassID = 0;

            $stmt->bind_result(
                $ClassID
            );

            $stmt->fetch();
            $stmt->close();

            $this->SetID($ClassID);
            $this->AddUser($User);
        }

        public function GetHomeworks() {
            $this->CheckIfInitialised();

            $stmt = $this->DBCon->stmt_init();

            $stmt->prepare('
				SELECT  `id`
				FROM	`homeworks` 
				WHERE	`class_id` = ?
			');

            if (!$stmt->bind_param('i', $this->ClassID))
                throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

            if (!$stmt->execute())
                throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

            $stmt->store_result();

            $Homeworks = array();

            $HomeworkID = 0;

            $stmt->bind_result(
                $HomeworkID
            );

            while($stmt->fetch()) {
                $Homework = new Homework($this->DBCon);
                $Homework->SetID($HomeworkID);

                $Homeworks[] = $Homework;
            }

            $stmt->close();

            return $Homeworks;
        }

        public function GetUsers() {
            $this->CheckIfInitialised();

            $stmt = $this->DBCon->stmt_init();

            $stmt->prepare('
				SELECT  `user_id`
				FROM	`class_members` 
				WHERE	`class_id` = ?
			');

            if (!$stmt->bind_param('i', $this->ClassID))
                throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

            if (!$stmt->execute())
                throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

            $stmt->store_result();

            $Users = array();

            $UserID = 0;

            $stmt->bind_result(
                $UserID
            );

            while($stmt->fetch()) {
                $User = new User($this->DBCon);
                $User -> setID($UserID);

                $Retrieve = $User -> retrieveUserData(true);
                if(!$Retrieve)
                    throw new Exception("Could not retrieve user data");

                $Users[] = $User;
            }

            $stmt->close();

            return $Users;
        }

    }
