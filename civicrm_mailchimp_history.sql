-- phpMyAdmin SQL Dump
-- version 3.3.10
-- http://www.phpmyadmin.net
--
-- Generation Time: Aug 23, 2013 at 02:02 PM
-- Server version: 5.5.27
-- PHP Version: 5.3.27

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `civicrm`
--

-- --------------------------------------------------------

--
-- Table structure for table `civicrm_mailchimp_history`
--

DROP TABLE IF EXISTS `civicrm_mailchimp_history`;
CREATE TABLE IF NOT EXISTS `civicrm_mailchimp_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `history_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `civicrm_mailchimp_history`
--

