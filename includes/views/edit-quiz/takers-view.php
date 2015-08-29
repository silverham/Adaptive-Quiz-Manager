<?php

$templateLogic = new templateLogic;
$templateLogic->setTitle('Manage Takers');
$templateLogic->setSubMenuType("edit-quiz", "takers");
$templateLogic->startBody();
?>


<?php
      echo "<p class=\"inputError\">$confirmAddMessage</p>";
      echo"<p class=\"inputError\">$confirmRemoveMessage</p>"
?>
<br />
<br />

<p>Adding a 'Taker' enables a user to complete your private quiz. Removing a user will disable a Taker's ability to attempt the quiz.</p>
<br />
<form action='#' method='post'>
    <p>Please enter the CSU Username of the User you would like to <span id='label'>enable</span> permissions for:</p>
    <br />
    <span id='label'> Username: </span>
    <input type='text' name='addNewUser' maxlength="20" />
    <button class="mybutton mySubmit" type="submit" name="confirmAddUser" value="Enter">Add user</button>
    <br />
    <br />
    <br />
    <p>Please enter the CSU Username of the User you would like to <span id='label'>disable</span> permissions for:</p>
    <br />
    <span id='label'> Username: </span>
    <input type='text' name='removeUser' maxlength="20" />
    <button class="mybutton mySubmit" type="submit" name="confirmRemoveUser" value="Enter">Remove user</button>
    <br />
</form>

<?php
    $templateLogic->endBody();
//html
    echo $templateLogic->render(); ?>