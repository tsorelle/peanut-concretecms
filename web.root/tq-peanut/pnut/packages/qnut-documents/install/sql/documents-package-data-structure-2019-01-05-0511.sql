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
/*Table structure for table `qnut_document_file_types` */

CREATE TABLE `qnut_document_file_types` (
  `id` int(10) unsigned NOT NULL,
  `code` varchar(64) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `createdby` varchar(64) NOT NULL DEFAULT 'system',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `changedby` varchar(64) DEFAULT NULL,
  `changedon` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_FILETYPE_code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

/*Data for the table `qnut_document_file_types` */

insert  into `qnut_document_file_types`(`id`,`code`,`name`,`description`,`createdby`,`createdon`,`changedby`,`changedon`,`active`) values
    (0,'pdf','PDF Document','PDF Readable Document','system','2018-10-03 06:33:31',NULL,NULL,1),
    (2,'doc_','Word Document','Microsoft Word','system','2018-10-03 06:34:32',NULL,NULL,1),
    (3,'xl%','Excel Document ','Microsoft Excel','system','2018-11-10 06:38:38',NULL,NULL,1),
    (4,'csv','CSV Data','Comma seperated value','system','2018-11-10 06:39:15',NULL,NULL,1),
    (5,'txt','Text file','Plain text file','system','2018-11-10 06:39:47',NULL,NULL,1),
    (6,'rtf','Rich Text Document','Rich text format file','system','2018-11-10 06:41:14',NULL,NULL,1);



/*Table structure for table `qnut_document_status_types` */

CREATE TABLE `qnut_document_status_types` (
  `id` int(10) unsigned NOT NULL,
  `code` varchar(64) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `createdby` varchar(64) NOT NULL DEFAULT 'system',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `changedby` varchar(64) DEFAULT NULL,
  `changedon` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_KEYNAME_code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

/*Data for the table `qnut_document_status_types` */

insert  into `qnut_document_status_types`(`id`,`code`,`name`,`description`,`createdby`,`createdon`,`changedby`,`changedon`,`active`) values
  (1,'draft','Draft',NULL,'system','2018-05-24 17:28:37',NULL,NULL,1),
  (2,'proposed','Proposed',NULL,'system','2018-06-29 16:29:01',NULL,NULL,1),
  (3,'approved','Approved',NULL,'system','2018-06-29 16:29:26',NULL,NULL,1),
  (4,'final','Final',NULL,'system','2018-06-29 16:29:54',NULL,NULL,1);



/*Table structure for table `qnut_document_text_index` */

CREATE TABLE `qnut_document_text_index` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `documentId` int(10) unsigned NOT NULL,
  `text` longtext,
  `author` varchar(512) DEFAULT NULL,
  `creationDate` varchar(30) DEFAULT NULL,
  `modificationDate` varchar(30) DEFAULT NULL,
  `pageCount` int(11) DEFAULT NULL,
  `processedDate` datetime DEFAULT NULL,
  `statusMessage` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;

/*Table structure for table `qnut_document_types` */

CREATE TABLE `qnut_document_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(64) NOT NULL,
  `name` varchar(128) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `createdby` varchar(64) NOT NULL DEFAULT 'system',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `changedby` varchar(64) DEFAULT NULL,
  `changedon` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_KEYNAME_code` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*Data for the table `qnut_document_types` */

insert  into `qnut_document_types`(`id`,`code`,`name`,`description`,`createdby`,`createdon`,`changedby`,`changedon`,`active`) values
     (6,'minutes','Minutes',NULL,'system','2018-06-29 16:28:17',NULL,NULL,1),
     (7,'report','Report',NULL,'system','2018-07-30 09:52:16',NULL,NULL,1),
     (8,'newsletter','Newsletter',NULL,'system','2018-07-30 09:52:26',NULL,NULL,1);

/*Table structure for table `qnut_documents` */

CREATE TABLE `qnut_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(256) DEFAULT NULL,
  `filename` varchar(128) NOT NULL,
  `folder` varchar(256) DEFAULT NULL,
  `abstract` text NOT NULL,
  `protected` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `publicationDate` date DEFAULT NULL,
  `createdby` varchar(50) NOT NULL DEFAULT 'system',
  `createdon` datetime DEFAULT CURRENT_TIMESTAMP,
  `changedby` varchar(50) DEFAULT NULL,
  `changedon` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
