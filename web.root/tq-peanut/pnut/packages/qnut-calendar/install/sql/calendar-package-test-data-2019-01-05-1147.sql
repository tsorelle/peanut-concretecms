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
/*Data for the table `qnut_calendar_event_committees` */

INSERT  INTO `qnut_calendar_event_committees`(`id`,`eventId`,`committeeId`) VALUES 
(1,8,49),
(2,2,49),
(4,2,46),
(6,44,45);

/*Data for the table `qnut_calendar_event_resources` */

INSERT  INTO `qnut_calendar_event_resources`(`id`,`eventId`,`resourceId`) VALUES 
(1,6,3),
(2,8,2),
(3,8,6),
(4,2,5),
(7,44,3);


/*Data for the table `qnut_calendar_events` */

INSERT  INTO `qnut_calendar_events`(`id`,`title`,`start`,`end`,`allDay`,`location`,`url`,`eventTypeId`,`description`,`notes`,`recurPattern`,`recurEnd`,`recurId`,`recurInstance`,`createdby`,`createdon`,`changedby`,`changedon`,`active`) VALUES 
(2,'Modified All Day Event','2018-04-09 00:00:00',NULL,0,'new location',NULL,1,'<p>Description test</p>','Notes test',NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52','tsorelle','2018-04-30 06:17:34',1),
(3,'Long Event','2018-04-25 09:00:00','2018-04-25 15:30:00',0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52',NULL,NULL,1),
(4,'Old Repeating Event','2018-01-09 00:00:00',NULL,1,NULL,NULL,1,'','','wk1,34','2018-05-31',NULL,NULL,'system','2018-02-03 09:08:52','tsorelle','2018-05-02 09:20:57',1),
(5,'Repeating Event 2','2018-04-02 00:00:00',NULL,1,NULL,NULL,1,'<p>test</p>','','wk1,3',NULL,NULL,NULL,'system','2018-02-03 09:08:52','tsorelle','2018-04-28 09:35:39',1),
(6,'Repeating Event 1','2018-01-16 16:00:00',NULL,0,'Butler School of Music - practice hall 7A',NULL,2,'','','md1,15',NULL,NULL,NULL,'system','2018-02-03 09:08:52','tsorelle','2018-06-18 17:43:25',1),
(7,'Conference','2018-05-04 00:00:00',NULL,1,'Quaker Hill Center',NULL,3,'','',NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52','tsorelle','2018-05-02 07:36:49',1),
(8,'Forum - Peace in the middle east','2018-04-14 10:30:00','2018-04-14 12:30:00',0,'Meeting House',NULL,1,'<h2>This is a big event.</h2>\r\n<p>A mighty event. Come one come all to the Quaker meeting house.  I am making this description as long as possible.\r\n<p>The reason is to text the display capacity of the form.  See? Does this work? This is a big event. A mighty event. Come one come all to the Quaker meeting house.  I am making this description as long as possible. The reason is to text the display capacity of the form.  See? Does this work?</p>\r\n<p>The reason is to text the display capacity of the form.  See? Does this work? This is a big event. A mighty event. Come one come all to the Quaker meeting house.  I am making this description as long as possible. The reason is to text the display capacity of the form.  See? Does this work?</p>\r\n<p>The reason is to text the display capacity of the form.  See? Does this work? This is a big event. A mighty event. Come one come all to the Quaker meeting house.  I am making this description as long as possible. The reason is to text the display capacity of the form.  See? Does this work?</p>\r\n','Here are some private notes.\r\n\r\nAnd here is a paragraph.  The quick rabbit jumped over the moon or something like that. The quick rabbit jumped over the moon or something like that. The quick rabbit jumped over the moon or something like that.',NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52',NULL,NULL,1),
(10,'Meeting','2018-04-12 14:30:00','2018-04-12 15:30:00',0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52',NULL,NULL,1),
(11,'Happy Hour','2018-04-03 17:30:00','2018-04-03 17:30:00',0,'Bozo\'s Lounge',NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52',NULL,NULL,1),
(12,'Dinner','2018-03-30 20:00:00','2018-03-30 20:00:00',0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52',NULL,NULL,1),
(13,'Birthday Party','2018-05-25 09:19:00','2018-05-25 11:19:00',0,NULL,NULL,2,'','',NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52','tsorelle','2018-05-24 10:54:03',1),
(29,'NEW Repeat 1','2018-04-01 00:00:00',NULL,1,'Somewhere',NULL,1,'','','wk1,3','2018-05-31',NULL,NULL,'tsorelle','2018-04-27 09:03:21','tsorelle','2018-04-29 06:42:04',1),
(15,'March Meeting','2018-03-12 10:30:00','2018-03-12 12:30:00',0,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'system','2018-02-03 09:08:52',NULL,NULL,1),
(16,'Another Long One','2018-05-28 00:00:00','2018-05-28 00:00:00',1,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,'system','2018-02-23 06:36:31',NULL,NULL,1),
(19,'Rescheduled Event 2','2018-04-18 00:00:00',NULL,1,'Butler School of Music - practice hall 7A',NULL,2,NULL,NULL,NULL,NULL,5,'2018-04-17','system','2018-02-03 09:08:52',NULL,NULL,0),
(56,'range - 7 days','2018-05-07 10:15:00','2018-05-07 11:15:00',0,'',NULL,1,'','',NULL,NULL,NULL,NULL,'tsorelle','2018-05-05 10:15:54','tsorelle','2018-05-05 10:23:31',1),
(44,'May Retreats','2018-05-23 00:00:00',NULL,1,'',NULL,1,'','','wk1,4',NULL,NULL,NULL,'tsorelle','2018-05-01 08:40:23','tsorelle','2018-05-02 08:59:22',1),
(57,'Before notification range','2018-04-29 10:16:00','2018-04-29 11:16:00',0,'',NULL,1,'','',NULL,NULL,NULL,NULL,'tsorelle','2018-05-05 10:16:29','tsorelle','2018-05-05 10:16:29',1),
(54,'Start Notification test','2018-04-30 10:07:00','2018-04-30 11:07:00',0,'',NULL,1,'','',NULL,NULL,NULL,NULL,'tsorelle','2018-05-05 10:07:43','tsorelle','2018-05-05 10:13:22',1),
(55,'range 6','2018-05-06 10:14:00','2018-05-06 11:14:00',0,'',NULL,1,'','',NULL,NULL,NULL,NULL,'tsorelle','2018-05-05 10:15:15','tsorelle','2018-05-05 10:23:18',1);

/*Data for the table `qnut_notification_subscriptions` */

INSERT  INTO `qnut_notification_subscriptions`(`id`,`notificationTypeId`,`itemId`,`personId`,`leadDays`) VALUES 
(5,1,13,173,2);

/*Data for the table `qnut_resources` */

INSERT  INTO `qnut_resources`(`id`,`code`,`name`,`description`,`resourceTypeId`,`createdby`,`createdon`,`changedby`,`changedon`,`active`) VALUES 
(1,'worship-room','Worship room','Meeting house worship room',1,'system','2018-02-02 10:39:03',NULL,NULL,1),
(2,'social-hall','Social hall','Social hall at meeting house',1,'system','2018-02-02 10:39:37',NULL,NULL,1),
(3,'library','Library','Library at meeting house',1,'system','2018-02-02 10:39:58',NULL,NULL,1),
(4,'classroom-a','Classroom A','Classroom A',1,'system','2018-02-02 10:40:47',NULL,NULL,1),
(5,'nursery','Nursery','Nursery',1,'system','2018-02-02 10:41:54',NULL,NULL,1),
(6,'projector','Projector','Computer projection',1,'system','2018-02-02 10:42:11',NULL,NULL,1),
(7,'Screen','Screen','Projection screen',1,'system','2018-02-02 10:42:34',NULL,NULL,1),
(8,'tv-cart','TV Cart','Cart with video monitoy and dvd player',1,'system','2018-02-02 10:43:28',NULL,NULL,1);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
