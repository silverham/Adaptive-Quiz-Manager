<?php

// include php files here 
require_once("includes/config.php");
// end of php file inclusion


$dbLogic = new DB();
$uid = ($_SESSION["username"]);
$dataArray = array(
        "IS_PUBLIC" => "1",
        "user_USERNAME" => "$uid"
        );
    $columnWhere = array(
        "Quiz_QUIZ_ID" => "QUIZ_ID"
    );
    ($answerID = $dbLogic->selectQuiz("QUIZ_NAME, QUIZ_ID", "quiz, taker", $dataArray, $columnWhere, false));
    //QUIZ_ID needed as you can put it in the URL maybe?

//html
include("quiz-list-view.php");
