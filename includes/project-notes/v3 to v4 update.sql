use aqm;
UPDATE  `aqm`.`version` SET  `BUILD_NUMBER` =  '4';

ALTER TABLE  `result` ADD  `shared_SHARED_QUIZ_ID` INT NOT NULL AFTER  `quiz_QUIZ_ID`;
ALTER TABLE  `result` ADD INDEX (  `shared_SHARED_QUIZ_ID` ) ;

SET foreign_key_checks = 0;
#ALTER TABLE  `result` DROP FOREIGN KEY  `fk_Result_User1` ;
#ALTER TABLE `result` ADD FOREIGN KEY (`quiz_QUIZ_ID`) REFERENCES `aqm`.`quiz`(`QUIZ_ID`) ON DELETE RESTRICT ON UPDATE RESTRICT;
#ALTER TABLE `result` DROP FOREIGN KEY `result_ibfk_8`;
ALTER TABLE `result` ADD FOREIGN KEY (`shared_SHARED_QUIZ_ID`) REFERENCES `aqm`.`quiz`(`SHARED_QUIZ_ID`) ON DELETE RESTRICT ON UPDATE RESTRICT;
#ALTER TABLE `result` ADD FOREIGN KEY (`user_USERNAME`) REFERENCES `aqm`.`user`(`USERNAME`) ON DELETE RESTRICT ON UPDATE RESTRICT;
SET foreign_key_checks = 1;

ALTER TABLE  `editor` ADD INDEX (  `user_USERNAME` ) ;
ALTER TABLE quiz DROP INDEX QUIZ_ID_UNIQUE;
ALTER TABLE `quiz` ADD INDEX( `IS_PUBLIC`, `NO_OF_ATTEMPTS`, `TIME_LIMIT`, `DATE_OPEN`, `DATE_CLOSED`, `IS_ENABLED`);
ALTER TABLE  `result_answer` ADD INDEX (  `PASS_NO` ) ;
ALTER TABLE user DROP INDEX USERNAME_UNIQUE;
ALTER TABLE  `user` ADD INDEX (  `ADMIN_TOGGLE` ) ;

#drop key prior to rename 
ALTER TABLE  `taker` DROP FOREIGN KEY  `taker_ibfk_1` ;
ALTER TABLE  `taker` CHANGE  `quiz_QUIZ_ID`  `shared_SHARED_QUIZ_ID` INT( 11 ) NOT NULL;
ALTER TABLE  `taker` ADD FOREIGN KEY (  `shared_SHARED_QUIZ_ID` ) REFERENCES  `aqm`.`quiz` (
`SHARED_QUIZ_ID`
) ON DELETE RESTRICT ON UPDATE RESTRICT ;

#drop key
ALTER TABLE  `editor` DROP FOREIGN KEY  `editor_ibfk_1` ;
ALTER TABLE `editor` CHANGE `quiz_QUIZ_ID` `shared_SHARED_QUIZ_ID` INT(11) NOT NULL;
ALTER TABLE  `editor` ADD FOREIGN KEY (  `shared_SHARED_QUIZ_ID` ) REFERENCES  `aqm`.`quiz` (
`SHARED_QUIZ_ID`
) ON DELETE RESTRICT ON UPDATE RESTRICT ;



