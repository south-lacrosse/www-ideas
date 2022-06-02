-- -----------------------------------------------------
-- Ideas for possible tables for handling results, ref
-- assignments etc. so we don't lose them
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Table result_code
-- -----------------------------------------------------
DROP TABLE IF EXISTS `result_code`;
CREATE TABLE IF NOT EXISTS `result_code` (
  `id` SMALLINT NOT NULL AUTO_INCREMENT,
  `code` CHAR(4) NOT NULL,
  `description` VARCHAR(50) NOT NULL,
  `result` VARCHAR(75) NULL,
  `home_result` CHAR(1) NULL,
  `away_result` CHAR(1) NULL,
  `home_points` SMALLINT NOT NULL,
  `away_points` SMALLINT NOT NULL,
  `home_goals` SMALLINT NULL,
  `away_goals` SMALLINT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `code_uq` (`code`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
  
INSERT INTO `result_code` (`code`, `description`, `result`, `home_result`,
  `away_result`, `home_points`, `away_points`, `home_goals`,`away_goals`)
VALUES
	('HW', 'Home win', NULL, 'W','L', 4,2, NULL,NULL)
	,('AW', 'Away win', NULL, 'L','W', 2,4, NULL,NULL)
	,('D', 'Draw', NULL, 'D','D', 3,3, NULL,NULL)
	,('HC', 'Home team concedes', '0 v 10 w/o', 'L','W', 0,4, 0,10)
	,('AC', 'Away team concedes', '10 v 0 w/o', 'W','L', 4,0, 10,0)
	,('HC24', 'Home team concedes within 24 hours of a match', '0 v 10 w/o<sup>*</sup>', 'L','W', -1,4, 0,10)
	,('AC24', 'Away team concedes within 24 hours of a match', '10 v 0 w/o<sup>*</sup>', 'W','L', 4,-1, 10,0)
	,('R', 'Rearranged by clubs', 'R - R', 'R','R', 0,0, NULL,NULL)
	,('PP', 'Postponed', 'P - P', 'P','P', 0,0, NULL,NULL)
	,('A',  'Abandoned', 'A - A', 'A','A', 0,0, NULL,NULL)
	,('V',  'Void', 'Void', 'V','V', 0,0, NULL,NULL)
	,('C',  'Cancelled', 'C - C', 'C','C', 0,0, NULL,NULL)
	;

-- -----------------------------------------------------
-- Table reported_result
-- Keep all submissions by teams, and then the admin
-- can make the final call
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `reported_result` (
  `id` INT NOT NULL,
  `fixture_id` INT NOT NULL,
  `user_id` BIGINT(20) UNSIGNED, -- from WordPress
  `home_goals` SMALLINT NOT NULL DEFAULT 0,
  `away_goals` SMALLINT NOT NULL DEFAULT 0,
  `result_code` CHAR(4) NOT NULL,
  `disputed` BOOLEAN NOT NULL DEFAULT FALSE,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` TIMESTAMP NULL,
  PRIMARY KEY (id),
  INDEX `reported_result_by_fixture` (`fixture_id`),
  INDEX `reported_result_by_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- -----------------------------------------------------
-- Table audit - just audit every change to be safe
-- -----------------------------------------------------
DROP TABLE IF EXISTS `audit`;
CREATE TABLE IF NOT EXISTS `audit` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `table_name` VARCHAR(30) NOT NULL,
  `table_id` INT NOT NULL,
  `user_id` BIGINT(20) UNSIGNED, -- from WordPress
  `action` CHAR(1) NOT NULL,
  `old_data` TEXT,
  `new_data` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

-- -----------------------------------------------------
-- Table referee_role
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `referee_role` (
  `referee_role` CHAR(3) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`referee_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
INSERT INTO `referee_role`
VALUES
	(1, 'HR','Head Referee'),
	(2, 'R1','Referee 1'),
	(3, 'R2','Referee 2'),
	(4, 'CBO','Chief Bench Official'),
	(5, 'BM','Bench Manager')
	;

-- -----------------------------------------------------
-- Table referee_assignment
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `referee_assignment` (
  `id` INT NOT NULL,
  `fixture_id` INT NOT NULL,
  `referee_role` CHAR(3) NOT NULL,
  `user_id` BIGINT(20) UNSIGNED, -- from WordPress ??? or just name, or refs table?
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` TIMESTAMP NULL,
  PRIMARY KEY (id),
  INDEX `referee_assignment_by_user` (`user_id`),
  INDEX `referee_assignment_by_fixture` (`fixture_id`),
  UNIQUE INDEX `referee_assignment_uq` (`fixture_id`, `referee_role`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;