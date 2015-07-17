SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `marines` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;
USE `marines` ;

-- -----------------------------------------------------
-- Table `marines`.`marine`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `marines`.`marine` ;

CREATE TABLE IF NOT EXISTS `marines`.`marine` (
  `id_marine` INT NOT NULL AUTO_INCREMENT,
  `identifier` INT NULL,
  `mmsi` INT NULL,
  `name` VARCHAR(45) NULL,
  `flag` VARCHAR(3) NULL,
  `type` INT NULL,
  `length` INT NULL,
  `port` VARCHAR(45) NULL,
  `col11` INT NULL,
  `col12` INT NULL,
  `col13` INT NULL,
  `col14` INT NULL,
  `col15` INT NULL,
  PRIMARY KEY (`id_marine`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `marines`.`track`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `marines`.`track` ;

CREATE TABLE IF NOT EXISTS `marines`.`track` (
  `id_track` INT NOT NULL AUTO_INCREMENT,
  `id_marine` INT NOT NULL,
  `lat` FLOAT NULL COMMENT 'широта координаты',
  `lon` FLOAT NULL COMMENT 'долгота координаты',
  `speed` INT NULL,
  `course` INT NULL,
  `age` INT NULL,
  `date_add` DATETIME NULL,
  PRIMARY KEY (`id_track`, `id_marine`),
  INDEX `fk_track_marine_idx` (`id_marine` ASC),
  CONSTRAINT `fk_track_marine`
    FOREIGN KEY (`id_marine`)
    REFERENCES `marines`.`marine` (`id_marine`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
