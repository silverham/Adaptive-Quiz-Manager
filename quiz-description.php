<<<<<<< HEAD
<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

// include php files here 
require_once("includes/config.php");
// end of php file inclusion

//quizData already used so??
//not sure if there is anything to do here

//html
include("quiz-description-view.php");

//echo($quizData["IMAGE"])
=======
<?php

// include php files here 
require_once("includes/config.php");
// end of php file inclusion

//retrieves QUIZ_ID from quiz-list



//Create a table of quiz information
//Don't access $_POST superglobal directly, filter first

  $quizID = filter_input(INPUT_POST,'quizid', FILTER_SANITIZE_STRING);  
  
    $dbLogic = new DB();
    
    $data = array(
        "QUIZ_ID" => "$quizID"
    );
    
    $columns = "*";

    ($answerID = $dbLogic->select($columns, "quiz", $data, true));
    extract($answerID);
    
    //html
    include ('quiz-description-view.php');
>>>>>>> origin/master
