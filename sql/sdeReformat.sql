#If the tables already exist, drop them
DROP TABLE IF EXISTS `systeminfo`;
DROP TABLE IF EXISTS `systemjumps`;

#Create the tables anew
CREATE TABLE `systeminfo` (
	`systemID` INT,
	`systemName` VARCHAR(255),
	`systemSecurity` DOUBLE,
	`regionID` INT,
	`regionName` VARCHAR(255),
	`knownSpace` BOOLEAN,
	PRIMARY KEY (`systemID`)
);
CREATE TABLE `systemjumps` (
	`systemFrom` INT,
	`systemTo` INT,
	PRIMARY KEY (`systemFrom`,`systemTo`)
);

#Fill the systeminfo table with data from the SDE
INSERT INTO `systeminfo` (`systemID`,`systemName`,`systemSecurity`,`regionID`,`regionName`,`knownSpace`)
	SELECT
		`mapsolarsystems`.`solarSystemID`,
		`mapsolarsystems`.`solarSystemName`,
		`mapsolarsystems`.`security`,
		`mapsolarsystems`.`regionID`,
		`mapregions`.`regionName`,
		`mapregions`.`regionName` REGEXP '^[^[:digit:]]+$'
	FROM
		`mapsolarsystems` JOIN
		`mapregions`
		USING (`regionID`);

#Fill the systemjumps table with data from the SDE
INSERT INTO `systemjumps` (`systemFrom`,`systemTo`)
	SELECT
		`mapsolarsystemjumps`.`fromSolarSystemID`,
		`mapsolarsystemjumps`.`toSolarSystemID`
	FROM
		`mapsolarsystemjumps`;

#Drop the tables imported from the SDE, as at this point they are just taking up space
DROP TABLE `mapregions`;
DROP TABLE `mapsolarsystemjumps`;
DROP TABLE `mapsolarsystems`;