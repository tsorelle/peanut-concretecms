DROP TABLE IF EXISTS tops_mailboxes;
CREATE TABLE `tops_mailboxes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `mailboxcode` VARCHAR(30) NOT NULL DEFAULT '',
  `address` VARCHAR(100) DEFAULT NULL,
  `displaytext` VARCHAR(100) DEFAULT NULL,
  `description` VARCHAR(100) DEFAULT NULL,
  `createdby` VARCHAR(50) NOT NULL DEFAULT 'unknown',
  `createdon` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `changedby` VARCHAR(50) DEFAULT NULL,
  `changedon` DATETIME DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT '1',
  `public` TINYINT(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `boxIndex` (`mailboxcode`)
) ENGINE=MYISAM;


INSERT INTO tops_mailboxes (id, mailboxcode,  address,  displayText,  description,  createdby,  createdon,  changedby,  changedon,  active,  public)
VALUES (1,'two-quakers-support','tls@2quakers.net','Peanut Support','Requests for support and information on Peanut','system',CURRENT_DATE,'system',CURRENT_DATE,1,0);

INSERT INTO tops_mailboxes (id, mailboxcode,  address,  displayText,  description,  createdby,  createdon,  changedby,  changedon,  active,  public)
VALUES (2,'bounce','bounce@yoursite.org','Bounce address','Bounce mailbox (please update)','system',CURRENT_DATE,'system',CURRENT_DATE,1,0);

INSERT INTO tops_mailboxes (id, mailboxcode,  address,  displayText,  description,  createdby,  createdon,  changedby,  changedon,  active,  public)
VALUES (3,'admin','admin@yoursite.org','Site Administrator','Administrator mailbox (please update)','system',CURRENT_DATE,'system',CURRENT_DATE,1,0);

INSERT INTO tops_mailboxes (id, mailboxcode,  address,  displayText,  description,  createdby,  createdon,  changedby,  changedon,  active,  public)
VALUES (4,'support','support@yoursite.org','Web Site Support','Support mailbox (please update)','system',CURRENT_DATE,'system',CURRENT_DATE,1,1);

INSERT INTO tops_mailboxes (id, mailboxcode,  address,  displayText,  description,  createdby,  createdon,  changedby,  changedon,  active,  public)
VALUES (5,'contact-form','support@yoursite.org','Contact form','Administrator mailbox (please update)','system',CURRENT_DATE,'system',CURRENT_DATE,1,0);

SELECT * FROM `tops_mailboxes`
