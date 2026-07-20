/*
SQLyog Professional v13.1.1 (64 bit)
MySQL - 5.7.14 : Database - twoquake_qnut
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `qnut_calendar_event_committees` */

CREATE TABLE `qnut_calendar_event_committees` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `eventId` INT(11) DEFAULT NULL,
  `committeeId` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_committee` (`eventId`,`committeeId`)
) ENGINE=MYISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

/*Table structure for table `qnut_calendar_event_resources` */

CREATE TABLE `qnut_calendar_event_resources` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `eventId` INT(10) UNSIGNED NOT NULL,
  `resourceId` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_resource` (`eventId`,`resourceId`)
) ENGINE=MYISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*Table structure for table `qnut_calendar_event_types` */

CREATE TABLE `qnut_calendar_event_types` (
  `id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `code` VARCHAR(32) DEFAULT NULL,
  `name` VARCHAR(128) DEFAULT NULL,
  `description` VARCHAR(256) DEFAULT NULL,
  `public` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `backgroundColor` VARCHAR(16) DEFAULT NULL,
  `borderColor` VARCHAR(16) DEFAULT NULL,
  `textColor` VARCHAR(16) DEFAULT NULL,
  `createdby` VARCHAR(64) NOT NULL DEFAULT 'system',
  `createdon` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `changedby` VARCHAR(64) DEFAULT NULL,
  `changedon` DATETIME DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_calendar_event_type_code` (`code`)
) ENGINE=MYISAM DEFAULT CHARSET=latin1;


/*Data for the table `qnut_calendar_event_types` */

INSERT  INTO `qnut_calendar_event_types`(`id`,`code`,`name`,`description`,`public`,`backgroundColor`,`borderColor`,`textColor`,`createdby`,`createdon`,`changedby`,`changedon`,`active`) VALUES 
(1,'public','Public','Public events',1,'#E0F7FF','#87e1ff','#000000','system','2018-02-02 06:59:20',NULL,NULL,1),
(2,'private','Private','Private events',0,'#E9F6E9','#8FC78F','#000000','system','2018-02-02 06:59:49',NULL,NULL,1),
(3,'outside','Outside','Events for outside group',0,'#E3D1B5','#BD924C','#000000','system','2018-02-02 07:00:18',NULL,NULL,1),
(4,'reservation','Reservation','Resource or room reservations',0,'#CCCCCC','#FF85A9','#000000','system','2018-02-02 07:01:50',NULL,NULL,1),
(0,'service','Service opportunity','Service opportunities',1,'#FFF0F4','#FF85A9','#000000','system','2018-02-02 10:28:17',NULL,NULL,1);


/*Table structure for table `qnut_calendar_events` */

CREATE TABLE `qnut_calendar_events` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(256) DEFAULT NULL,
  `start` DATETIME NOT NULL,
  `end` DATETIME DEFAULT NULL,
  `allDay` TINYINT(1) NOT NULL DEFAULT '0',
  `location` VARCHAR(258) DEFAULT NULL,
  `url` VARCHAR(128) DEFAULT NULL,
  `eventTypeId` INT(11) DEFAULT NULL,
  `description` TEXT,
  `notes` TEXT,
  `recurPattern` VARCHAR(16) DEFAULT NULL,
  `recurEnd` DATE DEFAULT NULL,
  `recurId` INT(11) DEFAULT NULL,
  `recurInstance` DATE DEFAULT NULL,
  `createdby` VARCHAR(50) NOT NULL DEFAULT 'system',
  `createdon` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `changedby` VARCHAR(50) DEFAULT NULL,
  `changedon` DATETIME DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MYISAM AUTO_INCREMENT=58 DEFAULT CHARSET=latin1;

/*Table structure for table `qnut_notification_subscriptions` */

CREATE TABLE `qnut_notification_subscriptions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `notificationTypeId` INT(10) UNSIGNED NOT NULL,
  `itemId` INT(10) UNSIGNED NOT NULL,
  `personId` INT(10) UNSIGNED NOT NULL,
  `leadDays` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uk_notification_subscription` (`notificationTypeId`,`itemId`,`personId`)
) ENGINE=MYISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

/*Table structure for table `qnut_notification_types` */

CREATE TABLE `qnut_notification_types` (
  `id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `code` VARCHAR(32) DEFAULT NULL,
  `name` VARCHAR(128) DEFAULT NULL,
  `description` VARCHAR(256) DEFAULT NULL,
  `createdby` VARCHAR(64) NOT NULL DEFAULT 'system',
  `createdon` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `changedby` VARCHAR(64) DEFAULT NULL,
  `changedon` DATETIME DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_resource_type_code` (`code`)
) ENGINE=MYISAM DEFAULT CHARSET=latin1;

/*Data for the table `qnut_notification_types` */

INSERT  INTO `qnut_notification_types`(`id`,`code`,`name`,`description`,`createdby`,`createdon`,`changedby`,`changedon`,`active`) VALUES 
(1,'calendar','Calendar events','Notify when calendar event occurs','system','2018-02-09 05:43:27',NULL,NULL,1),
(2,'committee-event','Committee Event','Notify when new comittee event posted','system','2018-02-09 05:44:44',NULL,NULL,1);


/*Table structure for table `qnut_resources` */

CREATE TABLE `qnut_resources` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(32) DEFAULT NULL,
  `name` VARCHAR(128) DEFAULT NULL,
  `description` VARCHAR(256) DEFAULT NULL,
  `resourceTypeId` INT(11) UNSIGNED NOT NULL DEFAULT '1',
  `createdby` VARCHAR(64) NOT NULL DEFAULT 'system',
  `createdon` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `changedby` VARCHAR(64) DEFAULT NULL,
  `changedon` DATETIME DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_committee_status_code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
