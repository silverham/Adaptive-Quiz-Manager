use aqm;
ALTER TABLE  `quiz` ADD  `CONSISTENT_STATE` TINYINT NOT NULL DEFAULT  '0';
UPDATE  `aqm`.`version` SET  `BUILD_NUMBER` =  '7';
UPDATE  `aqm`.`quiz` SET  `CONSISTENT_STATE` =  '1';