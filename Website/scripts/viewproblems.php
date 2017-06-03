<?php
	function getProblemCount($DBCon, $Visible = true) {			
		$stmt = $DBCon -> stmt_init();
		
		if($Visible) {
			// Count only visible problems
			$stmt -> prepare('
				SELECT		COUNT(`id`)
				FROM		`problems` 
				WHERE		`visible` = 1
			');
		} else {
			// Count all problems
			$stmt -> prepare('
				SELECT		COUNT(`id`)
				FROM		`problems` 
			');
		}
		
		if(!$stmt -> execute())
			throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
		
		$stmt -> store_result();
		
		$problemCount = 0;
		
		$stmt -> bind_result($problemCount);
		$stmt -> fetch();
		
		$stmt -> close();
		
		return $problemCount;
	}
	
	function getProblems($DBCon, $page, $resultsOnPage, $Visible = true) {
		$resultsOffset = ($page - 1) * $resultsOnPage;
			
		$stmt = $DBCon -> stmt_init();
		
		if($Visible) {
			// Get only visible problems
			$stmt -> prepare('
				SELECT		`id`, `name`, `author`
				FROM		`problems` 
				WHERE		`visible` = 1
				ORDER BY	`date_added` DESC
				LIMIT		?
				OFFSET		?
			');
			
			if(!$stmt -> bind_param('ii', $resultsOnPage, $resultsOffset))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
		} else {
			// Get all problems
			$stmt -> prepare('
				SELECT		`id`, `name`, `author`
				FROM		`problems` 
				ORDER BY	`date_added` DESC
				LIMIT		?
				OFFSET		?
			');
			
			if(!$stmt -> bind_param('ii', $resultsOnPage, $resultsOffset))
				throw new Exception('MySQL parameter bind error ' . $stmt -> errno . ': ' . $stmt -> error);
		}
		
		if(!$stmt -> execute())
			throw new Exception('MySQL statement execution error ' . $stmt -> errno . ': ' . $stmt -> error);
		
		$problemArray = array();
		$stmt -> store_result();
		
		$problemID = 0;
		$problemName = "";
		$problemAuthor = "";
		
		$stmt -> bind_result(
			$problemID,
			$problemName,
			$problemAuthor);
			
		while($stmt -> fetch()) {
			$problemArray[] = array(
				'id'				=> $problemID,
				'name'				=> $problemName,
				'author'			=> $problemAuthor
			);
		}
		
		$stmt -> close();
		
		return $problemArray;
	}
	
	$resultsOnPage = 10;
	
	if($LoggedIn && $_SESSION['rank'] == 'admin')
		$solutionCount = getProblemCount($DBCon, false);
	else
		$solutionCount = getProblemCount($DBCon);
	
	$pageCount = floor($solutionCount / $resultsOnPage) + ($solutionCount % $resultsOnPage != 0);
	
	$solutionPage = 1;
	if(isset($_GET['page']) && $_GET['page'] >= 1 && $_GET['page'] <= $pageCount)
		$solutionPage = $_GET['page'];
	
	$scriptOut['problemsCurrentPage'] = $solutionPage;
	$scriptOut['problemsCount'] = $solutionCount;
	$scriptOut['problemsPageCount'] = $pageCount;
	
	if($LoggedIn && $_SESSION['rank'] == 'admin')
		$scriptOut['problems'] = getProblems($DBCon, $solutionPage, $resultsOnPage, false);
	else
		$scriptOut['problems'] = getProblems($DBCon, $solutionPage, $resultsOnPage);

	$scriptOut['problemsShowStart'] = ($solutionPage - 1) * $resultsOnPage + 1;
	$scriptOut['problemsShowEnd'] = min(
			$solutionPage * $resultsOnPage, $solutionCount);

	