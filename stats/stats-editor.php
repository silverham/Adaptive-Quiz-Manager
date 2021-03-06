<?php

/** 
 * The loader for the individual user stats selection page
 */

// include php files here 
require_once("../includes/config.php");
include ("timeConverter.php");
// end of php file inclusion

$uid = $_SESSION['username'];

$dbLogic = new dbLogic();
  
        //On first load, set the $_SESSION variable to the QUIZ_ID selected form the quiz list
        //On subsequent page lloads between previous/current versions of the quiz, the $quizidconfirm will remain the same
        //If user selects 'Current version' for all users 
        if(isset($_POST['quizid'])){
          
            $_SESSION['quizid'] = filter_input(INPUT_POST, "quizid");
        }  
        $pageUser = "All users";
        $quizidconfirm = $_SESSION['quizid'];
            
        $shareColumn = "quiz_QUIZ_ID";

        $currentResults = true;

        //If user selects 'Previous versions' for all users    
        if(isset($_POST['previousVersions'])){
            
            $pageUser =  "All users";
            //Get shared quiz id for chosen quiz to prepare results inclusive of older versions
            $whereSharedQuiz = array(
                "QUIZ_ID"=> $quizidconfirm
            );
        
            $sharedID = $dbLogic->select("SHARED_QUIZ_ID", "quiz", $whereSharedQuiz, true);   
            
            $quizidconfirm = $sharedID['SHARED_QUIZ_ID'];
             
            $shareColumn = "shared_SHARED_QUIZ_ID";
            
            $currentResults = false;
        } 

        $wherevalue = array(
            $shareColumn => $quizidconfirm
            
        );
        $wherecolumn2 = array(
            "RESULT_ID" => "result_RESULT_ID"
                
        );
        $notNullColumn = "FINISHED_AT";
        $graphResults = $dbLogic->selectWithColumnsIsNotNull('*', 'result, result_answer', $wherevalue, 
                $wherecolumn2, $notNullColumn, false);
        
        //If no results are returned, quiz hasnt been attempted. Load empty result view
        if(empty($graphResults)){
	    
            include("stats-editor-empty-view.php");
            exit();
        }
        //Creates a new array of the RESULT_ID's
        $totalAttempts = array();
        $i= 0;
        foreach ($graphResults as $rowResults){         
            $totalAttempts[$i] = ($rowResults["RESULT_ID"]);           
            $i++;
        }
        //Retrieves only the unqiue values of RESULT_ID's
        $uniqueAttempts = array_unique($totalAttempts);
        //Creates new array and fills it with unique values granting new array keys [0], [1] etc instead of old.
        $newArray = array_values($uniqueAttempts);

        //Select QUESTION FROM QUESTION WHERE QUESTION_ID = ''
        $questions = array();
        $j= 0;
        foreach ($graphResults as $rowResults){         
            $questions[$j] = ($rowResults["question_QUESTION_ID"]);
            $j++;
        }
           
        //Retrieves only the unqiue values of RESULT_ID's
        $uniqueQuestions = array_unique($questions);
        //Creates new array and fills it with unique values granting new array keys [0], [1] etc instead of old.
        $newQuestionArray = array_values($uniqueQuestions);
        
        //Place questions of quiz in ascending order
        sort($newQuestionArray);
        
        //Get question text for Graph Title
        //Retrieves Question text using question_ids from result, then populates an array to use in stats-graph-view.php
        $questionText = array();
        $q=0;
        for($y=0; $y<count($newQuestionArray); $y++){
                $whereQuestionDescr = array(
                    "QUESTION_ID" => $newQuestionArray[$y]
                );
                
                $questionDescr = $dbLogic->select("QUESTION", "question", $whereQuestionDescr, false);
                
                foreach ($questionDescr as $rowResults){         
                    $questionText[$q] = ($rowResults["QUESTION"]);
                    $q++;
                }
        }
        $graphData = array();
        for($l=0; $l<count($newQuestionArray); $l++){
            
                $whereQuestion = array(              
                $shareColumn => $quizidconfirm,
                "question_QUESTION_ID" => $newQuestionArray[$l]           
            );
            
            $whereAnswer = array (
                "result_answer.ANSWER" => "answer.ANSWER_ID",
                "result_RESULT_ID" => "RESULT_ID"
            );

            $answerResults = $dbLogic->selectWithColumnsIsNotNullGroupBy('question_QUESTION_ID, answer.answer, COUNT(*) as CHOSEN', 
                    "result, result_answer, answer", $whereQuestion, $whereAnswer, $notNullColumn, "answer.answer", false);
            
            foreach($answerResults as $answerNumbers){
                $graphData{$l}[$answerNumbers['answer']] = $answerNumbers['CHOSEN'];
            }            
        }

        $countAttempts = count(array_unique($totalAttempts));
        $countQuestions = count(array_unique($questions));
     
        //Retrieve time values for attempts 
        $timearray = array(
            'times' => array()
            );
        
        foreach($graphResults as $timeResults){
            
                $timearray['times'][] = array(
                    'Started' => $timeResults['STARTED_AT'], 'Finished' =>$timeResults['FINISHED_AT']
                        );               
        }
        
        $sessions = array();
        
        foreach ($timearray as $name) {
            if (is_array($name)) {
                foreach ($name as $application) {
                    $sessions[] = strtotime($application['Finished']) - strtotime($application['Started']);
                }
            } else {
                echo "There was an error, Start/Finish dates are not stored correctly.";
            }
        }
        
        $average = array_sum($sessions) / count($sessions);
        
        //Pass time in seconds to timeConverter function to change to a readable format
        $averageTime = secs_to_h($average);
        
        $h=0;
        $min = $sessions[$h];
        $max = $sessions[$h];
        
        for($h=0; $h<count($sessions); $h++){

            if($min > $sessions[$h]){
                $min = $sessions[$h];
            }
            if($max < $sessions[$h]){
                $max = $sessions[$h];
            }
        }
        
        $minTime = secs_to_h($min);
        $maxTime = secs_to_h($max);
        
    include("stats-editor-graph-view.php");