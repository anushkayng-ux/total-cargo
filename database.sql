-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: tpt_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(50) NOT NULL,
  `module_ref_id` int(10) unsigned DEFAULT NULL,
  `action_type` varchar(40) NOT NULL,
  `action_description` varchar(400) DEFAULT NULL,
  `old_value_json` mediumtext DEFAULT NULL,
  `new_value_json` mediumtext DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `actor_type` varchar(20) DEFAULT 'staff',
  `client_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `module_name_module_ref_id` (`module_name`,`module_ref_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,'auth',1,'login','User admin logged in #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-29 11:01:40'),(2,'lead',21,'create','Lead captured #21',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-03 12:01:40'),(3,'lead',7,'create','Lead captured #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-05 04:01:40'),(4,'lead',17,'create','Lead captured #17',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-03 08:01:40'),(5,'lead',1,'create','Lead captured #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-05 05:01:40'),(6,'lead',20,'create','Lead captured #20',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-04 11:01:40'),(7,'lead',6,'create','Lead captured #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-01 21:01:40'),(8,'lead',11,'create','Lead captured #11',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-04 16:01:40'),(9,'lead',12,'create','Lead captured #12',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-05 05:01:40'),(10,'lead',29,'create','Lead captured #29',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-03 22:01:40'),(11,'lead',23,'create','Lead captured #23',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-04 06:01:40'),(12,'lead',21,'status','Lead status updated #21',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-19 22:01:40'),(13,'lead',7,'status','Lead status updated #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-20 14:01:40'),(14,'lead',17,'status','Lead status updated #17',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-20 08:01:40'),(15,'lead',1,'status','Lead status updated #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-22 01:01:40'),(16,'lead',20,'status','Lead status updated #20',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-23 14:01:40'),(17,'rfq',1,'create','RFQ raised #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-18 21:01:40'),(18,'rfq',2,'create','RFQ raised #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-17 00:01:40'),(19,'rfq',3,'create','RFQ raised #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-16 21:01:40'),(20,'rfq',4,'create','RFQ raised #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-15 23:01:40'),(21,'rfq',5,'create','RFQ raised #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-17 02:01:40'),(22,'rfq',6,'create','RFQ raised #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-15 18:01:40'),(23,'rfq',7,'create','RFQ raised #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-19 09:01:40'),(24,'rfq',8,'create','RFQ raised #8',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-17 14:01:40'),(25,'rfq',19,'create','RFQ raised #19',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-19 13:01:40'),(26,'rfq',20,'create','RFQ raised #20',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-17 15:01:40'),(27,'rfq',9,'create','RFQ raised #9',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-19 03:01:40'),(28,'rfq',10,'create','RFQ raised #10',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-19 23:01:40'),(29,'rfq',11,'create','RFQ raised #11',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-15 23:01:40'),(30,'rfq',12,'create','RFQ raised #12',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-18 14:01:40'),(31,'rfq',13,'create','RFQ raised #13',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-19 05:01:40'),(32,'rfq',14,'create','RFQ raised #14',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-15 11:01:40'),(33,'rfq',15,'create','RFQ raised #15',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-19 21:01:40'),(34,'rfq',16,'create','RFQ raised #16',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-15 13:01:40'),(35,'rfq',17,'create','RFQ raised #17',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-20 00:01:40'),(36,'rfq',18,'create','RFQ raised #18',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-16 15:01:40'),(37,'rfq',1,'dispatch','WhatsApp RFQ dispatched to 3 vendors — RFQ #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-31 01:01:40'),(38,'rfq',2,'dispatch','WhatsApp RFQ dispatched to 3 vendors — RFQ #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-30 18:01:40'),(39,'rfq',3,'dispatch','WhatsApp RFQ dispatched to 3 vendors — RFQ #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-01 19:01:40'),(40,'rfq',4,'dispatch','WhatsApp RFQ dispatched to 3 vendors — RFQ #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-03 06:01:40'),(41,'rfq',5,'dispatch','WhatsApp RFQ dispatched to 3 vendors — RFQ #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-03 16:01:40'),(42,'rfq',6,'dispatch','WhatsApp RFQ dispatched to 3 vendors — RFQ #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-01 14:01:40'),(43,'quotation',1,'approve','Quotation awarded for RFQ #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-16 07:01:40'),(44,'quotation',2,'approve','Quotation awarded for RFQ #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-18 19:01:40'),(45,'quotation',3,'approve','Quotation awarded for RFQ #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-16 16:01:40'),(46,'quotation',4,'approve','Quotation awarded for RFQ #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-18 21:01:40'),(47,'booking',1,'create','Booking created #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-03 00:01:40'),(48,'booking',2,'create','Booking created #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-04 02:01:40'),(49,'booking',3,'create','Booking created #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-04 07:01:40'),(50,'booking',4,'create','Booking created #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-02 08:01:40'),(51,'booking',5,'create','Booking created #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-03-31 20:01:40'),(52,'booking',6,'create','Booking created #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-04 08:01:40'),(53,'booking',7,'create','Booking created #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-04 09:01:40'),(54,'booking',8,'create','Booking created #8',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-02 15:01:40'),(55,'booking',1,'approve','Booking approved #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-18 16:01:40'),(56,'booking',2,'approve','Booking approved #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-18 21:01:40'),(57,'booking',3,'approve','Booking approved #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-17 21:01:40'),(58,'booking',4,'approve','Booking approved #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-16 00:01:40'),(59,'booking',5,'approve','Booking approved #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-14 21:01:40'),(60,'booking',6,'approve','Booking approved #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-17 18:01:40'),(61,'trip',1,'create','Trip opened #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-16 14:01:40'),(62,'trip',2,'create','Trip opened #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-18 16:01:40'),(63,'trip',3,'create','Trip opened #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-15 13:01:40'),(64,'trip',4,'create','Trip opened #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-19 05:01:40'),(65,'trip',5,'create','Trip opened #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-14 16:01:40'),(66,'trip',6,'create','Trip opened #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-15 11:01:40'),(67,'trip',7,'create','Trip opened #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-18 19:01:40'),(68,'trip',8,'create','Trip opened #8',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-15 00:01:40'),(69,'trip',1,'status','Trip status advanced #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-24 08:01:40'),(70,'trip',2,'status','Trip status advanced #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-22 02:01:40'),(71,'trip',3,'status','Trip status advanced #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-19 19:01:40'),(72,'trip',4,'status','Trip status advanced #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-21 05:01:40'),(73,'trip',5,'status','Trip status advanced #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-21 03:01:40'),(74,'invoice',1,'create','Invoice generated #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-23 11:01:40'),(75,'invoice',7,'create','Invoice generated #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-20 00:01:40'),(76,'invoice',6,'create','Invoice generated #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-20 23:01:40'),(77,'invoice',2,'create','Invoice generated #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-20 12:01:40'),(78,'invoice',3,'create','Invoice generated #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-19 15:01:40'),(79,'invoice',5,'create','Invoice generated #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-19 16:01:40'),(80,'invoice',4,'create','Invoice generated #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-20 03:01:40'),(81,'invoice',1,'approve','Invoice finalized #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-23 02:01:40'),(82,'invoice',7,'approve','Invoice finalized #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-23 05:01:40'),(83,'invoice',6,'approve','Invoice finalized #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-25 09:01:40'),(84,'invoice',2,'approve','Invoice finalized #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-24 09:01:40'),(85,'receipt',1,'create','Client receipt recorded against invoice #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-28 13:01:40'),(86,'receipt',7,'create','Client receipt recorded against invoice #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-27 11:01:40'),(87,'receipt',6,'create','Client receipt recorded against invoice #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-26 20:01:40'),(88,'receipt',2,'create','Client receipt recorded against invoice #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-25 03:01:40'),(89,'receipt',3,'create','Client receipt recorded against invoice #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-29 02:01:40'),(90,'receipt',5,'create','Client receipt recorded against invoice #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-26 04:01:40'),(91,'receipt',4,'create','Client receipt recorded against invoice #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-28 23:01:40'),(92,'vendor_bill',6,'create','Vendor bill posted #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-24 21:01:40'),(93,'vendor_bill',1,'create','Vendor bill posted #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-25 09:01:40'),(94,'vendor_bill',2,'create','Vendor bill posted #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-25 22:01:40'),(95,'vendor_bill',3,'create','Vendor bill posted #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-23 13:01:40'),(96,'vendor_bill',7,'create','Vendor bill posted #7',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-25 09:01:40'),(97,'vendor_bill',4,'create','Vendor bill posted #4',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-22 22:01:40'),(98,'vendor_bill',5,'create','Vendor bill posted #5',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-21 13:01:40'),(99,'vendor_bill',6,'update','Vendor payment captured on bill #6',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-27 15:01:40'),(100,'vendor_bill',1,'update','Vendor payment captured on bill #1',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-29 17:01:40'),(101,'vendor_bill',2,'update','Vendor payment captured on bill #2',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-28 18:01:40'),(102,'vendor_bill',3,'update','Vendor payment captured on bill #3',NULL,NULL,1,'staff',NULL,'127.0.0.1','Mozilla/5.0 TPT Demo Seed','2026-04-27 14:01:40');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alert_rules`
--

DROP TABLE IF EXISTS `alert_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alert_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_key` varchar(60) NOT NULL,
  `audience` varchar(30) NOT NULL,
  `channel` varchar(20) NOT NULL DEFAULT 'email',
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_key_audience_channel` (`event_key`,`audience`,`channel`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_rules`
--

LOCK TABLES `alert_rules` WRITE;
/*!40000 ALTER TABLE `alert_rules` DISABLE KEYS */;
INSERT INTO `alert_rules` VALUES (1,'rfq_dispatched','vendor','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(2,'quote_selected','vendor','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(3,'quote_selected','client','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(4,'trip_assigned','client','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(5,'trip_assigned','vendor','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(6,'trip_in_transit','client','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(7,'trip_in_transit','internal','email',0,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(8,'trip_arrived','client','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(9,'trip_arrived','internal','email',0,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(10,'trip_delivered','client','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(11,'trip_delivered','vendor','email',0,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(12,'trip_delivered','internal','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(13,'driver_dispatch','internal','email',1,'2026-05-12 05:58:08','2026-05-12 05:58:08'),(14,'driver_dispatch','driver','email',0,'2026-05-12 05:58:08','2026-05-12 05:58:08');
/*!40000 ALTER TABLE `alert_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_no` varchar(30) NOT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `client_id` int(10) unsigned DEFAULT NULL,
  `vendor_id` int(10) unsigned DEFAULT NULL,
  `final_buy_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `final_sell_rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `margin_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `route_text` varchar(255) DEFAULT NULL,
  `vehicle_type` varchar(80) DEFAULT NULL,
  `vehicle_count` smallint(5) unsigned DEFAULT 1,
  `load_details` varchar(255) DEFAULT NULL,
  `loading_date` date DEFAULT NULL,
  `billing_party` varchar(200) DEFAULT NULL,
  `consignee_name` varchar(200) DEFAULT NULL,
  `consignee_mobile` varchar(20) DEFAULT NULL,
  `consignee_address` varchar(400) DEFAULT NULL,
  `consignee_gstin` varchar(20) DEFAULT NULL,
  `freight_mode` enum('To Pay','Paid','To Be Billed') DEFAULT 'To Be Billed',
  `instructions` text DEFAULT NULL,
  `booking_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_no` (`booking_no`),
  KEY `lead_id` (`lead_id`),
  KEY `client_id` (`client_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `booking_status` (`booking_status`),
  KEY `idx_bk_loading_status` (`loading_date`,`booking_status`),
  KEY `idx_bk_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,'BK00001',1,1,3,41829.00,48520.00,6691.00,'Surat → Chennai','32ft SXL',1,'Fabric rolls · 14 TON','2025-12-08','Same as client','South Metro Agencies','9200100037','Chennai wholesale market, near transport nagar',NULL,'Paid','Handle with care. Share LR on WhatsApp. Unload with care at gate.','Completed',1,'2025-12-06 10:01:40',1,NULL,'2025-12-06 10:01:40','2025-12-07 10:01:40',NULL),(2,'BK00002',2,3,3,49252.00,56150.00,6898.00,'Ludhiana → Mumbai','32ft MXL',1,'Knitwear · 17 TON','2026-03-29','Same as client','Coastal Distributors','9200100074','Mumbai wholesale market, near transport nagar',NULL,'To Be Billed','Handle with care. Share LR on WhatsApp. Unload with care at gate.','Completed',1,'2026-03-27 10:01:40',1,NULL,'2026-03-27 10:01:40','2026-03-28 10:01:40',NULL),(3,'BK00003',3,3,5,44133.00,48550.00,4417.00,'Pune → Delhi','32ft SXL',1,'Textiles · 15 TON','2026-02-03','Same as client','NCR Wholesale Depot','9200100111','Delhi wholesale market, near transport nagar',NULL,'To Pay','Handle with care. Share LR on WhatsApp. Unload with care at gate.','Completed',1,'2026-02-01 10:01:40',1,NULL,'2026-02-01 10:01:40','2026-02-02 10:01:40',NULL),(4,'BK00004',4,7,10,50854.00,54920.00,4066.00,'Delhi → Kolkata','32ft MXL',1,'Machinery · 20 TON','2026-03-26','Same as client','Bay Mercantile','9200100148','Kolkata wholesale market, near transport nagar',NULL,'Paid','Handle with care. Share LR on WhatsApp. Unload with care at gate.','Completed',1,'2026-03-24 10:01:40',1,NULL,'2026-03-24 10:01:40','2026-03-25 10:01:40',NULL),(5,'BK00005',5,4,10,30729.00,33490.00,2761.00,'Hyderabad → Delhi','40ft Container',1,'Pharma · 8 TON','2026-03-29','Same as client','NCR Wholesale Depot','9200100185','Delhi wholesale market, near transport nagar',NULL,'To Be Billed','Handle with care. Share LR on WhatsApp. Unload with care at gate.','Completed',1,'2026-03-27 10:01:40',1,NULL,'2026-03-27 10:01:40','2026-03-28 10:01:40',NULL),(6,'BK00006',6,2,2,42650.00,49470.00,6820.00,'Surat → Chennai','32ft SXL',1,'Fabric rolls · 14 TON','2025-12-15','Same as client','South Metro Agencies','9200100222','Chennai wholesale market, near transport nagar',NULL,'To Pay','Handle with care. Share LR on WhatsApp. Unload with care at gate.','Handed Over',1,'2025-12-13 10:01:40',1,NULL,'2025-12-13 10:01:40','2025-12-14 10:01:40',NULL),(7,'BK00007',7,1,5,31770.00,36850.00,5080.00,'Ahmedabad → Mumbai','14ft Closed Body',1,'Packaged food · 6 TON','2025-12-06','Same as client','Coastal Distributors','9200100259','Mumbai wholesale market, near transport nagar',NULL,'Paid','Handle with care. Share LR on WhatsApp. Unload with care at gate.','Handed Over',1,'2025-12-04 10:01:40',1,NULL,'2025-12-04 10:01:40','2025-12-05 10:01:40',NULL),(8,'BK00008',8,2,4,46989.00,51690.00,4701.00,'Mumbai → Chennai','32ft SXL',1,'FMCG · 18 TON','2026-04-01','Same as client','South Metro Agencies','9200100296','Chennai wholesale market, near transport nagar',NULL,'To Be Billed','Handle with care. Share LR on WhatsApp. Unload with care at gate.','Approved',1,'2026-03-30 10:01:40',1,NULL,'2026-03-30 10:01:40','2026-03-31 10:01:40',NULL);
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ci_sessions`
--

DROP TABLE IF EXISTS `ci_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT 0,
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ci_sessions`
--

LOCK TABLES `ci_sessions` WRITE;
/*!40000 ALTER TABLE `ci_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ci_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_user_invites`
--

DROP TABLE IF EXISTS `client_user_invites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_user_invites` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `portal_role` enum('Owner','Booker','Accounts','Viewer') NOT NULL DEFAULT 'Owner',
  `token` varchar(80) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  `accepted_at` datetime DEFAULT NULL,
  `invited_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `client_user_invites_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_user_invites`
--

LOCK TABLES `client_user_invites` WRITE;
/*!40000 ALTER TABLE `client_user_invites` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_user_invites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_user_login_logs`
--

DROP TABLE IF EXISTS `client_user_login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_user_login_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_user_id` int(10) unsigned DEFAULT NULL,
  `email_tried` varchar(150) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(80) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_user_id` (`client_user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_user_login_logs`
--

LOCK TABLES `client_user_login_logs` WRITE;
/*!40000 ALTER TABLE `client_user_login_logs` DISABLE KEYS */;
INSERT INTO `client_user_login_logs` VALUES (1,2,'portal2@example.com',1,'ok','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-12 04:04:44');
/*!40000 ALTER TABLE `client_user_login_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_user_resets`
--

DROP TABLE IF EXISTS `client_user_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_user_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_user_id` int(10) unsigned NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `client_user_id` (`client_user_id`),
  CONSTRAINT `client_user_resets_client_user_id_foreign` FOREIGN KEY (`client_user_id`) REFERENCES `client_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_user_resets`
--

LOCK TABLES `client_user_resets` WRITE;
/*!40000 ALTER TABLE `client_user_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_user_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_users`
--

DROP TABLE IF EXISTS `client_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `portal_role` enum('Owner','Booker','Accounts','Viewer') NOT NULL DEFAULT 'Owner',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `failed_attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `notify_whatsapp` tinyint(1) NOT NULL DEFAULT 1,
  `notify_email` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `client_id` (`client_id`),
  KEY `mobile` (`mobile`),
  CONSTRAINT `client_users_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_users`
--

LOCK TABLES `client_users` WRITE;
/*!40000 ALTER TABLE `client_users` DISABLE KEYS */;
INSERT INTO `client_users` VALUES (1,1,'Pat Patel (Owner)','portal1@example.com','9810000100','$2y$10$UpZpWTwpQNfmlrEDrTkJa.R7sNdx.pPSlPIAnH4KNI9qdMDx/syXy','Owner',1,0,0,NULL,NULL,NULL,1,1,NULL,NULL,'2026-05-01 10:01:41','2026-05-01 10:01:41',NULL),(2,2,'Riya Roy (Booker)','portal2@example.com','9810000101','$2y$10$vCkiixfwjtCX/3H8/Ny4Z.oEG0U7jvc3Jp07hddCZCN8OF94Xz9bm','Booker',1,0,0,NULL,'2026-05-12 04:04:44','::1',1,1,NULL,NULL,'2026-05-01 10:01:41','2026-05-12 04:04:44',NULL),(3,3,'Vivek Verma (Accounts)','portal3@example.com','9810000102','$2y$10$.yfXM1hPVJ9F9M1QcP2sIO2hhDR5cih5661PkWo.o7umouTljf4h.','Accounts',1,0,0,NULL,NULL,NULL,1,1,NULL,NULL,'2026-05-01 10:01:41','2026-05-01 10:01:41',NULL);
/*!40000 ALTER TABLE `client_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_code` varchar(30) NOT NULL,
  `company_name` varchar(200) NOT NULL,
  `contact_name` varchar(150) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `alt_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `gst_no` varchar(20) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `address` varchar(400) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state` varchar(80) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `gst_treatment` enum('rcm','fcm5','fcm12') DEFAULT 'fcm5',
  `tds_rate` decimal(5,2) DEFAULT 2.00,
  `detention_free_hours_loading` tinyint(3) unsigned DEFAULT 4,
  `detention_free_hours_unloading` tinyint(3) unsigned DEFAULT 4,
  `detention_rate_per_hour` decimal(10,2) DEFAULT 150.00,
  `is_msme` tinyint(1) DEFAULT 0,
  `credit_limit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit_days` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `portal_enabled` tinyint(1) DEFAULT 0,
  `kyc_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `portal_notes` varchar(255) DEFAULT NULL,
  `account_manager_user_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_code` (`client_code`),
  KEY `mobile` (`mobile`),
  KEY `company_name` (`company_name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,'CL00001','Acme Traders Pvt Ltd','Ravi Kumar','9876500001',NULL,'ravi@acme.in','27AACCA1234C1ZV',NULL,'Pune industrial area','Pune','Maharashtra','400001','fcm5',2.00,4,4,150.00,0,500000.00,30,1,1,'Verified',NULL,9,1,NULL,'2025-12-02 10:01:39','2026-05-01 10:01:41',NULL),(2,'CL00002','Blue Horizon Textiles','Priya Shah','9876500002',NULL,'priya@bluehz.in','27AADCB5678D1Z8',NULL,'Mumbai industrial area','Mumbai','Maharashtra','400001','fcm5',2.00,4,4,150.00,0,550000.00,30,1,1,'Verified',NULL,9,1,NULL,'2025-12-02 10:01:39','2026-05-01 10:01:41',NULL),(3,'CL00003','Coromandel Agro Ltd','S. Ramanujam','9876500003',NULL,'finance@corom.in','33AADCC9876E1ZK',NULL,'Chennai industrial area','Chennai','Tamil Nadu','400001','fcm5',2.00,4,4,150.00,0,600000.00,30,1,1,'Verified',NULL,9,1,NULL,'2025-12-02 10:01:39','2026-05-01 10:01:41',NULL),(4,'CL00004','Delhi Chem Industries','Amit Verma','9876500004',NULL,'ops@dchem.in','07AADCD4321F1ZJ',NULL,'New Delhi industrial area','New Delhi','Delhi','400001','fcm5',2.00,4,4,150.00,0,650000.00,30,1,0,'Pending',NULL,NULL,1,NULL,'2025-12-02 10:01:39','2026-04-26 10:01:39',NULL),(5,'CL00005','Eastern Steel Works','Subrata Bose','9876500005',NULL,'logistics@est.in','19AADCE5555G1ZP',NULL,'Kolkata industrial area','Kolkata','West Bengal','400001','fcm5',2.00,4,4,150.00,0,700000.00,30,1,0,'Pending',NULL,NULL,1,NULL,'2025-12-02 10:01:39','2026-04-26 10:01:39',NULL),(6,'CL00006','Fortune Foods Ltd','Neha Gupta','9876500006',NULL,'neha@fortunef.in','24AADCF7777H1ZQ',NULL,'Ahmedabad industrial area','Ahmedabad','Gujarat','400001','fcm5',2.00,4,4,150.00,0,750000.00,30,1,0,'Pending',NULL,NULL,1,NULL,'2025-12-02 10:01:39','2026-04-26 10:01:39',NULL),(7,'CL00007','Green Valley Packaging','Joseph Mathew','9876500007',NULL,'joseph@gvp.in','32AADCG3333I1ZM',NULL,'Kochi industrial area','Kochi','Kerala','400001','fcm5',2.00,4,4,150.00,0,800000.00,30,1,0,'Pending',NULL,NULL,1,NULL,'2025-12-02 10:01:39','2026-04-26 10:01:39',NULL),(8,'CL00008','Harsha Cements Pvt Ltd','Raghav Reddy','9876500008',NULL,'raghav@harsha.in','36AADCH2222J1ZN',NULL,'Hyderabad industrial area','Hyderabad','Telangana','400001','fcm5',2.00,4,4,150.00,0,850000.00,30,1,0,'Pending',NULL,NULL,1,NULL,'2025-12-02 10:01:39','2026-04-26 10:01:39',NULL);
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(50) NOT NULL,
  `module_ref_id` int(10) unsigned NOT NULL,
  `document_type` varchar(60) NOT NULL,
  `original_file_name` varchar(255) NOT NULL,
  `stored_file_name` varchar(255) NOT NULL,
  `file_path` varchar(400) NOT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `mime_type` varchar(120) DEFAULT NULL,
  `source_channel` enum('upload','whatsapp','email','system') NOT NULL DEFAULT 'upload',
  `verification_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `remarks` varchar(255) DEFAULT NULL,
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `module_name_module_ref_id` (`module_name`,`module_ref_id`),
  KEY `document_type` (`document_type`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES (1,'trip',1,'POD','POD-TR00001.pdf','pod-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',55104,'application/pdf','upload','Pending','Awaiting verification.',1,'2025-12-08 10:01:40',NULL),(2,'trip',2,'POD','POD-TR00002.pdf','pod-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',37466,'application/pdf','whatsapp','Verified','Signed and stamped POD received.',1,'2026-03-29 10:01:40',NULL),(3,'trip',3,'POD','POD-TR00003.pdf','pod-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',69754,'application/pdf','upload','Verified','Signed and stamped POD received.',1,'2026-02-03 10:01:40',NULL),(4,'trip',4,'POD','POD-TR00004.pdf','pod-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',31167,'application/pdf','whatsapp','Pending','Awaiting verification.',1,'2026-03-26 10:01:40',NULL),(5,'trip',5,'POD','POD-TR00005.pdf','pod-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',30645,'application/pdf','upload','Pending','Awaiting verification.',1,'2026-03-29 10:01:40',NULL),(6,'trip',6,'POD','POD-TR00006.pdf','pod-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',44429,'application/pdf','whatsapp','Verified','Signed and stamped POD received.',1,'2025-12-15 10:01:40',NULL),(7,'trip',1,'LR','LR-TR00001.pdf','lr-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',18000,'application/pdf','upload','Verified','Consignor copy.',1,'2026-04-27 10:01:40',NULL),(8,'trip',2,'LR','LR-TR00002.pdf','lr-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',18000,'application/pdf','upload','Verified','Consignor copy.',1,'2026-04-27 10:01:40',NULL),(9,'vendor',1,'Insurance','vendor-insurance.pdf','ins-sample.pdf','trips/1/1776759572_1e8a945e416429f89a9b.pdf',32000,'application/pdf','upload','Verified','Valid till next year.',1,'2026-04-01 10:01:40',NULL),(10,'trip',8,'Insurance Policy','Insurance_Certificate.pdf','insurance_demo.pdf','trips/8/insurance_demo.pdf',31,'application/pdf','upload','Verified',NULL,NULL,'2026-05-12 10:57:55',NULL),(11,'trip',8,'Gate Pass','GatePass_Mumbai.jpg','gatepass_demo.jpg','trips/8/gatepass_demo.jpg',19,'image/jpeg','upload','Pending',NULL,NULL,'2026-05-12 10:57:55',NULL);
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `driver_messages`
--

DROP TABLE IF EXISTS `driver_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `driver_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `direction` enum('driver','staff','system') NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `body` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL,
  `attachment_orig` varchar(255) DEFAULT NULL,
  `is_read_by_staff` tinyint(1) NOT NULL DEFAULT 0,
  `is_read_by_driver` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `trip_id_id` (`trip_id`,`id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `driver_messages`
--

LOCK TABLES `driver_messages` WRITE;
/*!40000 ALTER TABLE `driver_messages` DISABLE KEYS */;
INSERT INTO `driver_messages` VALUES (1,8,'system',NULL,'Driver tracking link generated',NULL,NULL,NULL,1,1,'2026-05-12 09:56:52'),(2,8,'staff',1,'Hi Ranjit, please confirm once you reach the loading point. Consignee is South Metro Agencies in Chennai.',NULL,NULL,NULL,1,1,'2026-05-12 10:06:52'),(4,8,'driver',NULL,'Reached pickup point. Loading starting in 15 min.','trips/8/messages/1778561864_12eea6e3150c9472f763.jpeg','image/jpeg','WhatsApp Image 2026-05-08 at 15.50.35.jpeg',1,1,'2026-05-12 04:57:44'),(5,8,'driver',NULL,'Photo of the loading dock ù they want us to unload from the back gate.','trips/8/messages/demo_pickup.jpg','image/jpeg','loading_dock.jpg',1,1,'2026-05-12 11:38:51'),(6,8,'staff',1,'hello',NULL,NULL,NULL,1,1,'2026-05-12 06:15:56'),(7,8,'driver',NULL,'jaldi pohuncho',NULL,NULL,NULL,1,1,'2026-05-12 06:16:41'),(8,8,'staff',1,'are you ok ?',NULL,NULL,NULL,1,1,'2026-05-12 06:17:09');
/*!40000 ALTER TABLE `driver_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `drivers`
--

DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drivers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned DEFAULT NULL,
  `driver_name` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `alt_mobile` varchar(20) DEFAULT NULL,
  `license_no` varchar(40) DEFAULT NULL,
  `aadhaar_last_4` varchar(4) DEFAULT NULL,
  `dl_verified` tinyint(1) DEFAULT 0,
  `kyc_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `kyc_verified_at` datetime DEFAULT NULL,
  `kyc_verified_by` int(10) unsigned DEFAULT NULL,
  `kyc_notes` varchar(255) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `drivers`
--

LOCK TABLES `drivers` WRITE;
/*!40000 ALTER TABLE `drivers` DISABLE KEYS */;
INSERT INTO `drivers` VALUES (1,1,'Rakesh Yadav','9870010000',NULL,'DL-8BA288',NULL,0,'Pending',NULL,NULL,NULL,'2026-09-24',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(2,2,'Santosh Pawar','9870010001',NULL,'DL-5A798E',NULL,0,'Pending',NULL,NULL,NULL,'2028-01-10',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(3,3,'Karan Singh','9870010002',NULL,'DL-00E993',NULL,0,'Pending',NULL,NULL,NULL,'2028-02-03',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(4,4,'Balwinder Kaur','9870010003',NULL,'DL-8EC837',NULL,0,'Pending',NULL,NULL,NULL,'2026-12-31',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(5,5,'Ramu Naidu','9870010004',NULL,'DL-054B9A',NULL,0,'Pending',NULL,NULL,NULL,'2026-07-04',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(6,6,'Sunil Sharma','9870010005',NULL,'DL-C6DEB9',NULL,0,'Pending',NULL,NULL,NULL,'2027-10-13',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(7,7,'Mohammed Ishaq','9870010006',NULL,'DL-7FDB70',NULL,0,'Pending',NULL,NULL,NULL,'2027-06-01',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(8,8,'Prakash Gowda','9870010007',NULL,'DL-7852FC',NULL,0,'Pending',NULL,NULL,NULL,'2026-11-28',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(9,9,'Ranjit Das','9870010008',NULL,'DL-AA586B',NULL,0,'Pending',NULL,NULL,NULL,'2028-02-09',1,'2026-01-01 10:01:39','2026-04-21 10:01:39'),(10,10,'Iqbal Khan','9870010009',NULL,'DL-B923CC',NULL,0,'Pending',NULL,NULL,NULL,'2028-04-28',1,'2026-01-01 10:01:39','2026-04-21 10:01:39');
/*!40000 ALTER TABLE `drivers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `einvoice_logs`
--

DROP TABLE IF EXISTS `einvoice_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `einvoice_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `request_payload` mediumtext DEFAULT NULL,
  `response_payload` mediumtext DEFAULT NULL,
  `irn_status` varchar(30) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `einvoice_logs_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `einvoice_logs`
--

LOCK TABLES `einvoice_logs` WRITE;
/*!40000 ALTER TABLE `einvoice_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `einvoice_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_events`
--

DROP TABLE IF EXISTS `email_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email_log_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(30) NOT NULL,
  `data_json` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `email_log_id` (`email_log_id`),
  KEY `event_type` (`event_type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `email_events_email_log_id_foreign` FOREIGN KEY (`email_log_id`) REFERENCES `email_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_events`
--

LOCK TABLES `email_events` WRITE;
/*!40000 ALTER TABLE `email_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tracking_token` varchar(64) NOT NULL,
  `template_key` varchar(80) DEFAULT NULL,
  `to_email` varchar(200) NOT NULL,
  `to_name` varchar(200) DEFAULT NULL,
  `cc` varchar(400) DEFAULT NULL,
  `bcc` varchar(400) DEFAULT NULL,
  `reply_to` varchar(200) DEFAULT NULL,
  `subject` varchar(250) NOT NULL,
  `body_html` mediumtext DEFAULT NULL,
  `body_text` text DEFAULT NULL,
  `attachments_json` text DEFAULT NULL,
  `status` enum('Queued','Sending','Sent','Delivered','Failed','Bounced','Complained','Suppressed','Opened','Clicked') NOT NULL DEFAULT 'Queued',
  `provider` varchar(30) DEFAULT NULL,
  `provider_msg_id` varchar(200) DEFAULT NULL,
  `error` varchar(500) DEFAULT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `worker_id` varchar(64) DEFAULT NULL,
  `worker_locked_at` datetime DEFAULT NULL,
  `queued_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `open_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `first_clicked_at` datetime DEFAULT NULL,
  `click_count` smallint(5) unsigned NOT NULL DEFAULT 0,
  `bounced_at` datetime DEFAULT NULL,
  `bounce_type` varchar(40) DEFAULT NULL,
  `complained_at` datetime DEFAULT NULL,
  `unsubscribed_at` datetime DEFAULT NULL,
  `last_event_at` datetime DEFAULT NULL,
  `related_module` varchar(30) DEFAULT NULL,
  `related_id` int(10) unsigned DEFAULT NULL,
  `related_client_id` int(10) unsigned DEFAULT NULL,
  `sent_by_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_token` (`tracking_token`),
  KEY `to_email` (`to_email`),
  KEY `status` (`status`),
  KEY `provider_msg_id` (`provider_msg_id`),
  KEY `related_module_related_id` (`related_module`,`related_id`),
  KEY `related_client_id` (`related_client_id`),
  KEY `created_at` (`created_at`),
  KEY `idx_emaillogs_status_worker` (`status`,`worker_locked_at`),
  KEY `idx_emaillogs_tpl_at` (`created_at`,`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_key` varchar(80) NOT NULL,
  `audience_type` enum('client','vendor','internal','driver') NOT NULL DEFAULT 'client',
  `subject` varchar(200) NOT NULL,
  `body_html` mediumtext NOT NULL,
  `body_text` text DEFAULT NULL,
  `variables_json` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
/*!40000 ALTER TABLE `email_templates` DISABLE KEYS */;
INSERT INTO `email_templates` VALUES (1,'invoice_share','client','Invoice {{invoice_no}} — ₹{{total}} due {{due_date}}','<h2>Your invoice is ready</h2>\n<p>Dear {{company}},</p>\n<p>Please find attached invoice <strong>{{invoice_no}}</strong> for <strong>₹{{total}}</strong> with due date <strong>{{due_date}}</strong>.</p>\n<div class=\"meta-card\">\n  <span class=\"meta-row\"><span class=\"meta-label\">Invoice</span> {{invoice_no}}</span>\n  <span class=\"meta-row\"><span class=\"meta-label\">Amount</span> ₹{{total}}</span>\n  <span class=\"meta-row\"><span class=\"meta-label\">Due</span> {{due_date}}</span>\n</div><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{view_url}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">View invoice</a></p><p style=\"font-size:13px;color:#777;\">If you have already paid, please ignore this email.</p>','Invoice {{invoice_no}} — ₹{{total}} due {{due_date}}. View at: {{view_url}}','[\"INV0001\",\"12,500.00\",\"15 May 2026\",\"Acme Logistics\",\"https:\\/\\/example.com\\/portal\\/invoices\\/1\"]',1,'2026-05-01 07:59:23','2026-05-01 07:59:23',NULL),(2,'payment_reminder','client','Friendly reminder · Invoice {{invoice_no}} — ₹{{balance}} pending','<h2>Payment reminder</h2>\n<p>Dear {{company}},</p>\n<p>This is a gentle reminder that invoice <strong>{{invoice_no}}</strong> with a balance of <strong>₹{{balance}}</strong> was due on <strong>{{due_date}}</strong>.</p><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{view_url}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">View &amp; pay</a></p><p style=\"font-size:13px;color:#777;\">If payment has already been made, please share the UTR or transaction reference so we can apply it.</p>','Reminder: invoice {{invoice_no}} of ₹{{balance}} was due {{due_date}}. View: {{view_url}}','[\"INV0001\",\"12,500.00\",\"15 May 2026\",\"https:\\/\\/example.com\\/portal\\/invoices\\/1\"]',1,'2026-05-01 07:59:23','2026-05-01 07:59:23',NULL),(3,'booking_confirmed','client','Booking {{booking_no}} confirmed — {{route}}','<h2>Your booking is confirmed</h2>\n<p>Dear {{company}},</p>\n<p>Booking <strong>{{booking_no}}</strong> for <strong>{{route}}</strong> has been approved. Our operations team will share vehicle details shortly.</p><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{view_url}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">Track this booking</a></p>','Booking {{booking_no}} for {{route}} is confirmed. Track at: {{view_url}}','[\"BK00012\",\"Mumbai \\u2192 Pune\",\"Acme Logistics\",\"https:\\/\\/example.com\\/portal\\/bookings\\/12\"]',1,'2026-05-01 07:59:23','2026-05-01 07:59:23',NULL),(4,'trip_in_transit','client','Trip {{trip_no}} is now in transit','<h2>On the way</h2>\n<p>Vehicle <strong>{{vehicle}}</strong> has dispatched for trip <strong>{{trip_no}}</strong> ({{route}}).</p><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{view_url}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">Live status &amp; GPS</a></p>','Trip {{trip_no}} ({{route}}) dispatched on vehicle {{vehicle}}. Live: {{view_url}}','[\"TR00045\",\"MH04AB1234\",\"Mumbai \\u2192 Pune\",\"https:\\/\\/example.com\\/portal\\/trips\\/45\"]',1,'2026-05-01 07:59:23','2026-05-01 07:59:23',NULL),(5,'trip_delivered','client','Trip {{trip_no}} delivered — POD requested','<h2>Delivered ✅</h2>\n<p>Trip <strong>{{trip_no}}</strong> has been delivered at <strong>{{drop}}</strong>. We will request the POD from the consignee. You can also share a signed POD with us via the portal.</p><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{view_url}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">View trip</a></p>','Trip {{trip_no}} delivered at {{drop}}. {{view_url}}','[\"TR00045\",\"Pune\",\"https:\\/\\/example.com\\/portal\\/trips\\/45\"]',1,'2026-05-01 07:59:23','2026-05-01 07:59:23',NULL),(6,'portal_invite','client','You have been invited to the {{company}} portal','<h2>Welcome aboard</h2>\n<p>Hi {{name}},</p>\n<p>You have been invited to access the <strong>{{company}}</strong> client portal where you can request trucks, track shipments, view invoices, and more.</p><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{invite_url}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">Activate your account</a></p><p style=\"font-size:13px;color:#777;\">This link is valid for 7 days. If it expires, contact your account manager for a fresh one.</p>','Activate your portal account: {{invite_url}}','[\"Pat Singh\",\"Acme Logistics\",\"https:\\/\\/example.com\\/portal\\/invite\\/abc\"]',1,'2026-05-01 07:59:23','2026-05-01 07:59:23',NULL),(7,'portal_password_reset','client','Reset your portal password','<h2>Reset your password</h2><p>Hi {{name}},</p><p>Click the button below to choose a new password. This link is valid for 1 hour and can only be used once.</p><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{reset_url}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">Choose new password</a></p><p style=\"font-size:13px;color:#777;\">If you did not request this reset, ignore this email — your existing password remains unchanged.</p>','Reset your password: {{reset_url}}','[\"Pat Singh\",\"https:\\/\\/example.com\\/portal\\/reset\\/abc\",\"https:\\/\\/example.com\\/portal\\/login\"]',1,'2026-05-01 07:59:23','2026-05-01 08:57:05',NULL),(8,'rfq_quote_to_client','client','Quotation for {{route}} — ₹{{rate}}','<h2>Your quote</h2>\n<p>Dear {{company}},</p>\n<p>For your enquiry on <strong>{{route}}</strong>, our best rate is <strong>₹{{rate}}</strong>, valid until <strong>{{valid_till}}</strong>.</p>\n<p>Reply to this email to confirm and we will dispatch the vehicle.</p>','Quote: {{route}} — ₹{{rate}} valid till {{valid_till}}. Reply to confirm.','[\"Acme Logistics\",\"Mumbai \\u2192 Pune\",\"45,000\",\"17 May 2026\"]',1,'2026-05-01 07:59:23','2026-05-01 07:59:23',NULL),(9,'vendor_rfq_invite','vendor','New RFQ {{rfq_no}} — {{route}} — submit your rate','<h2>New Request for Quotation</h2>\n<p>Hi {{vendor_name}},</p>\n<p>We have a new load that matches your fleet. Please submit your best rate by clicking the button below — no login required.</p>\n<div class=\"meta-card\" style=\"background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;\">\n  <div><strong>RFQ:</strong> {{rfq_no}}</div>\n  <div><strong>Route:</strong> {{route}}</div>\n  <div><strong>Vehicle:</strong> {{vehicle_type}}</div>\n  <div><strong>Material:</strong> {{material}}</div>\n  <div><strong>Weight:</strong> {{weight}}</div>\n  <div><strong>Loading date:</strong> {{loading_date}}</div>\n</div><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{quote_link}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">Submit your rate</a></p><p style=\"color:#555;font-size:13px;\">The link is unique to your firm. Quote anytime — we close RFQs after the loading date.</p>','New RFQ — {{rfq_no}}\n\nRoute: {{route}}\nVehicle: {{vehicle_type}}\nMaterial: {{material}}\nWeight: {{weight}}\nLoading: {{loading_date}}\n\nSubmit your rate: {{quote_link}}','[\"rfq_no\",\"route\",\"vehicle_type\",\"material\",\"weight\",\"loading_date\",\"quote_link\",\"vendor_name\"]',1,'2026-05-12 05:47:07','2026-05-12 05:47:07',NULL),(10,'vendor_quote_received','internal','New quote on RFQ {{rfq_no}} from {{vendor_name}} — ₹{{quote_amount}}','<h2>Vendor quoted on RFQ {{rfq_no}}</h2>\n<p>A vendor just submitted a rate. Open the RFQ to compare quotes and decide.</p>\n<div class=\"meta-card\" style=\"background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;\">\n  <div><strong>RFQ:</strong> {{rfq_no}} — {{route}}</div>\n  <div><strong>Vendor:</strong> {{vendor_name}}</div>\n  <div><strong>Rate:</strong> ₹{{quote_amount}}</div>\n  <div><strong>Transit:</strong> {{transit_days}} days</div>\n  <div><strong>Remarks:</strong> {{remarks}}</div>\n</div><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{rfq_link}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">Open RFQ</a></p>','Vendor quoted on RFQ {{rfq_no}}\n\n{{vendor_name}} — ₹{{quote_amount}}\nTransit: {{transit_days}} days\nRemarks: {{remarks}}\n\nOpen RFQ: {{rfq_link}}','[\"rfq_no\",\"route\",\"vendor_name\",\"quote_amount\",\"transit_days\",\"remarks\",\"rfq_link\"]',1,'2026-05-12 05:47:07','2026-05-12 05:47:07',NULL),(11,'vendor_won_assignment','vendor','Awarded: Trip {{trip_no}} — {{route}}','<h2>Congratulations — you are awarded this trip</h2>\n<p>Hi {{vendor_name}}, your rate has been accepted. Please confirm vehicle placement at the loading point on the scheduled date.</p>\n<div class=\"meta-card\" style=\"background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;\">\n  <div><strong>Trip:</strong> {{trip_no}} (from {{rfq_no}})</div>\n  <div><strong>Route:</strong> {{route}}</div>\n  <div><strong>Vehicle:</strong> {{vehicle_type}}</div>\n  <div><strong>Loading date:</strong> {{loading_date}}</div>\n  <div><strong>Agreed rate:</strong> ₹{{rate}}</div>\n  <div style=\"margin-top:8px;padding-top:8px;border-top:1px solid #e2e5e9;\"><strong>Consignee:</strong> {{consignee_name}}</div>\n  <div><strong>Address:</strong> {{consignee_address}}</div>\n  <div><strong>Consignee phone:</strong> {{consignee_mobile}}</div>\n</div>\n<p>For assistance call us on {{contact_phone}}.</p>','Awarded: Trip {{trip_no}}\n\nVendor: {{vendor_name}}\nRoute: {{route}}\nVehicle: {{vehicle_type}}\nLoading: {{loading_date}}\nRate: ₹{{rate}}\n\nConsignee: {{consignee_name}}\n{{consignee_address}}\nPhone: {{consignee_mobile}}\n\nQueries: {{contact_phone}}','[\"trip_no\",\"rfq_no\",\"vendor_name\",\"route\",\"vehicle_type\",\"loading_date\",\"rate\",\"consignee_name\",\"consignee_address\",\"consignee_mobile\",\"contact_phone\"]',1,'2026-05-12 05:47:07','2026-05-12 05:47:07',NULL),(12,'client_truck_assigned','client','Truck assigned for booking {{booking_no}} — {{vehicle_number}}','<h2>Your truck is assigned</h2>\n<p>Dear {{client_name}},</p>\n<p>We have placed a vehicle for your booking <strong>{{booking_no}}</strong>. Driver details below — you can reach the driver directly if needed.</p>\n<div class=\"meta-card\" style=\"background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;\">\n  <div><strong>Route:</strong> {{route}}</div>\n  <div><strong>Loading date:</strong> {{loading_date}}</div>\n  <div><strong>Vehicle:</strong> {{vehicle_number}} ({{vehicle_type}})</div>\n  <div><strong>Driver:</strong> {{driver_name}}</div>\n  <div><strong>Driver phone:</strong> {{driver_mobile}}</div>\n  <div><strong>Transporter:</strong> {{vendor_company}}</div>\n  <div><strong>Trip:</strong> {{trip_no}}</div>\n</div>','Truck assigned — booking {{booking_no}}\n\nVehicle: {{vehicle_number}} ({{vehicle_type}})\nDriver: {{driver_name}} — {{driver_mobile}}\nTransporter: {{vendor_company}}\nRoute: {{route}}\nLoading: {{loading_date}}\nTrip: {{trip_no}}','[\"client_name\",\"booking_no\",\"trip_no\",\"vehicle_number\",\"vehicle_type\",\"driver_name\",\"driver_mobile\",\"route\",\"loading_date\",\"vendor_company\"]',1,'2026-05-12 05:47:07','2026-05-12 05:47:07',NULL),(13,'driver_dispatch_link','driver','Loading today — open your driver app for {{trip_no}}','<h2>Loading today — open the driver tracker</h2>\n<p>Hi {{driver_name}}, please open the link below and tap \"Start sharing location\" before you begin loading. Keep the page open during the trip.</p>\n<div class=\"meta-card\" style=\"background:#f6f7f9;border-radius:8px;padding:14px 18px;margin:18px 0;font-size:14px;\">\n  <div><strong>Trip:</strong> {{trip_no}}</div>\n  <div><strong>LR:</strong> {{lr_no}}</div>\n  <div><strong>Vehicle:</strong> {{vehicle_number}}</div>\n  <div><strong>Route:</strong> {{route}}</div>\n  <div><strong>Loading point:</strong> {{loading_point}}</div>\n</div><p style=\"margin:24px 0;\"><a class=\"btn\" href=\"{{driver_link}}\" style=\"display:inline-block;padding:11px 22px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;font-size:14px;\">Open driver app</a></p><p style=\"color:#555;font-size:13px;\">On Android Chrome you can tap \"Install app\" to add the tracker to your home screen.</p>','Loading today — open the driver tracker\n\nTrip: {{trip_no}} (LR {{lr_no}})\nVehicle: {{vehicle_number}}\nRoute: {{route}}\nLoading point: {{loading_point}}\n\nOpen: {{driver_link}}','[\"driver_name\",\"trip_no\",\"lr_no\",\"route\",\"vehicle_number\",\"loading_point\",\"driver_link\"]',1,'2026-05-12 05:47:07','2026-05-12 05:47:07',NULL),(14,'trip_arrived_destination','client','Trip {{trip_no}} arrived at destination','<h2>Truck has arrived at the destination</h2>\n<p>Trip <strong>{{trip_no}}</strong> ({{route}}) reached the unloading point at {{arrived_at}}.</p>\n<p>Vehicle <strong>{{vehicle_number}}</strong> · Driver {{driver_name}}.</p>\n<p>We will share POD once unloading is complete.</p>','Trip {{trip_no}} arrived at destination\n\nRoute: {{route}}\nVehicle: {{vehicle_number}}\nDriver: {{driver_name}}\nArrived: {{arrived_at}}\n\nPOD will follow once unloading is complete.','[\"trip_no\",\"route\",\"vehicle_number\",\"driver_name\",\"arrived_at\"]',1,'2026-05-12 05:47:07','2026-05-12 05:47:07',NULL);
/*!40000 ALTER TABLE `email_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_unsubscribes`
--

DROP TABLE IF EXISTS `email_unsubscribes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_unsubscribes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(200) NOT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `source` enum('link','complaint','bounce','manual') NOT NULL DEFAULT 'link',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_unsubscribes`
--

LOCK TABLES `email_unsubscribes` WRITE;
/*!40000 ALTER TABLE `email_unsubscribes` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_unsubscribes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `epod_signatures`
--

DROP TABLE IF EXISTS `epod_signatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `epod_signatures` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `consignee_name` varchar(200) NOT NULL,
  `consignee_mobile` varchar(20) DEFAULT NULL,
  `signed_at` datetime NOT NULL,
  `signature_data` mediumtext NOT NULL,
  `remarks` varchar(400) DEFAULT NULL,
  `damage_noted` tinyint(1) NOT NULL DEFAULT 0,
  `shortage_noted` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `geo_lat` decimal(10,7) DEFAULT NULL,
  `geo_lng` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  CONSTRAINT `epod_signatures_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `epod_signatures`
--

LOCK TABLES `epod_signatures` WRITE;
/*!40000 ALTER TABLE `epod_signatures` DISABLE KEYS */;
/*!40000 ALTER TABLE `epod_signatures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ewb_consolidated`
--

DROP TABLE IF EXISTS `ewb_consolidated`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ewb_consolidated` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `consol_no` varchar(30) NOT NULL,
  `vehicle_no` varchar(20) NOT NULL,
  `from_state` varchar(60) DEFAULT NULL,
  `trip_ids_json` text DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Active',
  `raw_payload` mediumtext DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `consol_no` (`consol_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ewb_consolidated`
--

LOCK TABLES `ewb_consolidated` WRITE;
/*!40000 ALTER TABLE `ewb_consolidated` DISABLE KEYS */;
/*!40000 ALTER TABLE `ewb_consolidated` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ewb_logs`
--

DROP TABLE IF EXISTS `ewb_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ewb_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `action` varchar(30) NOT NULL,
  `request_payload` mediumtext DEFAULT NULL,
  `response_payload` mediumtext DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Queued',
  `error_message` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ewb_logs`
--

LOCK TABLES `ewb_logs` WRITE;
/*!40000 ALTER TABLE `ewb_logs` DISABLE KEYS */;
INSERT INTO `ewb_logs` VALUES (1,1,'manual','{\"ewbNo\":\"181020260017\",\"source\":\"demo-seed\"}',NULL,'Success',NULL,NULL,'2025-12-06 10:01:40'),(2,2,'manual','{\"ewbNo\":\"181020260035\",\"source\":\"demo-seed\"}',NULL,'Success',NULL,NULL,'2026-03-27 10:01:40'),(3,3,'manual','{\"ewbNo\":\"181020260053\",\"source\":\"demo-seed\"}',NULL,'Success',NULL,NULL,'2026-02-01 10:01:40'),(4,4,'manual','{\"ewbNo\":\"181020260071\",\"source\":\"demo-seed\"}',NULL,'Success',NULL,NULL,'2026-03-24 10:01:40'),(5,5,'manual','{\"ewbNo\":\"181020260089\",\"source\":\"demo-seed\"}',NULL,'Success',NULL,NULL,'2026-03-27 10:01:40'),(6,6,'manual','{\"ewbNo\":\"181020260107\",\"source\":\"demo-seed\"}',NULL,'Success',NULL,NULL,'2025-12-13 10:01:40'),(7,7,'manual','{\"ewbNo\":\"181020260125\",\"source\":\"demo-seed\"}',NULL,'Success',NULL,NULL,'2025-12-04 10:01:40');
/*!40000 ALTER TABLE `ewb_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_flags`
--

DROP TABLE IF EXISTS `feature_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feature_flags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `flag_key` varchar(60) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `notes` varchar(255) DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `flag_key` (`flag_key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_flags`
--

LOCK TABLES `feature_flags` WRITE;
/*!40000 ALTER TABLE `feature_flags` DISABLE KEYS */;
INSERT INTO `feature_flags` VALUES (1,'whatsapp',1,NULL,NULL,'2026-05-01 09:48:04'),(2,'email',1,NULL,NULL,'2026-05-01 09:48:04'),(3,'gps',1,NULL,NULL,'2026-05-01 09:48:04'),(4,'portal',1,NULL,NULL,'2026-05-01 09:48:04'),(5,'einvoice',1,NULL,NULL,'2026-05-01 09:48:04'),(6,'eway_bill',1,NULL,NULL,'2026-05-01 09:48:04'),(7,'reports',1,NULL,NULL,'2026-05-01 09:48:04'),(8,'documents',1,NULL,NULL,'2026-05-01 09:48:04'),(9,'comments',1,NULL,NULL,'2026-05-01 09:48:04'),(10,'rfq',1,NULL,NULL,'2026-05-01 09:48:04'),(11,'vendor_scoring',1,NULL,NULL,'2026-05-01 09:48:04'),(12,'rate_contracts',0,'Long-term rate cards per client/lane',NULL,'2026-05-01 11:23:56'),(13,'loading_slots',0,'Loading slot booking with consignor warehouse',NULL,'2026-05-01 11:23:56'),(14,'epod',0,'Consignee digital signature on POD',NULL,'2026-05-01 11:23:56'),(15,'tds_certificates',0,'Quarterly Form 16A collection workflow',NULL,'2026-05-01 11:23:56'),(16,'vendor_deposits',0,'Vendor security deposit ledger',NULL,'2026-05-01 11:23:56'),(17,'lane_profitability',0,'Lane-level profitability report',NULL,'2026-05-01 11:23:56'),(18,'trip_insurance',0,'Per-trip cargo insurance quotes',NULL,'2026-05-01 11:23:56');
/*!40000 ALTER TABLE `feature_flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gps_logs`
--

DROP TABLE IF EXISTS `gps_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gps_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned DEFAULT NULL,
  `vehicle_number` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `gps_timestamp` datetime DEFAULT NULL,
  `speed` decimal(6,2) DEFAULT NULL,
  `address` varchar(400) DEFAULT NULL,
  `raw_payload` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `source` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `vehicle_number` (`vehicle_number`),
  KEY `gps_timestamp` (`gps_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gps_logs`
--

LOCK TABLES `gps_logs` WRITE;
/*!40000 ALTER TABLE `gps_logs` DISABLE KEYS */;
INSERT INTO `gps_logs` VALUES (1,6,'WB12GH1738',21.1702000,72.8311000,'2025-12-13 10:01:40',0.00,'Surat — demo',NULL,'2025-12-13 10:01:40',NULL),(2,6,'WB12GH1738',19.5527000,74.3190200,'2025-12-14 10:01:40',51.00,'Surat — demo',NULL,'2025-12-14 10:01:40',NULL),(3,6,'WB12GH1738',17.9352000,75.8069400,'2025-12-14 10:01:40',47.00,'Surat — demo',NULL,'2025-12-14 10:01:40',NULL),(4,6,'WB12GH1738',16.3177000,77.2948600,'2025-12-14 10:01:40',63.00,'Surat — demo',NULL,'2025-12-14 10:01:40',NULL),(5,6,'WB12GH1738',14.7002000,78.7827800,'2025-12-14 10:01:40',54.00,'Surat — demo',NULL,'2025-12-14 10:01:40',NULL),(6,6,'WB12GH1738',13.0827000,80.2707000,'2025-12-14 10:01:40',15.00,'Chennai — demo',NULL,'2025-12-14 10:01:40',NULL),(7,7,'UP32HI1861',23.0225000,72.5714000,'2025-12-04 10:01:40',0.00,'Ahmedabad — demo',NULL,'2025-12-04 10:01:40',NULL),(8,7,'UP32HI1861',22.2332000,72.6326600,'2025-12-05 10:01:40',49.00,'Ahmedabad — demo',NULL,'2025-12-05 10:01:40',NULL),(9,7,'UP32HI1861',21.4439000,72.6939200,'2025-12-05 10:01:40',58.00,'Ahmedabad — demo',NULL,'2025-12-05 10:01:40',NULL),(10,7,'UP32HI1861',20.6546000,72.7551800,'2025-12-05 10:01:40',61.00,'Ahmedabad — demo',NULL,'2025-12-05 10:01:40',NULL),(11,7,'UP32HI1861',19.8653000,72.8164400,'2025-12-05 10:01:40',51.00,'Ahmedabad — demo',NULL,'2025-12-05 10:01:40',NULL),(12,7,'UP32HI1861',19.0760000,72.8777000,'2025-12-05 10:01:40',15.00,'Mumbai — demo',NULL,'2025-12-05 10:01:40',NULL);
/*!40000 ALTER TABLE `gps_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `help_topics`
--

DROP TABLE IF EXISTS `help_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `help_topics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `category` varchar(60) NOT NULL,
  `body_md` mediumtext NOT NULL,
  `applicable_roles` varchar(255) NOT NULL DEFAULT 'all',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 100,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `help_topics`
--

LOCK TABLES `help_topics` WRITE;
/*!40000 ALTER TABLE `help_topics` DISABLE KEYS */;
INSERT INTO `help_topics` VALUES (1,'welcome','Welcome to TPT Aggregator','Getting Started','# Welcome\n\nTPT Aggregator is an end-to-end platform for an Indian transport / freight aggregator. It covers the full lifecycle:\n\n1. **CRM** — capture leads, raise RFQs to vendors, send quotations to clients.\n2. **Operations** — convert a confirmed booking into a trip, place a vehicle, track GPS, capture POD.\n3. **Finance** — raise GST invoices, record receipts, post vendor bills, run TDS, file E-Way bills.\n4. **Communication** — WhatsApp, Email and a Client Portal for self-service bookings.\n5. **Compliance** — E-Way bill (NIC), E-Invoice (IRP), GTA tax (RCM/FCM), TDS u/s 194C.\n\n> **Tip:** Use the left sidebar to navigate, the global search (top-bar) to jump to records, and the **Calendar** to see today\'s pickups & deliveries.','all',10,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(2,'logging-in','Logging in & resetting your password','Getting Started','## Logging in\n\n- Open the app URL given by your administrator.\n- Enter your **email** and **password**.\n- If 2FA is enabled for your account, enter the 6-digit code from your authenticator app.\n\n## Forgot password?\n\nUse the **Forgot password** link on the login page. You will get a reset email valid for 30 minutes. The password is **only** changed when you click the link and submit a new one — so requesting a reset will never lock you out.\n\n## Sessions\n\n- Idle session timeout is configurable by your admin (default 60 minutes).\n- Logging in invalidates any open session in the *opposite* realm (staff vs. portal) on the same browser, so impersonation across realms is prevented.','all',11,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(3,'tour-the-dashboard','A tour of the dashboard','Getting Started','The **dashboard** is the landing page after login and is tailored to your role:\n\n- **KPI tiles** — open leads, today\'s bookings, in-transit trips, unbilled trips, overdue invoices.\n- **SOP / TAT** widgets — Standard Operating Procedure adherence, Turn-Around-Times for lead → quote, booking → placement, delivery → POD.\n- **Calendar mini-widget** — today\'s pickup & delivery events.\n- **Comments inbox** — internal @mentions on leads, bookings, trips, invoices that you should action.\n\n> Tiles are **clickable** — they take you to the underlying filtered list.','all',12,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(4,'master-data-overview','Master data: clients, vendors, drivers, vehicles','Master Data','Master data is shared across the app. Get this clean once and the rest of the workflow stays clean.\n\n- **Clients** — companies you sell to. Capture GSTIN, billing address, credit limit, payment terms.\n- **Vendors** — fleet owners or transporters you buy capacity from. Capture GSTIN, PAN (drives TDS), bank details.\n- **Drivers** — KYC-validated. Aadhaar/DL/PAN with expiry alerts.\n- **Vehicles** — RC, fitness, insurance, PUC, permit. Compliance gate blocks placement if any document is expired.\n\n> Bookings, RFQs and trips can only be raised against **active** master records.','admin,management,crm_exec,crm_mgr,pur_exec,pur_mgr,ops_exec,ops_mgr',13,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(5,'manage-clients','Adding & managing clients','Master Data','## Add a client\n\nGo to **Clients → New**.\n\nRequired: company name, billing state (drives IGST/CGST split), GSTIN (validated by format), default credit terms.\n\n## Credit limit & payment terms\n\nThe dashboard\'s **Receivables** card uses these to flag *overdue* and *over-limit* clients in red. Bookings against an over-limit client need approval (see *Settings → Approvals*).\n\n## Client portal access\n\nYou can grant **Client Portal** access from the client\'s profile. Choose a role:\n\n- **Owner** — full access, can invite team members, can rebook.\n- **Booker** — can raise bookings; cannot see invoices.\n- **Accounts** — can see invoices, receipts, statements; cannot raise bookings.\n- **Viewer** — read-only.\n\n> Inviting a portal user sends them an email with a one-time **set-password** link.','admin,management,crm_exec,crm_mgr,accounts',14,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(6,'manage-vendors','Vendors, contacts & GPS configuration','Master Data','## Vendor profile\n\nA vendor is the fleet owner / transporter who supplies capacity. Capture:\n\n- **PAN** — drives TDS u/s 194C (1% individual, 2% otherwise). Without PAN, TDS jumps to 20%.\n- **GSTIN** — drives whether GTA tax is RCM (recipient pays) or FCM (vendor pays).\n- **Bank account** — for vendor payouts.\n- **Rating** — 1–5, used by RFQ scoring.\n\n## Contacts\n\nA vendor can have multiple people:\n\n- Owner / Manager / Accountant / Driver coordinator\n- Each: name, phone, email, designation\n- **Phone is unique within a vendor** — prevents duplicate contacts.\n\nWhen you place a vehicle, the system pings the contact whose role matches the situation (e.g., Driver coordinator gets the placement WhatsApp).\n\n## GPS configuration on vehicles\n\nOn each *Vehicle* record (under the vendor) you can set:\n\n- **GPS provider** — LocoNav, FastTag, Driver Phone PWA, External link.\n- **IMEI / device ID** for hardware GPS.\n- **Tracking URL** — paste the share link from the provider; the GPS module pulls position from this when other sources are unavailable.\n- **Notes** — login info, last verification date, etc.\n\nThe GPS router prioritizes: LocoNav → FastTag → Driver Phone PWA → External link.','admin,management,pur_exec,pur_mgr,ops_exec,ops_mgr,accounts',15,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(7,'manage-drivers','Drivers & KYC','Master Data','Driver KYC is mandatory before a driver can be placed on a trip.\n\nRequired documents: **Aadhaar, Driving Licence, PAN**. The system tracks expiry on each and blocks assignment when any is expired.\n\nFor each driver, capture: name, mobile (used for WhatsApp + PWA login), home address, blood group (safety), emergency contact, language preference (English/Hindi/regional).\n\n> The Driver Phone PWA login uses the driver\'s mobile + a one-time code; once logged in, the phone reports GPS automatically when a trip is active.','admin,management,pur_exec,pur_mgr,ops_exec,ops_mgr',16,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(8,'manage-vehicles','Vehicles & compliance gate','Master Data','For each vehicle: registration number, vehicle type (e.g., 32ft SXL, 22ft Container), tonnage, owner vendor, GPS config.\n\n## Compliance gate\n\nThe system tracks expiry dates for:\n- RC (Registration Certificate)\n- Fitness certificate\n- National / State permit\n- Insurance\n- PUC\n\nIf **any** is expired or expires within the trip window, placement is blocked with a clear error. Renewals are recorded under the vehicle profile.','admin,management,pur_exec,pur_mgr,ops_exec,ops_mgr',17,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(9,'crm-leads','Capturing & qualifying leads','CRM','A **lead** is an inbound enquiry. Sources can be Website, WhatsApp, Inbound Call, Referral, Marketplace, Social, Manual Entry, Client Portal.\n\n## Lead lifecycle\n\n\\`New → Contacted → Qualified → Quoted → Won / Lost\\`\n\n- **New** — captured but not yet touched.\n- **Contacted** — first call/email made.\n- **Qualified** — confirmed real opportunity (route, vehicle, weight, expected dispatch).\n- **Quoted** — quotation sent.\n- **Won** — converted to booking.\n- **Lost** — record reason (price/timing/competitor/other).\n\n> Mark a lead **Lost with reason** rather than deleting it — the *Lost reason* report drives sales improvement.','admin,management,crm_exec,crm_mgr',18,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(10,'crm-rfq-quotations','RFQs to vendors & quotations to clients','CRM','## RFQ — Request For Quote (to vendors)\n\nFrom a qualified lead, raise an **RFQ** to multiple vendors at once. The RFQ contains route, vehicle type, material, weight, loading date.\n\nYou can:\n- **Broadcast** the RFQ via WhatsApp template \\`rfq_vendor\\`.\n- **Receive** vendor replies in the RFQ thread.\n- **Score** vendors (rate, reliability, past performance).\n- **Pick** the winning vendor and convert to a booking.\n\n## Quotation — to client\n\nOnce you have your buy rate, mark up and send a **quotation** to the client. The quotation has a validity period (default 7 days). When the client confirms, **Convert to Booking** — the lead becomes Won, the quotation is locked, and a booking is created.\n\n> Quotations are versioned. If the client negotiates, create a new version rather than overwriting — for audit and the *Win-rate vs. negotiation* report.','admin,management,crm_exec,crm_mgr,pur_exec,pur_mgr',19,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(11,'ops-bookings','Bookings — from confirmation to dispatch','Operations','A **booking** is a confirmed order. It captures: client, route (pickup → drop with possible multi-pickup / multi-drop), vehicle requirement, sell rate, expected dispatch, special instructions.\n\n## Booking lifecycle\n\n\\`Confirmed → Vehicle Placed → In Transit → Delivered → POD Received → Closed\\`\n\n## What happens at each step\n\n- **Confirmed**: ops team gets a task to *find a vehicle*.\n- **Vehicle Placed**: a *trip* record is created automatically. Driver is locked in. WhatsApp goes to the client (\\`vehicle_placed\\` template).\n- **In Transit**: GPS starts streaming. Loading slot, loading time, unloading slot are captured.\n- **Delivered**: time-stamped at unloading. Detention auto-accrues if loading or unloading exceeded the free time configured in the rate contract.\n- **POD Received**: e-POD signature (driver phone) or scanned paper POD uploaded.\n- **Closed**: invoice raised; nothing more to do.\n\n> A booking can be **rebooked** from the Client Portal (Owner/Booker roles) — useful for repeat lanes.','admin,management,crm_exec,crm_mgr,ops_exec,ops_mgr',20,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(12,'ops-trips-and-pod','Trips, e-POD & detention','Operations','A **trip** is the execution side of a booking. One booking = one trip (or many, in multi-vehicle scenarios).\n\n## Status flow\n\n\\`Placed → Loading → In Transit → Unloading → Delivered → POD Received\\`\n\nEach transition timestamps the trip and may trigger:\n- WhatsApp / Email to the client.\n- **Detention auto-accrual** — if loading or unloading exceeds free time, the system creates a detention line item on the trip with the rate from the rate contract.\n- **Driver advance** — record cash/UPI advances to the driver against the trip; deducted from the final freight payable.\n\n## e-POD\n\nOpen the trip on the **Driver PWA** and let the consignee sign on screen. The signature is captured as PNG, stamped with timestamp + GPS coordinate. Trip auto-moves to **POD Received**.\n\nPaper POD: scan and upload on the trip page; mark *Verified* once verified.','admin,management,ops_exec,ops_mgr',21,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(13,'ops-gps-tracking','GPS tracking — sources & priority','Operations','The platform supports four GPS sources, used in priority order:\n\n1. **LocoNav** — direct API, polled every 5 min.\n2. **FastTag** — provider-agnostic; uses NHAI plaza pings.\n3. **Driver Phone PWA** — when the driver opens the PWA on their phone, location is reported via HTML5 \\`watchPosition\\`. Wake Lock keeps the screen on; offline buffer queues pings when connectivity is lost.\n4. **External link** — paste a tracking share URL from any third-party provider.\n\nConfigure on each Vehicle profile (see *Vendors & GPS configuration* topic).\n\n## In the UI\n\n- **Trip → Map** tab shows live position + the route taken.\n- **GPS log** records every ping with source, timestamp, lat/lng, speed.\n- ETA is computed from current position + average speed of last 30 min.\n\n> If GPS is silent for >2 hours during In Transit, the trip is flagged red on the dashboard.','admin,management,ops_exec,ops_mgr',22,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(14,'finance-invoices','Invoices, GST & E-Invoice','Finance','## Raising an invoice\n\nFrom a delivered trip with POD received, click **Raise Invoice**. The system pre-fills:\n\n- Client + billing GSTIN\n- Line items (freight, detention, multi-pickup, halting charges, insurance)\n- GST split — IGST if states differ, CGST+SGST if same\n- TCS / TDS adjustments\n\nYou can edit before posting. Once posted, the invoice number is locked.\n\n## E-Invoice (IRP)\n\nIf your turnover crosses the threshold (currently ₹5 cr aggregate), invoices auto-fire to the **IRP** for IRN + signed QR. The QR is rendered on the printable PDF.\n\n## E-Way Bill on the invoice\n\nIf the invoice triggers an EWB requirement (value > ₹50k inter-state, etc.), generate **EWB** from the invoice page. Part-B (vehicle no.) is auto-filled if a trip is linked. You can also extend the EWB or cancel it within 24 hrs.','admin,management,accounts',23,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(15,'finance-receipts','Receiving payments & ageing','Finance','- **Receipts** are credits to the client account: bank transfer, UPI, cheque, cash.\n- A receipt can be **knocked off** against one or more invoices fully or partially.\n- **Unallocated** balance sits in the client\'s wallet and shows on the next statement.\n- **Ageing buckets**: 0-30, 31-60, 61-90, 90+. Configurable in *Settings → Finance*.\n\n## Statements\n\nGenerate a client statement from the client profile or by date range. Downloadable as PDF and shareable via WhatsApp/Email directly from the same screen.','admin,management,accounts',24,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(16,'finance-vendor-bills-tds','Vendor bills, TDS u/s 194C & Form 16A','Finance','## Vendor bill\n\nCreated from a delivered trip → records the *buy* side: freight to the vendor, advances given to the driver, detention, plus GTA tax handling.\n\n### GTA tax: RCM vs FCM\n\n- **GTA under RCM** — *you* (the recipient) pay GST and claim ITC. Vendor invoice is GST-zero. This is the default when the vendor\'s GSTIN status flag = \"RCM\".\n- **GTA under FCM** — vendor charges 5%/12% on the bill; you pay it and claim ITC.\n\nThe system picks the right path automatically based on vendor flags.\n\n## TDS u/s 194C\n\nFor freight to a transporter (who has not declared <10 vehicles), TDS applies:\n- **1%** if vendor is an individual / HUF\n- **2%** otherwise\n- **20%** if PAN is missing (avoid this — capture PAN at onboarding)\n\nTDS is auto-deducted on the vendor bill. Net payable = bill - TDS - advances.\n\n## Form 16A (TDS certificate)\n\nQuarterly Form 16A can be generated from **Reports → TDS Certificates**. It includes vendor PAN, deduction summary, and challan reference (entered manually after filing TDS return).','admin,management,accounts,pur_exec,pur_mgr',25,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(17,'finance-vendor-payments','Vendor payments & deposits','Finance','## Payments\n\nFrom an unpaid vendor bill, hit **Pay**. Choose mode (NEFT/RTGS/IMPS/UPI/Cheque/Cash), bank account, transaction reference. Payment knocks off the bill (or part of it).\n\n## Vendor security deposits\n\nOptional — track refundable deposits taken from new vendors:\n- Hold against trips with pending POD.\n- Auto-release after a configurable cooling period.\n- Forfeit if vendor abandons a trip (with audit trail).\n\n> Enable / disable from **Settings → Feature Flags → Vendor Deposits**.','admin,management,accounts',26,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(18,'comm-whatsapp','WhatsApp templates','Communication','The platform integrates with the WhatsApp Business API. Templates must be both **registered locally** and **approved in Meta** before they can fire.\n\nBuilt-in templates:\n\n- \\`rfq_vendor\\` — broadcast RFQ to vendors\n- \\`quote_to_client\\` — share quotation\n- \\`vehicle_placed\\` — notify client when vehicle is placed\n- \\`trip_in_transit\\` — periodic location update\n- \\`trip_delivered\\` — delivery confirmation\n- \\`pod_reminder\\` — chase POD from vendor\n- \\`invoice_share\\` — send invoice\n- \\`payment_reminder\\` — chase payment\n\nVariables in the body use \\`{{1}}, {{2}}, ...\\` matching the order in the template registration in Meta.','admin,management,crm_exec,crm_mgr,ops_exec,ops_mgr',27,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(19,'comm-email','Email templates, queue & analytics','Communication','## Drivers\n\nConfigure one of: **Brevo, Amazon SES, Google SMTP** in *Settings → Email*.\n\n## Templates\n\nHTML templates with merge variables. Pre-loaded templates cover: welcome, password reset, invoice share, payment reminder, statement, generic notification.\n\n## Queue\n\nAll outbound mail is queued. A worker process claims rows (with \\`worker_id\\` lock) and dispatches. A **reaper** resets rows stuck in *Sending* >5 min back to *Queued*.\n\n## Tracking\n\n- **Open**: HMAC-signed pixel.\n- **Click**: HMAC-signed link redirector.\n- **Bounce / Spam**: webhook from Brevo/SES; for SES, the SubscriptionConfirmation is signature-verified.\n\nAnalytics dashboard: sent, delivered, opens, clicks, bounces, complaints.','admin,management,accounts',28,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(20,'compliance-eway-bill','E-Way Bill — generate, Part-B, extend, cancel, consolidated','Compliance','The platform integrates with the **NIC EWB** API (mediated via ClearTax, configured in Settings).\n\nOperations supported on every EWB:\n\n- **Generate** — from an invoice or trip.\n- **Part-B update** — vehicle number, mode (Road/Rail/Air/Ship), transporter ID. Auto-filled from the linked trip.\n- **Extend validity** — when delays push the journey past the EWB validity (validity = 1 day per 200 km in normal cargo).\n- **Cancel** — only within 24 hrs of generation, only if not verified by an officer.\n- **Consolidated EWB** — one truck, multiple consignments → one consolidated EWB.\n\n> If the EWB API errors out, the failure is logged with the exact error code; you can retry without losing form data.','admin,management,ops_exec,ops_mgr,accounts',29,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(21,'compliance-gst-tds','GST, TCS, TDS — practical reference','Compliance','## GST on transport (GTA)\n\n- Freight is taxable under HSN **9965**.\n- **5%** without ITC, or **12%** with ITC — vendor\'s choice, declared annually.\n- **RCM** is the common practice: the recipient pays GST.\n\n## TCS\n\nIf you operate as an **e-commerce operator** (aggregator), TCS u/s 52 may apply on supplies through your platform. Configure rates in *Settings → Finance → TCS*.\n\n## TDS\n\n- **194C** on freight (1% or 2%, 20% without PAN).\n- **194Q** on purchases over ₹50 lakh (0.1%).\n- Vendor records PAN/Aadhaar; the system computes TDS automatically on each vendor bill.','admin,management,accounts',30,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(22,'portal-client-overview','Client portal — for your customers','Client Portal','The client portal is a separate login at \\`/portal\\` for your customer\'s team.\n\n## Roles\n\n- **Owner** — invite teammates, raise bookings, see invoices.\n- **Booker** — raise bookings, rebook, view live trips.\n- **Accounts** — see invoices, statements, raise payment receipts.\n- **Viewer** — read-only.\n\n## What clients can do\n\n- Raise a booking from a calendar slot or \"Repeat last booking\".\n- Track live GPS of their trips.\n- Download POD, invoices, statements.\n- Approve quotations sent by your sales team.\n- Pay invoices via UPI / payment gateway (if integrated).\n\n> Client logins use a different cookie key than staff (\\`cu_*\\` vs \\`auth_*\\`) and the two cannot exist on the same browser at the same time — preventing accidental privilege escalation.','admin,management,crm_exec,crm_mgr,client_owner,client_booker,client_accounts,client_viewer',31,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(23,'reports-overview','Reports & dashboards','Reports','Reports cover the full P&L:\n\n- **Sales** — leads, conversion %, win rate, lost reasons, quotation ageing.\n- **Operations** — placement TAT, in-transit count, on-time %, detention hours, POD ageing.\n- **Finance** — receivables ageing, payables ageing, unbilled trips, GST output/input, TDS register.\n- **Vendor** — scorecard (rate, reliability, on-time, detention frequency, KYC status).\n- **Lane profitability** — revenue minus all costs (freight, detention, advances, tolls) per lane.\n\nMost reports support: date range, role-based filters, CSV export, \"save as default view\".','admin,management,crm_mgr,ops_mgr,pur_mgr,accounts',32,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(24,'reports-vendor-scorecard','Vendor scorecard explained','Reports','The **Vendor Scorecard** ranks vendors on a 0-100 score based on:\n\n- **Rate competitiveness** (vs. lane average)  — 30%\n- **On-time placement & delivery** — 30%\n- **POD turnaround** — 15%\n- **Detention frequency** — 10%\n- **KYC / compliance** completeness — 10%\n- **Client feedback rating** — 5%\n\nUse the scorecard to:\n- Tier vendors (A/B/C) and route the next RFQ to your A-tier first.\n- Drop vendors with score <40 or persistent KYC gaps.','admin,management,pur_exec,pur_mgr',33,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(25,'admin-roles-permissions','Roles, permissions & access control','Admin','The system uses **module × action** RBAC: each role gets per-module flags for *view, add, edit, delete, approve, export*.\n\n## Built-in roles\n\n- Administrator (everything)\n- Management (view + approve everywhere)\n- CRM Executive / Manager\n- Purchase Executive / Manager\n- Operations Executive / Manager\n- Accounts\n\n## Adding a custom role\n\n*Roles → New*. Pick role key (machine name) and label. Set the flags per module. Assign the new role to users.\n\n> Role changes take effect on the user\'s **next page load** — no logout needed.','admin,management',34,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(26,'admin-settings','Settings — what to configure where','Admin','**Settings → Company** — name, logo, GSTIN, PAN, registered address. Drives invoice headers.\n\n**Settings → Numbering** — prefixes for Lead/RFQ/Booking/Trip/Invoice/LR.\n\n**Settings → Finance** — default GST rate, TCS, TDS thresholds, ageing buckets.\n\n**Settings → Email** — driver (Brevo/SES/SMTP), from address, tracking pixel HMAC key.\n\n**Settings → WhatsApp** — Meta credentials, default sender.\n\n**Settings → Approvals** — when to require management approval (over-credit-limit booking, large discount, etc.).\n\n**Settings → Feature Flags** — toggle modular features (Rate Contracts, Loading Slots, e-POD signature, TDS Certificates, Vendor Deposits, Lane Profitability, Trip Insurance Auto-Quote, Support Rating, etc.).\n\n**Settings → Maintenance** — put the app in read-only maintenance mode (super admin only).','admin,management',35,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(27,'admin-feature-flags','Modular features & feature flags','Admin','Several advanced features are **modular** — turn them on only if you need them:\n\n| Flag | What it does |\n|------|--------------|\n| \\`rate_contracts\\` | Lock buy/sell rates for a client × lane × period |\n| \\`loading_slots\\` | Dock booking + slot management |\n| \\`epod_signature\\` | Touch-screen signature on Driver PWA |\n| \\`tds_certificates\\` | Quarterly Form 16A generation |\n| \\`vendor_deposits\\` | Refundable security deposits from vendors |\n| \\`lane_profitability\\` | P&L per lane report |\n| \\`trip_insurance\\` | Auto-quote cargo insurance on bookings |\n| \\`support_rating_enabled\\` | Allow customers to rate support tickets |\n\nDefense-in-depth: flag-gated routes also have an \\`auth:<module>\\` filter, so flipping a flag off prevents both access *and* sidebar surfacing.','admin,management',36,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(28,'superadmin','Super Admin — diagnostics & maintenance','Super Admin','The **Super Admin** is invisible to other admins — does not appear in user lists, role assignments, or audit logs unless you specifically un-redact in *Sys → Audit*.\n\n## Tools\n\n- **Diagnostics** — PHP/MariaDB versions, ext loaded, queue depths, slow queries, broken cron jobs.\n- **SQL console** — read-only by default. Switch to write only with a per-session confirm.\n- **Impersonation** — log in *as* any user. The session carries an \\`impersonator_id\\` and every action audit-logs both.\n- **TOTP 2FA toggle** — per-user enforcement.\n- **Maintenance mode** — read-only banner across the app while you migrate / run jobs.\n- **Feature flags** — same as Admin, plus dangerous ones (debug toolbar, query log, dev-only routes).\n\n> Super-admin actions appear in the audit log under a dedicated \"Sys\" channel.','super',37,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL),(29,'how-to-use-support','How to raise a support ticket','Getting Started','1. Open the **Support** section from the left sidebar.\n2. Click **Raise ticket**.\n3. Pick the **Type** — Bug, Improvement, Question, Other.\n4. Set **Priority** — Low, Normal, High, Urgent.\n5. Paste the screen URL where you saw the issue (helpful for bugs).\n6. Write a clear subject and a description with steps to reproduce.\n\n## After raising\n\n- You\'ll get a ticket number like \\`SUP00123\\`.\n- The support team will reply in the ticket thread; you\'ll be notified by email.\n- A reply from you on a *Resolved* ticket reopens it automatically.\n- Once your ticket is **Resolved** or **Closed**, you can rate the support 1–5 stars (if your admin has enabled rating).','all',38,1,NULL,'2026-05-01 12:25:03','2026-05-01 12:25:03',NULL);
/*!40000 ALTER TABLE `help_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `insurance_quotes`
--

DROP TABLE IF EXISTS `insurance_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `insurance_quotes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `cargo_value` decimal(14,2) NOT NULL,
  `premium` decimal(10,2) NOT NULL,
  `provider` varchar(80) NOT NULL,
  `policy_no` varchar(80) DEFAULT NULL,
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `raw_payload` mediumtext DEFAULT NULL,
  `status` enum('Quoted','Bound','Cancelled') NOT NULL DEFAULT 'Quoted',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  CONSTRAINT `insurance_quotes_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `insurance_quotes`
--

LOCK TABLES `insurance_quotes` WRITE;
/*!40000 ALTER TABLE `insurance_quotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `insurance_quotes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `description` varchar(400) NOT NULL,
  `hsn_sac` varchar(20) DEFAULT NULL,
  `qty` decimal(10,2) NOT NULL DEFAULT 1.00,
  `rate` decimal(12,2) NOT NULL DEFAULT 0.00,
  `taxable_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `gst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `invoice_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
INSERT INTO `invoice_items` VALUES (1,1,'Freight Surat → Chennai · DL01BC1123 · Fabric rolls · 14 TON','996791',1.00,48520.00,48520.00,5.00,2426.00,50946.00),(2,2,'Freight Ludhiana → Mumbai · KA05CD1246 · Knitwear · 17 TON','996791',1.00,56150.00,56150.00,5.00,2807.50,58957.50),(3,3,'Freight Pune → Delhi · TN09DE1369 · Textiles · 15 TON','996791',1.00,48550.00,48550.00,5.00,2427.50,50977.50),(4,4,'Freight Delhi → Kolkata · GJ01EF1492 · Machinery · 20 TON','996791',1.00,54920.00,54920.00,5.00,2746.00,57666.00),(5,5,'Freight Hyderabad → Delhi · HR26FG1615 · Pharma · 8 TON','996791',1.00,33490.00,33490.00,5.00,1674.50,35164.50),(6,6,'Freight Surat → Chennai · WB12GH1738 · Fabric rolls · 14 TON','996791',1.00,49470.00,49470.00,5.00,2473.50,51943.50),(7,7,'Freight Ahmedabad → Mumbai · UP32HI1861 · Packaged food · 6 TON','996791',1.00,36850.00,36850.00,5.00,1842.50,38692.50);
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(40) NOT NULL,
  `invoice_date` date NOT NULL,
  `client_id` int(10) unsigned NOT NULL,
  `booking_id` int(10) unsigned DEFAULT NULL,
  `trip_id` int(10) unsigned DEFAULT NULL,
  `taxable_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sgst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `igst_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gst_treatment` enum('rcm','fcm5','fcm12') DEFAULT 'fcm5',
  `detention_amount` decimal(10,2) DEFAULT 0.00,
  `tds_rate` decimal(5,2) DEFAULT 0.00,
  `tds_amount` decimal(10,2) DEFAULT 0.00,
  `net_receivable` decimal(12,2) DEFAULT 0.00,
  `round_off` decimal(6,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_received` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `invoice_status` varchar(30) NOT NULL DEFAULT 'Draft',
  `pdf_path` varchar(255) DEFAULT NULL,
  `irn_no` varchar(120) DEFAULT NULL,
  `ack_no` varchar(60) DEFAULT NULL,
  `ack_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `client_id` (`client_id`),
  KEY `booking_id` (`booking_id`),
  KEY `invoice_status` (`invoice_status`),
  KEY `idx_inv_status_date` (`invoice_status`,`invoice_date`),
  KEY `idx_inv_status_due` (`invoice_status`,`due_date`,`balance_due`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV00001','2025-12-08',1,1,1,48520.00,1213.00,1213.00,0.00,'fcm5',0.00,0.00,0.00,0.00,0.00,50946.00,50946.00,0.00,'2025-12-23','Paid',NULL,NULL,NULL,NULL,'Freight charges as per booking.',1,NULL,'2025-12-08 10:01:40','2025-12-08 10:01:40',NULL),(2,'INV00002','2026-03-29',3,2,2,56150.00,0.00,0.00,2807.50,'fcm5',0.00,0.00,0.00,0.00,0.50,58958.00,58958.00,0.00,'2026-04-13','Paid',NULL,NULL,NULL,NULL,'Freight charges as per booking.',1,NULL,'2026-03-29 10:01:40','2026-03-29 10:01:40',NULL),(3,'INV00003','2026-02-03',3,3,3,48550.00,0.00,0.00,2427.50,'fcm5',0.00,0.00,0.00,0.00,0.50,50978.00,50978.00,0.00,'2026-02-18','Paid',NULL,NULL,NULL,NULL,'Freight charges as per booking.',1,NULL,'2026-02-03 10:01:40','2026-02-03 10:01:40',NULL),(4,'INV00004','2026-03-26',7,4,4,54920.00,0.00,0.00,2746.00,'fcm5',0.00,0.00,0.00,0.00,0.00,57666.00,57666.00,0.00,'2026-04-10','Paid',NULL,NULL,NULL,NULL,'Freight charges as per booking.',1,NULL,'2026-03-26 10:01:40','2026-03-26 10:01:40',NULL),(5,'INV00005','2026-03-29',4,5,5,33490.00,0.00,0.00,1674.50,'fcm5',0.00,0.00,0.00,0.00,0.50,35165.00,35165.00,0.00,'2026-04-13','Paid',NULL,NULL,NULL,NULL,'Freight charges as per booking.',1,NULL,'2026-03-29 10:01:40','2026-03-29 10:01:40',NULL),(6,'INV00006','2025-12-15',2,6,6,49470.00,1236.75,1236.75,0.00,'fcm5',0.00,0.00,0.00,0.00,0.50,51944.00,25972.00,25972.00,'2025-12-30','Partially Paid',NULL,NULL,NULL,NULL,'Freight charges as per booking.',1,NULL,'2025-12-15 10:01:40','2025-12-15 10:01:40',NULL),(7,'INV00007','2025-12-06',1,7,7,36850.00,921.25,921.25,0.00,'fcm5',0.00,0.00,0.00,0.00,0.50,38693.00,19346.50,19346.50,'2025-12-21','Partially Paid',NULL,NULL,NULL,NULL,'Freight charges as per booking.',1,NULL,'2025-12-06 10:01:40','2025-12-06 10:01:40',NULL);
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `latest_vehicle_status`
--

DROP TABLE IF EXISTS `latest_vehicle_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `latest_vehicle_status` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned DEFAULT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `gps_timestamp` datetime DEFAULT NULL,
  `speed` decimal(6,2) DEFAULT NULL,
  `address` varchar(400) DEFAULT NULL,
  `eta_text` varchar(100) DEFAULT NULL,
  `delay_flag` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL,
  `source` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_number` (`vehicle_number`),
  KEY `trip_id` (`trip_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `latest_vehicle_status`
--

LOCK TABLES `latest_vehicle_status` WRITE;
/*!40000 ALTER TABLE `latest_vehicle_status` DISABLE KEYS */;
INSERT INTO `latest_vehicle_status` VALUES (1,6,'WB12GH1738',13.0827000,80.2707000,'2026-05-01 10:01:40',15.00,'Chennai — near unloading','Approaching drop point',0,'2026-05-01 10:01:40',NULL),(2,7,'UP32HI1861',19.0760000,72.8777000,'2026-05-01 10:01:40',15.00,'Mumbai — near unloading','Approaching drop point',1,'2026-05-01 10:01:40',NULL);
/*!40000 ALTER TABLE `latest_vehicle_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_followups`
--

DROP TABLE IF EXISTS `lead_followups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_followups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` int(10) unsigned NOT NULL,
  `followup_datetime` datetime DEFAULT NULL,
  `followup_type` varchar(40) DEFAULT NULL,
  `discussion_notes` text DEFAULT NULL,
  `next_followup_datetime` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  CONSTRAINT `lead_followups_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_followups`
--

LOCK TABLES `lead_followups` WRITE;
/*!40000 ALTER TABLE `lead_followups` DISABLE KEYS */;
INSERT INTO `lead_followups` VALUES (1,2,'2026-03-27 10:01:39','Call','Client confirmed requirement.',NULL,1,'2026-03-27 10:01:39'),(2,8,'2026-03-30 10:01:39','Call','Client confirmed requirement.',NULL,1,'2026-03-30 10:01:39'),(3,10,'2026-04-04 10:01:39','Call','Client confirmed requirement.',NULL,1,'2026-04-04 10:01:39'),(4,11,'2025-12-19 10:01:39','Call','Client confirmed requirement.',NULL,1,'2025-12-19 10:01:39'),(5,12,'2025-12-22 10:01:39','Call','Client confirmed requirement.',NULL,1,'2025-12-22 10:01:39'),(6,13,'2026-02-28 10:01:39','Call','Client confirmed requirement.',NULL,1,'2026-02-28 10:01:39'),(7,16,'2026-04-15 10:01:39','Call','Client confirmed requirement.',NULL,1,'2026-04-15 10:01:39'),(8,17,'2025-12-04 10:01:39','Call','Client confirmed requirement.',NULL,1,'2025-12-04 10:01:39'),(9,22,'2026-04-08 10:01:39','Call','Client confirmed requirement.',NULL,1,'2026-04-08 10:01:39'),(10,26,'2026-01-28 10:01:39','Call','Client confirmed requirement.',NULL,1,'2026-01-28 10:01:39'),(11,29,'2025-12-24 10:01:39','Call','Client confirmed requirement.',NULL,1,'2025-12-24 10:01:39'),(12,30,'2026-01-19 10:01:39','Call','Client confirmed requirement.',NULL,1,'2026-01-19 10:01:39');
/*!40000 ALTER TABLE `lead_followups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_sources`
--

DROP TABLE IF EXISTS `lead_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_sources` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_name` varchar(80) NOT NULL,
  `source_key` varchar(40) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `source_key` (`source_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_sources`
--

LOCK TABLES `lead_sources` WRITE;
/*!40000 ALTER TABLE `lead_sources` DISABLE KEYS */;
INSERT INTO `lead_sources` VALUES (1,'Website','web',1),(2,'WhatsApp','whatsapp',1),(3,'Inbound Call','call',1),(4,'Referral','referral',1),(5,'Marketplace','marketplace',1),(6,'Social','social',1),(7,'Manual Entry','manual',1),(8,'Client Portal','portal',1);
/*!40000 ALTER TABLE `lead_sources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lead_status_history`
--

DROP TABLE IF EXISTS `lead_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lead_status_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` int(10) unsigned NOT NULL,
  `old_status` varchar(40) DEFAULT NULL,
  `new_status` varchar(40) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `changed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  CONSTRAINT `lead_status_history_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lead_status_history`
--

LOCK TABLES `lead_status_history` WRITE;
/*!40000 ALTER TABLE `lead_status_history` DISABLE KEYS */;
INSERT INTO `lead_status_history` VALUES (1,1,NULL,'New','Lead created',1,'2025-12-05 10:01:39'),(2,1,'New','Won','Demo workflow',1,'2025-12-06 10:01:39'),(3,2,NULL,'New','Lead created',1,'2026-03-26 10:01:39'),(4,2,'New','Won','Demo workflow',1,'2026-03-27 10:01:39'),(5,3,NULL,'New','Lead created',1,'2026-01-31 10:01:39'),(6,3,'New','Won','Demo workflow',1,'2026-02-01 10:01:39'),(7,4,NULL,'New','Lead created',1,'2026-03-23 10:01:39'),(8,4,'New','Won','Demo workflow',1,'2026-03-24 10:01:39'),(9,5,NULL,'New','Lead created',1,'2026-03-26 10:01:39'),(10,5,'New','Won','Demo workflow',1,'2026-03-27 10:01:39'),(11,6,NULL,'New','Lead created',1,'2025-12-12 10:01:39'),(12,6,'New','Won','Demo workflow',1,'2025-12-13 10:01:39'),(13,7,NULL,'New','Lead created',1,'2025-12-03 10:01:39'),(14,7,'New','Won','Demo workflow',1,'2025-12-04 10:01:39'),(15,8,NULL,'New','Lead created',1,'2026-03-29 10:01:39'),(16,8,'New','Won','Demo workflow',1,'2026-03-30 10:01:39'),(17,9,NULL,'New','Lead created',1,'2026-04-22 10:01:39'),(18,9,'New','Lost','Demo workflow',1,'2026-04-23 10:01:39'),(19,10,NULL,'New','Lead created',1,'2026-04-03 10:01:39'),(20,10,'New','Lost','Demo workflow',1,'2026-04-04 10:01:39'),(21,11,NULL,'New','Lead created',1,'2025-12-18 10:01:39'),(22,11,'New','Lost','Demo workflow',1,'2025-12-19 10:01:39'),(23,12,NULL,'New','Lead created',1,'2025-12-21 10:01:39'),(24,12,'New','Lost','Demo workflow',1,'2025-12-22 10:01:39'),(25,13,NULL,'New','Lead created',1,'2026-02-27 10:01:39'),(26,13,'New','Closed','Demo workflow',1,'2026-02-28 10:01:39'),(27,14,NULL,'New','Lead created',1,'2026-01-24 10:01:39'),(28,14,'New','Closed','Demo workflow',1,'2026-01-25 10:01:39'),(29,15,NULL,'New','Lead created',1,'2026-04-01 10:01:39'),(30,15,'New','Sent to Client','Demo workflow',1,'2026-04-02 10:01:39'),(31,16,NULL,'New','Lead created',1,'2026-04-14 10:01:39'),(32,16,'New','Sent to Client','Demo workflow',1,'2026-04-15 10:01:39'),(33,17,NULL,'New','Lead created',1,'2025-12-03 10:01:39'),(34,17,'New','Sent to Client','Demo workflow',1,'2025-12-04 10:01:39'),(35,18,NULL,'New','Lead created',1,'2026-04-17 10:01:39'),(36,18,'New','Negotiation','Demo workflow',1,'2026-04-18 10:01:39'),(37,19,NULL,'New','Lead created',1,'2026-02-08 10:01:39'),(38,19,'New','Negotiation','Demo workflow',1,'2026-02-09 10:01:39'),(39,20,NULL,'New','Lead created',1,'2025-12-11 10:01:39'),(40,20,'New','Quote Received','Demo workflow',1,'2025-12-12 10:01:39'),(41,21,NULL,'New','Lead created',1,'2025-12-02 10:01:39'),(42,21,'New','Quote Received','Demo workflow',1,'2025-12-03 10:01:39'),(43,22,NULL,'New','Lead created',1,'2026-04-07 10:01:39'),(44,22,'New','Quote Received','Demo workflow',1,'2026-04-08 10:01:39'),(45,23,NULL,'New','Lead created',1,'2025-12-29 10:01:39'),(46,23,'New','Sent to Purchase','Demo workflow',1,'2025-12-30 10:01:39'),(47,24,NULL,'New','Lead created',1,'2026-03-06 10:01:39'),(48,24,'New','Sent to Purchase','Demo workflow',1,'2026-03-07 10:01:39'),(49,25,NULL,'New','Lead created',1,'2026-03-12 10:01:39'),(50,25,'New','Under Review','Demo workflow',1,'2026-03-13 10:01:39'),(51,26,NULL,'New','Lead created',1,'2026-01-27 10:01:39'),(52,26,'New','Under Review','Demo workflow',1,'2026-01-28 10:01:39'),(53,27,NULL,'New','Lead created',1,'2026-04-16 10:01:39'),(54,28,NULL,'New','Lead created',1,'2026-03-21 10:01:39'),(55,29,NULL,'New','Lead created',1,'2025-12-23 10:01:39'),(56,30,NULL,'New','Lead created',1,'2026-01-18 10:01:39');
/*!40000 ALTER TABLE `lead_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lead_no` varchar(30) NOT NULL,
  `lead_datetime` datetime DEFAULT NULL,
  `source_id` int(10) unsigned DEFAULT NULL,
  `client_id` int(10) unsigned DEFAULT NULL,
  `client_name` varchar(150) DEFAULT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `alt_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `pickup_city` varchar(80) DEFAULT NULL,
  `pickup_state` varchar(80) DEFAULT NULL,
  `drop_city` varchar(80) DEFAULT NULL,
  `drop_state` varchar(80) DEFAULT NULL,
  `material_type` varchar(120) DEFAULT NULL,
  `vehicle_type_required` varchar(80) DEFAULT NULL,
  `vehicle_count` smallint(5) unsigned DEFAULT 1,
  `weight` decimal(10,2) DEFAULT NULL,
  `weight_unit` varchar(10) NOT NULL DEFAULT 'TON',
  `expected_dispatch_date` date DEFAULT NULL,
  `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `assigned_crm_user_id` int(10) unsigned DEFAULT NULL,
  `current_status` varchar(40) NOT NULL DEFAULT 'New',
  `lost_reason` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lead_no` (`lead_no`),
  KEY `current_status` (`current_status`),
  KEY `assigned_crm_user_id` (`assigned_crm_user_id`),
  KEY `client_id` (`client_id`),
  KEY `pickup_city_drop_city` (`pickup_city`,`drop_city`),
  KEY `idx_lead_expected` (`expected_dispatch_date`),
  KEY `idx_lead_assigned_created` (`assigned_crm_user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'LD00001','2025-12-05 10:01:39',1,1,NULL,NULL,NULL,NULL,NULL,'Surat','Gujarat','Chennai','Tamil Nadu','Fabric rolls','32ft SXL',1,14.00,'TON','2025-12-08','Urgent',1,'Won',NULL,NULL,1,NULL,'2025-12-05 10:01:39','2025-12-06 10:01:39',NULL),(2,'LD00002','2026-03-26 10:01:39',8,3,NULL,NULL,NULL,NULL,NULL,'Ludhiana','Punjab','Mumbai','Maharashtra','Knitwear','32ft MXL',1,17.00,'TON','2026-03-29','Normal',1,'Won',NULL,NULL,1,NULL,'2026-03-26 10:01:39','2026-03-27 10:01:39',NULL),(3,'LD00003','2026-01-31 10:01:39',5,3,NULL,NULL,NULL,NULL,NULL,'Pune','Maharashtra','Delhi','Delhi','Textiles','32ft SXL',1,15.00,'TON','2026-02-03','High',1,'Won',NULL,NULL,1,NULL,'2026-01-31 10:01:39','2026-02-01 10:01:39',NULL),(4,'LD00004','2026-03-23 10:01:39',7,7,NULL,NULL,NULL,NULL,NULL,'Delhi','Delhi','Kolkata','West Bengal','Machinery','32ft MXL',1,20.00,'TON','2026-03-26','Normal',1,'Won',NULL,NULL,1,NULL,'2026-03-23 10:01:39','2026-03-24 10:01:39',NULL),(5,'LD00005','2026-03-26 10:01:39',7,4,NULL,NULL,NULL,NULL,NULL,'Hyderabad','Telangana','Delhi','Delhi','Pharma','40ft Container',1,8.00,'TON','2026-03-29','High',1,'Won',NULL,NULL,1,NULL,'2026-03-26 10:01:39','2026-03-27 10:01:39',NULL),(6,'LD00006','2025-12-12 10:01:39',3,2,NULL,NULL,NULL,NULL,NULL,'Surat','Gujarat','Chennai','Tamil Nadu','Fabric rolls','32ft SXL',1,14.00,'TON','2025-12-15','Normal',1,'Won',NULL,NULL,1,NULL,'2025-12-12 10:01:39','2025-12-13 10:01:39',NULL),(7,'LD00007','2025-12-03 10:01:39',2,1,NULL,NULL,NULL,NULL,NULL,'Ahmedabad','Gujarat','Mumbai','Maharashtra','Packaged food','14ft Closed Body',1,6.00,'TON','2025-12-06','Normal',1,'Won',NULL,NULL,1,NULL,'2025-12-03 10:01:39','2025-12-04 10:01:39',NULL),(8,'LD00008','2026-03-29 10:01:39',4,2,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra','Chennai','Tamil Nadu','FMCG','32ft SXL',1,18.00,'TON','2026-04-01','High',1,'Won',NULL,NULL,1,NULL,'2026-03-29 10:01:39','2026-03-30 10:01:39',NULL),(9,'LD00009','2026-04-22 10:01:39',7,3,NULL,NULL,NULL,NULL,NULL,'Ahmedabad','Gujarat','Mumbai','Maharashtra','Packaged food','14ft Closed Body',1,6.00,'TON','2026-04-25','High',1,'Lost','Rate too high',NULL,1,NULL,'2026-04-22 10:01:39','2026-04-23 10:01:39',NULL),(10,'LD00010','2026-04-03 10:01:39',6,6,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra','Chennai','Tamil Nadu','FMCG','32ft SXL',1,18.00,'TON','2026-04-06','Urgent',1,'Lost','Rate too high',NULL,1,NULL,'2026-04-03 10:01:39','2026-04-04 10:01:39',NULL),(11,'LD00011','2025-12-18 10:01:39',8,5,NULL,NULL,NULL,NULL,NULL,'Bangalore','Karnataka','Kochi','Kerala','Auto parts','22ft Open Body',1,12.00,'TON','2025-12-21','High',1,'Lost','Rate too high',NULL,1,NULL,'2025-12-18 10:01:39','2025-12-19 10:01:39',NULL),(12,'LD00012','2025-12-21 10:01:39',2,2,NULL,NULL,NULL,NULL,NULL,'Bangalore','Karnataka','Kochi','Kerala','Auto parts','22ft Open Body',1,12.00,'TON','2025-12-24','Normal',1,'Lost','Rate too high',NULL,1,NULL,'2025-12-21 10:01:39','2025-12-22 10:01:39',NULL),(13,'LD00013','2026-02-27 10:01:39',8,1,NULL,NULL,NULL,NULL,NULL,'Chennai','Tamil Nadu','Hyderabad','Telangana','Electronics','20ft Container',1,10.00,'TON','2026-03-02','Low',1,'Closed',NULL,NULL,1,NULL,'2026-02-27 10:01:39','2026-02-28 10:01:39',NULL),(14,'LD00014','2026-01-24 10:01:39',8,7,NULL,NULL,NULL,NULL,NULL,'Surat','Gujarat','Chennai','Tamil Nadu','Fabric rolls','32ft SXL',1,14.00,'TON','2026-01-27','Urgent',1,'Closed',NULL,NULL,1,NULL,'2026-01-24 10:01:39','2026-01-25 10:01:39',NULL),(15,'LD00015','2026-04-01 10:01:39',4,2,NULL,NULL,NULL,NULL,NULL,'Chennai','Tamil Nadu','Hyderabad','Telangana','Electronics','20ft Container',1,10.00,'TON','2026-04-04','Low',1,'Sent to Client',NULL,NULL,1,NULL,'2026-04-01 10:01:39','2026-04-02 10:01:39',NULL),(16,'LD00016','2026-04-14 10:01:39',6,7,NULL,NULL,NULL,NULL,NULL,'Ludhiana','Punjab','Mumbai','Maharashtra','Knitwear','32ft MXL',1,17.00,'TON','2026-04-17','Urgent',1,'Sent to Client',NULL,NULL,1,NULL,'2026-04-14 10:01:39','2026-04-15 10:01:39',NULL),(17,'LD00017','2025-12-03 10:01:39',2,3,NULL,NULL,NULL,NULL,NULL,'Hyderabad','Telangana','Delhi','Delhi','Pharma','40ft Container',1,8.00,'TON','2025-12-06','Low',1,'Sent to Client',NULL,NULL,1,NULL,'2025-12-03 10:01:39','2025-12-04 10:01:39',NULL),(18,'LD00018','2026-04-17 10:01:39',8,6,NULL,NULL,NULL,NULL,NULL,'Bangalore','Karnataka','Kochi','Kerala','Auto parts','22ft Open Body',1,12.00,'TON','2026-04-20','Low',1,'Negotiation',NULL,NULL,1,NULL,'2026-04-17 10:01:39','2026-04-18 10:01:39',NULL),(19,'LD00019','2026-02-08 10:01:39',1,2,NULL,NULL,NULL,NULL,NULL,'Pune','Maharashtra','Delhi','Delhi','Textiles','32ft SXL',1,15.00,'TON','2026-02-11','Normal',1,'Negotiation',NULL,NULL,1,NULL,'2026-02-08 10:01:39','2026-02-09 10:01:39',NULL),(20,'LD00020','2025-12-11 10:01:39',6,4,NULL,NULL,NULL,NULL,NULL,'Pune','Maharashtra','Delhi','Delhi','Textiles','32ft SXL',1,15.00,'TON','2025-12-14','Normal',1,'Quote Received',NULL,NULL,1,NULL,'2025-12-11 10:01:39','2025-12-12 10:01:39',NULL),(21,'LD00021','2025-12-02 10:01:39',6,1,NULL,NULL,NULL,NULL,NULL,'Jaipur','Rajasthan','Mumbai','Maharashtra','Marble slabs','32ft SXL',1,22.00,'TON','2025-12-05','Low',1,'Quote Received',NULL,NULL,1,NULL,'2025-12-02 10:01:39','2025-12-03 10:01:39',NULL),(22,'LD00022','2026-04-07 10:01:39',1,1,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra','Chennai','Tamil Nadu','FMCG','32ft SXL',1,18.00,'TON','2026-04-10','Low',1,'Quote Received',NULL,NULL,1,NULL,'2026-04-07 10:01:39','2026-04-08 10:01:39',NULL),(23,'LD00023','2025-12-29 10:01:39',3,8,NULL,NULL,NULL,NULL,NULL,'Ludhiana','Punjab','Mumbai','Maharashtra','Knitwear','32ft MXL',1,17.00,'TON','2026-01-01','Normal',1,'Sent to Purchase',NULL,NULL,1,NULL,'2025-12-29 10:01:39','2025-12-30 10:01:39',NULL),(24,'LD00024','2026-03-06 10:01:39',1,3,NULL,NULL,NULL,NULL,NULL,'Ludhiana','Punjab','Mumbai','Maharashtra','Knitwear','32ft MXL',1,17.00,'TON','2026-03-09','High',1,'Sent to Purchase',NULL,NULL,1,NULL,'2026-03-06 10:01:39','2026-03-07 10:01:39',NULL),(25,'LD00025','2026-03-12 10:01:39',2,4,NULL,NULL,NULL,NULL,NULL,'Pune','Maharashtra','Delhi','Delhi','Textiles','32ft SXL',1,15.00,'TON','2026-03-15','High',1,'Under Review',NULL,NULL,1,NULL,'2026-03-12 10:01:39','2026-03-13 10:01:39',NULL),(26,'LD00026','2026-01-27 10:01:39',2,2,NULL,NULL,NULL,NULL,NULL,'Mumbai','Maharashtra','Chennai','Tamil Nadu','FMCG','32ft SXL',1,18.00,'TON','2026-01-30','Normal',1,'Under Review',NULL,NULL,1,NULL,'2026-01-27 10:01:39','2026-01-28 10:01:39',NULL),(27,'LD00027','2026-04-16 10:01:39',6,1,NULL,NULL,NULL,NULL,NULL,'Hyderabad','Telangana','Delhi','Delhi','Pharma','40ft Container',1,8.00,'TON','2026-04-19','Normal',1,'New',NULL,NULL,1,NULL,'2026-04-16 10:01:39','2026-04-17 10:01:39',NULL),(28,'LD00028','2026-03-21 10:01:39',8,2,NULL,NULL,NULL,NULL,NULL,'Jaipur','Rajasthan','Mumbai','Maharashtra','Marble slabs','32ft SXL',1,22.00,'TON','2026-03-24','High',1,'New',NULL,NULL,1,NULL,'2026-03-21 10:01:39','2026-03-22 10:01:39',NULL),(29,'LD00029','2025-12-23 10:01:39',6,5,NULL,NULL,NULL,NULL,NULL,'Delhi','Delhi','Kolkata','West Bengal','Machinery','32ft MXL',1,20.00,'TON','2025-12-26','Urgent',1,'New',NULL,NULL,1,NULL,'2025-12-23 10:01:39','2025-12-24 10:01:39',NULL),(30,'LD00030','2026-01-18 10:01:39',4,8,NULL,NULL,NULL,NULL,NULL,'Delhi','Delhi','Kolkata','West Bengal','Machinery','32ft MXL',1,20.00,'TON','2026-01-21','Normal',1,'New',NULL,NULL,1,NULL,'2026-01-18 10:01:39','2026-01-19 10:01:39',NULL);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loading_slots`
--

DROP TABLE IF EXISTS `loading_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loading_slots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL,
  `plant_name` varchar(200) NOT NULL,
  `slot_date` date NOT NULL,
  `slot_window_start` time DEFAULT NULL,
  `slot_window_end` time DEFAULT NULL,
  `gate_pass_no` varchar(60) DEFAULT NULL,
  `status` enum('Requested','Confirmed','Cancelled','Used','Missed') NOT NULL DEFAULT 'Requested',
  `requested_at` datetime DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `loading_slots_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loading_slots`
--

LOCK TABLES `loading_slots` WRITE;
/*!40000 ALTER TABLE `loading_slots` DISABLE KEYS */;
/*!40000 ALTER TABLE `loading_slots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026-04-21-000001','App\\Database\\Migrations\\CreateAuthTables','default','App',1776755500,1),(2,'2026-04-21-000002','App\\Database\\Migrations\\CreateClientAndLeadTables','default','App',1776755500,1),(3,'2026-04-21-000003','App\\Database\\Migrations\\CreateVendorTables','default','App',1776755501,1),(4,'2026-04-21-000004','App\\Database\\Migrations\\CreateRfqAndQuotationTables','default','App',1776755501,1),(5,'2026-04-21-000005','App\\Database\\Migrations\\CreateBookingAndTripTables','default','App',1776755501,1),(6,'2026-04-21-000006','App\\Database\\Migrations\\CreateGpsTables','default','App',1776755501,1),(7,'2026-04-21-000007','App\\Database\\Migrations\\CreateFinanceTables','default','App',1776755501,1),(8,'2026-04-21-000008','App\\Database\\Migrations\\CreateDocumentsTable','default','App',1776755501,1),(9,'2026-04-21-000009','App\\Database\\Migrations\\CreateWhatsappTables','default','App',1776755501,1),(10,'2026-04-21-000010','App\\Database\\Migrations\\CreateSystemTables','default','App',1776755502,1),(11,'2026-04-21-000011','App\\Database\\Migrations\\AddDispatchPackFields','default','App',1776763659,2),(12,'2026-04-21-000012','App\\Database\\Migrations\\CreateTripExpenseTables','default','App',1776924113,3),(13,'2026-04-21-000013','App\\Database\\Migrations\\AddEwbFields','default','App',1776924113,3),(14,'2026-04-21-000014','App\\Database\\Migrations\\CreateClientPortalTables','default','App',1777616574,4),(15,'2026-04-21-000015','App\\Database\\Migrations\\AddAccountManagerAndVehicleCount','default','App',1777621157,5),(16,'2026-04-21-000016','App\\Database\\Migrations\\CreateRecordComments','default','App',1777621157,5),(17,'2026-04-21-000017','App\\Database\\Migrations\\CreateEmailTables','default','App',1777622359,6),(18,'2026-04-21-000018','App\\Database\\Migrations\\HardeningTables','default','App',1777625825,7),(19,'2026-04-21-000019','App\\Database\\Migrations\\CreateSuperAdminTables','default','App',1777628884,8),(20,'2026-04-21-000020','App\\Database\\Migrations\\AddGpsSourcesAndDriverTracking','default','App',1777632021,9),(21,'2026-04-21-000021','App\\Database\\Migrations\\AddIndianLogisticsFeatures','default','App',1777633632,10),(22,'2026-04-21-000022','App\\Database\\Migrations\\AddAdvancedLogisticsModules','default','App',1777634636,11),(23,'2026-04-21-000023','App\\Database\\Migrations\\AddVendorContactsAndVehicleGps','default','App',1777635036,12),(24,'2026-04-21-000024','App\\Database\\Migrations\\AddSupportAndHelpTables','default','App',1777638299,13),(25,'2026-05-12-000001','App\\Database\\Migrations\\AddDriverMessages','default','App',1778561588,14),(26,'2026-05-12-000002','App\\Database\\Migrations\\AddWorkflowEmailTemplates','default','App',1778564827,15),(27,'2026-05-12-000003','App\\Database\\Migrations\\AddRfqVendorQuoteToken','default','App',1778564851,16),(28,'2026-05-12-000004','App\\Database\\Migrations\\AddAlertRules','default','App',1778565488,17);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_key` varchar(80) NOT NULL,
  `action_key` varchar(40) NOT NULL,
  `label` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_key_action_key` (`module_key`,`action_key`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard','access','Dashboard — Access',1),(2,'users','access','Users — Access',1),(3,'roles','access','Roles & Permissions — Access',1),(4,'clients','access','Clients — Access',1),(5,'vendors','access','Vendors — Access',1),(6,'drivers','access','Drivers — Access',1),(7,'vehicles','access','Vehicles — Access',1),(8,'lead_sources','access','Lead Sources — Access',1),(9,'leads','access','Leads — Access',1),(10,'rfq','access','RFQ / Purchase — Access',1),(11,'quotations','access','Quotations — Access',1),(12,'bookings','access','Bookings — Access',1),(13,'trips','access','Trips / Operations — Access',1),(14,'gps','access','GPS Tracking — Access',1),(15,'invoices','access','Invoices / Billing — Access',1),(16,'receipts','access','Client Receipts — Access',1),(17,'vendor_bills','access','Vendor Bills — Access',1),(18,'vendor_payments','access','Vendor Payments — Access',1),(19,'documents','access','Documents — Access',1),(20,'whatsapp','access','WhatsApp — Access',1),(21,'reports','access','Reports & Dashboards — Access',1),(22,'settings','access','Settings — Access',1),(23,'audit_logs','access','Audit Logs — Access',1),(24,'client_portal','access','Client Portal Management — Access',1),(25,'email','access','Email Templates / Logs — Access',1),(26,'comments','access','Internal Comments — Access',1),(27,'rate_contracts','access','Rate Contracts — Access',1),(28,'tds_certificates','access','TDS Certificates (Form 16A) — Access',1),(29,'vendor_deposits','access','Vendor Security Deposits — Access',1),(30,'support','access','Support Tickets — Access',1),(31,'help','access','Help &amp; Documentation — Access',1);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_comparison_logs`
--

DROP TABLE IF EXISTS `quotation_comparison_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_comparison_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL,
  `selected_vendor_id` int(10) unsigned DEFAULT NULL,
  `logic_notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rfq_id` (`rfq_id`),
  CONSTRAINT `quotation_comparison_logs_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfq_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_comparison_logs`
--

LOCK TABLES `quotation_comparison_logs` WRITE;
/*!40000 ALTER TABLE `quotation_comparison_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotation_comparison_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL,
  `vendor_id` int(10) unsigned NOT NULL,
  `quote_amount` decimal(12,2) NOT NULL,
  `availability_notes` varchar(255) DEFAULT NULL,
  `transit_days` int(11) DEFAULT NULL,
  `quote_valid_till` date DEFAULT NULL,
  `response_source` enum('whatsapp','manual','email','phone') NOT NULL DEFAULT 'manual',
  `is_shortlisted` tinyint(1) NOT NULL DEFAULT 0,
  `is_final_selected` tinyint(1) NOT NULL DEFAULT 0,
  `response_time_minutes` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotations_vendor_id_foreign` (`vendor_id`),
  KEY `rfq_id_vendor_id` (`rfq_id`,`vendor_id`),
  CONSTRAINT `quotations_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfq_master` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quotations_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
INSERT INTO `quotations` VALUES (1,1,3,42500.00,NULL,3,NULL,'email',1,1,64,'Smoke test via curl','2025-12-06 10:01:39','2026-05-12 05:49:31'),(2,1,4,41404.00,'Available',4,'2025-12-10','whatsapp',0,0,135,'Demo quote','2025-12-06 10:01:39','2025-12-06 10:01:39'),(3,1,9,41446.00,'Available',3,'2025-12-10','whatsapp',0,0,155,'Demo quote','2025-12-06 10:01:39','2025-12-06 10:01:39'),(4,2,1,45465.00,'Available',6,'2026-03-31','whatsapp',0,0,27,'Demo quote','2026-03-27 10:01:39','2026-03-27 10:01:39'),(5,2,3,49252.00,'Available',6,'2026-03-31','whatsapp',1,1,107,'Demo quote','2026-03-27 10:01:39','2026-03-27 10:01:39'),(6,2,7,46262.00,'Available',4,'2026-03-31','whatsapp',0,0,226,'Demo quote','2026-03-27 10:01:39','2026-03-27 10:01:39'),(7,3,3,44597.00,'Available',3,'2026-02-05','whatsapp',0,0,215,'Demo quote','2026-02-01 10:01:39','2026-02-01 10:01:39'),(8,3,5,44133.00,'Available',3,'2026-02-05','whatsapp',1,1,123,'Demo quote','2026-02-01 10:01:39','2026-02-01 10:01:39'),(9,3,7,41579.00,'Available',6,'2026-02-05','whatsapp',0,0,150,'Demo quote','2026-02-01 10:01:39','2026-02-01 10:01:39'),(10,4,6,52479.00,'Available',3,'2026-03-28','whatsapp',0,0,88,'Demo quote','2026-03-24 10:01:39','2026-03-24 10:01:39'),(11,4,7,53933.00,'Available',5,'2026-03-28','whatsapp',0,0,231,'Demo quote','2026-03-24 10:01:39','2026-03-24 10:01:39'),(12,4,10,50854.00,'Available',3,'2026-03-28','whatsapp',1,1,133,'Demo quote','2026-03-24 10:01:39','2026-03-24 10:01:39'),(13,5,5,30662.00,'Available',3,'2026-03-31','whatsapp',0,0,80,'Demo quote','2026-03-27 10:01:39','2026-03-27 10:01:39'),(14,5,8,32354.00,'Available',3,'2026-03-31','whatsapp',0,0,69,'Demo quote','2026-03-27 10:01:39','2026-03-27 10:01:39'),(15,5,10,30729.00,'Available',3,'2026-03-31','whatsapp',1,1,221,'Demo quote','2026-03-27 10:01:39','2026-03-27 10:01:39'),(16,6,2,42650.00,'Available',4,'2025-12-17','whatsapp',1,1,231,'Demo quote','2025-12-13 10:01:39','2025-12-13 10:01:39'),(17,6,6,44206.00,'Available',5,'2025-12-17','whatsapp',0,0,101,'Demo quote','2025-12-13 10:01:39','2025-12-13 10:01:39'),(18,6,9,43387.00,'Available',5,'2025-12-17','whatsapp',0,0,237,'Demo quote','2025-12-13 10:01:39','2025-12-13 10:01:39'),(19,7,5,31770.00,'Available',6,'2025-12-08','whatsapp',1,1,114,'Demo quote','2025-12-04 10:01:39','2025-12-04 10:01:39'),(20,7,7,27835.00,'Available',2,'2025-12-08','whatsapp',0,0,205,'Demo quote','2025-12-04 10:01:39','2025-12-04 10:01:39'),(21,7,10,30941.00,'Available',3,'2025-12-08','whatsapp',0,0,97,'Demo quote','2025-12-04 10:01:39','2025-12-04 10:01:39'),(22,8,1,47636.00,'Available',6,'2026-04-03','whatsapp',0,0,233,'Demo quote','2026-03-30 10:01:39','2026-03-30 10:01:39'),(23,8,4,46989.00,'Available',3,'2026-04-03','whatsapp',1,1,194,'Demo quote','2026-03-30 10:01:39','2026-03-30 10:01:39'),(24,8,7,47435.00,'Available',6,'2026-04-03','whatsapp',0,0,199,'Demo quote','2026-03-30 10:01:39','2026-03-30 10:01:39'),(25,9,1,38731.00,'Available',4,'2026-04-06','whatsapp',1,1,38,'Demo quote','2026-04-02 10:01:39','2026-04-02 10:01:39'),(26,9,6,38401.00,'Available',2,'2026-04-06','whatsapp',0,0,55,'Demo quote','2026-04-02 10:01:39','2026-04-02 10:01:39'),(27,9,7,34127.00,'Available',2,'2026-04-06','whatsapp',0,0,237,'Demo quote','2026-04-02 10:01:39','2026-04-02 10:01:39'),(28,10,1,46624.00,'Available',3,'2026-04-19','whatsapp',0,0,99,'Demo quote','2026-04-15 10:01:39','2026-04-15 10:01:39'),(29,10,5,49203.00,'Available',2,'2026-04-19','whatsapp',1,1,180,'Demo quote','2026-04-15 10:01:39','2026-04-15 10:01:39'),(30,10,9,44269.00,'Available',5,'2026-04-19','whatsapp',0,0,80,'Demo quote','2026-04-15 10:01:39','2026-04-15 10:01:39'),(31,11,8,30266.00,'Available',6,'2025-12-08','whatsapp',1,1,103,'Demo quote','2025-12-04 10:01:39','2025-12-04 10:01:39'),(32,11,9,35173.00,'Available',5,'2025-12-08','whatsapp',0,0,70,'Demo quote','2025-12-04 10:01:39','2025-12-04 10:01:39'),(33,11,10,32257.00,'Available',3,'2025-12-08','whatsapp',0,0,26,'Demo quote','2025-12-04 10:01:39','2025-12-04 10:01:39'),(34,12,8,39519.00,'Available',3,'2026-04-22','whatsapp',0,0,96,'Demo quote','2026-04-18 10:01:39','2026-04-18 10:01:39'),(35,12,9,38600.00,'Available',5,'2026-04-22','whatsapp',1,1,158,'Demo quote','2026-04-18 10:01:39','2026-04-18 10:01:39'),(36,12,10,36047.00,'Available',3,'2026-04-22','whatsapp',0,0,156,'Demo quote','2026-04-18 10:01:39','2026-04-18 10:01:39'),(37,13,1,44698.00,'Available',2,'2026-02-13','whatsapp',1,1,159,'Demo quote','2026-02-09 10:01:40','2026-02-09 10:01:40'),(38,13,4,43905.00,'Available',3,'2026-02-13','whatsapp',0,0,231,'Demo quote','2026-02-09 10:01:40','2026-02-09 10:01:40'),(39,13,8,44117.00,'Available',5,'2026-02-13','whatsapp',0,0,132,'Demo quote','2026-02-09 10:01:40','2026-02-09 10:01:40'),(40,14,2,44686.00,'Available',6,'2025-12-16','whatsapp',1,1,157,'Demo quote','2025-12-12 10:01:40','2025-12-12 10:01:40'),(41,14,6,43654.00,'Available',5,'2025-12-16','whatsapp',0,0,108,'Demo quote','2025-12-12 10:01:40','2025-12-12 10:01:40'),(42,14,9,43321.00,'Available',2,'2025-12-16','whatsapp',0,0,84,'Demo quote','2025-12-12 10:01:40','2025-12-12 10:01:40'),(43,15,2,54641.00,'Available',2,'2025-12-07','whatsapp',1,1,25,'Demo quote','2025-12-03 10:01:40','2025-12-03 10:01:40'),(44,15,4,54497.00,'Available',2,'2025-12-07','whatsapp',0,0,125,'Demo quote','2025-12-03 10:01:40','2025-12-03 10:01:40'),(45,15,9,53797.00,'Available',3,'2025-12-07','whatsapp',0,0,43,'Demo quote','2025-12-03 10:01:40','2025-12-03 10:01:40'),(46,16,7,46779.00,'Available',5,'2026-04-12','whatsapp',0,0,17,'Demo quote','2026-04-08 10:01:40','2026-04-08 10:01:40'),(47,16,8,48453.00,'Available',5,'2026-04-12','whatsapp',1,1,169,'Demo quote','2026-04-08 10:01:40','2026-04-08 10:01:40'),(48,16,9,49213.00,'Available',4,'2026-04-12','whatsapp',0,0,99,'Demo quote','2026-04-08 10:01:40','2026-04-08 10:01:40'),(49,17,1,43845.00,'Available',4,'2026-01-03','whatsapp',1,1,104,'Demo quote','2025-12-30 10:01:40','2025-12-30 10:01:40'),(50,17,4,49239.00,'Available',2,'2026-01-03','whatsapp',0,0,208,'Demo quote','2025-12-30 10:01:40','2025-12-30 10:01:40'),(51,17,9,46997.00,'Available',5,'2026-01-03','whatsapp',0,0,125,'Demo quote','2025-12-30 10:01:40','2025-12-30 10:01:40'),(52,18,3,45112.00,'Available',6,'2026-03-11','whatsapp',0,0,187,'Demo quote','2026-03-07 10:01:40','2026-03-07 10:01:40'),(53,18,4,48722.00,'Available',2,'2026-03-11','whatsapp',1,1,225,'Demo quote','2026-03-07 10:01:40','2026-03-07 10:01:40'),(54,18,7,43555.00,'Available',5,'2026-03-11','whatsapp',0,0,139,'Demo quote','2026-03-07 10:01:40','2026-03-07 10:01:40'),(55,19,2,35434.00,'Available',5,'2026-03-04','whatsapp',1,1,87,'Demo quote','2026-02-28 10:01:40','2026-02-28 10:01:40'),(56,19,3,37470.00,'Available',6,'2026-03-04','whatsapp',0,0,199,'Demo quote','2026-02-28 10:01:40','2026-02-28 10:01:40'),(57,19,6,35209.00,'Available',2,'2026-03-04','whatsapp',0,0,168,'Demo quote','2026-02-28 10:01:40','2026-02-28 10:01:40'),(58,20,3,44084.00,'Available',3,'2026-01-29','whatsapp',1,1,229,'Demo quote','2026-01-25 10:01:40','2026-01-25 10:01:40'),(59,20,7,41114.00,'Available',5,'2026-01-29','whatsapp',0,0,38,'Demo quote','2026-01-25 10:01:40','2026-01-25 10:01:40'),(60,20,8,40159.00,'Available',6,'2026-01-29','whatsapp',0,0,122,'Demo quote','2026-01-25 10:01:40','2026-01-25 10:01:40');
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_contract_lanes`
--

DROP TABLE IF EXISTS `rate_contract_lanes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_contract_lanes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` int(10) unsigned NOT NULL,
  `pickup_city` varchar(80) NOT NULL,
  `drop_city` varchar(80) NOT NULL,
  `vehicle_type` varchar(80) DEFAULT NULL,
  `rate_inr` decimal(12,2) NOT NULL,
  `min_load_tons` decimal(8,2) DEFAULT NULL,
  `free_loading_hrs` tinyint(3) unsigned DEFAULT NULL,
  `free_unloading_hrs` tinyint(3) unsigned DEFAULT NULL,
  `detention_per_hour` decimal(8,2) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_id_pickup_city_drop_city` (`contract_id`,`pickup_city`,`drop_city`),
  CONSTRAINT `rate_contract_lanes_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `rate_contracts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_contract_lanes`
--

LOCK TABLES `rate_contract_lanes` WRITE;
/*!40000 ALTER TABLE `rate_contract_lanes` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_contract_lanes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_contracts`
--

DROP TABLE IF EXISTS `rate_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rate_contracts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_no` varchar(30) NOT NULL,
  `client_id` int(10) unsigned NOT NULL,
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `status` enum('Active','Expired','Suspended','Draft') NOT NULL DEFAULT 'Active',
  `tds_rate` decimal(5,2) DEFAULT NULL,
  `gst_treatment` enum('rcm','fcm5','fcm12') NOT NULL DEFAULT 'fcm5',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_no` (`contract_no`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `rate_contracts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rate_contracts`
--

LOCK TABLES `rate_contracts` WRITE;
/*!40000 ALTER TABLE `rate_contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `receipts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned DEFAULT NULL,
  `receipt_date` date NOT NULL,
  `payment_mode` varchar(40) DEFAULT NULL,
  `amount_received` decimal(12,2) NOT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `idx_rct_date` (`receipt_date`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipts`
--

LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
INSERT INTO `receipts` VALUES (1,1,1,'2025-12-15','NEFT',50946.00,'TXN-30F9C7','Final payment',1,'2025-12-15 10:01:40'),(2,3,2,'2026-04-05','UPI',58958.00,'TXN-A89A6E','Final payment',1,'2026-04-05 10:01:40'),(3,3,3,'2026-02-10','Bank Transfer',50978.00,'TXN-E3712A','Final payment',1,'2026-02-10 10:01:40'),(4,7,4,'2026-04-02','Bank Transfer',57666.00,'TXN-E37DE8','Final payment',1,'2026-04-02 10:01:40'),(5,4,5,'2026-04-05','RTGS',35165.00,'TXN-AAF31B','Final payment',1,'2026-04-05 10:01:40'),(6,2,6,'2025-12-22','RTGS',25972.00,'TXN-E3A8E8','Part payment',1,'2025-12-22 10:01:40'),(7,1,7,'2025-12-13','RTGS',19346.50,'TXN-BB64E8','Part payment',1,'2025-12-13 10:01:40');
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `record_comments`
--

DROP TABLE IF EXISTS `record_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `record_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `record_type` varchar(30) NOT NULL,
  `record_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `body` text NOT NULL,
  `mentions_json` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `record_type_record_id` (`record_type`,`record_id`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `record_comments`
--

LOCK TABLES `record_comments` WRITE;
/*!40000 ALTER TABLE `record_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `record_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfq_master`
--

DROP TABLE IF EXISTS `rfq_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rfq_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_no` varchar(30) NOT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `masked_reference` varchar(40) NOT NULL,
  `pickup_city` varchar(80) DEFAULT NULL,
  `drop_city` varchar(80) DEFAULT NULL,
  `vehicle_type` varchar(80) DEFAULT NULL,
  `material_category` varchar(120) DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `weight_unit` varchar(10) NOT NULL DEFAULT 'TON',
  `loading_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Open',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfq_no` (`rfq_no`),
  UNIQUE KEY `masked_reference` (`masked_reference`),
  KEY `lead_id` (`lead_id`),
  KEY `status` (`status`),
  CONSTRAINT `rfq_master_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfq_master`
--

LOCK TABLES `rfq_master` WRITE;
/*!40000 ALTER TABLE `rfq_master` DISABLE KEYS */;
INSERT INTO `rfq_master` VALUES (1,'RFQ00001',1,'REF-F61F0003','Surat','Chennai','32ft SXL','Fabric rolls',14.00,'TON','2025-12-08','Awarded',1,'2025-12-05 10:01:39','2025-12-06 10:01:39'),(2,'RFQ00002',2,'REF-2A2B228E','Ludhiana','Mumbai','32ft MXL','Knitwear',17.00,'TON','2026-03-29','Awarded',1,'2026-03-26 10:01:39','2026-03-27 10:01:39'),(3,'RFQ00003',3,'REF-14280C29','Pune','Delhi','32ft SXL','Textiles',15.00,'TON','2026-02-03','Awarded',1,'2026-01-31 10:01:39','2026-02-01 10:01:39'),(4,'RFQ00004',4,'REF-1EEE6081','Delhi','Kolkata','32ft MXL','Machinery',20.00,'TON','2026-03-26','Awarded',1,'2026-03-23 10:01:39','2026-03-24 10:01:39'),(5,'RFQ00005',5,'REF-C0418191','Hyderabad','Delhi','40ft Container','Pharma',8.00,'TON','2026-03-29','Awarded',1,'2026-03-26 10:01:39','2026-03-27 10:01:39'),(6,'RFQ00006',6,'REF-C95B9FF2','Surat','Chennai','32ft SXL','Fabric rolls',14.00,'TON','2025-12-15','Awarded',1,'2025-12-12 10:01:39','2025-12-13 10:01:39'),(7,'RFQ00007',7,'REF-AF0286B3','Ahmedabad','Mumbai','14ft Closed Body','Packaged food',6.00,'TON','2025-12-06','Awarded',1,'2025-12-03 10:01:39','2025-12-04 10:01:39'),(8,'RFQ00008',8,'REF-ED2F2FF7','Mumbai','Chennai','32ft SXL','FMCG',18.00,'TON','2026-04-01','Awarded',1,'2026-03-29 10:01:39','2026-03-30 10:01:39'),(9,'RFQ00009',15,'REF-2AE5E010','Chennai','Hyderabad','20ft Container','Electronics',10.00,'TON','2026-04-04','Awarded',1,'2026-04-01 10:01:39','2026-04-02 10:01:39'),(10,'RFQ00010',16,'REF-08D8AD33','Ludhiana','Mumbai','32ft MXL','Knitwear',17.00,'TON','2026-04-17','Awarded',1,'2026-04-14 10:01:39','2026-04-15 10:01:39'),(11,'RFQ00011',17,'REF-F4338443','Hyderabad','Delhi','40ft Container','Pharma',8.00,'TON','2025-12-06','Awarded',1,'2025-12-03 10:01:39','2025-12-04 10:01:39'),(12,'RFQ00012',18,'REF-520F7C9E','Bangalore','Kochi','22ft Open Body','Auto parts',12.00,'TON','2026-04-20','Awarded',1,'2026-04-17 10:01:39','2026-04-18 10:01:39'),(13,'RFQ00013',19,'REF-9F0415DE','Pune','Delhi','32ft SXL','Textiles',15.00,'TON','2026-02-11','Awarded',1,'2026-02-08 10:01:40','2026-02-09 10:01:40'),(14,'RFQ00014',20,'REF-9B192E25','Pune','Delhi','32ft SXL','Textiles',15.00,'TON','2025-12-14','Awarded',1,'2025-12-11 10:01:40','2025-12-12 10:01:40'),(15,'RFQ00015',21,'REF-D5F1A93A','Jaipur','Mumbai','32ft SXL','Marble slabs',22.00,'TON','2025-12-05','Awarded',1,'2025-12-02 10:01:40','2025-12-03 10:01:40'),(16,'RFQ00016',22,'REF-1559A11B','Mumbai','Chennai','32ft SXL','FMCG',18.00,'TON','2026-04-10','Awarded',1,'2026-04-07 10:01:40','2026-04-08 10:01:40'),(17,'RFQ00017',23,'REF-7A0DAFBF','Ludhiana','Mumbai','32ft MXL','Knitwear',17.00,'TON','2026-01-01','Awarded',1,'2025-12-29 10:01:40','2025-12-30 10:01:40'),(18,'RFQ00018',24,'REF-61AA31F6','Ludhiana','Mumbai','32ft MXL','Knitwear',17.00,'TON','2026-03-09','Awarded',1,'2026-03-06 10:01:40','2026-03-07 10:01:40'),(19,'RFQ00019',13,'REF-58CEA57B','Chennai','Hyderabad','20ft Container','Electronics',10.00,'TON','2026-03-02','Awarded',1,'2026-02-27 10:01:40','2026-02-28 10:01:40'),(20,'RFQ00020',14,'REF-E8E2FCE7','Surat','Chennai','32ft SXL','Fabric rolls',14.00,'TON','2026-01-27','Awarded',1,'2026-01-24 10:01:40','2026-01-25 10:01:40');
/*!40000 ALTER TABLE `rfq_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rfq_vendors`
--

DROP TABLE IF EXISTS `rfq_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rfq_vendors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rfq_id` int(10) unsigned NOT NULL,
  `vendor_id` int(10) unsigned NOT NULL,
  `whatsapp_message_id` varchar(150) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `reminder_count` int(11) NOT NULL DEFAULT 0,
  `last_reminder_at` datetime DEFAULT NULL,
  `response_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `quote_token` varchar(64) DEFAULT NULL,
  `quote_token_expires_at` datetime DEFAULT NULL,
  `quote_submitted_at` datetime DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rfq_id_vendor_id` (`rfq_id`,`vendor_id`),
  KEY `rfq_vendors_vendor_id_foreign` (`vendor_id`),
  KEY `idx_rfq_vendor_quote_token` (`quote_token`),
  CONSTRAINT `rfq_vendors_rfq_id_foreign` FOREIGN KEY (`rfq_id`) REFERENCES `rfq_master` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rfq_vendors_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rfq_vendors`
--

LOCK TABLES `rfq_vendors` WRITE;
/*!40000 ALTER TABLE `rfq_vendors` DISABLE KEYS */;
INSERT INTO `rfq_vendors` VALUES (1,1,3,'wamid.DEMO000010','2025-12-05 10:01:39',0,NULL,'Quoted','55e98e5b394d5403de7861cf3d6cadf9c5bb661930ac47a7b4b39854e60e0351','2026-05-26 11:19:31','2026-05-12 05:49:31','2026-05-12 05:49:31'),(2,1,4,'wamid.DEMO000011','2025-12-05 10:01:39',0,NULL,'Received','b68501bf7423fc7d990ba57b8073cf1613b5381a8f9d5053fc3af4b84419c944','2026-05-26 05:59:56',NULL,'2025-12-06 10:01:39'),(3,1,9,'wamid.DEMO000012','2025-12-05 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-06 10:01:39'),(4,2,1,'wamid.DEMO000020','2026-03-26 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-27 10:01:39'),(5,2,3,'wamid.DEMO000021','2026-03-26 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-27 10:01:39'),(6,2,7,'wamid.DEMO000022','2026-03-26 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-27 10:01:39'),(7,3,3,'wamid.DEMO000030','2026-01-31 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-02-01 10:01:39'),(8,3,5,'wamid.DEMO000031','2026-01-31 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-02-01 10:01:39'),(9,3,7,'wamid.DEMO000032','2026-01-31 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-02-01 10:01:39'),(10,4,6,'wamid.DEMO000040','2026-03-23 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-24 10:01:39'),(11,4,7,'wamid.DEMO000041','2026-03-23 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-24 10:01:39'),(12,4,10,'wamid.DEMO000042','2026-03-23 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-24 10:01:39'),(13,5,5,'wamid.DEMO000050','2026-03-26 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-27 10:01:39'),(14,5,8,'wamid.DEMO000051','2026-03-26 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-27 10:01:39'),(15,5,10,'wamid.DEMO000052','2026-03-26 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-27 10:01:39'),(16,6,2,'wamid.DEMO000060','2025-12-12 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-13 10:01:39'),(17,6,6,'wamid.DEMO000061','2025-12-12 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-13 10:01:39'),(18,6,9,'wamid.DEMO000062','2025-12-12 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-13 10:01:39'),(19,7,5,'wamid.DEMO000070','2025-12-03 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-04 10:01:39'),(20,7,7,'wamid.DEMO000071','2025-12-03 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-04 10:01:39'),(21,7,10,'wamid.DEMO000072','2025-12-03 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-04 10:01:39'),(22,8,1,'wamid.DEMO000080','2026-03-29 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-30 10:01:39'),(23,8,4,'wamid.DEMO000081','2026-03-29 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-30 10:01:39'),(24,8,7,'wamid.DEMO000082','2026-03-29 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-03-30 10:01:39'),(25,9,1,'wamid.DEMO000090','2026-04-01 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-02 10:01:39'),(26,9,6,'wamid.DEMO000091','2026-04-01 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-02 10:01:39'),(27,9,7,'wamid.DEMO000092','2026-04-01 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-02 10:01:39'),(28,10,1,'wamid.DEMO000100','2026-04-14 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-15 10:01:39'),(29,10,5,'wamid.DEMO000101','2026-04-14 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-15 10:01:39'),(30,10,9,'wamid.DEMO000102','2026-04-14 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-15 10:01:39'),(31,11,8,'wamid.DEMO000110','2025-12-03 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-04 10:01:39'),(32,11,9,'wamid.DEMO000111','2025-12-03 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-04 10:01:39'),(33,11,10,'wamid.DEMO000112','2025-12-03 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2025-12-04 10:01:39'),(34,12,8,'wamid.DEMO000120','2026-04-17 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-18 10:01:39'),(35,12,9,'wamid.DEMO000121','2026-04-17 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-18 10:01:39'),(36,12,10,'wamid.DEMO000122','2026-04-17 10:01:39',0,NULL,'Received',NULL,NULL,NULL,'2026-04-18 10:01:39'),(37,13,1,'wamid.DEMO000130','2026-02-08 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-02-09 10:01:40'),(38,13,4,'wamid.DEMO000131','2026-02-08 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-02-09 10:01:40'),(39,13,8,'wamid.DEMO000132','2026-02-08 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-02-09 10:01:40'),(40,14,2,'wamid.DEMO000140','2025-12-11 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-12 10:01:40'),(41,14,6,'wamid.DEMO000141','2025-12-11 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-12 10:01:40'),(42,14,9,'wamid.DEMO000142','2025-12-11 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-12 10:01:40'),(43,15,2,'wamid.DEMO000150','2025-12-02 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-03 10:01:40'),(44,15,4,'wamid.DEMO000151','2025-12-02 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-03 10:01:40'),(45,15,9,'wamid.DEMO000152','2025-12-02 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-03 10:01:40'),(46,16,7,'wamid.DEMO000160','2026-04-07 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-04-08 10:01:40'),(47,16,8,'wamid.DEMO000161','2026-04-07 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-04-08 10:01:40'),(48,16,9,'wamid.DEMO000162','2026-04-07 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-04-08 10:01:40'),(49,17,1,'wamid.DEMO000170','2025-12-29 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-30 10:01:40'),(50,17,4,'wamid.DEMO000171','2025-12-29 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-30 10:01:40'),(51,17,9,'wamid.DEMO000172','2025-12-29 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2025-12-30 10:01:40'),(52,18,3,'wamid.DEMO000180','2026-03-06 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-03-07 10:01:40'),(53,18,4,'wamid.DEMO000181','2026-03-06 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-03-07 10:01:40'),(54,18,7,'wamid.DEMO000182','2026-03-06 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-03-07 10:01:40'),(55,19,2,'wamid.DEMO000190','2026-02-27 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-02-28 10:01:40'),(56,19,3,'wamid.DEMO000191','2026-02-27 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-02-28 10:01:40'),(57,19,6,'wamid.DEMO000192','2026-02-27 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-02-28 10:01:40'),(58,20,3,'wamid.DEMO000200','2026-01-24 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-01-25 10:01:40'),(59,20,7,'wamid.DEMO000201','2026-01-24 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-01-25 10:01:40'),(60,20,8,'wamid.DEMO000202','2026-01-24 10:01:40',0,NULL,'Received',NULL,NULL,NULL,'2026-01-25 10:01:40');
/*!40000 ALTER TABLE `rfq_vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT 0,
  `can_add` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `can_approve` tinyint(1) NOT NULL DEFAULT 0,
  `can_export` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_id_permission_id` (`role_id`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  CONSTRAINT `role_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1,1,1,1,1,1,1,1),(2,1,2,1,1,1,1,1,1),(3,1,3,1,1,1,1,1,1),(4,1,4,1,1,1,1,1,1),(5,1,5,1,1,1,1,1,1),(6,1,6,1,1,1,1,1,1),(7,1,7,1,1,1,1,1,1),(8,1,8,1,1,1,1,1,1),(9,1,9,1,1,1,1,1,1),(10,1,10,1,1,1,1,1,1),(11,1,11,1,1,1,1,1,1),(12,1,12,1,1,1,1,1,1),(13,1,13,1,1,1,1,1,1),(14,1,14,1,1,1,1,1,1),(15,1,15,1,1,1,1,1,1),(16,1,16,1,1,1,1,1,1),(17,1,17,1,1,1,1,1,1),(18,1,18,1,1,1,1,1,1),(19,1,19,1,1,1,1,1,1),(20,1,20,1,1,1,1,1,1),(21,1,21,1,1,1,1,1,1),(22,1,22,1,1,1,1,1,1),(23,1,23,1,1,1,1,1,1),(24,1,24,1,1,1,1,1,1),(25,1,25,1,1,1,1,1,1),(26,1,26,1,1,1,1,1,1),(27,2,1,1,1,1,0,1,1),(28,2,2,1,1,1,0,1,1),(29,2,3,1,1,1,0,1,1),(30,2,4,1,1,1,0,1,1),(31,2,5,1,1,1,0,1,1),(32,2,6,1,1,1,0,1,1),(33,2,7,1,1,1,0,1,1),(34,2,8,1,1,1,0,1,1),(35,2,9,1,1,1,0,1,1),(36,2,10,1,1,1,0,1,1),(37,2,11,1,1,1,0,1,1),(38,2,12,1,1,1,0,1,1),(39,2,13,1,1,1,0,1,1),(40,2,14,1,1,1,0,1,1),(41,2,15,1,1,1,0,1,1),(42,2,16,1,1,1,0,1,1),(43,2,17,1,1,1,0,1,1),(44,2,18,1,1,1,0,1,1),(45,2,19,1,1,1,0,1,1),(46,2,20,1,1,1,0,1,1),(47,2,21,1,1,1,0,1,1),(48,2,22,1,1,1,0,1,1),(49,2,23,1,1,1,0,1,1),(50,2,26,1,1,1,0,1,1),(51,2,25,1,1,1,0,1,1),(52,2,24,1,1,1,0,1,1),(53,3,1,1,0,0,0,0,0),(54,3,9,1,1,1,0,0,1),(55,3,8,1,0,0,0,0,0),(56,3,4,1,1,1,0,0,1),(57,3,26,1,1,1,0,0,0),(58,3,19,1,1,0,0,0,0),(59,3,20,1,1,0,0,0,0),(60,3,21,1,0,0,0,0,1),(61,4,1,1,0,0,0,0,0),(62,4,9,1,1,1,1,1,1),(63,4,8,1,1,1,1,0,0),(64,4,4,1,1,1,1,0,1),(65,4,10,1,0,0,0,0,1),(66,4,11,1,0,0,0,0,1),(67,4,26,1,1,1,1,0,0),(68,4,19,1,1,1,0,0,0),(69,4,20,1,1,1,1,0,0),(70,4,21,1,0,0,0,0,1),(71,5,1,1,0,0,0,0,0),(72,5,5,1,1,1,0,0,1),(73,5,10,1,1,1,0,0,1),(74,5,11,1,1,1,0,0,1),(75,5,9,1,0,0,0,0,0),(76,5,26,1,1,1,0,0,0),(77,5,20,1,1,0,0,0,0),(78,5,19,1,1,0,0,0,0),(79,5,21,1,0,0,0,0,1),(80,6,1,1,0,0,0,0,0),(81,6,5,1,1,1,1,1,1),(82,6,10,1,1,1,1,1,1),(83,6,11,1,1,1,1,1,1),(84,6,9,1,0,0,0,0,0),(85,6,26,1,1,1,1,0,0),(86,6,20,1,1,1,1,0,0),(87,6,19,1,1,1,1,0,0),(88,6,21,1,0,0,0,0,1),(89,7,1,1,0,0,0,0,0),(90,7,12,1,1,1,0,0,1),(91,7,13,1,1,1,0,0,1),(92,7,6,1,1,1,0,0,0),(93,7,7,1,1,1,0,0,0),(94,7,14,1,0,0,0,0,1),(95,7,19,1,1,0,0,0,0),(96,7,26,1,1,1,0,0,0),(97,7,20,1,1,0,0,0,0),(98,7,21,1,0,0,0,0,1),(99,8,1,1,0,0,0,0,0),(100,8,12,1,1,1,1,1,1),(101,8,13,1,1,1,1,1,1),(102,8,6,1,1,1,1,0,0),(103,8,7,1,1,1,1,0,0),(104,8,14,1,1,1,0,0,1),(105,8,19,1,1,1,1,0,0),(106,8,26,1,1,1,1,0,0),(107,8,20,1,1,1,1,0,0),(108,8,21,1,0,0,0,0,1),(109,9,1,1,0,0,0,0,0),(110,9,15,1,1,1,1,1,1),(111,9,16,1,1,1,1,0,1),(112,9,17,1,1,1,1,1,1),(113,9,18,1,1,1,1,0,1),(114,9,4,1,0,0,0,0,1),(115,9,5,1,0,0,0,0,1),(116,9,12,1,0,0,0,0,1),(117,9,13,1,0,0,0,0,1),(118,9,26,1,1,1,0,0,0),(119,9,19,1,1,0,0,0,0),(120,9,21,1,0,0,0,0,1),(121,9,25,1,0,0,0,0,0),(122,1,27,1,1,1,1,1,1),(123,1,28,1,1,1,1,1,1),(124,1,29,1,1,1,1,1,1),(125,1,30,1,1,1,1,1,1),(126,1,31,1,1,1,1,1,1);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(80) NOT NULL,
  `role_key` varchar(60) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_key` (`role_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Administrator','admin',1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(2,'Management','management',1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(3,'CRM Executive','crm_exec',1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(4,'CRM Manager','crm_mgr',1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(5,'Purchase Executive','pur_exec',1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(6,'Purchase Manager','pur_mgr',1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(7,'Operations Exec','ops_exec',1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(8,'Operations Manager','ops_mgr',1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(9,'Accounts','accounts',1,'2026-04-21 07:12:40','2026-04-21 07:12:40');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(60) NOT NULL DEFAULT 'general',
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `setting_group` (`setting_group`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'company_name','TPT Logistics','company',NULL,'2026-04-21 07:12:40'),(2,'company_gstin','27AAAPL1234C1ZV','company',NULL,'2026-04-21 07:12:40'),(3,'company_pan','','company',NULL,'2026-04-21 07:12:40'),(4,'company_address','','company',NULL,'2026-04-21 07:12:40'),(5,'company_state','Maharashtra','company',NULL,'2026-04-21 07:12:40'),(6,'company_phone','9810000000','company',NULL,'2026-04-21 07:12:40'),(7,'company_email','dispatch@tpt.local','company',NULL,'2026-04-21 07:12:40'),(8,'default_gst_rate','5','billing',NULL,'2026-04-21 07:12:40'),(9,'invoice_prefix','INV','numbering',NULL,'2026-04-21 07:12:40'),(10,'lead_prefix','LD','numbering',NULL,'2026-04-21 07:12:40'),(11,'rfq_prefix','RFQ','numbering',NULL,'2026-04-21 07:12:40'),(12,'booking_prefix','BK','numbering',NULL,'2026-04-21 07:12:40'),(13,'trip_prefix','TR','numbering',NULL,'2026-04-21 07:12:40'),(14,'lr_prefix','LR','numbering',NULL,'2026-04-21 09:27:54'),(15,'support_rating_enabled','1','support',NULL,'2026-05-01 12:24:59'),(16,'email_driver','brevo','email',1,'2026-05-12 06:10:21'),(17,'mail_from_email','ashish@yngmedia.com','email',NULL,'2026-05-12 11:15:14'),(18,'mail_from_name','TPT Logistics','email',NULL,'2026-05-12 11:15:14'),(19,'brevo_api_key','','email',1,'2026-05-12 06:10:22'),(20,'brevo_webhook_secret','20b658415518f28d1d2c3c22f3edcb9f3596a625c68f30de','email',1,'2026-05-12 06:10:22'),(21,'smtp_host','','email',1,'2026-05-12 06:10:22'),(22,'smtp_port','587','email',1,'2026-05-12 06:10:22'),(23,'smtp_user','','email',1,'2026-05-12 06:10:22'),(24,'smtp_pass','','email',1,'2026-05-12 06:10:22'),(25,'smtp_crypto','tls','email',1,'2026-05-12 06:10:22'),(26,'email_track_secret','enc:ZP/dEeUu8NZ0LoTxusDDSaWA9Wq7IPXVDQKU3ziFYU2LhXcSBAIOuFcGOXum4fyyDDEf1jREJvWxzB/pshaX6x82e2vkmDr3v5cmHNq2opMJZt3206adrWETRR761XYnza1gyZ5rPKqq+NWcDVNo3PLEZgoOxpLDaKlFVyH9fdrgyTu49VeZ0SXUPrqPJPX4','email',NULL,'2026-05-12 05:45:55'),(27,'test_email_override','ashishcv@gmail.com','email',NULL,'2026-05-12 11:32:29'),(28,'from_email','ashish@yngmedia.com','email',1,'2026-05-12 06:10:21'),(29,'from_name','TPT Logistics','email',1,'2026-05-12 06:10:21'),(30,'ses_region','us-east-1','email',1,'2026-05-12 06:10:22'),(31,'ses_access_key','','email',1,'2026-05-12 06:10:22'),(32,'ses_secret_key','','email',1,'2026-05-12 06:10:22'),(33,'ses_configuration_set','','email',1,'2026-05-12 06:10:22');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `super_admin_audit`
--

DROP TABLE IF EXISTS `super_admin_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `super_admin_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `action` varchar(80) NOT NULL,
  `target_type` varchar(60) DEFAULT NULL,
  `target_id` bigint(20) unsigned DEFAULT NULL,
  `description` varchar(400) DEFAULT NULL,
  `details_json` mediumtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `super_admin_audit`
--

LOCK TABLES `super_admin_audit` WRITE;
/*!40000 ALTER TABLE `super_admin_audit` DISABLE KEYS */;
/*!40000 ALTER TABLE `super_admin_audit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_ticket_replies`
--

DROP TABLE IF EXISTS `support_ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_ticket_replies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `body` text NOT NULL,
  `is_internal` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  CONSTRAINT `support_ticket_replies_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `support_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_ticket_replies`
--

LOCK TABLES `support_ticket_replies` WRITE;
/*!40000 ALTER TABLE `support_ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_tickets`
--

DROP TABLE IF EXISTS `support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_tickets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_no` varchar(30) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `type` enum('Bug','Improvement','Question','Other') NOT NULL DEFAULT 'Question',
  `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `subject` varchar(200) NOT NULL,
  `body` text NOT NULL,
  `screen_url` varchar(400) DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved','Closed','Reopened') NOT NULL DEFAULT 'Open',
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(10) unsigned DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `rating` tinyint(3) unsigned DEFAULT NULL,
  `rating_comment` varchar(500) DEFAULT NULL,
  `rated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_no` (`ticket_no`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_tickets`
--

LOCK TABLES `support_tickets` WRITE;
/*!40000 ALTER TABLE `support_tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tds_certificates`
--

DROP TABLE IF EXISTS `tds_certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tds_certificates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(10) unsigned NOT NULL,
  `financial_year` varchar(9) NOT NULL,
  `quarter` enum('Q1','Q2','Q3','Q4') NOT NULL,
  `expected_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `received_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `certificate_no` varchar(60) DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `status` enum('Pending','Received','Disputed','Reconciled') NOT NULL DEFAULT 'Pending',
  `notes` varchar(400) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id_financial_year_quarter` (`client_id`,`financial_year`,`quarter`),
  CONSTRAINT `tds_certificates_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tds_certificates`
--

LOCK TABLES `tds_certificates` WRITE;
/*!40000 ALTER TABLE `tds_certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `tds_certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_advances`
--

DROP TABLE IF EXISTS `trip_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_advances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `mode` enum('Cash','UPI','Bank Transfer','FuelCard','FastTag') NOT NULL DEFAULT 'Cash',
  `reference_no` varchar(80) DEFAULT NULL,
  `given_to` varchar(120) DEFAULT NULL,
  `given_at` datetime DEFAULT NULL,
  `given_by` int(10) unsigned DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL,
  `settled_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(400) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  CONSTRAINT `trip_advances_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_advances`
--

LOCK TABLES `trip_advances` WRITE;
/*!40000 ALTER TABLE `trip_advances` DISABLE KEYS */;
/*!40000 ALTER TABLE `trip_advances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_expense_categories`
--

DROP TABLE IF EXISTS `trip_expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_expense_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `default_is_billable` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_expense_categories`
--

LOCK TABLES `trip_expense_categories` WRITE;
/*!40000 ALTER TABLE `trip_expense_categories` DISABLE KEYS */;
INSERT INTO `trip_expense_categories` VALUES (1,'Toll / FASTag',0,10,1),(2,'Diesel / Fuel',0,20,1),(3,'Driver Bhatta',0,30,1),(4,'Loading Charges',1,40,1),(5,'Unloading Charges',1,50,1),(6,'Detention',1,60,1),(7,'Weighbridge',1,70,1),(8,'Parking',0,80,1),(9,'Maamul / Border',0,90,1),(10,'Escort / Pilot',1,100,1),(11,'POD Courier',0,110,1),(12,'Cleaning / Tarpaulin',0,120,1),(13,'Multi-point Charges',1,130,1),(14,'Breakdown Recovery',0,140,1),(15,'Miscellaneous',0,150,1);
/*!40000 ALTER TABLE `trip_expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_expenses`
--

DROP TABLE IF EXISTS `trip_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `expense_date` date DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_billable` tinyint(1) NOT NULL DEFAULT 0,
  `billed_on_invoice_id` int(10) unsigned DEFAULT NULL,
  `paid_to` enum('Driver','Vendor','Direct','Self') NOT NULL DEFAULT 'Direct',
  `payment_mode` varchar(40) DEFAULT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `document_id` int(10) unsigned DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `is_billable` (`is_billable`),
  KEY `billed_on_invoice_id` (`billed_on_invoice_id`),
  KEY `category` (`category`),
  KEY `idx_tx_deleted_date` (`deleted_at`,`expense_date`),
  CONSTRAINT `trip_expenses_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_expenses`
--

LOCK TABLES `trip_expenses` WRITE;
/*!40000 ALTER TABLE `trip_expenses` DISABLE KEYS */;
INSERT INTO `trip_expenses` VALUES (1,1,'2025-12-07','Detention','Detained at unloading over 24h',7559.00,1,NULL,'Direct','UPI','VCH-72BA',NULL,NULL,1,NULL,'2025-12-07 10:00:00','2025-12-07 10:00:00',NULL),(2,1,'2025-12-08','Unloading Charges','Hamali at destination',4333.00,1,NULL,'Direct','Cash','VCH-5946',NULL,NULL,1,NULL,'2025-12-08 10:00:00','2025-12-08 10:00:00',NULL),(3,1,'2025-12-07','Parking','Overnight parking',400.00,0,NULL,'Driver','Cash','VCH-46A4',NULL,NULL,1,NULL,'2025-12-07 10:00:00','2025-12-07 10:00:00',NULL),(4,1,'2025-12-08','Weighbridge','Dharam Kanta weighment',152.00,1,1,'Direct','Cash','VCH-B6EC',NULL,NULL,1,NULL,'2025-12-08 10:00:00','2025-12-08 10:00:00',NULL),(5,1,'2025-12-08','Driver Bhatta','3-day trip allowance',4139.00,0,NULL,'Driver','Cash','VCH-72A0',NULL,NULL,1,NULL,'2025-12-08 10:00:00','2025-12-08 10:00:00',NULL),(6,2,'2026-03-28','Diesel / Fuel','Diesel top-up',4681.00,0,NULL,'Driver','Cash','VCH-0E9A',NULL,NULL,1,NULL,'2026-03-28 10:00:00','2026-03-28 10:00:00',NULL),(7,2,'2026-03-27','Weighbridge','Dharam Kanta weighment',191.00,1,2,'Direct','Cash','VCH-FE34',NULL,NULL,1,NULL,'2026-03-27 10:00:00','2026-03-27 10:00:00',NULL),(8,2,'2026-03-29','Toll / FASTag','Toll expenses en route',2491.00,0,NULL,'Driver','Cash','VCH-937E',NULL,NULL,1,NULL,'2026-03-29 10:00:00','2026-03-29 10:00:00',NULL),(9,3,'2026-02-03','Unloading Charges','Hamali at destination',1923.00,1,NULL,'Direct','Cash','VCH-BD4C',NULL,NULL,1,NULL,'2026-02-03 10:00:00','2026-02-03 10:00:00',NULL),(10,3,'2026-02-03','Parking','Overnight parking',229.00,0,NULL,'Driver','Cash','VCH-0872',NULL,NULL,1,NULL,'2026-02-03 10:00:00','2026-02-03 10:00:00',NULL),(11,3,'2026-02-01','Toll / FASTag','Toll expenses en route',3296.00,0,NULL,'Driver','Cash','VCH-9B8E',NULL,NULL,1,NULL,'2026-02-01 10:00:00','2026-02-01 10:00:00',NULL),(12,3,'2026-02-02','Weighbridge','Dharam Kanta weighment',177.00,1,3,'Direct','Cash','VCH-1C59',NULL,NULL,1,NULL,'2026-02-02 10:00:00','2026-02-02 10:00:00',NULL),(13,4,'2026-03-26','Detention','Detained at unloading over 24h',3210.00,1,4,'Direct','UPI','VCH-45F7',NULL,NULL,1,NULL,'2026-03-26 10:00:00','2026-03-26 10:00:00',NULL),(14,4,'2026-03-26','Toll / FASTag','Toll expenses en route',3416.00,0,NULL,'Driver','Cash','VCH-FBA2',NULL,NULL,1,NULL,'2026-03-26 10:00:00','2026-03-26 10:00:00',NULL),(15,4,'2026-03-26','Diesel / Fuel','Diesel top-up',5287.00,0,NULL,'Driver','Cash','VCH-0A61',NULL,NULL,1,NULL,'2026-03-26 10:00:00','2026-03-26 10:00:00',NULL),(16,4,'2026-03-24','Driver Bhatta','3-day trip allowance',5200.00,0,NULL,'Driver','Cash','VCH-06AD',NULL,NULL,1,NULL,'2026-03-24 10:00:00','2026-03-24 10:00:00',NULL),(17,4,'2026-03-26','Loading Charges','Labour charges at loading point',3012.00,1,4,'Direct','Cash','VCH-4C89',NULL,NULL,1,NULL,'2026-03-26 10:00:00','2026-03-26 10:00:00',NULL),(18,5,'2026-03-29','Weighbridge','Dharam Kanta weighment',252.00,1,5,'Direct','Cash','VCH-2D91',NULL,NULL,1,NULL,'2026-03-29 10:00:00','2026-03-29 10:00:00',NULL),(19,5,'2026-03-27','Loading Charges','Labour charges at loading point',1294.00,1,5,'Direct','Cash','VCH-8F3A',NULL,NULL,1,NULL,'2026-03-27 10:00:00','2026-03-27 10:00:00',NULL),(20,5,'2026-03-29','Unloading Charges','Hamali at destination',1768.00,1,NULL,'Direct','Cash','VCH-529D',NULL,NULL,1,NULL,'2026-03-29 10:00:00','2026-03-29 10:00:00',NULL),(21,5,'2026-03-27','Detention','Detained at unloading over 24h',6182.00,1,5,'Direct','UPI','VCH-031F',NULL,NULL,1,NULL,'2026-03-27 10:00:00','2026-03-27 10:00:00',NULL),(22,5,'2026-03-27','Driver Bhatta','3-day trip allowance',2147.00,0,NULL,'Driver','Cash','VCH-C7F9',NULL,NULL,1,NULL,'2026-03-27 10:00:00','2026-03-27 10:00:00',NULL),(23,6,'2025-12-14','Diesel / Fuel','Diesel top-up',11558.00,0,NULL,'Driver','Cash','VCH-69D6',NULL,NULL,1,NULL,'2025-12-14 10:00:00','2025-12-14 10:00:00',NULL),(24,6,'2025-12-15','Weighbridge','Dharam Kanta weighment',209.00,1,6,'Direct','Cash','VCH-456E',NULL,NULL,1,NULL,'2025-12-15 10:00:00','2025-12-15 10:00:00',NULL),(25,6,'2025-12-14','Toll / FASTag','Toll expenses en route',5316.00,0,NULL,'Driver','Cash','VCH-0635',NULL,NULL,1,NULL,'2025-12-14 10:00:00','2025-12-14 10:00:00',NULL),(26,6,'2025-12-13','Driver Bhatta','3-day trip allowance',5624.00,0,NULL,'Driver','Cash','VCH-2FA6',NULL,NULL,1,NULL,'2025-12-13 10:00:00','2025-12-13 10:00:00',NULL),(27,7,'2025-12-04','Detention','Detained at unloading over 24h',5565.00,1,7,'Direct','UPI','VCH-2145',NULL,NULL,1,NULL,'2025-12-04 10:00:00','2025-12-04 10:00:00',NULL),(28,7,'2025-12-05','Weighbridge','Dharam Kanta weighment',188.00,1,7,'Direct','Cash','VCH-C4A0',NULL,NULL,1,NULL,'2025-12-05 10:00:00','2025-12-05 10:00:00',NULL),(29,7,'2025-12-06','Parking','Overnight parking',204.00,0,NULL,'Driver','Cash','VCH-593F',NULL,NULL,1,NULL,'2025-12-06 10:00:00','2025-12-06 10:00:00',NULL),(30,7,'2025-12-06','Diesel / Fuel','Diesel top-up',12410.00,0,NULL,'Driver','Cash','VCH-79AD',NULL,NULL,1,NULL,'2025-12-06 10:00:00','2025-12-06 10:00:00',NULL),(31,7,'2025-12-05','Loading Charges','Labour charges at loading point',1604.00,1,NULL,'Direct','Cash','VCH-1C23',NULL,NULL,1,NULL,'2025-12-05 10:00:00','2025-12-05 10:00:00',NULL),(32,8,'2026-04-01','Parking','Overnight parking',435.00,0,NULL,'Driver','Cash','VCH-AD53',NULL,NULL,1,NULL,'2026-04-01 10:00:00','2026-04-01 10:00:00',NULL),(33,8,'2026-03-30','Unloading Charges','Hamali at destination',1288.00,1,NULL,'Direct','Cash','VCH-BDB8',NULL,NULL,1,NULL,'2026-03-30 10:00:00','2026-03-30 10:00:00',NULL),(34,8,'2026-03-31','Detention','Detained at unloading over 24h',6700.00,1,NULL,'Direct','UPI','VCH-A3DE',NULL,NULL,1,NULL,'2026-03-31 10:00:00','2026-03-31 10:00:00',NULL),(35,8,'2026-04-01','Toll / FASTag','Toll expenses en route',4326.00,0,NULL,'Driver','Cash','VCH-3E8D',NULL,NULL,1,NULL,'2026-04-01 10:00:00','2026-04-01 10:00:00',NULL),(36,8,'2026-03-31','Driver Bhatta','3-day trip allowance',3838.00,0,NULL,'Driver','Cash','VCH-6CCE',NULL,NULL,1,NULL,'2026-03-31 10:00:00','2026-03-31 10:00:00',NULL);
/*!40000 ALTER TABLE `trip_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_status_history`
--

DROP TABLE IF EXISTS `trip_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_status_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `old_status` varchar(40) DEFAULT NULL,
  `new_status` varchar(40) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `changed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  CONSTRAINT `trip_status_history_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_status_history`
--

LOCK TABLES `trip_status_history` WRITE;
/*!40000 ALTER TABLE `trip_status_history` DISABLE KEYS */;
INSERT INTO `trip_status_history` VALUES (1,1,NULL,'Booking Created','Handed over from booking',1,'2025-12-06 10:01:40'),(2,1,'Booking Created','Closed','Auto-advanced',1,'2025-12-07 10:01:40'),(3,2,NULL,'Booking Created','Handed over from booking',1,'2026-03-27 10:01:40'),(4,2,'Booking Created','Closed','Auto-advanced',1,'2026-03-28 10:01:40'),(5,3,NULL,'Booking Created','Handed over from booking',1,'2026-02-01 10:01:40'),(6,3,'Booking Created','Closed','Auto-advanced',1,'2026-02-02 10:01:40'),(7,4,NULL,'Booking Created','Handed over from booking',1,'2026-03-24 10:01:40'),(8,4,'Booking Created','Closed','Auto-advanced',1,'2026-03-25 10:01:40'),(9,5,NULL,'Booking Created','Handed over from booking',1,'2026-03-27 10:01:40'),(10,5,'Booking Created','Closed','Auto-advanced',1,'2026-03-28 10:01:40'),(11,6,NULL,'Booking Created','Handed over from booking',1,'2025-12-13 10:01:40'),(12,6,'Booking Created','Delivered','Auto-advanced',1,'2025-12-14 10:01:40'),(13,7,NULL,'Booking Created','Handed over from booking',1,'2025-12-04 10:01:40'),(14,7,'Booking Created','Unloading','Auto-advanced',1,'2025-12-05 10:01:40'),(15,8,NULL,'Booking Created','Handed over from booking',1,'2026-03-30 10:01:40'),(16,8,'Booking Created','Vehicle Placed','Auto-advanced',1,'2026-03-31 10:01:40');
/*!40000 ALTER TABLE `trip_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_stops`
--

DROP TABLE IF EXISTS `trip_stops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trip_stops` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` int(10) unsigned NOT NULL,
  `sequence` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `stop_type` enum('pickup','drop') NOT NULL DEFAULT 'pickup',
  `address` varchar(400) NOT NULL,
  `city` varchar(80) DEFAULT NULL,
  `contact_name` varchar(120) DEFAULT NULL,
  `contact_mobile` varchar(20) DEFAULT NULL,
  `gstin` varchar(20) DEFAULT NULL,
  `planned_at` datetime DEFAULT NULL,
  `arrived_at` datetime DEFAULT NULL,
  `departed_at` datetime DEFAULT NULL,
  `notes` varchar(400) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id_sequence` (`trip_id`,`sequence`),
  CONSTRAINT `trip_stops_trip_id_foreign` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_stops`
--

LOCK TABLES `trip_stops` WRITE;
/*!40000 ALTER TABLE `trip_stops` DISABLE KEYS */;
/*!40000 ALTER TABLE `trip_stops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trips`
--

DROP TABLE IF EXISTS `trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trips` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trip_no` varchar(30) NOT NULL,
  `lr_no` varchar(30) DEFAULT NULL,
  `lr_generated_at` datetime DEFAULT NULL,
  `ewb_no` varchar(30) DEFAULT NULL,
  `ewb_date` datetime DEFAULT NULL,
  `ewb_valid_until` datetime DEFAULT NULL,
  `ewb_status` varchar(20) DEFAULT 'None',
  `booking_id` int(10) unsigned NOT NULL,
  `vendor_id` int(10) unsigned DEFAULT NULL,
  `driver_id` int(10) unsigned DEFAULT NULL,
  `vehicle_id` int(10) unsigned DEFAULT NULL,
  `driver_name` varchar(150) DEFAULT NULL,
  `driver_mobile` varchar(20) DEFAULT NULL,
  `driver_track_token` varchar(64) DEFAULT NULL,
  `driver_track_started_at` datetime DEFAULT NULL,
  `driver_track_last_ping` datetime DEFAULT NULL,
  `vehicle_number` varchar(20) DEFAULT NULL,
  `loading_point` varchar(255) DEFAULT NULL,
  `unloading_point` varchar(255) DEFAULT NULL,
  `dispatch_datetime` datetime DEFAULT NULL,
  `delivery_datetime` datetime DEFAULT NULL,
  `current_status` varchar(40) NOT NULL DEFAULT 'Booking Created',
  `delay_reason` varchar(255) DEFAULT NULL,
  `pod_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `pod_received_at` datetime DEFAULT NULL,
  `epod_token` varchar(64) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `cargo_value_inr` decimal(14,2) DEFAULT NULL,
  `insurance_policy_no` varchar(80) DEFAULT NULL,
  `insurance_provider` varchar(120) DEFAULT NULL,
  `loading_arrived_at` datetime DEFAULT NULL,
  `loading_departed_at` datetime DEFAULT NULL,
  `unloading_arrived_at` datetime DEFAULT NULL,
  `unloading_departed_at` datetime DEFAULT NULL,
  `detention_billable_hours` decimal(6,2) DEFAULT 0.00,
  `detention_amount` decimal(10,2) DEFAULT 0.00,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trip_no` (`trip_no`),
  UNIQUE KEY `uniq_lr_no` (`lr_no`),
  KEY `booking_id` (`booking_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `vehicle_number` (`vehicle_number`),
  KEY `current_status` (`current_status`),
  KEY `idx_trip_dispatch` (`dispatch_datetime`),
  KEY `idx_trip_delivery` (`delivery_datetime`),
  KEY `idx_trip_pod_status` (`pod_status`),
  KEY `idx_trip_track_token` (`driver_track_token`),
  KEY `idx_trip_epod_token` (`epod_token`),
  CONSTRAINT `trips_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trips`
--

LOCK TABLES `trips` WRITE;
/*!40000 ALTER TABLE `trips` DISABLE KEYS */;
INSERT INTO `trips` VALUES (1,'TR00001','LR00001','2025-12-06 10:01:40','181020260017','2025-12-06 10:01:40','2025-12-09 10:01:40','Active',1,3,2,2,'Santosh Pawar','9870010001',NULL,NULL,NULL,'DL01BC1123','Surat','Chennai','2025-12-07 10:01:40','2025-12-08 10:01:40','Closed',NULL,'Received','2025-12-08 10:01:40',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,1,NULL,'2025-12-06 10:01:40','2025-12-07 10:01:40',NULL),(2,'TR00002','LR00002','2026-03-27 10:01:40','181020260035','2026-03-27 10:01:40','2026-03-30 10:01:40','Active',2,3,3,3,'Karan Singh','9870010002',NULL,NULL,NULL,'KA05CD1246','Ludhiana','Mumbai','2026-03-28 10:01:40','2026-03-29 10:01:40','Closed',NULL,'Received','2026-03-29 10:01:40',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,1,NULL,'2026-03-27 10:01:40','2026-03-28 10:01:40',NULL),(3,'TR00003','LR00003','2026-02-01 10:01:40','181020260053','2026-02-01 10:01:40','2026-02-04 10:01:40','Active',3,5,4,4,'Balwinder Kaur','9870010003',NULL,NULL,NULL,'TN09DE1369','Pune','Delhi','2026-02-02 10:01:40','2026-02-03 10:01:40','Closed',NULL,'Received','2026-02-03 10:01:40',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,1,NULL,'2026-02-01 10:01:40','2026-02-02 10:01:40',NULL),(4,'TR00004','LR00004','2026-03-24 10:01:40','181020260071','2026-03-24 10:01:40','2026-03-27 10:01:40','Active',4,10,5,5,'Ramu Naidu','9870010004',NULL,NULL,NULL,'GJ01EF1492','Delhi','Kolkata','2026-03-25 10:01:40','2026-03-26 10:01:40','Closed',NULL,'Received','2026-03-26 10:01:40',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,1,NULL,'2026-03-24 10:01:40','2026-03-25 10:01:40',NULL),(5,'TR00005','LR00005','2026-03-27 10:01:40','181020260089','2026-03-27 10:01:40','2026-03-30 10:01:40','Active',5,10,6,6,'Sunil Sharma','9870010005',NULL,NULL,NULL,'HR26FG1615','Hyderabad','Delhi','2026-03-28 10:01:40','2026-03-29 10:01:40','Closed',NULL,'Received','2026-03-29 10:01:40',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,1,NULL,'2026-03-27 10:01:40','2026-03-28 10:01:40',NULL),(6,'TR00006','LR00006','2025-12-13 10:01:40','181020260107','2025-12-13 10:01:40','2025-12-16 10:01:40','Active',6,2,7,7,'Mohammed Ishaq','9870010006',NULL,NULL,NULL,'WB12GH1738','Surat','Chennai','2025-12-14 10:01:40','2025-12-15 10:01:40','Delivered',NULL,'Received','2025-12-15 10:01:40',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,1,NULL,'2025-12-13 10:01:40','2025-12-14 10:01:40',NULL),(7,'TR00007','LR00007','2025-12-04 10:01:40','181020260125','2025-12-04 10:01:40','2025-12-07 10:01:40','Active',7,5,8,8,'Prakash Gowda','9870010007',NULL,NULL,NULL,'UP32HI1861','Ahmedabad','Mumbai','2025-12-05 10:01:40',NULL,'Unloading',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,1,NULL,'2025-12-04 10:01:40','2025-12-05 10:01:40',NULL),(8,'TR00008','LR00008','2026-03-30 10:01:40',NULL,NULL,NULL,'None',8,4,9,9,'Ranjit Das','9870010008','0d9badcd4c733f1fd4ecf191fe997e1f4cbdf0dd7a0ade74f5c1e8f36af8e2ab','2026-05-12 09:51:51',NULL,'RJ14IJ1984','Mumbai','Chennai',NULL,NULL,'Vehicle Placed',NULL,'Pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,0.00,1,NULL,'2026-03-30 10:01:40','2026-03-31 10:01:40',NULL);
/*!40000 ALTER TABLE `trips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `is_super_admin` tinyint(1) DEFAULT 0,
  `totp_secret` varchar(64) DEFAULT NULL,
  `totp_enabled` tinyint(1) DEFAULT 0,
  `name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `failed_attempts` tinyint(3) unsigned DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  KEY `mobile` (`mobile`),
  KEY `idx_users_super` (`is_super_admin`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,0,NULL,0,'System Admin','admin@tpt.local','9999999999','$2y$10$x4w7ZNG2CvxSBcquvHOtEu3VSMDpMSMYc9X2mqdF4c./hjsWt8fuW',1,'2026-05-12 06:02:05','::1',0,NULL,NULL,NULL,'2026-04-21 07:12:40','2026-05-12 06:02:05',NULL),(2,1,1,NULL,0,'SuperAdmin','super@tpt.local',NULL,'$2y$10$p41UrlX1z7YAOW.XCyHF1O.tpu2aIUL9QGeisAwNS7uLlTpB.bdky',1,NULL,NULL,0,NULL,NULL,NULL,'2026-05-01 09:50:34','2026-05-01 09:50:42',NULL),(3,2,0,NULL,0,'Mira Mehta (Management)','mgr@tpt.local','9810000001','$2y$10$zRoNoHEIalnf7rbY4UMMdubPHA4bDclD/GpR4uR7UCN7wY1oUVuHO',1,'2026-05-12 05:45:51','::1',0,NULL,NULL,NULL,'2026-05-01 10:01:35','2026-05-12 05:45:51',NULL),(4,3,0,NULL,0,'Cory Sharma (CRM Exec)','crm.exec@tpt.local','9810000002','$2y$10$z.iu3145OWiVszrC6BfYzuW1.lsZa5uMP28JAcKIxvhE6gusfSpba',1,'2026-05-12 05:45:56','::1',0,NULL,NULL,NULL,'2026-05-01 10:01:35','2026-05-12 05:45:56',NULL),(5,4,0,NULL,0,'Charu Iyer (CRM Mgr)','crm.mgr@tpt.local','9810000003','$2y$10$Yf3j54Nc9UK/fEeiz40dGeid5KhPZIPUhuxT/DkeDro.y0/rMAJLq',1,'2026-05-12 05:46:09','::1',0,NULL,NULL,NULL,'2026-05-01 10:01:35','2026-05-12 05:46:09',NULL),(6,5,0,NULL,0,'Pranav Singh (Purchase)','pur.exec@tpt.local','9810000004','$2y$10$LlhquxDDT.LxkzM9REE9ZOAJCiEWq9Jvl7ttrmAcIWAR5ojUSkhHa',1,'2026-05-12 05:46:15','::1',0,NULL,NULL,NULL,'2026-05-01 10:01:35','2026-05-12 05:46:15',NULL),(7,6,0,NULL,0,'Priya Nair (Purchase Mgr)','pur.mgr@tpt.local','9810000005','$2y$10$pE4aoJUZwlexqq1uLkTdv.54Sn3zdUlukGdW1OPUXYcKSURGb1ozq',1,'2026-05-12 05:46:22','::1',0,NULL,NULL,NULL,'2026-05-01 10:01:35','2026-05-12 05:46:22',NULL),(8,7,0,NULL,0,'Omar Khan (Ops Exec)','ops.exec@tpt.local','9810000006','$2y$10$yGZ42NkcRmz8K6SCu0s7Xu8ALVRR0KUh31nggEPkAfeXv4QxeJqXK',1,'2026-05-12 05:46:28','::1',0,NULL,NULL,NULL,'2026-05-01 10:01:35','2026-05-12 05:46:28',NULL),(9,8,0,NULL,0,'Olivia Rao (Ops Mgr)','ops.mgr@tpt.local','9810000007','$2y$10$bisyXESfw5NfHbKmpT2Egu9/4JqziZvuy3cgiX54ZEsO1zPSvBYeK',1,NULL,NULL,0,NULL,NULL,NULL,'2026-05-01 10:01:35','2026-05-01 10:01:35',NULL),(10,9,0,NULL,0,'Anil Bhat (Accounts)','accounts@tpt.local','9810000008','$2y$10$PQa2fjRdEI68YhAuJTk8kulv2qosPFiZ3x4XcpABouxinRrSh.Jry',1,'2026-05-12 05:48:29','::1',0,NULL,NULL,NULL,'2026-05-01 10:01:35','2026-05-12 05:48:29',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vehicles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned DEFAULT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `vehicle_type` varchar(80) DEFAULT NULL,
  `rc_no` varchar(40) DEFAULT NULL,
  `permit_no` varchar(40) DEFAULT NULL,
  `insurance_no` varchar(60) DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `fitness_expiry` date DEFAULT NULL,
  `gps_provider` enum('none','loconav','fasttag','dedicated_link','driver_phone_only') DEFAULT 'none',
  `gps_device_imei` varchar(20) DEFAULT NULL,
  `gps_tracking_url` varchar(500) DEFAULT NULL,
  `gps_notes` varchar(400) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_number` (`vehicle_number`),
  KEY `vendor_id` (`vendor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,1,'MH12AB1000','32ft SXL','RC-2334BD','PER-8D24A0','INS-6EFEC1E0','2027-02-12','2027-01-01','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(2,2,'DL01BC1123','32ft MXL','RC-05551B','PER-44F44F','INS-A9CF3BC8','2027-03-29','2026-08-21','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(3,3,'KA05CD1246','20ft Container','RC-F0CCD5','PER-D8ADB2','INS-2CD3D8A1','2027-04-28','2026-09-30','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(4,4,'TN09DE1369','40ft Container','RC-12444B','PER-D1B723','INS-CC647708','2026-07-16','2026-11-06','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(5,5,'GJ01EF1492','14ft Closed Body','RC-EAE6AF','PER-2209FA','INS-E75451CC','2026-08-08','2027-02-02','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(6,6,'HR26FG1615','22ft Open Body','RC-0A9AA1','PER-26AC62','INS-03BAB859','2026-09-09','2026-11-12','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(7,7,'WB12GH1738','32ft SXL','RC-492B7D','PER-A29B6F','INS-BCC6E464','2026-12-27','2026-08-24','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(8,8,'UP32HI1861','32ft MXL','RC-C20EB5','PER-F50F69','INS-A203D08C','2026-07-05','2026-10-18','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(9,9,'RJ14IJ1984','20ft Container','RC-D5844E','PER-BC5DDB','INS-AF98D09B','2027-02-12','2027-02-06','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(10,10,'TS09JK2107','40ft Container','RC-56D189','PER-2E57AB','INS-612A393D','2027-01-05','2027-02-02','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(11,1,'MH12KL2230','14ft Closed Body','RC-7C6AB8','PER-4FBB26','INS-83C3C4E4','2026-08-13','2027-04-30','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39'),(12,2,'DL01LM2353','22ft Open Body','RC-53A3FE','PER-576D1E','INS-A8A480AB','2027-03-25','2026-09-18','none',NULL,NULL,NULL,1,'2026-01-01 10:01:39','2026-04-26 10:01:39');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_bills`
--

DROP TABLE IF EXISTS `vendor_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_bills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `trip_id` int(10) unsigned DEFAULT NULL,
  `bill_no` varchar(60) DEFAULT NULL,
  `bill_date` date DEFAULT NULL,
  `bill_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Open',
  `document_id` int(10) unsigned DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `trip_id` (`trip_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_bills`
--

LOCK TABLES `vendor_bills` WRITE;
/*!40000 ALTER TABLE `vendor_bills` DISABLE KEYS */;
INSERT INTO `vendor_bills` VALUES (1,3,1,'VB00001','2025-12-08',41829.00,41829.00,0.00,'2025-12-23','Paid',NULL,'Freight settlement',1,'2025-12-08 10:01:40','2025-12-08 10:01:40'),(2,3,2,'VB00002','2026-03-29',49252.00,49252.00,0.00,'2026-04-13','Paid',NULL,'Freight settlement',1,'2026-03-29 10:01:40','2026-03-29 10:01:40'),(3,5,3,'VB00003','2026-02-03',44133.00,44133.00,0.00,'2026-02-18','Paid',NULL,'Freight settlement',1,'2026-02-03 10:01:40','2026-02-03 10:01:40'),(4,10,4,'VB00004','2026-03-26',50854.00,50854.00,0.00,'2026-04-10','Paid',NULL,'Freight settlement',1,'2026-03-26 10:01:40','2026-03-26 10:01:40'),(5,10,5,'VB00005','2026-03-29',30729.00,30729.00,0.00,'2026-04-13','Paid',NULL,'Freight settlement',1,'2026-03-29 10:01:40','2026-03-29 10:01:40'),(6,2,6,'VB00006','2025-12-15',42650.00,25590.00,17060.00,'2025-12-30','Partially Paid',NULL,'Freight settlement',1,'2025-12-15 10:01:40','2025-12-15 10:01:40'),(7,5,7,'VB00007','2025-12-06',31770.00,19062.00,12708.00,'2025-12-21','Partially Paid',NULL,'Freight settlement',1,'2025-12-06 10:01:40','2025-12-06 10:01:40');
/*!40000 ALTER TABLE `vendor_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_contacts`
--

DROP TABLE IF EXISTS `vendor_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `contact_name` varchar(150) NOT NULL,
  `designation` varchar(80) DEFAULT NULL,
  `mobile` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_id_mobile` (`vendor_id`,`mobile`),
  KEY `vendor_id` (`vendor_id`),
  CONSTRAINT `vendor_contacts_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_contacts`
--

LOCK TABLES `vendor_contacts` WRITE;
/*!40000 ALTER TABLE `vendor_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_deposits`
--

DROP TABLE IF EXISTS `vendor_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_deposits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `txn_type` enum('Deposit','Release','Forfeit') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `reference_trip_id` int(10) unsigned DEFAULT NULL,
  `reason` varchar(400) DEFAULT NULL,
  `txn_date` date NOT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  CONSTRAINT `vendor_deposits_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_deposits`
--

LOCK TABLES `vendor_deposits` WRITE;
/*!40000 ALTER TABLE `vendor_deposits` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_payments`
--

DROP TABLE IF EXISTS `vendor_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `vendor_bill_id` int(10) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `payment_mode` varchar(40) DEFAULT NULL,
  `amount_paid` decimal(12,2) NOT NULL,
  `reference_no` varchar(80) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `vendor_bill_id` (`vendor_bill_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_payments`
--

LOCK TABLES `vendor_payments` WRITE;
/*!40000 ALTER TABLE `vendor_payments` DISABLE KEYS */;
INSERT INTO `vendor_payments` VALUES (1,3,1,'2025-12-18','NEFT',41829.00,'NEFT-A75ADF','Full settlement',1,'2025-12-18 10:01:40'),(2,3,2,'2026-04-08','NEFT',49252.00,'NEFT-F3F83B','Full settlement',1,'2026-04-08 10:01:40'),(3,5,3,'2026-02-13','NEFT',44133.00,'NEFT-A062A1','Full settlement',1,'2026-02-13 10:01:40'),(4,10,4,'2026-04-05','NEFT',50854.00,'NEFT-918CD2','Full settlement',1,'2026-04-05 10:01:40'),(5,10,5,'2026-04-08','NEFT',30729.00,'NEFT-D0FB92','Full settlement',1,'2026-04-08 10:01:40'),(6,2,6,'2025-12-25','NEFT',25590.00,'NEFT-AAC80E','Advance',1,'2025-12-25 10:01:40'),(7,5,7,'2025-12-16','NEFT',19062.00,'NEFT-686C42','Advance',1,'2025-12-16 10:01:40');
/*!40000 ALTER TABLE `vendor_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_routes`
--

DROP TABLE IF EXISTS `vendor_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_routes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `pickup_city` varchar(80) NOT NULL,
  `drop_city` varchar(80) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `vendor_id_pickup_city_drop_city` (`vendor_id`,`pickup_city`,`drop_city`),
  CONSTRAINT `vendor_routes_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_routes`
--

LOCK TABLES `vendor_routes` WRITE;
/*!40000 ALTER TABLE `vendor_routes` DISABLE KEYS */;
INSERT INTO `vendor_routes` VALUES (1,1,'Pune','Delhi',1),(2,1,'Bangalore','Kochi',1),(3,1,'Jaipur','Mumbai',1),(4,2,'Chennai','Hyderabad',1),(5,2,'Ahmedabad','Mumbai',1),(6,2,'Bangalore','Kochi',1),(7,3,'Mumbai','Chennai',1),(8,3,'Bangalore','Kochi',1),(9,3,'Ludhiana','Mumbai',1),(10,4,'Pune','Delhi',1),(11,4,'Chennai','Hyderabad',1),(12,4,'Delhi','Kolkata',1),(13,5,'Delhi','Kolkata',1),(14,5,'Ahmedabad','Mumbai',1),(15,5,'Ludhiana','Mumbai',1),(16,6,'Mumbai','Chennai',1),(17,6,'Ahmedabad','Mumbai',1),(18,6,'Bangalore','Kochi',1),(19,7,'Chennai','Hyderabad',1),(20,7,'Ahmedabad','Mumbai',1),(21,7,'Bangalore','Kochi',1),(22,8,'Pune','Delhi',1),(23,8,'Bangalore','Kochi',1),(24,8,'Jaipur','Mumbai',1),(25,9,'Pune','Delhi',1),(26,9,'Mumbai','Chennai',1),(27,9,'Bangalore','Kochi',1),(28,10,'Mumbai','Chennai',1),(29,10,'Delhi','Kolkata',1),(30,10,'Jaipur','Mumbai',1);
/*!40000 ALTER TABLE `vendor_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_vehicle_types`
--

DROP TABLE IF EXISTS `vendor_vehicle_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendor_vehicle_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` int(10) unsigned NOT NULL,
  `vehicle_type` varchar(80) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `vendor_id_vehicle_type` (`vendor_id`,`vehicle_type`),
  CONSTRAINT `vendor_vehicle_types_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_vehicle_types`
--

LOCK TABLES `vendor_vehicle_types` WRITE;
/*!40000 ALTER TABLE `vendor_vehicle_types` DISABLE KEYS */;
INSERT INTO `vendor_vehicle_types` VALUES (1,1,'32ft SXL',1),(2,1,'32ft MXL',1),(3,1,'22ft Open Body',1),(4,2,'32ft SXL',1),(5,2,'40ft Container',1),(6,2,'22ft Open Body',1),(7,3,'32ft SXL',1),(8,3,'32ft MXL',1),(9,3,'40ft Container',1),(10,4,'32ft SXL',1),(11,4,'14ft Closed Body',1),(12,4,'22ft Open Body',1),(13,5,'20ft Container',1),(14,5,'14ft Closed Body',1),(15,5,'22ft Open Body',1),(16,6,'40ft Container',1),(17,6,'14ft Closed Body',1),(18,6,'22ft Open Body',1),(19,7,'32ft SXL',1),(20,7,'32ft MXL',1),(21,7,'20ft Container',1),(22,8,'32ft SXL',1),(23,8,'20ft Container',1),(24,8,'14ft Closed Body',1),(25,9,'32ft MXL',1),(26,9,'20ft Container',1),(27,9,'40ft Container',1),(28,10,'32ft SXL',1),(29,10,'32ft MXL',1),(30,10,'14ft Closed Body',1);
/*!40000 ALTER TABLE `vendor_vehicle_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vendors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(30) NOT NULL,
  `owner_name` varchar(150) DEFAULT NULL,
  `company_name` varchar(200) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `alt_mobile` varchar(20) DEFAULT NULL,
  `whatsapp_no` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(400) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `state` varchar(80) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `gst_no` varchar(20) DEFAULT NULL,
  `pan_no` varchar(20) DEFAULT NULL,
  `bank_name` varchar(150) DEFAULT NULL,
  `account_no` varchar(40) DEFAULT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `is_preferred` tinyint(1) NOT NULL DEFAULT 0,
  `is_blacklisted` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) unsigned DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_code` (`vendor_code`),
  KEY `mobile` (`mobile`),
  KEY `whatsapp_no` (`whatsapp_no`),
  KEY `company_name` (`company_name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
INSERT INTO `vendors` VALUES (1,'VN00001','Suresh Sharma','Sharma Roadlines','9990001111',NULL,'9990001111',NULL,NULL,'Delhi','Delhi','110001','01AAACV1000K1ZX','AAACV1000K','HDFC Bank','500000001111','HDFC0001234',4.70,1,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(2,'VN00002','Vikas Arora','Delhi Truckers LLP','9990001112',NULL,'9990001112',NULL,NULL,'New Delhi','Delhi','110001','02AAACV1001K1ZX','AAACV1001K','HDFC Bank','500000001112','HDFC0001234',4.00,0,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(3,'VN00003','Harpreet Singh','Bharat Express Logistics','9990001113',NULL,'9990001113',NULL,NULL,'Ludhiana','Punjab','110001','03AAACV1002K1ZX','AAACV1002K','HDFC Bank','500000001113','HDFC0001234',4.50,1,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(4,'VN00004','Ganesh Patil','Maharashtra Cargo Pvt Ltd','9990001114',NULL,'9990001114','ops@maharashtracargo.in',NULL,'Pune','Maharashtra','110001','04AAACV1003K1ZX','AAACV1003K','HDFC Bank','500000001114','HDFC0001234',4.60,1,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(5,'VN00005','M. Kumaravel','Chennai Movers & Packers','9990001115',NULL,'9990001115',NULL,NULL,'Chennai','Tamil Nadu','110001','05AAACV1004K1ZX','AAACV1004K','HDFC Bank','500000001115','HDFC0001234',3.80,0,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(6,'VN00006','Kiran Desai','Gujarat Freight Carriers','9990001116',NULL,'9990001116',NULL,NULL,'Surat','Gujarat','110001','06AAACV1005K1ZX','AAACV1005K','HDFC Bank','500000001116','HDFC0001234',4.40,1,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(7,'VN00007','Balaji Iyer','South India Transports','9990001117',NULL,'9990001117',NULL,NULL,'Bangalore','Karnataka','110001','07AAACV1006K1ZX','AAACV1006K','HDFC Bank','500000001117','HDFC0001234',4.20,0,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(8,'VN00008','Dipu Das','East Coast Roadways','9990001118',NULL,'9990001118',NULL,NULL,'Kolkata','West Bengal','110001','08AAACV1007K1ZX','AAACV1007K','HDFC Bank','500000001118','HDFC0001234',3.50,0,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(9,'VN00009','Ashok Malik','Royal Haulage Corp','9990001119',NULL,'9990001119',NULL,NULL,'Jaipur','Rajasthan','110001','09AAACV1008K1ZX','AAACV1008K','HDFC Bank','500000001119','HDFC0001234',4.00,0,0,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL),(10,'VN00010','Ravinder Yadav','Jet Speed Logistics','9990001120',NULL,'9990001120',NULL,NULL,'Gurgaon','Haryana','110001','010AAACV1009K1ZX','AAACV1009K','HDFC Bank','500000001120','HDFC0001234',2.90,0,1,1,1,NULL,'2025-12-02 10:01:39','2026-04-21 10:01:39',NULL);
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_incoming_messages`
--

DROP TABLE IF EXISTS `whatsapp_incoming_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_incoming_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_no` varchar(20) NOT NULL,
  `provider_message_id` varchar(150) DEFAULT NULL,
  `message_type` varchar(20) NOT NULL DEFAULT 'text',
  `text_body` text DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `media_type` varchar(60) DEFAULT NULL,
  `media_id` varchar(150) DEFAULT NULL,
  `rfq_id` int(10) unsigned DEFAULT NULL,
  `trip_id` int(10) unsigned DEFAULT NULL,
  `booking_id` int(10) unsigned DEFAULT NULL,
  `vendor_id` int(10) unsigned DEFAULT NULL,
  `client_id` int(10) unsigned DEFAULT NULL,
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `raw_payload` mediumtext DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sender_no` (`sender_no`),
  KEY `provider_message_id` (`provider_message_id`),
  KEY `rfq_id` (`rfq_id`),
  KEY `trip_id` (`trip_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_incoming_messages`
--

LOCK TABLES `whatsapp_incoming_messages` WRITE;
/*!40000 ALTER TABLE `whatsapp_incoming_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_incoming_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_logs`
--

DROP TABLE IF EXISTS `whatsapp_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(50) DEFAULT NULL,
  `module_ref_id` int(10) unsigned DEFAULT NULL,
  `audience_type` varchar(20) DEFAULT NULL,
  `recipient_no` varchar(20) NOT NULL,
  `template_key` varchar(80) DEFAULT NULL,
  `message_type` varchar(20) NOT NULL DEFAULT 'text',
  `provider_message_id` varchar(150) DEFAULT NULL,
  `delivery_status` varchar(30) NOT NULL DEFAULT 'Queued',
  `request_payload` mediumtext DEFAULT NULL,
  `response_payload` mediumtext DEFAULT NULL,
  `error_message` varchar(255) DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `failed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipient_no` (`recipient_no`),
  KEY `provider_message_id` (`provider_message_id`),
  KEY `module_name_module_ref_id` (`module_name`,`module_ref_id`),
  KEY `delivery_status` (`delivery_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_logs`
--

LOCK TABLES `whatsapp_logs` WRITE;
/*!40000 ALTER TABLE `whatsapp_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_templates`
--

DROP TABLE IF EXISTS `whatsapp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_key` varchar(80) NOT NULL,
  `audience_type` enum('client','vendor','internal','driver') NOT NULL DEFAULT 'client',
  `template_name` varchar(150) NOT NULL,
  `language_code` varchar(10) NOT NULL DEFAULT 'en',
  `body_text` text DEFAULT NULL,
  `variables` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_key` (`template_key`),
  KEY `audience_type` (`audience_type`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_templates`
--

LOCK TABLES `whatsapp_templates` WRITE;
/*!40000 ALTER TABLE `whatsapp_templates` DISABLE KEYS */;
INSERT INTO `whatsapp_templates` VALUES (1,'rfq_vendor','vendor','rfq_vendor','en','RFQ {{1}}\nRoute: {{2}}\nVehicle: {{3}}\nMaterial: {{4}}\nWeight: {{5}}\nLoading: {{6}}\nReply with your best rate.',NULL,1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(2,'quote_to_client','client','quote_to_client','en','Dear {{1}}, our quote for {{2}} route (Vehicle {{3}}) is INR {{4}}. Valid till {{5}}.',NULL,1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(3,'vehicle_placed','client','vehicle_placed','en','Booking {{1}}: Vehicle {{2}} placed. Driver {{3}} ({{4}}).',NULL,1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(4,'trip_in_transit','client','trip_in_transit','en','Trip {{1}} is in transit. Last location: {{2}}.',NULL,1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(5,'trip_delivered','client','trip_delivered','en','Trip {{1}} delivered. Please share POD confirmation.',NULL,1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(6,'pod_reminder','vendor','pod_reminder','en','Trip {{1}}: please send POD at the earliest.',NULL,1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(7,'invoice_share','client','invoice_share','en','Invoice {{1}} for INR {{2}} is attached. Due: {{3}}.',NULL,1,'2026-04-21 07:12:40','2026-04-21 07:12:40'),(8,'payment_reminder','client','payment_reminder','en','Friendly reminder: invoice {{1}} of INR {{2}} is due on {{3}}.',NULL,1,'2026-04-21 07:12:40','2026-04-21 07:12:40');
/*!40000 ALTER TABLE `whatsapp_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'tpt_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-12 12:15:05
