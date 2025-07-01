-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
-- Host: 127.0.0.1
-- Generation Time: Jun 30, 2025 at 09:09 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `mode_of_procurement`
-- --------------------------------------------------------
CREATE TABLE `mode_of_procurement` (
  `MoPID` int(11) NOT NULL AUTO_INCREMENT,
  `MoPDescription` varchar(255) NOT NULL,
  PRIMARY KEY (`MoPID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `mode_of_procurement` (`MoPID`, `MoPDescription`) VALUES
(1, 'Competitive Bidding'),
(2, 'Limited Source Bidding'),
(3, 'Direct Contracting'),
(4, 'Repeat Order'),
(5, 'Shopping'),
(6, 'NP-53.1 Two Failed Biddings'),
(7, 'NP-53.2 Emergency Cases'),
(8, 'Emergency Procurement under the Bayanihan Act'),
(9, 'NP-53.3 Take-over of Contracts'),
(10, 'NP-53.4 Adjacent or Contiguous'),
(11, 'NP-53.5 Agency-to-Agency'),
(12, 'NP-53.6 Scientific, Scholarly, Artistic Work, Exclusive Technology and Media Services'),
(13, 'NP-53.7 Highly Technical Consultants'),
(14, 'NP-53.8 Defense Cooperation Agreement'),
(15, 'NP-53.9 Small Value Procurement'),
(16, 'NP-53.10 Lease of Real Property and Venue'),
(17, 'NP-53.11 NGO Participation'),
(18, 'NP-53.12 Community Participation'),
(19, 'NP-53.13 UN Agencies, Int''l Organizations or International Financing Institutions'),
(20, 'NP-53.14 Direct Retail Purchase of Petroleum Fuel, Oil and Lubricant (POL) Products and Airline Tickets'),
(21, 'Others - Foreign-funded procurement');

-- --------------------------------------------------------
-- Table structure for table `officeid`
-- --------------------------------------------------------
CREATE TABLE `officeid` (
  `officeID` int(11) NOT NULL AUTO_INCREMENT,
  `officename` varchar(255) NOT NULL,
  `officedetails` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`officeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `officeid` (`officeID`, `officename`, `officedetails`) VALUES
(1, 'OSDS', NULL),
(2, 'OASDS', NULL),
(3, 'ADMIN', NULL),
(4, 'SGOD CHIEF', NULL),
(5, 'CID CHIEF', NULL),
(6, 'CID', NULL),
(7, 'SGOD', NULL),
(8, 'RECORDS', NULL),
(9, 'BAC', NULL),
(10, 'CASH', NULL),
(11, 'BUDGET', NULL),
(12, 'PERSONNEL', NULL),
(13, 'PAYROLL', NULL),
(14, 'SUPPLY', NULL),
(15, 'IT', NULL),
(16, 'MEDICAL', NULL),
(17, 'DENTAL', NULL);

-- --------------------------------------------------------
-- Table structure for table `tbluser`
-- --------------------------------------------------------
CREATE TABLE `tbluser` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `admin` tinyint(1) DEFAULT 0,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `officeID` int(11) DEFAULT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_user_office` (`officeID`),
  CONSTRAINT `fk_user_office` FOREIGN KEY (`officeID`) REFERENCES `officeid` (`officeID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tbluser` (`userID`, `firstname`, `middlename`, `lastname`, `position`, `admin`, `username`, `password`, `officeID`) VALUES
(1, 'Admin', 'Admin', 'Admin', 'Admin', 1, 'admin', 'admin', 1),
(2, 'Eloi', 'Pogi', 'Baculpo', 'Employee', 0, 'user', 'user', 2);

-- --------------------------------------------------------
-- Table structure for table `tblproject`
-- --------------------------------------------------------
CREATE TABLE `tblproject` (
  `projectID` int(11) NOT NULL AUTO_INCREMENT,
  `projectDetails` text DEFAULT NULL,
  `userID` int(11) DEFAULT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `editedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `prNumber` varchar(20) NOT NULL,
  `remarks` text DEFAULT NULL,
  `editedBy` int(11) DEFAULT NULL,
  `lastAccessedAt` datetime DEFAULT NULL,
  `lastAccessedBy` int(11) DEFAULT NULL,
  `MoPID` int(11) DEFAULT NULL,
  `programOwner` varchar(255) DEFAULT NULL,
  `programOffice` varchar(255) DEFAULT NULL,
  `totalABC` int(11) DEFAULT NULL,
  PRIMARY KEY (`projectID`),
  UNIQUE KEY `prNumber` (`prNumber`),
  KEY `userID` (`userID`),
  KEY `fk_edited_by` (`editedBy`),
  KEY `fk_last_accessed_by` (`lastAccessedBy`),
  KEY `fk_project_mop` (`MoPID`),
  CONSTRAINT `fk_edited_by` FOREIGN KEY (`editedBy`) REFERENCES `tbluser` (`userID`) ON DELETE SET NULL,
  CONSTRAINT `fk_last_accessed_by` FOREIGN KEY (`lastAccessedBy`) REFERENCES `tbluser` (`userID`) ON DELETE SET NULL,
  CONSTRAINT `fk_project_mop` FOREIGN KEY (`MoPID`) REFERENCES `mode_of_procurement` (`MoPID`) ON DELETE SET NULL,
  CONSTRAINT `tblproject_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `tbluser` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tblproject` (`projectID`, `projectDetails`, `userID`, `createdAt`, `editedAt`, `prNumber`, `remarks`, `editedBy`, `lastAccessedAt`, `lastAccessedBy`, `MoPID`, `programOwner`, `programOffice`, `totalABC`) VALUES
(1, 'Sample', 1, NULL, NULL, '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Table structure for table `tblproject_stages`
-- --------------------------------------------------------
CREATE TABLE `tblproject_stages` (
  `stageID` int(11) NOT NULL AUTO_INCREMENT,
  `projectID` int(11) NOT NULL,
  `stageName` varchar(255) NOT NULL,
  `createdAt` datetime DEFAULT current_timestamp(),
  `approvedAt` datetime DEFAULT NULL,
  `officeID` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `isSubmitted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`stageID`),
  KEY `projectID` (`projectID`),
  KEY `fk_stage_office` (`officeID`),
  CONSTRAINT `fk_stage_office` FOREIGN KEY (`officeID`) REFERENCES `officeid` (`officeID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `tblproject_stages_fk` FOREIGN KEY (`projectID`) REFERENCES `tblproject` (`projectID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tblproject_stages` (`stageID`, `projectID`, `stageName`, `createdAt`, `approvedAt`, `officeID`, `remarks`, `isSubmitted`) VALUES
(1, 1, 'Purchase Request', NULL, NULL, NULL, NULL, 0),
(2, 1, 'RFQ 1', NULL, NULL, NULL, NULL, 0),
(3, 1, 'RFQ 2', NULL, NULL, NULL, NULL, 0),
(4, 1, 'RFQ 3', NULL, NULL, NULL, NULL, 0),
(5, 1, 'Abstract of Quotation', NULL, NULL, NULL, NULL, 0),
(6, 1, 'Purchase Order', NULL, NULL, NULL, NULL, 0),
(7, 1, 'Notice of Award', NULL, NULL, NULL, NULL, 0),
(8, 1, 'Notice to Proceed', NULL, NULL, NULL, NULL, 0);

COMMIT;