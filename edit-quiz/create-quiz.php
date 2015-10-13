<?php

/* 
 * The Loader for teh create quiz page
 */

// include php files here 
require_once("../includes/config.php");
// end of php file inclusion

//If form is submitted, run this section of code, otherwise just load the view
//Get values from submitted form
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if(isset($_POST['confirmQuiz'])){
        include("create-quiz-post.php");
    }else{
        header('Location: '. CONFIG_ROOT_URL );
    }
    
} else {    //a Get request
    
    include("create-quiz-get.php");

}