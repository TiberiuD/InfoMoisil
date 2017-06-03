/*
Navicat MySQL Data Transfer

Source Server         : HomeServer
Source Server Version : 50555
Source Host           : 10.8.0.1:3306
Source Database       : judge

Target Server Type    : MYSQL
Target Server Version : 50555
File Encoding         : 65001

Date: 2017-05-31 10:35:27
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for files
-- ----------------------------
DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `binary` longblob NOT NULL,
  `hash` varchar(255) NOT NULL,
  `date_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for job_tests
-- ----------------------------
DROP TABLE IF EXISTS `job_tests`;
CREATE TABLE `job_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `status` enum('correct','failed','tle','mle') NOT NULL,
  `memory_used` double DEFAULT NULL,
  `execution_time` double DEFAULT NULL,
  `output_file_id` int(11) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `language` enum('c','cpp','pascal') NOT NULL DEFAULT 'c',
  `send_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'low',
  `done` bit(1) NOT NULL DEFAULT b'0',
  `done_time` timestamp NULL DEFAULT NULL,
  `compiler_message` text,
  `score` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for notifications
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `time` datetime NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `read` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `permission` varchar(255) NOT NULL,
  `arg1` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for private_messages
-- ----------------------------
DROP TABLE IF EXISTS `private_messages`;
CREATE TABLE `private_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user` int(11) NOT NULL,
  `to_user` int(11) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `message` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for problem_tests
-- ----------------------------
DROP TABLE IF EXISTS `problem_tests`;
CREATE TABLE `problem_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `problem_id` int(11) NOT NULL,
  `input_file_id` int(11) NOT NULL,
  `output_file_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for problems
-- ----------------------------
DROP TABLE IF EXISTS `problems`;
CREATE TABLE `problems` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `name_clean` varchar(255) NOT NULL,
  `statement` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `input` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `output` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `notes` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `example_input` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `example_output` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `author` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `io_method` enum('std','file') NOT NULL DEFAULT 'file',
  `max_mem` int(11) NOT NULL,
  `max_time` int(11) NOT NULL,
  `solution_file_id` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `visible` bit(1) NOT NULL DEFAULT b'0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `coins` decimal(10,0) NOT NULL,
  `register_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `rank` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `preffered_language` enum('c','cpp','pas') DEFAULT 'c',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
