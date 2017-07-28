<?php
	class FileHandler {
		private $DBCon;
		
		private $FileID;
		
		private $FileName;
		private $BinaryData;
		private $Hash;
		private $DateModified;
		
		public function __construct($databaseLink) {
			$this -> DBCon = $databaseLink;
		}
		
		public function setID($FileID) {
			$this -> FileID = $FileID;
		}
		
		public function getID() {
			return $this -> FileID;
		}
		
		public function setFileName($FileName) {
			$this -> FileName = $FileName;
		}

		public function getFileName() {
			return $this -> FileName;
		}
		
		public function setBinaryData($BinaryData) {
			$this -> BinaryData = $BinaryData;
		}

		public function getBinaryData() {
			return $this -> BinaryData;
		}
		
		private function computeHash() {
			if(empty($this -> BinaryData))
				throw new Exception('Can not compute the hash for an empty file!');
			
			$this -> Hash = sha1($this -> BinaryData);
		}
		
		public function getHash() {
			if(empty($this -> Hash))
				$this -> computeHash();
			
			return $this -> Hash;
		}
		
		public function pushToDatabase() {
			if(empty($this -> FileName))
				throw new Exception('Can not commit a file without a name to the database!');
			
			if(empty($this -> BinaryData))
				throw new Exception('Can not commit a file with no content to the database!');
			
			if(empty($this -> Hash))
				$this -> computeHash();			
			
			$query = $this -> DBCon -> query("INSERT INTO `files` (`filename`, `binary`, `hash`, `date_modified`) VALUES ('"
										. $this -> FileName . "', '"
										. $this -> DBCon -> real_escape_string($this -> BinaryData) . "', '"
										. $this -> Hash . "', "
										. "NOW());");
			
			if(!$query)
				throw new Exception('Database query error: ' . $this->DBCon -> error);
			
			$this -> FileID = $this -> DBCon -> insert_id;
			
			return $this -> FileID;
		}
		
		public function pullFromDatabase() {
			if(empty($this -> FileID))
				throw new Exception('Can pull a file without ID from the database!');	
			
			$stmt = $this -> DBCon -> stmt_init();
			
			$stmt -> prepare('
				SELECT		`filename`,
							`binary`,
							`hash`,
							`date_modified`
				FROM		`files` 
				WHERE		`id` = ?
			');
						
			if(!$stmt -> bind_param('i', $this -> FileID))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			if(!$stmt -> execute())
				throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
			
			$stmt -> store_result();
			
			if($stmt -> num_rows == 0)
				throw new Exception('File ' . $this -> FileID . ' does not exist!', ERR_PROBLEM_SOLUTION_NOTFOUND);
			
			$stmt -> bind_result(
				$this -> FileName,
				$this -> BinaryData,
				$this -> Hash,
				$this -> DateModified);
				
			$stmt -> fetch();
			$stmt -> close();
		}
	}