<?php
/**
 * Created by PhpStorm.
 * User: Tiberiu
 * Date: 7/26/2017
 * Time: 6:01 PM
 */

if(!$LoggedIn) {
    throw new Exception("User has to be logged in in order to access this page.", ERR_PERMISSION_NOTAUTH);
}
if(isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'default':
        case 'homework':
        case 'newhomework':
        case 'viewhomeworks':
        case 'members':
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
    $Class = new StudentClass($DBCon);
    $Class->SetID($_GET['id']);

    $scriptOut['ClassID'] = $Class->GetID();
    $scriptOut['ClassName'] = $Class->GetName();
    $scriptOut['ClassAccessKey'] = $Class->GetAccessKey();

    $permissionManager = new UserPermissionManager($DBCon);
    $permissionManager -> setUser($User);

    $scriptOut['ClassManagePermission'] = $permissionManager->canManageClass($Class->GetID());

    switch($scriptOut['pageAction']) {
        case 'homework': {
            if (!isset($_GET['homeworkID']) || empty($_GET['homeworkID']))
                throw new Exception("Homework ID not specified.");

            $Homework = new Homework($DBCon);
            $Homework->SetID($_GET['homeworkID']);

            if(isset($_GET['success']))
                $scriptOut['ActionSuccessful'] = true;

            if (isset($_GET['deleteproblem']) && $permissionManager->canManageClass($Class->GetID())) {
                if (empty($_GET['deleteproblem']))
                    throw new Exception("ID of the problem to be deleted not set!");

                $Problem = new Problem($DBCon);
                $Problem->setProblemID($_GET['deleteproblem']);
                $Problem->retrieveFromDB();

                $Homework->RemoveProblem($Problem);

                //TODO: FIX THIS VERY SHITTY APPROACH
                header("Location: /?p=class&id=" . $Class->GetID() . "&action=homework&homeworkID=" .
                    $Homework->GetID() . "&success");
                die();
            }

            if (isset($_GET['addproblem']) && $permissionManager->canManageClass($Class->GetID())) {
                if (empty($_GET['addproblem']))
                    throw new Exception("ID of the problem to be added not set!");

                $Problem = new Problem($DBCon);
                $Problem->setProblemID($_GET['addproblem']);
                $Problem->retrieveFromDB();

                $Homework->AddProblem($Problem);

                //TODO: FIX THIS VERY SHITTY APPROACH
                header("Location: /?p=class&id=" . $Class->GetID() . "&action=homework&homeworkID=" .
                    $Homework->GetID() . "&success");
                die();
            }

            if (isset($_GET['publish']) && $permissionManager->canManageClass($Class->GetID())) {
                $Homework->SetPublishingStatus(Homework::STATUS_PUBLISHED);

                //TODO: FIX THIS VERY SHITTY APPROACH
                header("Location: /?p=class&id=" . $Class->GetID() . "&action=homework&homeworkID=" .
                    $Homework->GetID() . "&success");
                die();
            }

            $scriptOut['HomeworkID'] = $Homework->GetID();
            $scriptOut['HomeworkName'] = $Homework->GetName();
            $scriptOut['HomeworkDeadline'] = $Homework->GetDeadline();
            $scriptOut['HomeworkStatus'] = $Homework->GetStatus();

            $ProblemObjects = $Homework->GetProblems();
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

            $scriptOut['HomeworkProblems'] = $Problems;

            $Users = $Class->GetUsers();

            $MemberInfo = array();

            foreach($Users as $Member) {
                $InfoArray = $Member->getDataArray();

                $ProblemScores = array();
                foreach($ProblemObjects as $Problem) {
                    $Stats = $Problem->getUserSolutionStats($Member->getID(), strtotime($Homework->GetDeadline()));

                    $ProblemScores[] = $Stats['maxScore'];
                }

                $MemberInfo[] = array(
                    "Name" => $InfoArray['name'],
                    "Scores" => $ProblemScores,
                );
            }

            $scriptOut['MemberInfo'] = $MemberInfo;


            break;
        }
        case 'members': {
            $Users = $Class->GetUsers();

            $MembersInfo = array();

            foreach($Users as $Member) {
                $UserPermissionManager = new UserPermissionManager($DBCon);
                $UserPermissionManager -> setUser($Member);
                $InfoArray = $Member->getDataArray();

                $MembersInfo[] = array(
                    "Name" => $InfoArray['name'],
                    "IsTeacher" =>  $permissionManager->canManageClass($Class->GetID())
                );
            }

            $scriptOut['MembersInfo'] = $MembersInfo;
            break;
        }
        case 'viewhomeworks': {
            $Homeworks = $Class->GetHomeworks();

            $HomeworkInfo = array();

            foreach($Homeworks as $Homework) {
                $HomeworkInfo[] = array(
                    "ID" => $Homework->GetID(),
                    "Name" => $Homework->GetName(),
                    "Deadline" => $Homework->GetDeadline(),
                    "Status" => $Homework->GetStatus()
                );
            }

            $scriptOut['HomeworkInfo'] = $HomeworkInfo;

            break;
        }
        case 'newhomework': {
            if (isset($_POST['newHomeworkForm'])) {
                $errorArray = array();

                // Check form integrity
                $formFields = array(
                    'name',
                    'deadline_submit',
                );

                $entireForm = true;
                foreach ($formFields as $field)
                    if (!isset($_POST[$field])) {
                        $entireForm = false;
                        $errorArray[] = ERR_CLASS_NEWHOMEWORK_INTEGRITYCHECK_FAILED;
                    }

                $Homework = new Homework($DBCon);

                if ($entireForm) {
                    // Check for invalid inputs
                    if (empty($_POST['name']))
                        $errorArray[] = ERR_CLASS_NEWHOMEWORK_NAME_NOTPROVIDED;

                    if (strlen($_POST['name']) > 32)
                        $errorArray[] = ERR_CLASS_NEWHOMEWORK_NAME_TOOLONG;

                    if (empty($_POST['deadline_submit']))
                        $errorArray[] = ERR_CLASS_NEWHOMEWORK_DATE_NOTPROVIDED;

                    if (empty($errorArray)) {
                        // SQL Escaping
                        $Name = $DBCon->real_escape_string($_POST['name']);
                        $Deadline = strtotime($DBCon->real_escape_string($_POST['deadline_submit']));

                        $Homework->Create($Name, $Class, $Deadline);
                    }
                }

                if (empty($errorArray)) {
                    $scriptOut['CreateStatus'] = 'OK';
                    $scriptOut['HomeworkID'] = $Homework->GetID();
                } else {
                    $scriptOut['CreateStatus'] = 'FAIL';
                    $scriptOut['CreateErrorArray'] = $errorArray;
                }
            } else {
                throw new Exception("Form not sent!");
            }
            break;
        }
        case 'default':
        default:
        {

            break;
        }
    }
} else {
    throw new Exception("Class ID not set while accessing the page!", ERR_CLASS_ID_NOTPROVIDED);
}

