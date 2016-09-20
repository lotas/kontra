# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.6.30)
# Database: mf
# Generation Time: 2016-09-20 21:22:33 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table bugs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `bugs`;

CREATE TABLE `bugs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` varchar(50) NOT NULL DEFAULT '',
  `text` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table chat
# ------------------------------------------------------------

DROP TABLE IF EXISTS `chat`;

CREATE TABLE `chat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `message` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `name` (`name`,`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table kanswer
# ------------------------------------------------------------

DROP TABLE IF EXISTS `kanswer`;

CREATE TABLE `kanswer` (
  `aid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` smallint(6) NOT NULL DEFAULT '0',
  `aquestion` int(11) NOT NULL DEFAULT '0',
  `answer` text NOT NULL,
  `data` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rating` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`aid`),
  KEY `userid` (`userid`),
  KEY `aquestion` (`aquestion`),
  KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table kquest
# ------------------------------------------------------------

DROP TABLE IF EXISTS `kquest`;

CREATE TABLE `kquest` (
  `qid` int(11) NOT NULL AUTO_INCREMENT,
  `userid` smallint(6) NOT NULL DEFAULT '0',
  `question` text NOT NULL,
  `data` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `rating` tinyint(4) DEFAULT '0',
  `shows` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`qid`),
  KEY `userid` (`userid`),
  KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table kusers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `kusers`;

CREATE TABLE `kusers` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `nick` varchar(30) NOT NULL DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(50) DEFAULT '',
  `fullname` varchar(50) DEFAULT '',
  `data` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `questions` smallint(6) DEFAULT '0',
  `answers` smallint(6) DEFAULT '0',
  `rating` int(4) DEFAULT '0',
  `status` enum('new','user','advanced','master','admin') NOT NULL DEFAULT 'new',
  `last_login_date` datetime NOT NULL DEFAULT '2003-01-01 00:00:01',
  `sex` varchar(6) NOT NULL DEFAULT 'male',
  PRIMARY KEY (`id`),
  KEY `nick` (`nick`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `kusers` (`id`, `nick`, `password`, `email`, `fullname`, `data`, `questions`, `answers`, `rating`, `status`, `last_login_date`, `sex`)
VALUES
  (99, 'kontra', 'kontra_rules', 'kontra@loliufl.io', 'Kontra - The Genius Machine', '2008-03-19 00:00:00', 1, 64, 479, 'master', '2008-03-28 15:57:46', 'bot');


# Dump of table onliners
# ------------------------------------------------------------

DROP TABLE IF EXISTS `onliners`;

CREATE TABLE `onliners` (
  `userid` smallint(6) NOT NULL DEFAULT '0',
  `nick` varchar(30) NOT NULL DEFAULT '',
  `logon` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



# Dump of table votes
# ------------------------------------------------------------

DROP TABLE IF EXISTS `votes`;

CREATE TABLE `votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` smallint(6) NOT NULL DEFAULT '0',
  `questid` int(11) DEFAULT NULL,
  `answerid` int(11) DEFAULT NULL,
  `rating` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `questid` (`questid`),
  KEY `answerid` (`answerid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
