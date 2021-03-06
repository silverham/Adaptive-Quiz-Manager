<?php

$templateLogic = new templateLogic;
$templateLogic->setTitle("Select Quiz");
$templateLogic->setHeading("Approved Quiz List");
$templateLogic->startBody();
?>

 <div id="content-centre">
    <?php if (count($quizArray) > 0) { //quizzes available ?>
        <form action="#" method="post">
            <br />
            <br />
            <div id='listWrapper'>
                <h4 class='lightLabel'>Select a quiz to attempt from the list below:</h4>
                <div id='listScroll'>
                    <div class="radios">
                    <?php foreach ($quizArray as $answerRow) { ?>
                        <div class="radio-group">
                        <?php echo ("<input type=\"radio\" name=\"quizid\" value=\"".$answerRow["QUIZ_ID"]."\" id=\"q".$answerRow["SHARED_QUIZ_ID"]."\" />") ?>
                            <?php echo ("<label class=\"radio\" for=\"q".$answerRow["SHARED_QUIZ_ID"]."\">"); ?>
                                <span class="quiz-title"><span id='label'>Quiz Name: </span><?php echo $answerRow["QUIZ_NAME"]; ?></span>
                                <span class="quiz-desc"><span id='label'>Description: </span><?php echo $answerRow["DESCRIPTION"]; ?></span>
                            <?php echo "</label>" ?>
                        </div>
                    <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- pad  the space between submit button and dropdown box -->
            <br />
            <span class="inputError"><?php echo $quizSelectionError; ?></span>
            <br />

            <p>
                Select a quiz from the list above, it contains all public quizzes,
                along with those which you have been assigned private access to attempt. 
                Upon selection, you will be taken to the Quiz Front Page, where you will
                be able to view the quiz information and confirm your choice. 
            </p>

            <br />
            <br />

            <button class="mybutton mySubmit" type="submit" name="selectQuiz" value="Select Quiz">
                Select Quiz
            </button>
        </form>
    <?php } else {  //there are NO quizes ?>
        <p>
            Currently, There are no quizzes available to take.
            <br />
            <br />
            How about having a go an creating a quiz?
            <a href="<?php echo (CONFIG_ROOT_URL . "/edit-quiz/create-quiz.php") ?>" class="mybutton mySubmit">
                    Create Quiz
            </a>
            <a href="<?php echo (CONFIG_ROOT_URL) ?>" class="mybutton myReturn">
                    Home
            </a>
        </p>

        <?php }//end quiz if statement ?>
 </div>
<?php
$templateLogic->endBody();

//html
echo $templateLogic->render();

