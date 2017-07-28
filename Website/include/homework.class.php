<?php
    class Homework {
        private $HomeworkID;
        private $Name;
        private $Deadline;
        private $StudentClass;
        private $Published;

        private $DBCon;

        const STATUS_PUBLISHED = 'published';
        const STATUS_UNPUBLISHED = 'unpublished';

        public function __construct($DatabaseLink) {
            if($DatabaseLink instanceof MySQLi)
                $this -> DBCon = $DatabaseLink;
            else
                throw new Exception('Provided DatabaseLink is not an instance of MySQLi!');
        }

        private function CheckIfInitialised() {
            if(empty($this->HomeworkID))
                throw new Exception("Homework object not initialised.");
        }

        public function Create($Name, $StudentClass, $DeadlineTimestamp) {
            if($StudentClass instanceof StudentClass)
                $this->StudentClass = $StudentClass;
            else
                throw new Exception('Provided class is not an instance of StudentClass!');

            $this->Name = $Name;
            $this->Deadline = date("Y-m-d", $DeadlineTimestamp);
            $stmt = $this->DBCon->stmt_init();

            $stmt -> prepare("
				INSERT INTO `homeworks`
				(
					`class_id`,
					`name`,
					`deadline`,
					`published`
				)
				VALUES
				(
					?,
					?,
					FROM_UNIXTIME(?),
					'" . self::STATUS_UNPUBLISHED . "'
				)
			");

            if(!$stmt -> bind_param('isi', $this->StudentClass->GetID(), $this->Name, $DeadlineTimestamp))
                throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

            if(!$stmt -> execute())
                throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);

            $stmt -> store_result();

            if($stmt -> affected_rows != 1)
                throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);

            $this->HomeworkID = $this->DBCon->insert_id;

            $stmt -> close();
        }

        private function PullFromDatabase()
        {
            $this->CheckIfInitialised();

            $stmt = $this->DBCon->stmt_init();

            $stmt->prepare('
				SELECT	`name`,
				        `class_id`,
				        `deadline`,
				        `published`
				FROM	`homeworks` 
				WHERE	`id` = ?
			');

            if (!$stmt->bind_param('i', $this->HomeworkID))
                throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

            if (!$stmt->execute())
                throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

            $stmt->store_result();

            if ($stmt->num_rows == 0)
                throw new Exception('Homework not found with required ID!');

            $ClassID = 0;

            $stmt->bind_result(
                $this->Name,
                $ClassID,
                $this->Deadline,
                $this->Published
            );

            $stmt->fetch();
            $stmt->close();

            $this->StudentClass = new StudentClass($this->DBCon);
            $this->StudentClass->SetID($ClassID);
        }

        public function SetID($HomeworkID) {
            $this->HomeworkID = $HomeworkID;
            $this->PullFromDatabase();
        }

        public function GetID() {
            $this->CheckIfInitialised();
            return $this->HomeworkID;
        }

        public function GetName() {
            $this->CheckIfInitialised();
            return $this->Name;
        }

        public function GetDeadline() {
            $this->CheckIfInitialised();
            return $this->Deadline;
        }

        public function GetStatus() {
            $this->CheckIfInitialised();
            return $this->Published;
        }

        public function AddProblem($Problem) {
            $this->CheckIfInitialised();

            if(!$Problem instanceof Problem)
                throw new Exception('Provided problem is not an instance of Problem!');

            $stmt = $this->DBCon->stmt_init();

            $stmt -> prepare('
				INSERT INTO `homework_problems`
				(
					`homework_id`,
					`problem_id`
				)
				VALUES
				(
					?,
					?
				)
			');

            if(!$stmt -> bind_param('ii', $this->HomeworkID, $Problem->getProblemID()))
                throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

            if(!$stmt -> execute())
                throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);

            $stmt -> store_result();

            if($stmt -> affected_rows != 1)
                throw new Exception('The number of inserted rows is not 1: ' . $stmt -> affected_rows);

            $stmt -> close();
        }

        public function RemoveProblem($Problem) {
            $this->CheckIfInitialised();

            if(!$Problem instanceof Problem)
                throw new Exception('Provided problem is not an instance of Problem!');

            $stmt = $this -> DBCon -> stmt_init();

            $stmt -> prepare('
				DELETE
				FROM		`homework_problems` 
				WHERE		`homework_id` = ?
				AND 		`problem_id` = ?
			');

            if(!$stmt -> bind_param('ii', $this->HomeworkID, $Problem->getProblemID()))
                throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

            if(!$stmt -> execute())
                throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);

            $stmt -> store_result();

            if($stmt -> affected_rows != 1)
                throw new Exception('The number of affected rows is not 1: ' . $stmt -> affected_rows);

            $stmt -> close();
        }

        public function GetProblems() {
            $this->CheckIfInitialised();
            $stmt = $this->DBCon->stmt_init();

            $stmt->prepare('
				SELECT	`problem_id`
				FROM	`homework_problems` 
				WHERE	`homework_id` = ?
			');

            if (!$stmt->bind_param('i', $this->HomeworkID))
                throw new Exception('MySQL parameter bind error ' . $stmt->errno . ': ' . $stmt->error);

            if (!$stmt->execute())
                throw new Exception('MySQL statement execution error ' . $stmt->errno . ': ' . $stmt->error);

            $stmt->store_result();

            $Problems = array();

            $ProblemID = 0;

            $stmt->bind_result(
                $ProblemID
            );

            while($stmt->fetch()) {
                $Problem = new Problem($this->DBCon);
                $Problem->setProblemID($ProblemID);
                $Problem->retrieveFromDB();

                $Problems[] = $Problem;
            }

            $stmt->close();

            return $Problems;
        }

        public function SetPublishingStatus($Status) {
            $this->CheckIfInitialised();

            $stmt = $this -> DBCon -> stmt_init();

            $stmt -> prepare('
				UPDATE `homeworks`
				SET `published` = ?
				WHERE `id` = ?
			');

            if(!$stmt -> bind_param('si', $Status, $this->HomeworkID))
                throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);

            if(!$stmt -> execute())
                throw new Exception('MySQL error ' . $stmt -> errno . ': ' . $stmt -> error);

            if($stmt -> affected_rows != 1)
                throw new Exception('The number of updated rows is not 1: ' . $stmt -> affected_rows);

            $stmt -> close();
        }
    }