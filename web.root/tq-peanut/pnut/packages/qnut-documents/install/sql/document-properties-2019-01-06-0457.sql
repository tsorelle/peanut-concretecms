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
/*Table structure for table `tops_entity_properties` */

CREATE TABLE if not exists `tops_entity_properties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entityCode` varchar(128) NOT NULL,
  `key` varchar(128) NOT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT '0',
  `valueCount` int(10) unsigned NOT NULL DEFAULT '1',
  `lookup` varchar(128) DEFAULT NULL,
  `required` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `defaultValue` varchar(128) DEFAULT NULL,
  `datatype` varchar(2) NOT NULL DEFAULT 's',
  `label` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*Data for the table `tops_entity_properties` */

insert  into `tops_entity_properties`(`id`,`entityCode`,`key`,`order`,`valueCount`,`lookup`,`required`,`defaultValue`,`datatype`,`label`) values 
(1,'document','committee',3,1,'qnut_committees',0,NULL,'k','committee-entity'),
(2,'document','doctype',1,1,'qnut_document_types',0,NULL,'k','document-doc-type'),
(4,'document','status',2,1,'qnut_document_status_types',0,NULL,'k','document-status-type');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
