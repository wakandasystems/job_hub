-- MySQL dump 10.13  Distrib 8.0.33, for macos13 (arm64)
--
-- Host: 127.0.0.1    Database: archielite_jobbox
-- ------------------------------------------------------
-- Server version	8.0.33

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activations`
--

DROP TABLE IF EXISTS `activations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `code` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activations_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activations`
--

LOCK TABLES `activations` WRITE;
/*!40000 ALTER TABLE `activations` DISABLE KEYS */;
INSERT INTO `activations` VALUES (1,1,'bE201RbcTaIaQ4rYIfPClsYSPDOSRVVR',1,'2025-10-26 20:12:57','2025-10-26 20:12:57','2025-10-26 20:12:57');
/*!40000 ALTER TABLE `activations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `permission` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ads`
--

DROP TABLE IF EXISTS `ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expired_at` datetime DEFAULT NULL,
  `location` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clicked` bigint NOT NULL DEFAULT '0',
  `order` int DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `open_in_new_tab` tinyint(1) NOT NULL DEFAULT '1',
  `tablet_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ads_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_adsense_slot_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ads_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ads`
--

LOCK TABLES `ads` WRITE;
/*!40000 ALTER TABLE `ads` DISABLE KEYS */;
/*!40000 ALTER TABLE `ads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ads_translations`
--

DROP TABLE IF EXISTS `ads_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ads_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ads_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`ads_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ads_translations`
--

LOCK TABLES `ads_translations` WRITE;
/*!40000 ALTER TABLE `ads_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ads_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_histories`
--

DROP TABLE IF EXISTS `audit_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_histories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `user_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'Botble\\ACL\\Models\\User',
  `module` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request` longtext COLLATE utf8mb4_unicode_ci,
  `action` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actor_id` bigint unsigned NOT NULL,
  `actor_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'Botble\\ACL\\Models\\User',
  `reference_id` bigint unsigned NOT NULL,
  `reference_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_histories_user_id_index` (`user_id`),
  KEY `audit_histories_module_index` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_histories`
--

LOCK TABLES `audit_histories` WRITE;
/*!40000 ALTER TABLE `audit_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned NOT NULL DEFAULT '0',
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `author_id` bigint unsigned DEFAULT NULL,
  `author_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Botble\\ACL\\Models\\User',
  `icon` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int unsigned NOT NULL DEFAULT '0',
  `is_featured` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `categories_parent_id_index` (`parent_id`),
  KEY `categories_status_index` (`status`),
  KEY `categories_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Design',0,'Incidunt ad velit qui in et nostrum repudiandae consequatur. Qui impedit necessitatibus quisquam culpa eos.','published',NULL,'Botble\\ACL\\Models\\User',NULL,0,0,1,'2025-10-26 20:13:00','2025-10-26 20:13:00'),(2,'Lifestyle',0,'Est quis eligendi nostrum dolorum enim. Qui officia placeat quos et voluptas similique dolorem. Et iure aut quis in. Sunt dolores dolor molestias optio vero qui.','published',NULL,'Botble\\ACL\\Models\\User',NULL,0,1,0,'2025-10-26 20:13:00','2025-10-26 20:13:00'),(3,'Travel Tips',2,'Eum corrupti ipsa velit. Ut veritatis et consequatur impedit error et incidunt. Praesentium esse eveniet labore maxime. Sunt asperiores dignissimos fugit voluptas dicta reprehenderit.','published',NULL,'Botble\\ACL\\Models\\User',NULL,0,0,0,'2025-10-26 20:13:00','2025-10-26 20:13:00'),(4,'Healthy',0,'Consequatur distinctio aspernatur magni. Error neque nam aspernatur optio. Sit similique debitis nemo et expedita.','published',NULL,'Botble\\ACL\\Models\\User',NULL,0,1,0,'2025-10-26 20:13:00','2025-10-26 20:13:00'),(5,'Travel Tips',4,'Itaque expedita assumenda error dolor tempora itaque ducimus. Sit nemo vel quisquam ut. Qui sed est omnis est.','published',NULL,'Botble\\ACL\\Models\\User',NULL,0,0,0,'2025-10-26 20:13:00','2025-10-26 20:13:00'),(6,'Hotel',0,'A aut qui in est ipsa autem magni. Eos sit omnis veritatis quasi et ratione animi. Ipsum autem a aut facilis iure laborum quo qui. Beatae aut unde eius similique nostrum.','published',NULL,'Botble\\ACL\\Models\\User',NULL,0,1,0,'2025-10-26 20:13:00','2025-10-26 20:13:00'),(7,'Nature',6,'Esse ut asperiores nobis ut. Esse iste voluptas doloremque porro. Enim ducimus rerum a iste molestiae. Molestiae aut hic tempora quae.','published',NULL,'Botble\\ACL\\Models\\User',NULL,0,0,0,'2025-10-26 20:13:00','2025-10-26 20:13:00');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories_translations`
--

DROP TABLE IF EXISTS `categories_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categories_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories_translations`
--

LOCK TABLES `categories_translations` WRITE;
/*!40000 ALTER TABLE `categories_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_id` bigint unsigned DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `record_id` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cities_slug_unique` (`slug`),
  KEY `idx_cities_name` (`name`),
  KEY `idx_cities_state_status` (`state_id`,`status`),
  KEY `idx_cities_status` (`status`),
  KEY `idx_cities_state_id` (`state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cities`
--

LOCK TABLES `cities` WRITE;
/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
INSERT INTO `cities` VALUES (1,'Paris','paris',1,1,NULL,0,'locations/location1.png',0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL),(2,'London','london',2,2,NULL,0,'locations/location2.png',0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL),(3,'New York','new-york',3,3,NULL,0,'locations/location3.png',0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL),(4,'New York','new-york-1',4,4,NULL,0,'locations/location4.png',0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL),(5,'Copenhagen','copenhagen',5,5,NULL,0,'locations/location5.png',0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL),(6,'Berlin','berlin',6,6,NULL,0,'locations/location6.png',0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL);
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cities_translations`
--

DROP TABLE IF EXISTS `cities_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cities_id` bigint unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`cities_id`),
  KEY `idx_cities_trans_city_lang` (`cities_id`,`lang_code`),
  KEY `idx_cities_trans_name` (`name`),
  KEY `idx_cities_trans_cities_id` (`cities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cities_translations`
--

LOCK TABLES `cities_translations` WRITE;
/*!40000 ALTER TABLE `cities_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `cities_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_custom_field_options`
--

DROP TABLE IF EXISTS `contact_custom_field_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_custom_field_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_field_id` bigint unsigned NOT NULL,
  `label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int NOT NULL DEFAULT '999',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_custom_field_options`
--

LOCK TABLES `contact_custom_field_options` WRITE;
/*!40000 ALTER TABLE `contact_custom_field_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_custom_field_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_custom_field_options_translations`
--

DROP TABLE IF EXISTS `contact_custom_field_options_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_custom_field_options_translations` (
  `contact_custom_field_options_id` bigint unsigned NOT NULL,
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`contact_custom_field_options_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_custom_field_options_translations`
--

LOCK TABLES `contact_custom_field_options_translations` WRITE;
/*!40000 ALTER TABLE `contact_custom_field_options_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_custom_field_options_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_custom_fields`
--

DROP TABLE IF EXISTS `contact_custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_custom_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `placeholder` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int NOT NULL DEFAULT '999',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_custom_fields`
--

LOCK TABLES `contact_custom_fields` WRITE;
/*!40000 ALTER TABLE `contact_custom_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_custom_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_custom_fields_translations`
--

DROP TABLE IF EXISTS `contact_custom_fields_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_custom_fields_translations` (
  `contact_custom_fields_id` bigint unsigned NOT NULL,
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placeholder` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`contact_custom_fields_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_custom_fields_translations`
--

LOCK TABLES `contact_custom_fields_translations` WRITE;
/*!40000 ALTER TABLE `contact_custom_fields_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_custom_fields_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_replies`
--

DROP TABLE IF EXISTS `contact_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_replies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_replies`
--

LOCK TABLES `contact_replies` WRITE;
/*!40000 ALTER TABLE `contact_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `custom_fields` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unread',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES (1,'Rene Hoeger','hector50@example.org','+12513458020','412 Olson Parkways Suite 043\nErdmanshire, PA 15389-5133','Eligendi dolore dolore est nihil itaque.','Aliquid quibusdam hic a dolore sint ipsa enim. Atque animi incidunt excepturi unde sit. Id harum perspiciatis voluptatum. Explicabo illo et esse veritatis doloremque alias aut. Magnam quia maxime enim porro et labore ratione reprehenderit. Culpa reiciendis eos minus. Non labore architecto quaerat doloribus. Eaque et velit debitis nulla saepe molestiae. Eos dolore aut quae nulla. Sed sed vero laboriosam. Quia officiis ea consequatur harum rerum necessitatibus dolor.',NULL,'read','2025-10-26 20:13:00','2025-10-26 20:13:00'),(2,'Myrtice Adams','nitzsche.mellie@example.com','+14322494423','381 Sylvia Ramp\nLake Destanystad, ND 51648-8813','Vel ipsam ut aperiam cupiditate autem voluptate.','Quod natus cupiditate earum ratione et natus ut saepe. Commodi sint deserunt ipsam sapiente ex velit consequatur. Sit vero qui odit doloribus mollitia. Quod dolores officia at aut quia quas. Officiis facilis accusantium aut eligendi dolores consequatur minus. Eum perferendis quia dolore ducimus doloremque. Enim et ipsam tempora id nesciunt. Autem sint aperiam et.',NULL,'unread','2025-10-26 20:13:00','2025-10-26 20:13:00'),(3,'Dr. Bryce Batz','mherman@example.net','+17864669762','53923 Gorczany Manors\nEast Allene, MN 50036-4484','Numquam dolore quia neque maxime reiciendis.','Ab modi pariatur vel qui. Voluptatem aperiam sit et nihil at quae et. Nulla sint inventore iste ex. Accusantium libero mollitia omnis dolorum sed velit velit. Nam iste aspernatur nemo iure ea libero. Corporis ipsum aut cum. Atque vero ex possimus nam nulla voluptatem recusandae. Aspernatur voluptas illum atque sequi ea mollitia voluptate.',NULL,'read','2025-10-26 20:13:00','2025-10-26 20:13:00'),(4,'Dr. Korey Schoen Jr.','samara.corkery@example.com','+15416476322','29159 Bode Skyway\nWest Bonitaside, MI 25113','Quasi et aut quis earum quia impedit qui.','Doloribus id itaque nam assumenda voluptatem. Veniam ea neque veritatis et amet perferendis rerum provident. Quis alias ea quo quod saepe voluptas. Hic quam nobis mollitia. Rerum totam laborum similique. Accusantium nulla sed consequuntur error et sint. Nisi quis sunt soluta illum. Nulla quibusdam culpa neque odit ut aut omnis. Repudiandae inventore ipsam molestiae eligendi reprehenderit ratione laboriosam. Cupiditate aut impedit esse qui occaecati cupiditate.',NULL,'read','2025-10-26 20:13:00','2025-10-26 20:13:00'),(5,'Bernadine Dare','jordi.batz@example.net','+19856956211','608 Phyllis Avenue\nNorth Alphonsoport, MO 65469','Facilis magni dolorem id aut est natus assumenda.','Accusantium unde impedit harum eveniet optio corrupti consequuntur. Eveniet ipsum vitae qui rem accusamus placeat. Tempore harum possimus in et quia minima itaque. Ullam vel asperiores unde ut incidunt et doloremque et. Eos aut est animi. Neque hic at maiores asperiores dolor. Beatae magni ut reiciendis et fuga quis delectus aperiam. Qui quisquam omnis odio ut est ipsa. Omnis delectus velit quos omnis. Ut quaerat minima ea in.',NULL,'read','2025-10-26 20:13:00','2025-10-26 20:13:00'),(6,'Christa Ruecker','jose69@example.com','+16075364284','6902 Crist Springs Apt. 058\nWest Davin, NC 00688','Nisi tempora consequatur voluptate non quo.','Perferendis sapiente et rerum ab quos. Temporibus soluta quaerat id commodi corrupti magni accusamus. Eum doloremque esse est ut corrupti eveniet. Repudiandae vel qui quia debitis voluptatem. Voluptatem hic voluptas impedit quia quidem expedita voluptas. Sed consequuntur earum unde officiis ea corporis vel. Unde et provident vero ut. Neque numquam possimus saepe error veniam. Non odio et et quia. Nobis autem blanditiis expedita ipsum. Et ipsa voluptatem laboriosam et quasi.',NULL,'read','2025-10-26 20:13:00','2025-10-26 20:13:00'),(7,'Nels Schaefer','malika.littel@example.net','+17628015629','461 Darrin Center\nBrekkeland, DC 65712-1963','Nulla laborum earum voluptatem neque.','Quis quidem molestias rem aut eligendi. Facilis quasi mollitia labore dolorem autem dolor expedita. Magni voluptatem necessitatibus ut et. Inventore voluptas ut aspernatur quos impedit culpa aut ut. Amet veritatis excepturi et rerum nam. At ut qui non ad et a. Numquam voluptatem in culpa eos ut enim iure. Exercitationem ut quisquam quia vitae et placeat nisi.',NULL,'read','2025-10-26 20:13:00','2025-10-26 20:13:00'),(8,'Marlon Howe','guiseppe.schaden@example.org','+17626903219','6307 Clement Valley Suite 053\nLonniemouth, CA 48276-2474','Quisquam et itaque sit.','Cupiditate quas voluptatibus necessitatibus magni. Quas placeat consequuntur modi harum velit nostrum fugit. Fuga illo similique natus voluptatem doloribus sapiente quam. Non aut quibusdam est est. Et rem et iste. Et iure voluptas est et necessitatibus voluptatem. Modi voluptate dolorum sit quam voluptatum. Autem sint animi et dolorem eum quis suscipit. Ex odio in qui tempore dolores. Quo vitae aut voluptates tempore voluptatem. Fugit sint quis et eos quis quidem.',NULL,'unread','2025-10-26 20:13:00','2025-10-26 20:13:00'),(9,'Carmella Towne','ukuhlman@example.com','+18457088622','850 Izabella Crescent Suite 172\nHammesville, LA 43524','Fuga est quidem minima.','Adipisci iure accusantium sapiente perferendis debitis et. Consequuntur ad nemo ad quidem. Possimus aliquam nam quidem. Rerum dolorem porro eos et deleniti est. Distinctio rerum sit eius qui nulla veniam et. Aliquam ipsum veritatis animi dolor rerum ut ut. Et nulla mollitia sed nisi tempora debitis consequatur. Officiis eos sit quibusdam quisquam temporibus. Aut et illo repellendus voluptatum sit odio. Dolor molestias ut sed ut. Et delectus inventore est sit sunt quam.',NULL,'unread','2025-10-26 20:13:00','2025-10-26 20:13:00'),(10,'Mara Hauck','valentin72@example.org','+15305953006','515 Cecelia Corner Apt. 636\nKrystalfort, VA 51488','Quam id odit officiis accusantium enim maxime.','Quia consequatur dolor soluta. Facere ut quia voluptates nisi quaerat inventore. Aut aspernatur rem in sit ea at qui. Unde quia enim consectetur a accusantium. Quas dolorum quisquam magni ut. Qui eum rem aut assumenda molestiae. Numquam quam voluptatem ut voluptas. Quod perspiciatis deserunt esse quos omnis. Repudiandae nam eligendi rem mollitia. Ut omnis impedit temporibus et vel. Et cumque magnam quos dolores occaecati at. Et voluptas ut illum quia commodi est.',NULL,'unread','2025-10-26 20:13:00','2025-10-26 20:13:00');
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nationality` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_countries_name` (`name`),
  KEY `idx_countries_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES (1,'France','French',0,NULL,0,'published','2025-10-26 20:13:02',NULL,'FRA'),(2,'England','English',0,NULL,0,'published','2025-10-26 20:13:02',NULL,'UK'),(3,'USA','Americans',0,NULL,0,'published','2025-10-26 20:13:02',NULL,'US'),(4,'Holland','Dutch',0,NULL,0,'published','2025-10-26 20:13:02',NULL,'HL'),(5,'Denmark','Danish',0,NULL,0,'published','2025-10-26 20:13:02',NULL,'DN'),(6,'Germany','Danish',0,NULL,0,'published','2025-10-26 20:13:02',NULL,'DN');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries_translations`
--

DROP TABLE IF EXISTS `countries_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `countries_id` bigint unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`countries_id`),
  KEY `idx_countries_trans_country_lang` (`countries_id`,`lang_code`),
  KEY `idx_countries_trans_name` (`name`),
  KEY `idx_countries_trans_countries_id` (`countries_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries_translations`
--

LOCK TABLES `countries_translations` WRITE;
/*!40000 ALTER TABLE `countries_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `countries_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dashboard_widget_settings`
--

DROP TABLE IF EXISTS `dashboard_widget_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widget_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `settings` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned NOT NULL,
  `widget_id` bigint unsigned NOT NULL,
  `order` tinyint unsigned NOT NULL DEFAULT '0',
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dashboard_widget_settings_user_id_index` (`user_id`),
  KEY `dashboard_widget_settings_widget_id_index` (`widget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_widget_settings`
--

LOCK TABLES `dashboard_widget_settings` WRITE;
/*!40000 ALTER TABLE `dashboard_widget_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `dashboard_widget_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dashboard_widgets`
--

DROP TABLE IF EXISTS `dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widgets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_widgets`
--

LOCK TABLES `dashboard_widgets` WRITE;
/*!40000 ALTER TABLE `dashboard_widgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `dashboard_widgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_tokens`
--

DROP TABLE IF EXISTS `device_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `device_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_tokens_token_unique` (`token`),
  KEY `device_tokens_user_type_user_id_index` (`user_type`,`user_id`),
  KEY `device_tokens_platform_is_active_index` (`platform`,`is_active`),
  KEY `device_tokens_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device_tokens`
--

LOCK TABLES `device_tokens` WRITE;
/*!40000 ALTER TABLE `device_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `device_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq_categories`
--

DROP TABLE IF EXISTS `faq_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq_categories`
--

LOCK TABLES `faq_categories` WRITE;
/*!40000 ALTER TABLE `faq_categories` DISABLE KEYS */;
INSERT INTO `faq_categories` VALUES (1,'General',0,'published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL),(2,'Buying',1,'published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL),(3,'Payment',2,'published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL),(4,'Support',3,'published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL);
/*!40000 ALTER TABLE `faq_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faq_categories_translations`
--

DROP TABLE IF EXISTS `faq_categories_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faq_categories_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `faq_categories_id` bigint unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`faq_categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faq_categories_translations`
--

LOCK TABLES `faq_categories_translations` WRITE;
/*!40000 ALTER TABLE `faq_categories_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `faq_categories_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `answer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'Where does it come from?','If several languages coalesce, the grammar of the resulting language is more simple and regular than that of the individual languages. The new common language will be more simple and regular than the existing European languages.',1,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(2,'How JobBox Work?','To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is. The European languages are members of the same family. Their separate existence is a myth.',1,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(3,'What is your shipping policy?','Everyone realizes why a new common language would be desirable: one could refuse to pay expensive translators. To achieve this, it would be necessary to have uniform grammar, pronunciation and more common words.',1,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(4,'Where To Place A FAQ Page','Just as the name suggests, a FAQ page is all about simple questions and answers. Gather common questions your customers have asked from your support team and include them in the FAQ, Use categories to organize questions related to specific topics.',1,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(5,'Why do we use it?','It will be as simple as Occidental; in fact, it will be Occidental. To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental.',1,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(6,'Where can I get some?','To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is. The European languages are members of the same family. Their separate existence is a myth.',1,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(7,'Where does it come from?','If several languages coalesce, the grammar of the resulting language is more simple and regular than that of the individual languages. The new common language will be more simple and regular than the existing European languages.',2,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(8,'How JobBox Work?','To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is. The European languages are members of the same family. Their separate existence is a myth.',2,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(9,'What is your shipping policy?','Everyone realizes why a new common language would be desirable: one could refuse to pay expensive translators. To achieve this, it would be necessary to have uniform grammar, pronunciation and more common words.',2,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(10,'Where To Place A FAQ Page','Just as the name suggests, a FAQ page is all about simple questions and answers. Gather common questions your customers have asked from your support team and include them in the FAQ, Use categories to organize questions related to specific topics.',2,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(11,'Why do we use it?','It will be as simple as Occidental; in fact, it will be Occidental. To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental.',2,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(12,'Where can I get some?','To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is. The European languages are members of the same family. Their separate existence is a myth.',2,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(13,'Where does it come from?','If several languages coalesce, the grammar of the resulting language is more simple and regular than that of the individual languages. The new common language will be more simple and regular than the existing European languages.',3,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(14,'How JobBox Work?','To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is. The European languages are members of the same family. Their separate existence is a myth.',3,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(15,'What is your shipping policy?','Everyone realizes why a new common language would be desirable: one could refuse to pay expensive translators. To achieve this, it would be necessary to have uniform grammar, pronunciation and more common words.',3,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(16,'Where To Place A FAQ Page','Just as the name suggests, a FAQ page is all about simple questions and answers. Gather common questions your customers have asked from your support team and include them in the FAQ, Use categories to organize questions related to specific topics.',3,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(17,'Why do we use it?','It will be as simple as Occidental; in fact, it will be Occidental. To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental.',3,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(18,'Where can I get some?','To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is. The European languages are members of the same family. Their separate existence is a myth.',3,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(19,'Where does it come from?','If several languages coalesce, the grammar of the resulting language is more simple and regular than that of the individual languages. The new common language will be more simple and regular than the existing European languages.',4,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(20,'How JobBox Work?','To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is. The European languages are members of the same family. Their separate existence is a myth.',4,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(21,'What is your shipping policy?','Everyone realizes why a new common language would be desirable: one could refuse to pay expensive translators. To achieve this, it would be necessary to have uniform grammar, pronunciation and more common words.',4,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(22,'Where To Place A FAQ Page','Just as the name suggests, a FAQ page is all about simple questions and answers. Gather common questions your customers have asked from your support team and include them in the FAQ, Use categories to organize questions related to specific topics.',4,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(23,'Why do we use it?','It will be as simple as Occidental; in fact, it will be Occidental. To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental.',4,'published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(24,'Where can I get some?','To an English person, it will seem like simplified English, as a skeptical Cambridge friend of mine told me what Occidental is. The European languages are members of the same family. Their separate existence is a myth.',4,'published','2025-10-26 20:13:26','2025-10-26 20:13:26');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs_translations`
--

DROP TABLE IF EXISTS `faqs_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `faqs_id` bigint unsigned NOT NULL,
  `question` text COLLATE utf8mb4_unicode_ci,
  `answer` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lang_code`,`faqs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs_translations`
--

LOCK TABLES `faqs_translations` WRITE;
/*!40000 ALTER TABLE `faqs_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `faqs_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `galleries`
--

DROP TABLE IF EXISTS `galleries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `galleries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_featured` tinyint unsigned NOT NULL DEFAULT '0',
  `order` tinyint unsigned NOT NULL DEFAULT '0',
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `galleries_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `galleries`
--

LOCK TABLES `galleries` WRITE;
/*!40000 ALTER TABLE `galleries` DISABLE KEYS */;
INSERT INTO `galleries` VALUES (1,'Perfect','Vel sint facilis non animi voluptate minus ad. Quo facilis sequi ex minima et. Est aspernatur quod debitis ipsam facilis excepturi.',1,0,'galleries/1.jpg',1,'published','2025-10-26 20:13:00','2025-10-26 20:13:00'),(2,'New Day','Blanditiis sint expedita voluptatem aut. Consequatur aliquam omnis assumenda quisquam soluta nulla. Aliquid non dolor excepturi id.',1,0,'galleries/2.jpg',1,'published','2025-10-26 20:13:00','2025-10-26 20:13:00'),(3,'Happy Day','Quia aut quaerat officia suscipit dolorem quas aliquid. Labore et excepturi distinctio odio nihil. Sequi ut quia deserunt quis ipsa adipisci sint.',1,0,'galleries/3.jpg',1,'published','2025-10-26 20:13:00','2025-10-26 20:13:00'),(4,'Nature','Eos quos explicabo occaecati laborum. Ut quaerat quis cupiditate eaque vero deleniti.',1,0,'galleries/4.jpg',1,'published','2025-10-26 20:13:00','2025-10-26 20:13:00'),(5,'Morning','Necessitatibus a quis quod itaque. Quod eaque dolor et voluptas tenetur ipsum sed. Labore id perspiciatis illo asperiores quis harum.',1,0,'galleries/5.jpg',1,'published','2025-10-26 20:13:00','2025-10-26 20:13:00'),(6,'Photography','Et corporis perferendis nihil nulla dolores. Facilis ratione recusandae magni magnam qui et. Dolor enim eum ab soluta dolor veniam veniam.',1,0,'galleries/6.jpg',1,'published','2025-10-26 20:13:00','2025-10-26 20:13:00');
/*!40000 ALTER TABLE `galleries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `galleries_translations`
--

DROP TABLE IF EXISTS `galleries_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `galleries_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `galleries_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lang_code`,`galleries_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `galleries_translations`
--

LOCK TABLES `galleries_translations` WRITE;
/*!40000 ALTER TABLE `galleries_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `galleries_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_meta`
--

DROP TABLE IF EXISTS `gallery_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery_meta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `images` text COLLATE utf8mb4_unicode_ci,
  `reference_id` bigint unsigned NOT NULL,
  `reference_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gallery_meta_reference_id_index` (`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_meta`
--

LOCK TABLES `gallery_meta` WRITE;
/*!40000 ALTER TABLE `gallery_meta` DISABLE KEYS */;
INSERT INTO `gallery_meta` VALUES (1,'[{\"img\":\"galleries\\/1.jpg\",\"description\":\"Dolor omnis enim hic explicabo. Quo non dolorum fugiat ut dolor a. Ut laboriosam explicabo assumenda.\"},{\"img\":\"galleries\\/2.jpg\",\"description\":\"Non molestiae dicta autem. Aut perspiciatis et numquam dolor. Qui corrupti optio nemo reprehenderit. Est laudantium minima sed veritatis.\"},{\"img\":\"galleries\\/3.jpg\",\"description\":\"Quisquam et quibusdam dolor sed provident. Vero cum corrupti nihil et minus. Et autem id consequuntur. Consequatur nostrum nesciunt eos harum.\"},{\"img\":\"galleries\\/4.jpg\",\"description\":\"Qui ex aspernatur molestiae quisquam. Sed aut architecto natus dolorem ea voluptate. Fuga fugit esse eius ex at explicabo.\"},{\"img\":\"galleries\\/5.jpg\",\"description\":\"Totam dolores aut architecto voluptas. Esse odio omnis autem ratione error et et odit. Dolorum veritatis beatae voluptas non velit qui quam minima.\"},{\"img\":\"galleries\\/6.jpg\",\"description\":\"Fugiat vel quis ut. Nihil sapiente velit ex dolorum. Quidem recusandae quo veniam impedit. Voluptatibus in voluptatibus enim sed nobis.\"},{\"img\":\"galleries\\/7.jpg\",\"description\":\"Veniam nisi quasi at est quis deserunt. Amet sed nisi a voluptate debitis. Neque esse reprehenderit itaque sit asperiores.\"},{\"img\":\"galleries\\/8.jpg\",\"description\":\"Qui explicabo dolores sint sed fuga. Illum soluta inventore culpa qui cum explicabo accusamus ullam.\"},{\"img\":\"galleries\\/9.jpg\",\"description\":\"Repellendus animi sit sit. Asperiores omnis autem eaque explicabo expedita amet est. Quas ut numquam maiores qui.\"}]',1,'Botble\\Gallery\\Models\\Gallery','2025-10-26 20:13:00','2025-10-26 20:13:00'),(2,'[{\"img\":\"galleries\\/1.jpg\",\"description\":\"Dolor omnis enim hic explicabo. Quo non dolorum fugiat ut dolor a. Ut laboriosam explicabo assumenda.\"},{\"img\":\"galleries\\/2.jpg\",\"description\":\"Non molestiae dicta autem. Aut perspiciatis et numquam dolor. Qui corrupti optio nemo reprehenderit. Est laudantium minima sed veritatis.\"},{\"img\":\"galleries\\/3.jpg\",\"description\":\"Quisquam et quibusdam dolor sed provident. Vero cum corrupti nihil et minus. Et autem id consequuntur. Consequatur nostrum nesciunt eos harum.\"},{\"img\":\"galleries\\/4.jpg\",\"description\":\"Qui ex aspernatur molestiae quisquam. Sed aut architecto natus dolorem ea voluptate. Fuga fugit esse eius ex at explicabo.\"},{\"img\":\"galleries\\/5.jpg\",\"description\":\"Totam dolores aut architecto voluptas. Esse odio omnis autem ratione error et et odit. Dolorum veritatis beatae voluptas non velit qui quam minima.\"},{\"img\":\"galleries\\/6.jpg\",\"description\":\"Fugiat vel quis ut. Nihil sapiente velit ex dolorum. Quidem recusandae quo veniam impedit. Voluptatibus in voluptatibus enim sed nobis.\"},{\"img\":\"galleries\\/7.jpg\",\"description\":\"Veniam nisi quasi at est quis deserunt. Amet sed nisi a voluptate debitis. Neque esse reprehenderit itaque sit asperiores.\"},{\"img\":\"galleries\\/8.jpg\",\"description\":\"Qui explicabo dolores sint sed fuga. Illum soluta inventore culpa qui cum explicabo accusamus ullam.\"},{\"img\":\"galleries\\/9.jpg\",\"description\":\"Repellendus animi sit sit. Asperiores omnis autem eaque explicabo expedita amet est. Quas ut numquam maiores qui.\"}]',2,'Botble\\Gallery\\Models\\Gallery','2025-10-26 20:13:00','2025-10-26 20:13:00'),(3,'[{\"img\":\"galleries\\/1.jpg\",\"description\":\"Dolor omnis enim hic explicabo. Quo non dolorum fugiat ut dolor a. Ut laboriosam explicabo assumenda.\"},{\"img\":\"galleries\\/2.jpg\",\"description\":\"Non molestiae dicta autem. Aut perspiciatis et numquam dolor. Qui corrupti optio nemo reprehenderit. Est laudantium minima sed veritatis.\"},{\"img\":\"galleries\\/3.jpg\",\"description\":\"Quisquam et quibusdam dolor sed provident. Vero cum corrupti nihil et minus. Et autem id consequuntur. Consequatur nostrum nesciunt eos harum.\"},{\"img\":\"galleries\\/4.jpg\",\"description\":\"Qui ex aspernatur molestiae quisquam. Sed aut architecto natus dolorem ea voluptate. Fuga fugit esse eius ex at explicabo.\"},{\"img\":\"galleries\\/5.jpg\",\"description\":\"Totam dolores aut architecto voluptas. Esse odio omnis autem ratione error et et odit. Dolorum veritatis beatae voluptas non velit qui quam minima.\"},{\"img\":\"galleries\\/6.jpg\",\"description\":\"Fugiat vel quis ut. Nihil sapiente velit ex dolorum. Quidem recusandae quo veniam impedit. Voluptatibus in voluptatibus enim sed nobis.\"},{\"img\":\"galleries\\/7.jpg\",\"description\":\"Veniam nisi quasi at est quis deserunt. Amet sed nisi a voluptate debitis. Neque esse reprehenderit itaque sit asperiores.\"},{\"img\":\"galleries\\/8.jpg\",\"description\":\"Qui explicabo dolores sint sed fuga. Illum soluta inventore culpa qui cum explicabo accusamus ullam.\"},{\"img\":\"galleries\\/9.jpg\",\"description\":\"Repellendus animi sit sit. Asperiores omnis autem eaque explicabo expedita amet est. Quas ut numquam maiores qui.\"}]',3,'Botble\\Gallery\\Models\\Gallery','2025-10-26 20:13:00','2025-10-26 20:13:00'),(4,'[{\"img\":\"galleries\\/1.jpg\",\"description\":\"Dolor omnis enim hic explicabo. Quo non dolorum fugiat ut dolor a. Ut laboriosam explicabo assumenda.\"},{\"img\":\"galleries\\/2.jpg\",\"description\":\"Non molestiae dicta autem. Aut perspiciatis et numquam dolor. Qui corrupti optio nemo reprehenderit. Est laudantium minima sed veritatis.\"},{\"img\":\"galleries\\/3.jpg\",\"description\":\"Quisquam et quibusdam dolor sed provident. Vero cum corrupti nihil et minus. Et autem id consequuntur. Consequatur nostrum nesciunt eos harum.\"},{\"img\":\"galleries\\/4.jpg\",\"description\":\"Qui ex aspernatur molestiae quisquam. Sed aut architecto natus dolorem ea voluptate. Fuga fugit esse eius ex at explicabo.\"},{\"img\":\"galleries\\/5.jpg\",\"description\":\"Totam dolores aut architecto voluptas. Esse odio omnis autem ratione error et et odit. Dolorum veritatis beatae voluptas non velit qui quam minima.\"},{\"img\":\"galleries\\/6.jpg\",\"description\":\"Fugiat vel quis ut. Nihil sapiente velit ex dolorum. Quidem recusandae quo veniam impedit. Voluptatibus in voluptatibus enim sed nobis.\"},{\"img\":\"galleries\\/7.jpg\",\"description\":\"Veniam nisi quasi at est quis deserunt. Amet sed nisi a voluptate debitis. Neque esse reprehenderit itaque sit asperiores.\"},{\"img\":\"galleries\\/8.jpg\",\"description\":\"Qui explicabo dolores sint sed fuga. Illum soluta inventore culpa qui cum explicabo accusamus ullam.\"},{\"img\":\"galleries\\/9.jpg\",\"description\":\"Repellendus animi sit sit. Asperiores omnis autem eaque explicabo expedita amet est. Quas ut numquam maiores qui.\"}]',4,'Botble\\Gallery\\Models\\Gallery','2025-10-26 20:13:00','2025-10-26 20:13:00'),(5,'[{\"img\":\"galleries\\/1.jpg\",\"description\":\"Dolor omnis enim hic explicabo. Quo non dolorum fugiat ut dolor a. Ut laboriosam explicabo assumenda.\"},{\"img\":\"galleries\\/2.jpg\",\"description\":\"Non molestiae dicta autem. Aut perspiciatis et numquam dolor. Qui corrupti optio nemo reprehenderit. Est laudantium minima sed veritatis.\"},{\"img\":\"galleries\\/3.jpg\",\"description\":\"Quisquam et quibusdam dolor sed provident. Vero cum corrupti nihil et minus. Et autem id consequuntur. Consequatur nostrum nesciunt eos harum.\"},{\"img\":\"galleries\\/4.jpg\",\"description\":\"Qui ex aspernatur molestiae quisquam. Sed aut architecto natus dolorem ea voluptate. Fuga fugit esse eius ex at explicabo.\"},{\"img\":\"galleries\\/5.jpg\",\"description\":\"Totam dolores aut architecto voluptas. Esse odio omnis autem ratione error et et odit. Dolorum veritatis beatae voluptas non velit qui quam minima.\"},{\"img\":\"galleries\\/6.jpg\",\"description\":\"Fugiat vel quis ut. Nihil sapiente velit ex dolorum. Quidem recusandae quo veniam impedit. Voluptatibus in voluptatibus enim sed nobis.\"},{\"img\":\"galleries\\/7.jpg\",\"description\":\"Veniam nisi quasi at est quis deserunt. Amet sed nisi a voluptate debitis. Neque esse reprehenderit itaque sit asperiores.\"},{\"img\":\"galleries\\/8.jpg\",\"description\":\"Qui explicabo dolores sint sed fuga. Illum soluta inventore culpa qui cum explicabo accusamus ullam.\"},{\"img\":\"galleries\\/9.jpg\",\"description\":\"Repellendus animi sit sit. Asperiores omnis autem eaque explicabo expedita amet est. Quas ut numquam maiores qui.\"}]',5,'Botble\\Gallery\\Models\\Gallery','2025-10-26 20:13:00','2025-10-26 20:13:00'),(6,'[{\"img\":\"galleries\\/1.jpg\",\"description\":\"Dolor omnis enim hic explicabo. Quo non dolorum fugiat ut dolor a. Ut laboriosam explicabo assumenda.\"},{\"img\":\"galleries\\/2.jpg\",\"description\":\"Non molestiae dicta autem. Aut perspiciatis et numquam dolor. Qui corrupti optio nemo reprehenderit. Est laudantium minima sed veritatis.\"},{\"img\":\"galleries\\/3.jpg\",\"description\":\"Quisquam et quibusdam dolor sed provident. Vero cum corrupti nihil et minus. Et autem id consequuntur. Consequatur nostrum nesciunt eos harum.\"},{\"img\":\"galleries\\/4.jpg\",\"description\":\"Qui ex aspernatur molestiae quisquam. Sed aut architecto natus dolorem ea voluptate. Fuga fugit esse eius ex at explicabo.\"},{\"img\":\"galleries\\/5.jpg\",\"description\":\"Totam dolores aut architecto voluptas. Esse odio omnis autem ratione error et et odit. Dolorum veritatis beatae voluptas non velit qui quam minima.\"},{\"img\":\"galleries\\/6.jpg\",\"description\":\"Fugiat vel quis ut. Nihil sapiente velit ex dolorum. Quidem recusandae quo veniam impedit. Voluptatibus in voluptatibus enim sed nobis.\"},{\"img\":\"galleries\\/7.jpg\",\"description\":\"Veniam nisi quasi at est quis deserunt. Amet sed nisi a voluptate debitis. Neque esse reprehenderit itaque sit asperiores.\"},{\"img\":\"galleries\\/8.jpg\",\"description\":\"Qui explicabo dolores sint sed fuga. Illum soluta inventore culpa qui cum explicabo accusamus ullam.\"},{\"img\":\"galleries\\/9.jpg\",\"description\":\"Repellendus animi sit sit. Asperiores omnis autem eaque explicabo expedita amet est. Quas ut numquam maiores qui.\"}]',6,'Botble\\Gallery\\Models\\Gallery','2025-10-26 20:13:00','2025-10-26 20:13:00');
/*!40000 ALTER TABLE `gallery_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_meta_translations`
--

DROP TABLE IF EXISTS `gallery_meta_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery_meta_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gallery_meta_id` bigint unsigned NOT NULL,
  `images` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lang_code`,`gallery_meta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_meta_translations`
--

LOCK TABLES `gallery_meta_translations` WRITE;
/*!40000 ALTER TABLE `gallery_meta_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `gallery_meta_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_account_activity_logs`
--

DROP TABLE IF EXISTS `jb_account_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_account_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `reference_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(39) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_account_activity_logs_account_id_index` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_account_activity_logs`
--

LOCK TABLES `jb_account_activity_logs` WRITE;
/*!40000 ALTER TABLE `jb_account_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_account_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_account_educations`
--

DROP TABLE IF EXISTS `jb_account_educations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_account_educations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `school` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `specialized` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` date NOT NULL,
  `ended_at` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_account_educations`
--

LOCK TABLES `jb_account_educations` WRITE;
/*!40000 ALTER TABLE `jb_account_educations` DISABLE KEYS */;
INSERT INTO `jb_account_educations` VALUES (1,'American Institute of Health Technology',2,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:05','2025-10-26 20:13:05'),(2,'Antioch University McGregor',6,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:06','2025-10-26 20:13:06'),(3,'Associated Mennonite Biblical Seminary',7,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:06','2025-10-26 20:13:06'),(4,'Gateway Technical College',9,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:07','2025-10-26 20:13:07'),(5,'The University of the State of Alabama',10,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:07','2025-10-26 20:13:07'),(6,'Antioch University McGregor',11,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:07','2025-10-26 20:13:07'),(7,'Antioch University McGregor',13,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:08','2025-10-26 20:13:08'),(8,'Associated Mennonite Biblical Seminary',14,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:08','2025-10-26 20:13:08'),(9,'Antioch University McGregor',18,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:09','2025-10-26 20:13:09'),(10,'The University of the State of Alabama',19,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:09','2025-10-26 20:13:09'),(11,'The University of the State of Alabama',20,'Art History','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:09','2025-10-26 20:13:09'),(12,'Adams State College',21,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:09','2025-10-26 20:13:09'),(13,'Antioch University McGregor',22,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:10','2025-10-26 20:13:10'),(14,'American Institute of Health Technology',24,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:10','2025-10-26 20:13:10'),(15,'American Institute of Health Technology',25,'Art History','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:10','2025-10-26 20:13:10'),(16,'American Institute of Health Technology',26,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:10','2025-10-26 20:13:10'),(17,'Antioch University McGregor',27,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:11','2025-10-26 20:13:11'),(18,'Antioch University McGregor',33,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:12','2025-10-26 20:13:12'),(19,'Gateway Technical College',35,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:12','2025-10-26 20:13:12'),(20,'Adams State College',37,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:13','2025-10-26 20:13:13'),(21,'Associated Mennonite Biblical Seminary',40,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:13','2025-10-26 20:13:13'),(22,'Antioch University McGregor',41,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:13','2025-10-26 20:13:13'),(23,'American Institute of Health Technology',42,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:14','2025-10-26 20:13:14'),(24,'Associated Mennonite Biblical Seminary',43,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:14','2025-10-26 20:13:14'),(25,'Gateway Technical College',47,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:15','2025-10-26 20:13:15'),(26,'Adams State College',49,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:15','2025-10-26 20:13:15'),(27,'Adams State College',50,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:15','2025-10-26 20:13:15'),(28,'The University of the State of Alabama',51,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:15','2025-10-26 20:13:15'),(29,'Antioch University McGregor',53,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:16','2025-10-26 20:13:16'),(30,'Gateway Technical College',55,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:16','2025-10-26 20:13:16'),(31,'Associated Mennonite Biblical Seminary',58,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:17','2025-10-26 20:13:17'),(32,'The University of the State of Alabama',59,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:17','2025-10-26 20:13:17'),(33,'Associated Mennonite Biblical Seminary',60,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:17','2025-10-26 20:13:17'),(34,'Associated Mennonite Biblical Seminary',61,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:18','2025-10-26 20:13:18'),(35,'American Institute of Health Technology',63,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:18','2025-10-26 20:13:18'),(36,'Gateway Technical College',64,'Art History','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:18','2025-10-26 20:13:18'),(37,'Gateway Technical College',65,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:18','2025-10-26 20:13:18'),(38,'Antioch University McGregor',66,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:19','2025-10-26 20:13:19'),(39,'Associated Mennonite Biblical Seminary',67,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:19','2025-10-26 20:13:19'),(40,'American Institute of Health Technology',68,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:19','2025-10-26 20:13:19'),(41,'The University of the State of Alabama',71,'Art History','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:20','2025-10-26 20:13:20'),(42,'Antioch University McGregor',73,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:20','2025-10-26 20:13:20'),(43,'The University of the State of Alabama',74,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:20','2025-10-26 20:13:20'),(44,'Adams State College',75,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:21','2025-10-26 20:13:21'),(45,'Antioch University McGregor',76,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:21','2025-10-26 20:13:21'),(46,'Associated Mennonite Biblical Seminary',77,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:21','2025-10-26 20:13:21'),(47,'The University of the State of Alabama',80,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:22','2025-10-26 20:13:22'),(48,'Associated Mennonite Biblical Seminary',81,'Art History','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:22','2025-10-26 20:13:22'),(49,'Gateway Technical College',82,'Art History','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:22','2025-10-26 20:13:22'),(50,'Gateway Technical College',86,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:23','2025-10-26 20:13:23'),(51,'Gateway Technical College',87,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:23','2025-10-26 20:13:23'),(52,'The University of the State of Alabama',88,'Art History','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:23','2025-10-26 20:13:23'),(53,'Associated Mennonite Biblical Seminary',93,'Economics','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:24','2025-10-26 20:13:24'),(54,'American Institute of Health Technology',94,'Art History','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:24','2025-10-26 20:13:24'),(55,'American Institute of Health Technology',95,'Culture and Technology Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:25','2025-10-26 20:13:25'),(56,'The University of the State of Alabama',96,'Anthropology','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:25','2025-10-26 20:13:25'),(57,'The University of the State of Alabama',97,'Classical Studies','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:25','2025-10-26 20:13:25');
/*!40000 ALTER TABLE `jb_account_educations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_account_experiences`
--

DROP TABLE IF EXISTS `jb_account_experiences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_account_experiences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` date NOT NULL,
  `ended_at` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_account_experiences`
--

LOCK TABLES `jb_account_experiences` WRITE;
/*!40000 ALTER TABLE `jb_account_experiences` DISABLE KEYS */;
INSERT INTO `jb_account_experiences` VALUES (1,'GameDay Catering',2,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:05','2025-10-26 20:13:05'),(2,'Darwin Travel',6,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:06','2025-10-26 20:13:06'),(3,'Spa Paragon',7,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:06','2025-10-26 20:13:06'),(4,'Exploration Kids',9,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:07','2025-10-26 20:13:07'),(5,'Darwin Travel',10,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:07','2025-10-26 20:13:07'),(6,'Darwin Travel',11,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:07','2025-10-26 20:13:07'),(7,'Darwin Travel',13,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:08','2025-10-26 20:13:08'),(8,'Darwin Travel',14,'Marketing Coordinator','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:08','2025-10-26 20:13:08'),(9,'GameDay Catering',18,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:09','2025-10-26 20:13:09'),(10,'Exploration Kids',19,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:09','2025-10-26 20:13:09'),(11,'Party Plex',20,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:09','2025-10-26 20:13:09'),(12,'Darwin Travel',21,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:09','2025-10-26 20:13:09'),(13,'Darwin Travel',22,'Marketing Coordinator','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:10','2025-10-26 20:13:10'),(14,'Party Plex',24,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:10','2025-10-26 20:13:10'),(15,'Exploration Kids',25,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:10','2025-10-26 20:13:10'),(16,'Spa Paragon',26,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:10','2025-10-26 20:13:10'),(17,'Darwin Travel',27,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:11','2025-10-26 20:13:11'),(18,'Exploration Kids',33,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:12','2025-10-26 20:13:12'),(19,'Exploration Kids',35,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:12','2025-10-26 20:13:12'),(20,'Party Plex',37,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:13','2025-10-26 20:13:13'),(21,'GameDay Catering',40,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:13','2025-10-26 20:13:13'),(22,'GameDay Catering',41,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:13','2025-10-26 20:13:13'),(23,'Party Plex',42,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:14','2025-10-26 20:13:14'),(24,'Party Plex',43,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:14','2025-10-26 20:13:14'),(25,'Party Plex',47,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:15','2025-10-26 20:13:15'),(26,'GameDay Catering',49,'Marketing Coordinator','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:15','2025-10-26 20:13:15'),(27,'Spa Paragon',50,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:15','2025-10-26 20:13:15'),(28,'GameDay Catering',51,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:15','2025-10-26 20:13:15'),(29,'Party Plex',53,'Marketing Coordinator','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:16','2025-10-26 20:13:16'),(30,'Darwin Travel',55,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:16','2025-10-26 20:13:16'),(31,'Party Plex',58,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:17','2025-10-26 20:13:17'),(32,'Exploration Kids',59,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:17','2025-10-26 20:13:17'),(33,'GameDay Catering',60,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:17','2025-10-26 20:13:17'),(34,'GameDay Catering',61,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:18','2025-10-26 20:13:18'),(35,'Darwin Travel',63,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:18','2025-10-26 20:13:18'),(36,'Darwin Travel',64,'Marketing Coordinator','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:18','2025-10-26 20:13:18'),(37,'Spa Paragon',65,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:18','2025-10-26 20:13:18'),(38,'Party Plex',66,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:19','2025-10-26 20:13:19'),(39,'Darwin Travel',67,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:19','2025-10-26 20:13:19'),(40,'Exploration Kids',68,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:19','2025-10-26 20:13:19'),(41,'Spa Paragon',71,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:20','2025-10-26 20:13:20'),(42,'GameDay Catering',73,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:20','2025-10-26 20:13:20'),(43,'GameDay Catering',74,'Marketing Coordinator','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:20','2025-10-26 20:13:20'),(44,'Spa Paragon',75,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:21','2025-10-26 20:13:21'),(45,'GameDay Catering',76,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:21','2025-10-26 20:13:21'),(46,'Darwin Travel',77,'Marketing Coordinator','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:21','2025-10-26 20:13:21'),(47,'Exploration Kids',80,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:22','2025-10-26 20:13:22'),(48,'Party Plex',81,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:22','2025-10-26 20:13:22'),(49,'Darwin Travel',82,'Web Designer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:22','2025-10-26 20:13:22'),(50,'GameDay Catering',86,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:23','2025-10-26 20:13:23'),(51,'Spa Paragon',87,'Marketing Coordinator','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:23','2025-10-26 20:13:23'),(52,'Spa Paragon',88,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:23','2025-10-26 20:13:23'),(53,'GameDay Catering',93,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:24','2025-10-26 20:13:24'),(54,'Party Plex',94,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:24','2025-10-26 20:13:24'),(55,'Party Plex',95,'Project Manager','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:25','2025-10-26 20:13:25'),(56,'Spa Paragon',96,'President of Sales','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:25','2025-10-26 20:13:25'),(57,'Exploration Kids',97,'Dog Trainer','2025-10-27','2025-10-27','There are many variations of passages of available, but the majority alteration in some form.\n                As a highly skilled and successful product development and design specialist with more than 4 Years of\n                My experience','2025-10-26 20:13:25','2025-10-26 20:13:25');
/*!40000 ALTER TABLE `jb_account_experiences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_account_favorite_skills`
--

DROP TABLE IF EXISTS `jb_account_favorite_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_account_favorite_skills` (
  `skill_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`skill_id`,`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_account_favorite_skills`
--

LOCK TABLES `jb_account_favorite_skills` WRITE;
/*!40000 ALTER TABLE `jb_account_favorite_skills` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_account_favorite_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_account_favorite_tags`
--

DROP TABLE IF EXISTS `jb_account_favorite_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_account_favorite_tags` (
  `tag_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`tag_id`,`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_account_favorite_tags`
--

LOCK TABLES `jb_account_favorite_tags` WRITE;
/*!40000 ALTER TABLE `jb_account_favorite_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_account_favorite_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_account_languages`
--

DROP TABLE IF EXISTS `jb_account_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_account_languages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint unsigned NOT NULL,
  `language_level_id` bigint unsigned NOT NULL,
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_native` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_account_languages`
--

LOCK TABLES `jb_account_languages` WRITE;
/*!40000 ALTER TABLE `jb_account_languages` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_account_languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_account_packages`
--

DROP TABLE IF EXISTS `jb_account_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_account_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint unsigned NOT NULL,
  `package_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_account_packages_account_id_index` (`account_id`),
  KEY `jb_account_packages_package_id_index` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_account_packages`
--

LOCK TABLES `jb_account_packages` WRITE;
/*!40000 ALTER TABLE `jb_account_packages` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_account_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_account_password_resets`
--

DROP TABLE IF EXISTS `jb_account_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_account_password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `jb_account_password_resets_email_index` (`email`),
  KEY `jb_account_password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_account_password_resets`
--

LOCK TABLES `jb_account_password_resets` WRITE;
/*!40000 ALTER TABLE `jb_account_password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_account_password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_accounts`
--

DROP TABLE IF EXISTS `jb_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `gender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar_id` bigint unsigned DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `email_verify_token` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'job-seeker',
  `credits` int unsigned DEFAULT NULL,
  `resume` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` mediumtext COLLATE utf8mb4_unicode_ci,
  `is_public_profile` tinyint unsigned NOT NULL DEFAULT '0',
  `hide_cv` tinyint(1) NOT NULL DEFAULT '0',
  `views` bigint unsigned NOT NULL DEFAULT '0',
  `is_featured` tinyint NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `available_for_hiring` tinyint(1) NOT NULL DEFAULT '1',
  `country_id` bigint unsigned DEFAULT '1',
  `state_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `cover_letter` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jb_accounts_email_unique` (`email`),
  UNIQUE KEY `jb_accounts_unique_id_unique` (`unique_id`),
  KEY `jb_accounts_type_index` (`type`),
  KEY `jb_accounts_is_featured_index` (`is_featured`),
  KEY `jb_accounts_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_accounts`
--

LOCK TABLES `jb_accounts` WRITE;
/*!40000 ALTER TABLE `jb_accounts` DISABLE KEYS */;
INSERT INTO `jb_accounts` VALUES (1,NULL,'Devonte','Sanford','Software Developer',NULL,'employer@archielite.com','$2y$12$9kwXv0I.NAxQGyuXUrJCjOD2LSeBDBP91aWvm2yskjHuwIdcmhBhO',186,'1996-06-24','+14807759531','2025-10-27 03:13:05',NULL,'employer',NULL,NULL,'7188 Bosco Ways Suite 368\nWest Eliezerville, NY 23907','Cheshire cats always grinned; in fact, a sort of use in waiting by the White Rabbit blew three blasts on the trumpet, and then the Mock Turtle Soup is made from,\' said the Cat, \'or you wouldn\'t.',1,0,4404,0,NULL,'2025-10-26 20:13:05','2025-10-26 20:13:05',0,1,NULL,NULL,NULL),(2,NULL,'Rory','Quigley','Creative Designer',NULL,'job_seeker@archielite.com','$2y$12$w.vWc3fTGSVqPrHd1kBnpOF0YRsY5sCsCgYJOh99MCn/Ey2GOwA8q',185,'1974-08-10','+17542355117','2025-10-27 03:13:05',NULL,'job-seeker',NULL,'resume/01.pdf','63851 Lind Lakes\nEast Tedborough, WI 44629-1721','By this time she went on \'And how do you know that cats COULD grin.\' \'They all can,\' said the King. \'Shan\'t,\' said the Mock Turtle, and said \'That\'s very important,\' the King replied. Here the.',1,0,3668,0,NULL,'2025-10-26 20:13:05','2025-10-26 20:13:05',0,1,NULL,NULL,NULL),(3,NULL,'Sarah','Harding','Creative Designer',NULL,'sarah_harding@archielite.com','$2y$12$0cuggdKQ5BOrMxKsMhs7cuOCIw/U9c3W1s4Gn4n2f4qkkTwusqqlC',184,'1991-03-03','+19725377984','2025-10-27 03:13:06',NULL,'employer',NULL,NULL,'127 Jessica Wall Apt. 313\nNew Brantville, ID 84039','William the Conqueror.\' (For, with all her life. Indeed, she had never been in a confused way, \'Prizes! Prizes!\' Alice had no reason to be done, I wonder?\' Alice guessed who it was, and, as a boon.',1,0,1981,0,NULL,'2025-10-26 20:13:06','2025-10-26 20:13:06',1,1,NULL,NULL,NULL),(4,NULL,'Steven','Jobs','Creative Designer',NULL,'steven_jobs@archielite.com','$2y$12$z3YkZHBfOv/2rt6hBors8.ZFkNqxuFcirGxj5/ECdTstunqu7Gl8.',185,'2007-08-05','+19367311551','2025-10-27 03:13:06',NULL,'employer',NULL,NULL,'392 Kirlin Causeway Apt. 027\nMadalinefort, CA 27435-8185','Queen to-day?\' \'I should have croqueted the Queen\'s voice in the long hall, and close to them, and then said \'The fourth.\' \'Two days wrong!\' sighed the Hatter. \'I told you butter wouldn\'t suit the.',1,0,285,1,NULL,'2025-10-26 20:13:06','2025-10-26 20:13:06',0,1,NULL,NULL,NULL),(5,NULL,'William','Kent','Creative Designer',NULL,'william_kent@archielite.com','$2y$12$waG2WI0YmwGWcfAnM/5VruE8e..jrmvOSg6OQ1tyA2RZPrJa1NMxy',186,'2016-12-15','+14193332667','2025-10-27 03:13:06',NULL,'employer',NULL,NULL,'664 Demetrius Roads Apt. 038\nHuelshaven, SD 55804-2581','When the Mouse was speaking, and this was not quite like the three gardeners at it, and behind them a new idea to Alice, and her eyes to see anything; then she looked up eagerly, half hoping that.',1,0,1772,0,NULL,'2025-10-26 20:13:06','2025-10-26 20:13:06',1,1,NULL,NULL,NULL),(6,NULL,'Isabella','Hahn','So they sat down.',NULL,'kirk61@yahoo.com','$2y$12$fF8wwmkZXnlsvOVo1EbPOuE.KyfTwSufg6Y.9rOUt7LLYSQ.BS8Dq',185,'2020-06-12','+15077930302','2025-10-27 03:13:06',NULL,'job-seeker',NULL,'resume/01.pdf','966 Corwin Hill Apt. 267\nLake Chadrickchester, VT 74477','As a duck with its eyelids, so he with his head!\' or \'Off with her head! Off--\' \'Nonsense!\' said Alice, very earnestly. \'I\'ve had nothing yet,\' Alice replied eagerly, for she was about a foot high.',1,0,852,0,NULL,'2025-10-26 20:13:06','2025-10-26 20:13:06',0,1,NULL,NULL,NULL),(7,NULL,'Ludie','Muller','HATED cats: nasty.',NULL,'luisa68@johnson.com','$2y$12$kaaq2HcWXNfZqnk2MExz7O.UBF3pOcvu29k4/j/7iX6DbO2/c3e2i',184,'1970-02-16','+17379949447','2025-10-27 03:13:06',NULL,'job-seeker',NULL,'resume/01.pdf','700 Verla Manor Apt. 017\nLake Leilanitown, ID 19154','I think I could, if I shall fall right THROUGH the earth! How funny it\'ll seem to put it to her that she had peeped into the garden with one eye; \'I seem to come down the chimney!\' \'Oh! So Bill\'s.',1,0,2839,1,NULL,'2025-10-26 20:13:06','2025-10-26 20:13:06',1,1,NULL,NULL,NULL),(8,NULL,'Tre','Steuber','Alice replied in a.',NULL,'iliana.vandervort@gmail.com','$2y$12$fZ75vn0bM5BmGT7lggZOiuENkmYhSqZ4QX.1ZILkfNs7Y3QYCz7lG',184,'1977-12-18','+18282695645','2025-10-27 03:13:07',NULL,'employer',NULL,NULL,'652 Langworth Summit\nGialand, DE 86826','I shall think nothing of the sea.\' \'I couldn\'t afford to learn it.\' said the Gryphon, and the moment she felt sure she would catch a bat, and that\'s all I can remember feeling a little quicker.',1,0,229,1,NULL,'2025-10-26 20:13:07','2025-10-26 20:13:07',0,1,NULL,NULL,NULL),(9,NULL,'Louie','Hackett','Lory, as soon as.',NULL,'ufeil@anderson.com','$2y$12$wpZ8z4XkLI8XnFtKXT3z.uia8rNrH0O2T1toLYWJIOVXzkBvckvbS',184,'2013-01-19','+15207947496','2025-10-27 03:13:07',NULL,'job-seeker',NULL,'resume/01.pdf','9923 Wiegand Plaza Apt. 780\nPadbergland, HI 48236','ONE with such a rule at processions; \'and besides, what would be as well say,\' added the Gryphon, and, taking Alice by the time they had to be a comfort, one way--never to be trampled under its.',1,0,3899,0,NULL,'2025-10-26 20:13:07','2025-10-26 20:13:07',0,1,NULL,NULL,NULL),(10,NULL,'Kendall','Jakubowski','Tell her to carry.',NULL,'jschaden@jenkins.biz','$2y$12$ZaYTk/e/QXv9GAW/RPxn2uS8yzyisYsVrcmUeK4sYIPFn4yuPU0Bq',184,'1999-11-29','+12126880097','2025-10-27 03:13:07',NULL,'job-seeker',NULL,'resume/01.pdf','9552 Tina Forks Suite 287\nPort Vince, AK 69297','The Mouse only shook its head to feel which way she put one arm out of that is--\"The more there is of mine, the less there is of yours.\"\' \'Oh, I beg your pardon!\' she exclaimed in a mournful tone.',1,0,2983,0,NULL,'2025-10-26 20:13:07','2025-10-26 20:13:07',0,1,NULL,NULL,NULL),(11,NULL,'Heidi','Rau','I should think!\'.',NULL,'reagan83@yahoo.com','$2y$12$wPvq7TWSVY3EAwCFIhdDoe0ov6iq9KFWSdGaKLIeeFkk6bPQCqQXG',186,'2003-05-20','+13412489900','2025-10-27 03:13:07',NULL,'job-seeker',NULL,'resume/01.pdf','807 Magali Lights\nNorth Gideon, DC 65930-5238','AND WASHING--extra.\"\' \'You couldn\'t have wanted it much,\' said Alice; \'it\'s laid for a conversation. Alice felt a little now and then; such as, that a moment\'s pause. The only things in the other.',1,0,1519,1,NULL,'2025-10-26 20:13:07','2025-10-26 20:13:07',0,1,NULL,NULL,NULL),(12,NULL,'Zane','Koepp','Off--\' \'Nonsense!\'.',NULL,'green.herman@runolfsdottir.biz','$2y$12$rOlKMnT.dJbZ2qNmkpg3deE1PHW1ZKEammUQGGqiG6191bk4IyCcy',185,'2008-12-08','+18659216057','2025-10-27 03:13:07',NULL,'employer',NULL,NULL,'9535 Gertrude Centers\nKertzmannberg, AR 16459-0508','There ought to go down the chimney?--Nay, I shan\'t! YOU do it!--That I won\'t, then!--Bill\'s to go through next walking about at the top of his head. But at any rate a book of rules for shutting.',1,0,3073,0,NULL,'2025-10-26 20:13:07','2025-10-26 20:13:07',1,1,NULL,NULL,NULL),(13,NULL,'Payton','Fisher','Queen was in the.',NULL,'haskell02@johns.org','$2y$12$0adkGKrUg6l9hI3wfHiJgePN45luTAKbcE6ZXuRqZUgcYA2o0RdXa',185,'1994-12-27','+15202474585','2025-10-27 03:13:08',NULL,'job-seeker',NULL,'resume/01.pdf','461 Pfeffer Plaza Apt. 605\nTrudiemouth, ID 31095-5101','THESE?\' said the Gryphon only answered \'Come on!\' cried the Mouse, who was beginning very angrily, but the Dormouse shall!\' they both bowed low, and their curls got entangled together. Alice laughed.',1,0,4881,1,NULL,'2025-10-26 20:13:08','2025-10-26 20:13:08',1,1,NULL,NULL,NULL),(14,NULL,'Sammie','Davis','Dinah here, I know.',NULL,'albert.kuhic@rolfson.com','$2y$12$8odF8U06KKwERw0jDpIMsOFDli/QYHfg6qtl/64x.WIvCeFRfQEIa',185,'2014-10-15','+13477407171','2025-10-27 03:13:08',NULL,'job-seeker',NULL,'resume/01.pdf','3446 Lubowitz Prairie Suite 744\nHalport, TN 59365','Queen, and Alice, were in custody and under sentence of execution.\' \'What for?\' said Alice. \'Of course not,\' Alice replied thoughtfully. \'They have their tails fast in their mouths; and the constant.',1,0,904,0,NULL,'2025-10-26 20:13:08','2025-10-26 20:13:08',0,1,NULL,NULL,NULL),(15,NULL,'Joyce','Thiel','I was sent for.\'.',NULL,'sawayn.lauriane@kovacek.org','$2y$12$egX3Rs9DqotkeytxPDT4Qu4F0r/6pNHhE85YHGFtDgP9HeOKNKOGi',186,'1977-05-05','+15405356695','2025-10-27 03:13:08',NULL,'employer',NULL,NULL,'730 Eleonore Plaza\nKevontown, ME 70408-2856','Alice considered a little wider. \'Come, it\'s pleased so far,\' said the Hatter: \'as the things get used to do:-- \'How doth the little--\"\' and she looked down into its eyes by this time?\' she said.',1,0,4192,1,NULL,'2025-10-26 20:13:08','2025-10-26 20:13:08',1,1,NULL,NULL,NULL),(16,NULL,'Helmer','Schulist','RABBIT\' engraved.',NULL,'carter.kayleigh@gmail.com','$2y$12$MkZK3sGznxmiEhqNjrr1YOePrqwzreAjIySd8ComiwBXQTXGOE85W',184,'2015-09-06','+15126605733','2025-10-27 03:13:08',NULL,'employer',NULL,NULL,'21514 Fadel Forks Suite 548\nMarcelinoborough, NJ 77477','Rabbit coming to look over their heads. She felt that it felt quite relieved to see it again, but it is.\' \'I quite forgot you didn\'t sign it,\' said Alice, a little feeble, squeaking voice, (\'That\'s.',1,0,4088,1,NULL,'2025-10-26 20:13:08','2025-10-26 20:13:08',1,1,NULL,NULL,NULL),(17,NULL,'Melyssa','Hettinger','March Hare,) \'--it.',NULL,'jany.parker@goldner.org','$2y$12$yVV8RLFIBJ8IJiIf6S5ZTeLl3j.JsCOvqwSKGfylNOOd/QijgNV4e',184,'1975-11-12','+17706348744','2025-10-27 03:13:08',NULL,'employer',NULL,NULL,'6870 Kozey Tunnel\nRodriguezville, MS 56539-5259','I\'ve got to the Gryphon. \'How the creatures wouldn\'t be in before the trial\'s begun.\' \'They\'re putting down their names,\' the Gryphon only answered \'Come on!\' cried the Gryphon, sighing in his.',1,0,3109,0,NULL,'2025-10-26 20:13:08','2025-10-26 20:13:08',1,1,NULL,NULL,NULL),(18,NULL,'Addison','Morissette','Alice noticed with.',NULL,'treichert@beer.com','$2y$12$9Ufklw.y2Dz9.dJcnb4jsetDJWiMdVgV5aIJMtFsp1FNCQ9aMCY2W',184,'2019-07-11','+13017478202','2025-10-27 03:13:09',NULL,'job-seeker',NULL,'resume/01.pdf','1214 Alexandre Shores Suite 115\nPhilipstad, OH 42946-9758','How puzzling all these changes are! I\'m never sure what I\'m going to give the hedgehog had unrolled itself, and began smoking again. This time there were ten of them, with her arms round it as to.',1,0,4745,1,NULL,'2025-10-26 20:13:09','2025-10-26 20:13:09',0,1,NULL,NULL,NULL),(19,NULL,'Julio','VonRueden','He was looking at.',NULL,'cummerata.jules@gmail.com','$2y$12$kbSDvHyScHEA7G1yrHK6HOtg8EhpIobLbtpS26MPGFOhvsG5x6NBi',186,'2004-02-06','+14102225160','2025-10-27 03:13:09',NULL,'job-seeker',NULL,'resume/01.pdf','480 Pagac Alley\nTorpville, OH 06048','Was kindly permitted to pocket the spoon: While the Duchess said after a few minutes to see how the game began. Alice thought she might as well as I was sent for.\' \'You ought to be seen: she found.',1,0,3658,0,NULL,'2025-10-26 20:13:09','2025-10-26 20:13:09',0,1,NULL,NULL,NULL),(20,NULL,'Florence','Adams','If I or she should.',NULL,'sunny.keeling@mosciski.com','$2y$12$aqCwtai5ljfDvxD2v4OdBe5XHwKMQxpHlSDNmiaM0BAUJqd.3j4qm',186,'1999-10-28','+19309293972','2025-10-27 03:13:09',NULL,'job-seeker',NULL,'resume/01.pdf','8778 Green Village\nBayermouth, MO 07947','MORE than nothing.\' \'Nobody asked YOUR opinion,\' said Alice. \'Come, let\'s try the first witness,\' said the Mock Turtle, \'but if you\'ve seen them so shiny?\' Alice looked up, and began by taking the.',1,0,1879,0,NULL,'2025-10-26 20:13:09','2025-10-26 20:13:09',1,1,NULL,NULL,NULL),(21,NULL,'Nelda','Farrell','Alice thought to.',NULL,'kaleigh.reichert@turcotte.com','$2y$12$aMBg6BAzW0c53Qr4inMFtOjdZ9WdM/ImOjRUz.Sgyd0.GgbpzV64O',185,'2017-09-18','+16629673249','2025-10-27 03:13:09',NULL,'job-seeker',NULL,'resume/01.pdf','54697 Murazik Mountain Apt. 028\nNew Sherwoodshire, KS 80264-0351','But the insolence of his great wig.\' The judge, by the hedge!\' then silence, and then they wouldn\'t be in before the trial\'s begun.\' \'They\'re putting down their names,\' the Gryphon remarked.',1,0,2226,0,NULL,'2025-10-26 20:13:09','2025-10-26 20:13:09',1,1,NULL,NULL,NULL),(22,NULL,'Anita','Auer','It\'s high time to.',NULL,'alejandrin45@lubowitz.biz','$2y$12$VcktGmwD8CtYd19QMwn.cuPVUEADBqXw1MHk88ZvQVd7nL/oehjkW',185,'1987-10-04','+12399286407','2025-10-27 03:13:10',NULL,'job-seeker',NULL,'resume/01.pdf','9078 Rutherford Overpass Suite 411\nEast Mariela, MI 48021','Queen had never before seen a good opportunity for showing off her head!\' the Queen of Hearts, and I had to leave the court; but on second thoughts she decided on going into the way of keeping up.',1,0,795,0,NULL,'2025-10-26 20:13:10','2025-10-26 20:13:10',1,1,NULL,NULL,NULL),(23,NULL,'Verna','Casper','Alice, and she had.',NULL,'ally.runte@hotmail.com','$2y$12$JuWRQRwwHhGZciTsrmUIhuUK3SDuHnQM4lw3oCMhqNqOnZJfbHCTC',186,'2002-10-23','+14846028532','2025-10-27 03:13:10',NULL,'employer',NULL,NULL,'92013 Anjali Rapids Suite 819\nWillardside, NM 25984-9025','Hatter. \'He won\'t stand beating. Now, if you could see her after the birds! Why, she\'ll eat a bat?\' when suddenly, thump! thump! down she came upon a little ledge of rock, and, as the question was.',1,0,2965,0,NULL,'2025-10-26 20:13:10','2025-10-26 20:13:10',0,1,NULL,NULL,NULL),(24,NULL,'Margie','Stoltenberg','Mabel! I\'ll try if.',NULL,'noble.brakus@yahoo.com','$2y$12$4cIbmv0xcwL0jbh1zI2bA.CQMd1oNR20psDFidIaMJjRisD1PM7L6',186,'2008-01-12','+12394571658','2025-10-27 03:13:10',NULL,'job-seeker',NULL,'resume/01.pdf','51130 Louvenia Rapid Apt. 364\nLake Marlen, DE 19375-6335','Hatter asked triumphantly. Alice did not quite like the largest telescope that ever was! Good-bye, feet!\' (for when she looked back once or twice she had asked it aloud; and in THAT direction,\' the.',1,0,3336,1,NULL,'2025-10-26 20:13:10','2025-10-26 20:13:10',0,1,NULL,NULL,NULL),(25,NULL,'Everett','Treutel','The Footman seemed.',NULL,'hugh15@gmail.com','$2y$12$LuR7MTxZBhQbYfHYLtKJaOyFCfvVdigf1qZu4zl9dKnVEVbuTplpW',186,'2005-08-19','+12812485020','2025-10-27 03:13:10',NULL,'job-seeker',NULL,'resume/01.pdf','96568 Leannon Springs\nBrakushaven, KS 62507-8324','A Caucus-Race and a Long Tale They were just beginning to grow up any more if you\'d rather not.\' \'We indeed!\' cried the Mouse, who seemed to listen, the whole pack rose up into the air off all its.',1,0,527,0,NULL,'2025-10-26 20:13:10','2025-10-26 20:13:10',0,1,NULL,NULL,NULL),(26,NULL,'Adriel','Trantow','The twelve jurors.',NULL,'turcotte.carlotta@swaniawski.biz','$2y$12$3tjsDYp8KG0EEmhOLyNLCOPymI3VrTiLbrbfJV1IhY6jcOVFOdODC',186,'1996-12-20','+19297833250','2025-10-27 03:13:10',NULL,'job-seeker',NULL,'resume/01.pdf','1910 Duncan Mount Suite 736\nLazarohaven, WI 46865-3471','CHAPTER VI. Pig and Pepper For a minute or two, she made some tarts, All on a bough of a tree in the pool a little three-legged table, all made of solid glass; there was no longer to be an old crab.',1,0,2941,1,NULL,'2025-10-26 20:13:10','2025-10-26 20:13:10',1,1,NULL,NULL,NULL),(27,NULL,'Cole','Kulas','Queen in front of.',NULL,'theodore39@yahoo.com','$2y$12$IokzSO3jg8Fi1IQjZD1yXu3SC/Cm.7A8Gz/V/OXioOxRe9VfW6lRO',186,'2019-05-05','+16803415565','2025-10-27 03:13:11',NULL,'job-seeker',NULL,'resume/01.pdf','5689 Lilla Walk Suite 467\nJacobsonhaven, MD 93543-0441','Alice, who was trembling down to the Knave \'Turn them over!\' The Knave did so, and giving it something out of sight: \'but it doesn\'t matter which way you can;--but I must sugar my hair.\" As a duck.',1,0,1630,0,NULL,'2025-10-26 20:13:11','2025-10-26 20:13:11',0,1,NULL,NULL,NULL),(28,NULL,'Loy','Corwin','Between yourself.',NULL,'gorczany.molly@thiel.com','$2y$12$.FRtGoIdpamhAzeqGfM5HexyyGXYIjop0AisRq2gokR84AOSxhbQq',186,'2025-09-26','+16513303703','2025-10-27 03:13:11',NULL,'employer',NULL,NULL,'7520 Presley Valleys Apt. 078\nPort Ralphtown, TN 15957','It was as long as you say pig, or fig?\' said the others. \'We must burn the house down!\' said the Cat. \'I said pig,\' replied Alice; \'and I do hope it\'ll make me smaller, I can find them.\' As she said.',1,0,4857,1,NULL,'2025-10-26 20:13:11','2025-10-26 20:13:11',1,1,NULL,NULL,NULL),(29,NULL,'Norwood','Legros','I could not taste.',NULL,'phermiston@gmail.com','$2y$12$aGt0IbZ8maWLJeQqaAk2guih/huUE52Gt4/x5tnvsVmNPjIZoDil6',184,'1971-11-19','+16313146879','2025-10-27 03:13:11',NULL,'employer',NULL,NULL,'992 Whitney Viaduct\nBereniceshire, NC 16115','INSIDE, you might like to try the thing Mock Turtle a little bit, and said to herself, \'in my going out altogether, like a candle. I wonder what they WILL do next! If they had any dispute with the.',1,0,129,0,NULL,'2025-10-26 20:13:11','2025-10-26 20:13:11',1,1,NULL,NULL,NULL),(30,NULL,'Mallie','Buckridge','Latitude was, or.',NULL,'berniece99@yahoo.com','$2y$12$CMyNtxHKFmSq0dT5wYSSteFrRE3yd7F2PkXad/XvcVFBu7nXlEmV.',186,'2009-10-26','+17695569273','2025-10-27 03:13:11',NULL,'employer',NULL,NULL,'834 German Mountain Apt. 855\nZiemannstad, CT 82170','There was a most extraordinary noise going on rather better now,\' she said, \'and see whether it\'s marked \"poison\" or not\'; for she had expected: before she got up, and began bowing to the other end.',1,0,120,1,NULL,'2025-10-26 20:13:11','2025-10-26 20:13:11',1,1,NULL,NULL,NULL),(31,NULL,'Demario','Watsica','I shall be a great.',NULL,'ymcdermott@gmail.com','$2y$12$3tAnKFa.2uirVfmh6Y/sjOpjGFnFai3i3GX1SleZITT3n9URGoCiC',184,'2003-01-14','+12512370629','2025-10-27 03:13:11',NULL,'employer',NULL,NULL,'4596 Lemke Pines Suite 238\nRogahnbury, VA 84492','The first witness was the cat.) \'I hope they\'ll remember her saucer of milk at tea-time. Dinah my dear! Let this be a lesson to you how it was over at last: \'and I do wonder what was on the slate.',1,0,298,1,NULL,'2025-10-26 20:13:11','2025-10-26 20:13:11',1,1,NULL,NULL,NULL),(32,NULL,'Geovanni','Smitham','Allow me to sell.',NULL,'bromaguera@hotmail.com','$2y$12$fbOrrARku6nzqlvuhHrX0urjKhflRaodKMPJ6Ai7tM3SzSGPi3pKq',186,'2014-06-20','+16505878243','2025-10-27 03:13:12',NULL,'employer',NULL,NULL,'13362 Gwen Camp Suite 943\nEast Efrain, MT 40464-2051','Presently the Rabbit was still in sight, and no more of it appeared. \'I don\'t see how he did with the distant green leaves. As there seemed to be no use in knocking,\' said the Gryphon. \'They can\'t.',1,0,4543,0,NULL,'2025-10-26 20:13:12','2025-10-26 20:13:12',1,1,NULL,NULL,NULL),(33,NULL,'Jerrell','Price','I eat\" is the same.',NULL,'brekke.katlyn@hickle.info','$2y$12$jVM2DmGEf6/g2yJBYkRnI.BpWvLAHtl7lVyexqtf8NtYEsezOuRBW',184,'1973-06-15','+15018436151','2025-10-27 03:13:12',NULL,'job-seeker',NULL,'resume/01.pdf','47298 Marks Creek Suite 460\nKochport, CT 03256-1448','Cat, \'if you only kept on good terms with him, he\'d do almost anything you liked with the Queen, but she was shrinking rapidly; so she took up the little door into that beautiful garden--how IS that.',1,0,856,1,NULL,'2025-10-26 20:13:12','2025-10-26 20:13:12',0,1,NULL,NULL,NULL),(34,NULL,'Lonzo','Pouros','Alice very meekly.',NULL,'hschroeder@lowe.com','$2y$12$ql2fe92yaZXsBnUMGG7Z2eVtv3oXUxo90U49pZzw4QkVLiJU7R6Fu',184,'1982-06-04','+13806434404','2025-10-27 03:13:12',NULL,'employer',NULL,NULL,'76312 Schiller Parkway\nZemlakmouth, MI 67198','Pray, what is the capital of Paris, and Paris is the driest thing I know. Silence all round, if you like,\' said the Gryphon. \'Do you know about this business?\' the King eagerly, and he went on.',1,0,1023,1,NULL,'2025-10-26 20:13:12','2025-10-26 20:13:12',1,1,NULL,NULL,NULL),(35,NULL,'Benedict','Weimann','She said this last.',NULL,'cassin.jose@ferry.com','$2y$12$HTbBYbrmfYhEWVFbafBM0.LcNcVgOzFeMZFVqrxlwkXWdKz4la./S',184,'1992-01-25','+13177796711','2025-10-27 03:13:12',NULL,'job-seeker',NULL,'resume/01.pdf','2111 Louisa Mall Suite 474\nMedhurstmouth, AR 00090','Hatter. \'I deny it!\' said the Dormouse, without considering at all a proper way of settling all difficulties, great or small. \'Off with her arms round it as to prevent its undoing itself,) she.',1,0,1901,1,NULL,'2025-10-26 20:13:12','2025-10-26 20:13:12',1,1,NULL,NULL,NULL),(36,NULL,'Wellington','Dach','The Footman seemed.',NULL,'mittie.brekke@hotmail.com','$2y$12$GXwztYw/WABZtxImeveQketdW1XtUJzbIXSEm5zpVSMaC58FY82Cm',184,'2019-03-21','+18022332940','2025-10-27 03:13:12',NULL,'employer',NULL,NULL,'968 Alfonzo Wall\nNorth Judy, KS 88880-5348','I am in the act of crawling away: besides all this, there was a little pattering of feet on the bank, and of having nothing to do: once or twice she had not noticed before, and he went on again.',1,0,2461,0,NULL,'2025-10-26 20:13:12','2025-10-26 20:13:12',1,1,NULL,NULL,NULL),(37,NULL,'Lucinda','Haag','Caterpillar. Alice.',NULL,'albertha.balistreri@gmail.com','$2y$12$.Y1.SdcCAbbv/aMZzr7ApObtRtiNpuVp7QFstNHrQqFUE/T9h4Hde',185,'1985-01-24','+18542412307','2025-10-27 03:13:13',NULL,'job-seeker',NULL,'resume/01.pdf','251 Will Drive Apt. 212\nPort Retha, IA 14669','Alice with one of them.\' In another minute the whole window!\' \'Sure, it does, yer honour: but it\'s an arm for all that.\' \'Well, it\'s got no business of MINE.\' The Queen smiled and passed on. \'Who.',1,0,3441,0,NULL,'2025-10-26 20:13:13','2025-10-26 20:13:13',1,1,NULL,NULL,NULL),(38,NULL,'Nigel','Hoppe','There was not an.',NULL,'watsica.willy@gutkowski.info','$2y$12$Ab7DUVU1ZfixHkJjZUQnXOnqtasMhr5yBR3dX1m6JQLkNbqVunRbK',184,'2021-07-18','+13195928652','2025-10-27 03:13:13',NULL,'employer',NULL,NULL,'56623 Jones Lake\nEast Dejah, MD 19754-1156','I have to fly; and the Queen\'s hedgehog just now, only it ran away when it saw mine coming!\' \'How do you call it purring, not growling,\' said Alice. The King and the procession came opposite to.',1,0,3608,1,NULL,'2025-10-26 20:13:13','2025-10-26 20:13:13',1,1,NULL,NULL,NULL),(39,NULL,'Elliot','Schmeler','White Rabbit, \'and.',NULL,'zaufderhar@hotmail.com','$2y$12$I84spuhbNwAK728b6fe6KO.ffQ5eyxr/CBHjYkKkXyYbfif8Uhv82',185,'1998-09-23','+16618892108','2025-10-27 03:13:13',NULL,'employer',NULL,NULL,'44484 Nathanael Drives Apt. 141\nSouth Teresa, DE 21158','Conqueror, whose cause was favoured by the time he had taken his watch out of sight: then it chuckled. \'What fun!\' said the Footman. \'That\'s the first position in dancing.\' Alice said; \'there\'s a.',1,0,1185,1,NULL,'2025-10-26 20:13:13','2025-10-26 20:13:13',0,1,NULL,NULL,NULL),(40,NULL,'Daron','Pfannerstill','Hearts, she made.',NULL,'boyle.mina@hamill.com','$2y$12$FOYths.t6sMK.wTeWXO/pOoAGiCPjXSrUr/loRMxfSyy59a3aLn6O',185,'1990-03-17','+14699442073','2025-10-27 03:13:13',NULL,'job-seeker',NULL,'resume/01.pdf','2115 Hansen Square\nJuliusberg, FL 43026-6180','Wonderland of long ago: and how she would get up and throw us, with the day of the song. \'What trial is it?\' \'Why,\' said the King, and he says it\'s so useful, it\'s worth a hundred pounds! He says it.',1,0,3140,0,NULL,'2025-10-26 20:13:13','2025-10-26 20:13:13',1,1,NULL,NULL,NULL),(41,NULL,'Dane','Ryan','Lobster; I heard.',NULL,'muller.marie@altenwerth.com','$2y$12$UEz87al..ar/5LQZVuhWfu3p74FynJtsSSoj4GrVfS.mi9DCT.RZi',186,'1972-02-04','+18634398578','2025-10-27 03:13:13',NULL,'job-seeker',NULL,'resume/01.pdf','783 Dibbert Court\nEinoview, CA 61017-5739','Multiplication Table doesn\'t signify: let\'s try Geography. London is the same size for ten minutes together!\' \'Can\'t remember WHAT things?\' said the cook. \'Treacle,\' said a sleepy voice behind her.',1,0,1053,0,NULL,'2025-10-26 20:13:13','2025-10-26 20:13:13',0,1,NULL,NULL,NULL),(42,NULL,'Kadin','Muller','Alice indignantly.',NULL,'eleanora.labadie@gmail.com','$2y$12$cKv6cSz0Qn8l7Hx.LODlleqyZfjdHiCgyRiiCKUVPLrolLUMZ12YC',186,'2015-08-29','+14703813915','2025-10-27 03:13:14',NULL,'job-seeker',NULL,'resume/01.pdf','839 Skylar Cliff Apt. 594\nKohlerport, FL 93665-8253','This question the Dodo said, \'EVERYBODY has won, and all the rest of the busy farm-yard--while the lowing of the month is it?\' Alice panted as she could, for her to begin.\' He looked at it, and.',1,0,2011,1,NULL,'2025-10-26 20:13:14','2025-10-26 20:13:14',0,1,NULL,NULL,NULL),(43,NULL,'Nolan','Schumm','I\'ll kick you down.',NULL,'fwitting@lehner.com','$2y$12$JWIpxQXnOuHrZtjpi6.P3eU3aPDUfgEVk/D2k/9ayXqE07qhT.76W',185,'2015-08-15','+14796545533','2025-10-27 03:13:14',NULL,'job-seeker',NULL,'resume/01.pdf','5428 Arely Field\nWest Trent, VA 94281-0035','Alice quietly said, just as well as she fell past it. \'Well!\' thought Alice \'without pictures or conversations in it, \'and what is the driest thing I ask! It\'s always six o\'clock now.\' A bright idea.',1,0,4504,0,NULL,'2025-10-26 20:13:14','2025-10-26 20:13:14',0,1,NULL,NULL,NULL),(44,NULL,'Carmelo','O\'Connell','Dormouse shall!\'.',NULL,'cronin.perry@beatty.com','$2y$12$zz73aMrIUYoQpRcIEy1BKO0OiaIw/xp1GsBs3lMfGUfsOe0t0FcgK',185,'2020-04-05','+17436125038','2025-10-27 03:13:14',NULL,'employer',NULL,NULL,'4022 Shaun Club\nDulcemouth, FL 80409','I don\'t care which happens!\' She ate a little pattering of feet on the ground near the door, and tried to open it; but, as the soldiers did. After these came the guests, mostly Kings and Queens, and.',1,0,4540,0,NULL,'2025-10-26 20:13:14','2025-10-26 20:13:14',0,1,NULL,NULL,NULL),(45,NULL,'Mallie','Kirlin','I don\'t care which.',NULL,'ghackett@gmail.com','$2y$12$uu73g58CGwv0mZyM0Qc6vecjdIaTG1kxnuUcip0u.rVcSDSdBDLgC',184,'1987-05-26','+16072451155','2025-10-27 03:13:14',NULL,'employer',NULL,NULL,'638 Terry Corner\nDaughertymouth, MD 28159','The first thing I\'ve got to?\' (Alice had no idea what to do it! Oh dear! I shall see it written down: but I THINK I can listen all day to day.\' This was quite silent for a rabbit! I suppose it.',1,0,3817,0,NULL,'2025-10-26 20:13:14','2025-10-26 20:13:14',1,1,NULL,NULL,NULL),(46,NULL,'Quinn','Eichmann','And concluded the.',NULL,'nichole.harber@gmail.com','$2y$12$ZOb.Wb2RvobA4KDgkTan6OKX6Xi4aXFzWZLj4i6TpZLxtQDWgOdTW',186,'1996-12-24','+13318559531','2025-10-27 03:13:14',NULL,'employer',NULL,NULL,'19239 Graham Freeway\nEast Cheyenneside, MD 58523','There could be beheaded, and that in some alarm. This time Alice waited patiently until it chose to speak again. The rabbit-hole went straight on like a writing-desk?\' \'Come, we shall get on.',1,0,299,1,NULL,'2025-10-26 20:13:14','2025-10-26 20:13:14',0,1,NULL,NULL,NULL),(47,NULL,'Cullen','Wintheiser','Alice. \'And where.',NULL,'abernathy.jaeden@yahoo.com','$2y$12$pA2BADk5tCCTF0TAD85O8.ik/1ywTiHHogsR3AHLruQqVpoe6JGHa',186,'2015-03-02','+19719670994','2025-10-27 03:13:15',NULL,'job-seeker',NULL,'resume/01.pdf','8942 Wiegand Shores Apt. 695\nMillerside, ID 12696-8676','Pool of Tears \'Curiouser and curiouser!\' cried Alice in a loud, indignant voice, but she had tired herself out with trying, the poor little Lizard, Bill, was in managing her flamingo: she succeeded.',1,0,2924,0,NULL,'2025-10-26 20:13:15','2025-10-26 20:13:15',0,1,NULL,NULL,NULL),(48,NULL,'Wilmer','Bednar','Wonderland of long.',NULL,'vschmeler@olson.com','$2y$12$bSBXn246m6TOrwGpHMY/rupzygjMZMMzFAgNyyIDtUx5uYiLTE1DS',184,'2011-01-07','+15076142551','2025-10-27 03:13:15',NULL,'employer',NULL,NULL,'56312 Crist Skyway\nPort Jada, DC 36490-3135','Alice went on, turning to the Gryphon. \'The reason is,\' said the King. The White Rabbit was still in existence; \'and now for the garden!\' and she felt a violent blow underneath her chin: it had lost.',1,0,979,1,NULL,'2025-10-26 20:13:15','2025-10-26 20:13:15',1,1,NULL,NULL,NULL),(49,NULL,'Thaddeus','Crona','The master was an.',NULL,'zemlak.nola@schneider.info','$2y$12$M30HYFJDDzlHtWrRmtHKnOINfJCMmuFiP.9E0gqyyAOVuMeESIFYy',186,'2009-05-18','+18458451288','2025-10-27 03:13:15',NULL,'job-seeker',NULL,'resume/01.pdf','9622 Hilpert Ports Suite 329\nKuhichaven, NH 53797','Lizard, who seemed too much overcome to do that,\' said the Gryphon. \'The reason is,\' said the Hatter. This piece of it at all,\' said the Caterpillar. \'Not QUITE right, I\'m afraid,\' said Alice, \'I\'ve.',1,0,1538,0,NULL,'2025-10-26 20:13:15','2025-10-26 20:13:15',0,1,NULL,NULL,NULL),(50,NULL,'Alphonso','Dooley','Mystery,\' the Mock.',NULL,'carolanne.hills@hotmail.com','$2y$12$4GPlP8pWNtaJu0upO2nwv.NrietbsrLL9Zg59NE0PvOWWQAvb0CJK',186,'2002-06-11','+15418544525','2025-10-27 03:13:15',NULL,'job-seeker',NULL,'resume/01.pdf','536 Dolly Island\nWest Daytonhaven, RI 60954','I suppose?\' said Alice. \'Nothing WHATEVER?\' persisted the King. \'Shan\'t,\' said the King said gravely, \'and go on crying in this affair, He trusts to you to sit down without being invited,\' said the.',1,0,2682,1,NULL,'2025-10-26 20:13:15','2025-10-26 20:13:15',1,1,NULL,NULL,NULL),(51,NULL,'Aniya','Dietrich','Oh dear! I wish I.',NULL,'cory.zieme@hotmail.com','$2y$12$4wh.KhLB..I6qwxXZ3gEJOO.j6N.yImLeY7ooJ5AKrUxJeCSxYcvi',185,'2021-05-07','+19544649461','2025-10-27 03:13:15',NULL,'job-seeker',NULL,'resume/01.pdf','9008 Hoppe Run Suite 240\nEast Hermanport, CO 65319-6254','I COULD NOT SWIM--\" you can\'t swim, can you?\' he added, turning to the Hatter. Alice felt a little way out of the treat. When the sands are all dry, he is gay as a boon, Was kindly permitted to.',1,0,1783,0,NULL,'2025-10-26 20:13:15','2025-10-26 20:13:15',0,1,NULL,NULL,NULL),(52,NULL,'Carmelo','Rath','Alice; \'I must be.',NULL,'regan.harvey@watsica.com','$2y$12$b4ed.xpvRQnm.5tSFaIdKuCR5z/5ojVYT5lVfUx4FkhEAz8ye0psi',185,'1988-09-20','+14804610221','2025-10-27 03:13:16',NULL,'employer',NULL,NULL,'98565 Mabel Estates\nShanelleberg, AK 96054','Dormouse into the sea, \'and in that poky little house, on the top of the birds hurried off at once, in a shrill, loud voice, and see that the mouse to the jury, and the fan, and skurried away into.',1,0,3096,1,NULL,'2025-10-26 20:13:16','2025-10-26 20:13:16',0,1,NULL,NULL,NULL),(53,NULL,'Jaylon','Boyer','Alice asked. The.',NULL,'adele.metz@gmail.com','$2y$12$cB6Kxpa4lCafV2hq3Ze6suOE47frRY40fod2df9qtZO50Gq5KbdjW',186,'2007-05-20','+13529254713','2025-10-27 03:13:16',NULL,'job-seeker',NULL,'resume/01.pdf','991 Abernathy Isle\nPort Mazie, WA 17733','Pigeon, raising its voice to a shriek, \'and just as well as pigs, and was beating her violently with its legs hanging down, but generally, just as well go back, and barking hoarsely all the while.',1,0,3288,0,NULL,'2025-10-26 20:13:16','2025-10-26 20:13:16',1,1,NULL,NULL,NULL),(54,NULL,'Sylvan','Kemmer','I breathe\"!\' \'It.',NULL,'nelda.cormier@haley.com','$2y$12$dwtbitQwrLvz9ttCf.TnFeFDeoiZyRd4iapSthrTTFAYzY5LvOCli',184,'1981-07-13','+13647922860','2025-10-27 03:13:16',NULL,'employer',NULL,NULL,'7657 Trevor Harbor\nTressiehaven, VA 52106-3112','Turtle.\' These words were followed by a very poor speaker,\' said the Pigeon the opportunity of taking it away. She did it so yet,\' said the Caterpillar contemptuously. \'Who are YOU?\' said the.',1,0,1989,0,NULL,'2025-10-26 20:13:16','2025-10-26 20:13:16',0,1,NULL,NULL,NULL),(55,NULL,'Calista','Konopelski','King said, turning.',NULL,'obeier@nolan.com','$2y$12$G1mVitADCwzez0Zqh3rTzeRdHBOhPQDYNHbzgzqbOHKP7hBDWxMzu',184,'2007-05-14','+13855179256','2025-10-27 03:13:16',NULL,'job-seeker',NULL,'resume/01.pdf','94857 Feest Trafficway Suite 072\nCooperstad, NV 04767','March Hare said--\' \'I didn\'t!\' the March Hare. \'Then it ought to tell its age, there was not quite know what you like,\' said the King. \'Then it ought to be sure, this generally happens when you.',1,0,3875,1,NULL,'2025-10-26 20:13:16','2025-10-26 20:13:16',0,1,NULL,NULL,NULL),(56,NULL,'Brandon','Monahan','Alice. \'Why, you.',NULL,'ulakin@oberbrunner.com','$2y$12$2jckBu3IOXsM1eb2XtE.ieWpsf5y/wMNILlwQPZYoPYyqT9WmH6am',185,'2018-07-05','+16785459593','2025-10-27 03:13:16',NULL,'employer',NULL,NULL,'5795 Armstrong Isle Apt. 763\nDejahbury, WV 03429-7502','On various pretexts they all crowded round her once more, while the rest were quite dry again, the cook was busily stirring the soup, and seemed to have lessons to learn! No, I\'ve made up my mind.',1,0,3409,0,NULL,'2025-10-26 20:13:16','2025-10-26 20:13:16',1,1,NULL,NULL,NULL),(57,NULL,'Bart','Okuneva','I\'m NOT a serpent.',NULL,'graynor@treutel.info','$2y$12$Dsuv2JK2rKUruojJCOl5BuNCXjfwhm1HQI9N5ixGUZ6SWVlNjePem',184,'2024-05-23','+16188393954','2025-10-27 03:13:17',NULL,'employer',NULL,NULL,'2958 Aron Turnpike\nLake Tavares, IA 57182-9727','It was, no doubt: only Alice did not like the Mock Turtle\'s heavy sobs. Lastly, she pictured to herself \'That\'s quite enough--I hope I shan\'t go, at any rate,\' said Alice: \'--where\'s the Duchess?\'.',1,0,3594,0,NULL,'2025-10-26 20:13:17','2025-10-26 20:13:17',1,1,NULL,NULL,NULL),(58,NULL,'Guadalupe','Heller','Alice considered a.',NULL,'mohammed.stokes@erdman.net','$2y$12$JL.Ta/3vjsrdAqkUZgzJne9z0LLe/Y5KOEFrN9lgoNRNU3hddGEUC',184,'2006-09-05','+15165269176','2025-10-27 03:13:17',NULL,'job-seeker',NULL,'resume/01.pdf','7597 Zelda Shoals Apt. 143\nPort Jaeden, IA 97081-2063','White Rabbit with pink eyes ran close by it, and they all cheered. Alice thought the whole pack rose up into the air, mixed up with the end of the ground--and I should say \"With what porpoise?\"\'.',1,0,299,1,NULL,'2025-10-26 20:13:17','2025-10-26 20:13:17',0,1,NULL,NULL,NULL),(59,NULL,'Blair','Fisher','Alice. \'I\'m a--I\'m.',NULL,'idell58@hotmail.com','$2y$12$5YqSvR48/fJK9snSKGX5AeC5ZZ9a/2rK3TGycLxwbq/LnlXy2A.dG',185,'2017-03-27','+12675032411','2025-10-27 03:13:17',NULL,'job-seeker',NULL,'resume/01.pdf','9851 Jacques Loop Suite 011\nPort Laylamouth, AZ 95612-5859','Mock Turtle said: \'advance twice, set to work at once to eat the comfits: this caused some noise and confusion, as the March Hare and his friends shared their never-ending meal, and the whole party.',1,0,4947,0,NULL,'2025-10-26 20:13:17','2025-10-26 20:13:17',1,1,NULL,NULL,NULL),(60,NULL,'Vada','Klocko','Alice angrily. \'It.',NULL,'okeefe.maxime@yahoo.com','$2y$12$spq37RW.ztryZiZ72yXet.qBs1tlsdrr./eej5J2jYSpFipH26hwe',186,'1999-11-23','+13649746911','2025-10-27 03:13:17',NULL,'job-seeker',NULL,'resume/01.pdf','418 Alba Turnpike Apt. 643\nNew Neal, NV 43531-9600','Alice began to repeat it, but her voice close to her in the distance, screaming with passion. She had not got into a small passage, not much larger than a rat-hole: she knelt down and began talking.',1,0,2509,0,NULL,'2025-10-26 20:13:17','2025-10-26 20:13:17',1,1,NULL,NULL,NULL),(61,NULL,'Miles','Davis','Where CAN I have.',NULL,'lorine.kris@hotmail.com','$2y$12$qKKO3LllfH4iN2CvKzjssepbiZKmTwk90JAkQl1ZqVrOWapCE89Hq',185,'2005-03-27','+16265509225','2025-10-27 03:13:18',NULL,'job-seeker',NULL,'resume/01.pdf','182 Antwan Plains Suite 503\nEmilieshire, IA 92514-3880','Alice, quite forgetting her promise. \'Treacle,\' said the King. The White Rabbit interrupted: \'UNimportant, your Majesty means, of course,\' said the Hatter, \'when the Queen furiously, throwing an.',1,0,807,0,NULL,'2025-10-26 20:13:18','2025-10-26 20:13:18',0,1,NULL,NULL,NULL),(62,NULL,'Linnea','Grimes','Down, down, down.',NULL,'jamie71@turcotte.com','$2y$12$YuFmSqe1wFJGDPAND6mP0.Qca.WXnFTA7.RczqcU8cihKvZZvcULi',186,'2014-08-27','+16519935073','2025-10-27 03:13:18',NULL,'employer',NULL,NULL,'385 Hudson Trail Suite 219\nEast Frances, ME 18382-8420','Mouse with an air of great relief. \'Now at OURS they had settled down in a very short time the Mouse to Alice again. \'No, I give you fair warning,\' shouted the Queen was in livery: otherwise.',1,0,1915,1,NULL,'2025-10-26 20:13:18','2025-10-26 20:13:18',0,1,NULL,NULL,NULL),(63,NULL,'Marcellus','Nitzsche','So they went up to.',NULL,'katlyn60@grimes.com','$2y$12$dOYuE7LMDsY6XG7.9QLky.u10ZKuuJwORDCDZJ4Ezjuk7o9Lc7VHW',186,'1990-09-30','+14234084183','2025-10-27 03:13:18',NULL,'job-seeker',NULL,'resume/01.pdf','840 Mathew Crest Apt. 936\nValeriechester, WA 12928','For the Mouse was bristling all over, and she did not seem to be\"--or if you\'d rather not.\' \'We indeed!\' cried the Mouse, sharply and very soon found herself at last in the trial done,\' she thought.',1,0,2508,0,NULL,'2025-10-26 20:13:18','2025-10-26 20:13:18',1,1,NULL,NULL,NULL),(64,NULL,'Melody','Schumm','Morcar, the earls.',NULL,'susan.powlowski@yahoo.com','$2y$12$8xpUCQ3s9a6e2vo6Rz0y4es7c/r9wvTwrVFvrVbl3Llnb0FMOw7MK',186,'2003-06-21','+19285404147','2025-10-27 03:13:18',NULL,'job-seeker',NULL,'resume/01.pdf','5617 Arthur Place\nNorth Dejon, NH 20082-0319','March Hare. \'Exactly so,\' said Alice. \'Off with her head!\' Alice glanced rather anxiously at the window.\' \'THAT you won\'t\' thought Alice, and, after waiting till she heard her sentence three of the.',1,0,4210,1,NULL,'2025-10-26 20:13:18','2025-10-26 20:13:18',0,1,NULL,NULL,NULL),(65,NULL,'Marion','Kulas','Lobster Quadrille.',NULL,'citlalli.padberg@gmail.com','$2y$12$UkOEVpZDj7K1KztkOOj21utAqvPoETfIUpWTsTPOlR/4Rk3qnC/aC',185,'1997-07-23','+12816820329','2025-10-27 03:13:18',NULL,'job-seeker',NULL,'resume/01.pdf','674 Amir Lodge\nNew Idell, MA 42922','Gryphon in an agony of terror. \'Oh, there goes his PRECIOUS nose\'; as an explanation. \'Oh, you\'re sure to happen,\' she said to herself; \'his eyes are so VERY remarkable in that; nor did Alice think.',1,0,4314,1,NULL,'2025-10-26 20:13:18','2025-10-26 20:13:18',1,1,NULL,NULL,NULL),(66,NULL,'Juliana','Koelpin','So she began: \'O.',NULL,'crooks.nola@murazik.com','$2y$12$FnvQdhpZvIWI2feootC8huIsdOYTOuNsCfv8eOBpniHxE949LrA4a',184,'1977-06-01','+14248469246','2025-10-27 03:13:19',NULL,'job-seeker',NULL,'resume/01.pdf','1412 Amely Street Suite 581\nStokeschester, TN 36611-9005','Hatter. \'Nor I,\' said the Duchess; \'and that\'s a fact.\' Alice did not venture to ask any more if you\'d like it very nice, (it had, in fact, a sort of thing never happened, and now here I am in the.',1,0,2975,0,NULL,'2025-10-26 20:13:19','2025-10-26 20:13:19',0,1,NULL,NULL,NULL),(67,NULL,'Dasia','Altenwerth','Alice, very much.',NULL,'donnelly.laurie@bednar.com','$2y$12$BAJlUOsAsAu9NJGYCfZOduBqhZYtU85F86sdCASe6uH4h6isp4PKG',184,'2007-11-06','+14808450923','2025-10-27 03:13:19',NULL,'job-seeker',NULL,'resume/01.pdf','1515 Beatty Cove Apt. 292\nLake Romanbury, SD 33079-8413','White Rabbit, \'but it doesn\'t matter which way you can;--but I must go and take it away!\' There was nothing on it were nine o\'clock in the window?\' \'Sure, it\'s an arm, yer honour!\' \'Digging for.',1,0,4408,1,NULL,'2025-10-26 20:13:19','2025-10-26 20:13:19',0,1,NULL,NULL,NULL),(68,NULL,'Jacklyn','Gaylord','White Rabbit, \'and.',NULL,'aschneider@gmail.com','$2y$12$Aae/UkI55HzgxTK2M.O23.WCi3oMH01SlI5KfN3/NDR6NFIaF9FY6',186,'2024-05-11','+13616538225','2025-10-27 03:13:19',NULL,'job-seeker',NULL,'resume/01.pdf','943 Johnston Way\nEast Amparo, OK 58204','I\'ve got to do,\' said the Pigeon went on, \'What HAVE you been doing here?\' \'May it please your Majesty,\' he began, \'for bringing these in: but I can\'t get out at the cook took the hookah out of.',1,0,2877,1,NULL,'2025-10-26 20:13:19','2025-10-26 20:13:19',0,1,NULL,NULL,NULL),(69,NULL,'Edgar','Runolfsson','Alice began, in a.',NULL,'adrianna31@rodriguez.com','$2y$12$Jc3VRQbj6exswTzzHVMT4eXUtfPrflVKn4H5sXbF/CXoSBVHjA.Sy',185,'1989-01-08','+18385369922','2025-10-27 03:13:19',NULL,'employer',NULL,NULL,'632 Rodriguez River Apt. 960\nDaishatown, CA 74274','Come on!\' \'Everybody says \"come on!\" here,\' thought Alice, as she heard her voice sounded hoarse and strange, and the baby--the fire-irons came first; then followed a shower of saucepans, plates.',1,0,4223,1,NULL,'2025-10-26 20:13:19','2025-10-26 20:13:19',0,1,NULL,NULL,NULL),(70,NULL,'Krystina','Marquardt','Gryphon: \'I went.',NULL,'thiel.roberta@hotmail.com','$2y$12$6VNeuZRG/HLDtmW9Iny6TelKJ6fGFAdxkwpyE277yk1Uwo0Hcj4zq',186,'1999-05-17','+14848765803','2025-10-27 03:13:20',NULL,'employer',NULL,NULL,'971 Jerde Prairie\nFlaviefort, IA 44229','Alice a good deal on where you want to stay in here any longer!\' She waited for some time without hearing anything more: at last the Dodo managed it.) First it marked out a box of comfits, (luckily.',1,0,2753,1,NULL,'2025-10-26 20:13:20','2025-10-26 20:13:20',1,1,NULL,NULL,NULL),(71,NULL,'Mackenzie','Hill','I\'m talking!\' Just.',NULL,'alba66@gmail.com','$2y$12$u8fTzGcS5ZGcWNzGnDAX8u0OFC9SlyPMsL.F7vsI24AZB6DZ.ioXa',184,'1974-03-24','+17724333146','2025-10-27 03:13:20',NULL,'job-seeker',NULL,'resume/01.pdf','744 Calista Tunnel\nAlexandreamouth, OR 03782-3224','There was a table, with a sigh: \'it\'s always tea-time, and we\'ve no time to wash the things I used to know. Let me see: that would happen: \'\"Miss Alice! Come here directly, and get ready for your.',1,0,2083,1,NULL,'2025-10-26 20:13:20','2025-10-26 20:13:20',1,1,NULL,NULL,NULL),(72,NULL,'Moshe','Halvorson','Good-bye, feet!\'.',NULL,'eosinski@hagenes.net','$2y$12$CpcLfOXsGU.0fXyBd4KkBu6PMWzBrW5AF1xMF9XoPp8BbUBHxCZ6S',184,'1974-10-29','+15743575401','2025-10-27 03:13:20',NULL,'employer',NULL,NULL,'705 Beaulah Ferry Suite 217\nEldridgemouth, MN 90080','YOUR temper!\' \'Hold your tongue!\' said the Footman, and began an account of the Mock Turtle said: \'no wise fish would go anywhere without a grin,\' thought Alice; \'I can\'t remember things as I was.',1,0,3331,0,NULL,'2025-10-26 20:13:20','2025-10-26 20:13:20',1,1,NULL,NULL,NULL),(73,NULL,'Zella','Swift','However, she got.',NULL,'qrowe@hotmail.com','$2y$12$N6NjDd2rwcHILCZFgdqYuufWHarC0tTjskGrtpvcf2.J4/IhYrrj6',186,'1990-05-05','+15348940329','2025-10-27 03:13:20',NULL,'job-seeker',NULL,'resume/01.pdf','5424 Chaz Islands\nCaleighfort, VT 13818-8261','White Rabbit as he found it advisable--\"\' \'Found WHAT?\' said the Hatter: \'but you could see her after the birds! Why, she\'ll eat a little anxiously. \'Yes,\' said Alice, and she set to work, and very.',1,0,2576,0,NULL,'2025-10-26 20:13:20','2025-10-26 20:13:20',1,1,NULL,NULL,NULL),(74,NULL,'Ivah','Schmitt','Lizard, who seemed.',NULL,'ed92@schamberger.com','$2y$12$kVCqzy7bRGl8wIYPEjk1HO1JWAvYeHZTnzKzYFtJxpFaxjnY6GFx.',186,'1998-03-12','+18603570676','2025-10-27 03:13:20',NULL,'job-seeker',NULL,'resume/01.pdf','69697 Madelynn Brooks\nEast Gudrunshire, TX 91939','Alice replied eagerly, for she felt unhappy. \'It was the BEST butter, you know.\' He was looking for eggs, I know I have ordered\'; and she heard a little bottle that stood near the house of the party.',1,0,2121,0,NULL,'2025-10-26 20:13:20','2025-10-26 20:13:20',1,1,NULL,NULL,NULL),(75,NULL,'Marcelina','O\'Conner','I should think you.',NULL,'cindy.morissette@hotmail.com','$2y$12$k8SyBcsqHs2V9ap0yaJCFOcjuf8r6OmFMzdL62PSASSq1slc1muwe',185,'1977-02-22','+18209349441','2025-10-27 03:13:21',NULL,'job-seeker',NULL,'resume/01.pdf','8965 Thiel Locks Apt. 173\nEast Justusberg, IL 63185','Alice did not at all what had become of you? I gave her one, they gave him two, You gave us three or more; They all made of solid glass; there was no time to wash the things between whiles.\' \'Then.',1,0,3387,0,NULL,'2025-10-26 20:13:21','2025-10-26 20:13:21',0,1,NULL,NULL,NULL),(76,NULL,'Kailey','Lakin','Alice thought to.',NULL,'jerod.johns@hammes.net','$2y$12$gis8QK9q.nTE8yrZC/L6V.xhGer/QcxF5kKe2/oSGyvKoBiB8XRtu',184,'2014-01-19','+14799142405','2025-10-27 03:13:21',NULL,'job-seeker',NULL,'resume/01.pdf','61478 Louvenia Squares Apt. 768\nEast Juliomouth, AZ 39225-2512','CHAPTER VI. Pig and Pepper For a minute or two, they began running about in all directions, tumbling up against each other; however, they got thrown out to the Gryphon. \'It all came different!\' the.',1,0,3177,0,NULL,'2025-10-26 20:13:21','2025-10-26 20:13:21',1,1,NULL,NULL,NULL),(77,NULL,'Shyann','Keeling','Alice started to.',NULL,'tre37@hotmail.com','$2y$12$KZUxbIDVSY8CQ2qbnJHTIO/TfxExLK3bVbvJMX6ORlEQ2oKbqOSDG',185,'2007-02-22','+19864017090','2025-10-27 03:13:21',NULL,'job-seeker',NULL,'resume/01.pdf','75181 Schultz Center Apt. 448\nAudreannechester, MS 19936','She had already heard her sentence three of her age knew the right size for ten minutes together!\' \'Can\'t remember WHAT things?\' said the King; \'and don\'t be nervous, or I\'ll have you got in as.',1,0,2975,1,NULL,'2025-10-26 20:13:21','2025-10-26 20:13:21',0,1,NULL,NULL,NULL),(78,NULL,'Tobin','Streich','I believe.\' \'Boots.',NULL,'pcassin@jast.biz','$2y$12$APtx6fak9AOw2S9pRwVIve0oxZgp4tp1CbOd8DlDqOy8FCowawbAW',186,'1971-11-09','+19389998188','2025-10-27 03:13:21',NULL,'employer',NULL,NULL,'8973 Buster Circle\nNayelifort, MO 56801','But I\'ve got back to the door, and the shrill voice of the country is, you ARE a simpleton.\' Alice did not sneeze, were the cook, to see the earth takes twenty-four hours to turn into a doze; but.',1,0,841,0,NULL,'2025-10-26 20:13:21','2025-10-26 20:13:21',1,1,NULL,NULL,NULL),(79,NULL,'Kimberly','Graham','I\'ve offended it.',NULL,'pagac.arnold@hotmail.com','$2y$12$5xU9xNoPUBxHNf9smlTotepYMo9DnKK7ecj9GQg1zKLgXBRtX1qJO',185,'1972-03-30','+19893836963','2025-10-27 03:13:21',NULL,'employer',NULL,NULL,'8844 Kylee Points Apt. 002\nJamiestad, RI 61837-6268','Dormouse,\' the Queen in a day is very confusing.\' \'It isn\'t,\' said the Dodo, pointing to Alice an excellent plan, no doubt, and very angrily. \'A knot!\' said Alice, seriously, \'I\'ll have nothing more.',1,0,3275,1,NULL,'2025-10-26 20:13:21','2025-10-26 20:13:21',0,1,NULL,NULL,NULL),(80,NULL,'Candelario','Lesch','I to get hold of.',NULL,'jolson@gmail.com','$2y$12$27ftJNQ2ppJq2iGemthDw.Piz9.g0mmn82uLWn/WaL0WZn6KaM0ZG',185,'1978-01-22','+16233738203','2025-10-27 03:13:22',NULL,'job-seeker',NULL,'resume/01.pdf','2810 Orpha Walk Suite 784\nWest Jaceyville, NM 16422-9237','Queen, and in another moment that it would like the wind, and was just going to be, from one minute to another! However, I\'ve got to go from here?\' \'That depends a good thing!\' she said to herself.',1,0,898,0,NULL,'2025-10-26 20:13:22','2025-10-26 20:13:22',1,1,NULL,NULL,NULL),(81,NULL,'Katrine','Daugherty','The master was an.',NULL,'cesar34@steuber.com','$2y$12$a7FPDxryLlHpmhpVivxTeeYT85QZPXpTU5Xp6CTkHyWBCC2x7K23u',186,'2002-07-24','+17759366674','2025-10-27 03:13:22',NULL,'job-seeker',NULL,'resume/01.pdf','23727 Armstrong Flats Suite 369\nParisianbury, OH 67184','Dormouse followed him: the March Hare. \'Exactly so,\' said the Hatter. \'He won\'t stand beating. Now, if you like!\' the Duchess replied, in a soothing tone: \'don\'t be angry about it. And yet you.',1,0,3399,1,NULL,'2025-10-26 20:13:22','2025-10-26 20:13:22',0,1,NULL,NULL,NULL),(82,NULL,'Guy','Zieme','I\'d only been the.',NULL,'mathew76@thompson.com','$2y$12$zntwjnlIeZXMrRlG97ANGe60f69nx6ZUMUAFL4bL9mxmSxRtVPeDK',186,'2012-01-06','+16822470075','2025-10-27 03:13:22',NULL,'job-seeker',NULL,'resume/01.pdf','8284 Feest Cliff Apt. 225\nJoburgh, VT 57775-5366','Mouse, sharply and very angrily. \'A knot!\' said Alice, and her eyes anxiously fixed on it, for she had a little different. But if I\'m Mabel, I\'ll stay down here! It\'ll be no use going back to the.',1,0,3711,1,NULL,'2025-10-26 20:13:22','2025-10-26 20:13:22',0,1,NULL,NULL,NULL),(83,NULL,'Leila','Murray','Alice had no very.',NULL,'sschmitt@bradtke.org','$2y$12$1kJNbmGFwoN.vYe1JztRT.szvyDQReN0QeAqjYFdhLX0v4GWK6TcG',185,'2013-11-15','+14583622512','2025-10-27 03:13:22',NULL,'employer',NULL,NULL,'397 Blaze Knolls Suite 383\nKossview, TX 27898-5939','CHAPTER X. The Lobster Quadrille is!\' \'No, indeed,\' said Alice. \'Anything you like,\' said the Duchess; \'and the moral of that is--\"Birds of a candle is blown out, for she was holding, and she drew.',1,0,4502,1,NULL,'2025-10-26 20:13:22','2025-10-26 20:13:22',1,1,NULL,NULL,NULL),(84,NULL,'Crystel','Yost','White Rabbit as he.',NULL,'annetta.jakubowski@yahoo.com','$2y$12$4/LfjjyndA9.1LcERRJwHOsZNsJqLyG0bEK7su9bZTMXVLjf/xIPC',186,'2013-02-27','+12792027412','2025-10-27 03:13:22',NULL,'employer',NULL,NULL,'90863 Haleigh Radial Apt. 046\nBeckerstad, LA 14424','I can\'t quite follow it as to bring tears into her face. \'Wake up, Alice dear!\' said her sister; \'Why, what a Mock Turtle replied, counting off the cake. * * * * * * * * * * * * * * * * * * * * * *.',1,0,3619,1,NULL,'2025-10-26 20:13:22','2025-10-26 20:13:22',0,1,NULL,NULL,NULL),(85,NULL,'Gay','Hayes','Gryphon, and the.',NULL,'waldo.franecki@hotmail.com','$2y$12$2zImQ7q8qsYIPmNw40TMGOGWMVQe15X2CK0mF7vOqpAbL1Ox/nBwK',185,'2017-04-23','+13257263423','2025-10-27 03:13:23',NULL,'employer',NULL,NULL,'65861 Dietrich Fords\nNorth Syblechester, IA 50488-3192','But the snail replied \"Too far, too far!\" and gave a sudden leap out of sight. Alice remained looking thoughtfully at the other, trying every door, she found this a very long silence, broken only by.',1,0,674,0,NULL,'2025-10-26 20:13:23','2025-10-26 20:13:23',1,1,NULL,NULL,NULL),(86,NULL,'Westley','Koepp','Cat again, sitting.',NULL,'rabernathy@hotmail.com','$2y$12$jcWsZ2FeIwxLLuCQ0.crdusAJFnhhOhwtzGyhRNWh/az0u9jEz1d2',185,'1972-02-22','+13522282180','2025-10-27 03:13:23',NULL,'job-seeker',NULL,'resume/01.pdf','20088 Morgan Brooks\nNew Zachariahtown, ID 39485','Which shall sing?\' \'Oh, YOU sing,\' said the Cat, \'if you only kept on puzzling about it while the Mouse was bristling all over, and she said to herself. \'Shy, they seem to put everything upon Bill!.',1,0,4012,1,NULL,'2025-10-26 20:13:23','2025-10-26 20:13:23',1,1,NULL,NULL,NULL),(87,NULL,'Eda','Labadie','It did so indeed.',NULL,'schiller.connor@west.com','$2y$12$LCsmZI0geqtQrz2pSrQ.xeyaEoPSy3W5I2KAsMbV1frHL6W3sdqmW',185,'1970-02-06','+19128212067','2025-10-27 03:13:23',NULL,'job-seeker',NULL,'resume/01.pdf','3668 Adell Inlet\nSouth Arlo, OR 93227','However, on the ground as she spoke. \'I must go and take it away!\' There was a body to cut it off from: that he had to be ashamed of yourself,\' said Alice, \'a great girl like you,\' (she might well.',1,0,353,0,NULL,'2025-10-26 20:13:23','2025-10-26 20:13:23',0,1,NULL,NULL,NULL),(88,NULL,'Osbaldo','Mann','Gryphon whispered.',NULL,'gernser@hagenes.net','$2y$12$I7GgP0vHIKg14Q8JkOPVOunbXMCfrJBaEJ5KGRP2gtS48hUlzI7pu',184,'2005-01-11','+19496131049','2025-10-27 03:13:23',NULL,'job-seeker',NULL,'resume/01.pdf','87968 Misty Stream Apt. 935\nWuckertview, VT 68699-5981','Alice\'s shoulder as he shook both his shoes off. \'Give your evidence,\' said the Dodo, \'the best way you go,\' said the Duchess. An invitation from the shock of being upset, and their curls got.',1,0,3356,1,NULL,'2025-10-26 20:13:23','2025-10-26 20:13:23',0,1,NULL,NULL,NULL),(89,NULL,'Lee','Champlin','Alice ventured to.',NULL,'bethany28@gmail.com','$2y$12$/Hc/LdZkHror27x2GEGiv.eCg9QA3BJun6XG/S7r6iBh5APCivCnu',185,'1985-06-22','+19188053498','2025-10-27 03:13:23',NULL,'employer',NULL,NULL,'26678 Simonis Field Suite 282\nOkunevachester, CA 92489-9187','King put on her lap as if it began ordering people about like mad things all this grand procession, came THE KING AND QUEEN OF HEARTS. Alice was rather glad there WAS no one listening, this time, as.',1,0,4602,1,NULL,'2025-10-26 20:13:23','2025-10-26 20:13:23',1,1,NULL,NULL,NULL),(90,NULL,'Elbert','Reichert','They\'re dreadfully.',NULL,'jwunsch@gmail.com','$2y$12$/XPE1TfZ6Z4vDCzhRlI7HOKFGLBHi1KV2NFAE7hLGGaHec.CONuoG',186,'2021-04-19','+17242442458','2025-10-27 03:13:24',NULL,'employer',NULL,NULL,'9690 Madisen Course\nWest Melynashire, NH 20661','Alice was more than that, if you were INSIDE, you might like to go down--Here, Bill! the master says you\'re to go among mad people,\' Alice remarked. \'Oh, you can\'t help that,\' said the White Rabbit.',1,0,510,0,NULL,'2025-10-26 20:13:24','2025-10-26 20:13:24',1,1,NULL,NULL,NULL),(91,NULL,'Laurence','Hansen','She generally gave.',NULL,'juvenal98@connelly.com','$2y$12$Z9D1YcjIu3q7xTXXAK0b.eHChtXTpWZ9F3nrf/XXV8g1CnZ2wkbZG',185,'2009-07-31','+16512072113','2025-10-27 03:13:24',NULL,'employer',NULL,NULL,'54675 Barton Field\nLoisland, OR 88133','Alice, \'a great girl like you,\' (she might well say this), \'to go on in the house, and found that, as nearly as large as the jury eagerly wrote down all three to settle the question, and they sat.',1,0,1757,0,NULL,'2025-10-26 20:13:24','2025-10-26 20:13:24',1,1,NULL,NULL,NULL),(92,NULL,'Neoma','O\'Keefe','I\'ll get into that.',NULL,'kling.clemmie@pollich.com','$2y$12$/l5zCn2MGQrn7os9O1q84.TFe8YHMaoUHFrcGUcDnrR451OLSHjEm',184,'1993-04-17','+12795141658','2025-10-27 03:13:24',NULL,'employer',NULL,NULL,'4349 Collins Cliffs Apt. 439\nLake Rossville, AK 47200-3492','Off--\' \'Nonsense!\' said Alice, who felt very curious to see if there are, nobody attends to them--and you\'ve no idea how to spell \'stupid,\' and that if something wasn\'t done about it while the rest.',1,0,4430,1,NULL,'2025-10-26 20:13:24','2025-10-26 20:13:24',1,1,NULL,NULL,NULL),(93,NULL,'Lauren','Kunde','Hatter. \'Does YOUR.',NULL,'keira.heathcote@gmail.com','$2y$12$tVzWjtqRc/vIFfv0gfhntupoHKXNnowLPG0Lr4iSvmppHa88qsH3W',185,'2017-01-26','+19868189555','2025-10-27 03:13:24',NULL,'job-seeker',NULL,'resume/01.pdf','47661 Maverick Keys\nAudreyton, FL 29434','Alice replied eagerly, for she was small enough to try the patience of an oyster!\' \'I wish I could say if I like being that person, I\'ll come up: if not, I\'ll stay down here with me! There are no.',1,0,646,1,NULL,'2025-10-26 20:13:24','2025-10-26 20:13:24',1,1,NULL,NULL,NULL),(94,NULL,'Blair','Hahn','He trusts to you.',NULL,'moen.savion@douglas.com','$2y$12$d.CZqFLferJ4P0RXw3Lpye98nUind6GxFeOPBVDq8UD5ZuQj/5yUC',184,'1975-03-25','+17543071084','2025-10-27 03:13:24',NULL,'job-seeker',NULL,'resume/01.pdf','90146 Kaci Islands Apt. 217\nWest Eviemouth, MI 04548-9096','I shall have somebody to talk nonsense. The Queen\'s Croquet-Ground A large rose-tree stood near the door as you are; secondly, because they\'re making such a rule at processions; \'and besides, what.',1,0,1744,1,NULL,'2025-10-26 20:13:24','2025-10-26 20:13:24',1,1,NULL,NULL,NULL),(95,NULL,'Zakary','Blick','Alice considered a.',NULL,'fhermann@gmail.com','$2y$12$e2cEZVIEHyDbwfc3D/0NiOFKd1qiCmww6NRg.WPR.Ae9vVzrEIPku',185,'1988-07-02','+19144552900','2025-10-27 03:13:25',NULL,'job-seeker',NULL,'resume/01.pdf','182 Torrey Ville\nNorth Goldaview, WA 29361-9688','BEST butter,\' the March Hare. \'Then it wasn\'t trouble enough hatching the eggs,\' said the Gryphon said to herself \'This is Bill,\' she gave a look askance-- Said he thanked the whiting kindly, but he.',1,0,2211,1,NULL,'2025-10-26 20:13:25','2025-10-26 20:13:25',0,1,NULL,NULL,NULL),(96,NULL,'Brianne','Watsica','Why, I haven\'t had.',NULL,'lang.rachel@tillman.com','$2y$12$D5NSdIzUzYdUxuKJjk5/6Oml6wvbn7FVYITEsMS7llExsupS/rJcC',185,'2014-12-29','+12162351980','2025-10-27 03:13:25',NULL,'job-seeker',NULL,'resume/01.pdf','688 Ernser Plains\nPorterport, NY 00155-0493','Bill, I fancy--Who\'s to go down--Here, Bill! the master says you\'re to go with the end of half an hour or so, and were quite silent, and looked along the sea-shore--\' \'Two lines!\' cried the Gryphon.',1,0,4444,1,NULL,'2025-10-26 20:13:25','2025-10-26 20:13:25',0,1,NULL,NULL,NULL),(97,NULL,'Dewitt','Mayer','Queen ordering off.',NULL,'geo.heller@mills.info','$2y$12$WjePBYZjZij4NDoDdax8yOKb5vmB0mpMgswuXGOjmYuweRJMcNEQS',186,'2001-03-30','+17245064224','2025-10-27 03:13:25',NULL,'job-seeker',NULL,'resume/01.pdf','6673 Bogan Cove\nMaxberg, MT 30515-9596','Quick, now!\' And Alice was beginning to get in?\' \'There might be some sense in your pocket?\' he went on, \'you see, a dog growls when it\'s angry, and wags its tail about in a great many more than.',1,0,3425,1,NULL,'2025-10-26 20:13:25','2025-10-26 20:13:25',1,1,NULL,NULL,NULL),(98,NULL,'Josephine','Kilback','Alice very meekly.',NULL,'lynch.lonzo@hotmail.com','$2y$12$tyeBJVmNxtSef6xzLr099ey3PDnYtkpvFvBwfudxy0iLCVMUqpFsa',185,'2022-11-01','+13606898183','2025-10-27 03:13:25',NULL,'employer',NULL,NULL,'84715 Hahn Harbors Apt. 269\nBaumbachhaven, NC 52333','Alice timidly. \'Would you tell me,\' said Alice, who was trembling down to the end of the sea.\' \'I couldn\'t help it,\' said the Pigeon; \'but if you\'ve seen them at dinn--\' she checked herself hastily.',1,0,4719,1,NULL,'2025-10-26 20:13:25','2025-10-26 20:13:25',1,1,NULL,NULL,NULL),(99,NULL,'Camryn','Schinner','SOMEBODY ought to.',NULL,'josie.runolfsson@ledner.com','$2y$12$zak52sXMOwpRs/iR3UmRMu8T3JzsXuyimO/n59Y6JcDp4DSwLGKha',184,'2006-05-06','+14245170891','2025-10-27 03:13:25',NULL,'employer',NULL,NULL,'4082 Emerson Parkway Suite 000\nNorth Samir, IN 93411','Ann! Mary Ann!\' said the Mouse, frowning, but very politely: \'Did you speak?\' \'Not I!\' he replied. \'We quarrelled last March--just before HE went mad, you know--\' She had quite a long breath, and.',1,0,536,1,NULL,'2025-10-26 20:13:25','2025-10-26 20:13:25',1,1,NULL,NULL,NULL),(100,NULL,'Franz','Flatley','Sir, With no jury.',NULL,'krystal03@runolfsson.com','$2y$12$Y3pXun2xHfxZ96j3jyCvAubH3LhbpstsX3QKiY.LqDcyPNE6D5IX.',184,'1976-03-24','+14783862873','2025-10-27 03:13:26',NULL,'employer',NULL,NULL,'5227 Reta Vista Suite 439\nNorth Shad, KY 54469','HE taught us Drawling, Stretching, and Fainting in Coils.\' \'What was that?\' inquired Alice. \'Reeling and Writhing, of course, to begin with; and being so many tea-things are put out here?\' she.',1,0,3543,1,NULL,'2025-10-26 20:13:26','2025-10-26 20:13:26',1,1,NULL,NULL,NULL);
/*!40000 ALTER TABLE `jb_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_accounts_translations`
--

DROP TABLE IF EXISTS `jb_accounts_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_accounts_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_accounts_id` bigint unsigned NOT NULL,
  `first_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_accounts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_accounts_translations`
--

LOCK TABLES `jb_accounts_translations` WRITE;
/*!40000 ALTER TABLE `jb_accounts_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_accounts_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_analytics`
--

DROP TABLE IF EXISTS `jb_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_analytics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` bigint unsigned NOT NULL,
  `country` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_full` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_analytics_job_id_index` (`job_id`),
  KEY `jb_analytics_created_at_index` (`created_at`),
  KEY `jb_analytics_job_date_index` (`job_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_analytics`
--

LOCK TABLES `jb_analytics` WRITE;
/*!40000 ALTER TABLE `jb_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_applications`
--

DROP TABLE IF EXISTS `jb_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `job_id` bigint unsigned NOT NULL,
  `resume` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_letter` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `is_external_apply` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_applications_job_id_index` (`job_id`),
  KEY `jb_applications_account_id_index` (`account_id`),
  KEY `jb_applications_status_index` (`status`),
  KEY `jb_applications_created_at_index` (`created_at`),
  KEY `jb_applications_job_status_index` (`job_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_applications`
--

LOCK TABLES `jb_applications` WRITE;
/*!40000 ALTER TABLE `jb_applications` DISABLE KEYS */;
INSERT INTO `jb_applications` VALUES (1,'Louie','Hackett','+15207947496','ufeil@anderson.com','Dormouse fell asleep instantly, and Alice was more hopeless than ever: she sat down again very sadly and quietly, and looked at Alice. \'I\'M not a bit of the pack, she could do to come upon them THIS.',1,'resume/01.pdf','resume/01.pdf',9,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(2,'Ivah','Schmitt','+18603570676','ed92@schamberger.com','Rabbit hastily interrupted. \'There\'s a great hurry; \'and their names were Elsie, Lacie, and Tillie; and they sat down, and the words \'DRINK ME,\' but nevertheless she uncorked it and put it into one.',24,'resume/01.pdf','resume/01.pdf',74,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(3,'Heidi','Rau','+13412489900','reagan83@yahoo.com','CHAPTER VI. Pig and Pepper For a minute or two, looking for them, and considered a little, half expecting to see it again, but it just grazed his nose, you know?\' \'It\'s the stupidest tea-party I.',14,'resume/01.pdf','resume/01.pdf',11,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(4,'Ludie','Muller','+17379949447','luisa68@johnson.com','First, because I\'m on the back. At last the Gryphon interrupted in a low, trembling voice. \'There\'s more evidence to come out among the bright flower-beds and the moon, and memory, and muchness--you.',21,'resume/01.pdf','resume/01.pdf',7,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(5,'Mackenzie','Hill','+17724333146','alba66@gmail.com','They had a head could be no chance of this, so she went on for some time without interrupting it. \'They must go by the pope, was soon left alone. \'I wish you wouldn\'t keep appearing and vanishing so.',19,'resume/01.pdf','resume/01.pdf',71,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(6,'Isabella','Hahn','+15077930302','kirk61@yahoo.com','I ought to be otherwise than what it was: she was dozing off, and found that it might appear to others that what you mean,\' said Alice. \'Well, I should like to be rude, so she bore it as well as she.',36,'resume/01.pdf','resume/01.pdf',6,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(7,'Marcelina','O\'Conner','+18209349441','cindy.morissette@hotmail.com','I\'d only been the whiting,\' said the Gryphon, and, taking Alice by the hedge!\' then silence, and then added them up, and there they lay on the glass table and the game was in the wood,\' continued.',35,'resume/01.pdf','resume/01.pdf',75,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(8,'Jerrell','Price','+15018436151','brekke.katlyn@hickle.info','Lory, as soon as she could have been changed several times since then.\' \'What do you know that you\'re mad?\' \'To begin with,\' said the White Rabbit returning, splendidly dressed, with a T!\' said the.',11,'resume/01.pdf','resume/01.pdf',33,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(9,'Nelda','Farrell','+16629673249','kaleigh.reichert@turcotte.com','What WILL become of me?\' Luckily for Alice, the little door was shut again, and did not sneeze, were the verses on his knee, and the Queen, \'and he shall tell you his history,\' As they walked off.',25,'resume/01.pdf','resume/01.pdf',21,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(10,'Kadin','Muller','+14703813915','eleanora.labadie@gmail.com','White Rabbit; \'in fact, there\'s nothing written on the back. At last the Mouse, who was sitting next to her. The Cat seemed to think that very few things indeed were really impossible. There seemed.',38,'resume/01.pdf','resume/01.pdf',42,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(11,'Westley','Koepp','+13522282180','rabernathy@hotmail.com','The door led right into it. \'That\'s very important,\' the King say in a furious passion, and went in. The door led right into it. \'That\'s very important,\' the King very decidedly, and the whole place.',27,'resume/01.pdf','resume/01.pdf',86,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(12,'Katrine','Daugherty','+17759366674','cesar34@steuber.com','Hardly knowing what she did, she picked her way into a tidy little room with a soldier on each side to guard him; and near the house if it wasn\'t trouble enough hatching the eggs,\' said the King.',3,'resume/01.pdf','resume/01.pdf',81,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(13,'Nolan','Schumm','+14796545533','fwitting@lehner.com','I ever saw one that size? Why, it fills the whole she thought to herself in a moment: she looked back once or twice she had never been so much surprised, that for the Duchess began in a tone of this.',23,'resume/01.pdf','resume/01.pdf',43,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(14,'Sammie','Davis','+13477407171','albert.kuhic@rolfson.com','Seaography: then Drawling--the Drawling-master was an uncomfortably sharp chin. However, she soon made out the verses on his flappers, \'--Mystery, ancient and modern, with Seaography: then.',6,'resume/01.pdf','resume/01.pdf',14,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(15,'Dane','Ryan','+18634398578','muller.marie@altenwerth.com','I see\"!\' \'You might just as the question was evidently meant for her. \'Yes!\' shouted Alice. \'Come on, then,\' said Alice, who was talking. \'How CAN I have dropped them, I wonder?\' As she said aloud.',16,'resume/01.pdf','resume/01.pdf',41,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(16,'Everett','Treutel','+12812485020','hugh15@gmail.com','How brave they\'ll all think me for his housemaid,\' she said this, she noticed that the reason and all her life. Indeed, she had not attended to this mouse? Everything is so out-of-the-way down here.',46,'resume/01.pdf','resume/01.pdf',25,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(17,'Calista','Konopelski','+13855179256','obeier@nolan.com','Alice. \'Why, there they are!\' said the Duchess. \'I make you grow taller, and the great puzzle!\' And she kept fanning herself all the rest of my life.\' \'You are old,\' said the Cat, \'if you don\'t even.',13,'resume/01.pdf','resume/01.pdf',55,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(18,'Margie','Stoltenberg','+12394571658','noble.brakus@yahoo.com','I know!\' exclaimed Alice, who felt ready to play croquet.\' The Frog-Footman repeated, in the world! Oh, my dear paws! Oh my fur and whiskers! She\'ll get me executed, as sure as ferrets are ferrets!.',22,'resume/01.pdf','resume/01.pdf',24,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(19,'Eda','Labadie','+19128212067','schiller.connor@west.com','Alice, very earnestly. \'I\'ve had nothing yet,\' Alice replied very gravely. \'What else have you executed, whether you\'re a little girl she\'ll think me for his housemaid,\' she said to Alice; and Alice.',8,'resume/01.pdf','resume/01.pdf',87,1,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26'),(20,'Jacklyn','Gaylord','+13616538225','aschneider@gmail.com','How I wonder if I shall ever see such a thing before, but she ran off as hard as it spoke. \'As wet as ever,\' said Alice thoughtfully: \'but then--I shouldn\'t be hungry for it, while the rest of the.',30,'resume/01.pdf','resume/01.pdf',68,0,'checked','2025-10-26 20:13:26','2025-10-26 20:13:26');
/*!40000 ALTER TABLE `jb_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_career_levels`
--

DROP TABLE IF EXISTS `jb_career_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_career_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_career_levels`
--

LOCK TABLES `jb_career_levels` WRITE;
/*!40000 ALTER TABLE `jb_career_levels` DISABLE KEYS */;
INSERT INTO `jb_career_levels` VALUES (1,'Department Head',0,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(2,'Entry Level',0,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(3,'Experienced Professional',0,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(4,'GM / CEO / Country Head / President',0,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(5,'Intern/Student',0,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02');
/*!40000 ALTER TABLE `jb_career_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_career_levels_translations`
--

DROP TABLE IF EXISTS `jb_career_levels_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_career_levels_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_career_levels_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_career_levels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_career_levels_translations`
--

LOCK TABLES `jb_career_levels_translations` WRITE;
/*!40000 ALTER TABLE `jb_career_levels_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_career_levels_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_categories`
--

DROP TABLE IF EXISTS `jb_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `is_featured` tinyint NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_id` bigint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `jb_categories_status_index` (`status`),
  KEY `jb_categories_is_featured_index` (`is_featured`),
  KEY `jb_categories_parent_id_index` (`parent_id`),
  KEY `jb_categories_order_index` (`order`),
  KEY `jb_categories_published_featured_index` (`status`,`is_featured`,`order`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_categories`
--

LOCK TABLES `jb_categories` WRITE;
/*!40000 ALTER TABLE `jb_categories` DISABLE KEYS */;
INSERT INTO `jb_categories` VALUES (1,'Content Writer',NULL,0,0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(2,'Market Research',NULL,1,0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(3,'Marketing &amp; Sale',NULL,2,0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(4,'Customer Help',NULL,3,0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(5,'Finance',NULL,4,0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(6,'Software',NULL,5,0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(7,'Human Resource',NULL,6,0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(8,'Management',NULL,7,0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(9,'Retail &amp; Products',NULL,8,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0),(10,'Security Analyst',NULL,9,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03',0);
/*!40000 ALTER TABLE `jb_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_categories_translations`
--

DROP TABLE IF EXISTS `jb_categories_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_categories_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_categories_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_categories_translations`
--

LOCK TABLES `jb_categories_translations` WRITE;
/*!40000 ALTER TABLE `jb_categories_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_categories_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_companies`
--

DROP TABLE IF EXISTS `jb_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` mediumtext COLLATE utf8mb4_unicode_ci,
  `website` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT '1',
  `state_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `postal_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year_founded` int unsigned DEFAULT NULL,
  `ceo` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_of_offices` int unsigned DEFAULT NULL,
  `number_of_employees` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annual_revenue` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_featured` tinyint NOT NULL DEFAULT '0',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verification_note` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `views` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tax_id` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jb_companies_unique_id_unique` (`unique_id`),
  KEY `jb_companies_status_index` (`status`),
  KEY `jb_companies_is_featured_index` (`is_featured`),
  KEY `jb_companies_country_id_index` (`country_id`),
  KEY `jb_companies_state_id_index` (`state_id`),
  KEY `jb_companies_city_id_index` (`city_id`),
  KEY `jb_companies_created_at_index` (`created_at`),
  KEY `jb_companies_published_featured_index` (`status`,`is_featured`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_companies`
--

LOCK TABLES `jb_companies` WRITE;
/*!40000 ALTER TABLE `jb_companies` DISABLE KEYS */;
INSERT INTO `jb_companies` VALUES (1,NULL,'LinkedIn',NULL,'Repellendus iusto quia id necessitatibus qui. Consequatur debitis sed praesentium quisquam. Alias ut sed harum nulla tempore consequuntur veritatis. Sed facere accusamus quasi consequatur voluptatem.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.linkedin.com/','companies/1.png','42.493982','-75.397501','2703 Lowe Pike\nHortensehaven, TX 75289',5,5,5,NULL,'+19304853488',1972,'John Doe',2,'3','6M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-07-01 20:13:04',1,'Premium partner - verified','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(2,NULL,'Adobe Illustrator',NULL,'Tempore illo ut et et necessitatibus totam. Ut qui est dolores ea. Quis temporibus libero omnis quidem qui. Aut voluptas dolor ut debitis dolor ut asperiores.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.adobe.com/','companies/2.png','42.649035','-75.390111','41664 Paucek Stream\nSouth Bobby, NH 73070',2,2,2,NULL,'+19019431872',1995,'Jeff Werner',6,'2','8M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-01-07 20:13:04',1,'Company credentials confirmed','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(3,NULL,'Bing Search',NULL,'Ab dolor sunt tempora pariatur. Cumque aperiam dolor qui dignissimos. Laboriosam architecto dolorem explicabo autem rerum expedita. Ut eligendi omnis natus facilis fugit eum.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.bing.com/','companies/3.png','43.064771','-75.991504','859 Noemi Bypass Apt. 250\nLake Traceyview, LA 61576',3,3,3,NULL,'+13474238266',2010,'Nakamura',3,'8','1M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-06-15 20:13:04',1,'Documents verified successfully','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(4,NULL,'Dailymotion',NULL,'Consequuntur voluptas veritatis non voluptatum in. Itaque similique aut laborum quisquam omnis quos placeat.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.dailymotion.com/','companies/4.png','43.027509','-75.962785','538 Becker Drive\nWelchchester, VA 79174-2166',4,4,4,NULL,'+19479288751',1996,'John Doe',6,'10','3M',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(5,NULL,'Linkedin',NULL,'Architecto voluptatibus voluptatum perspiciatis impedit velit. Quia aliquam aut possimus voluptatibus. Non blanditiis eligendi vitae corrupti.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.linkedin.com/','companies/5.png','43.905129','-75.226524','411 Jakob Rapid Apt. 269\nSouth Vanessa, NV 80619-2801',4,4,4,NULL,'+19569727699',2007,'John Doe',6,'3','5M',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(6,NULL,'Quora JSC',NULL,'Cumque eligendi nisi ducimus rerum. Veritatis placeat iusto sint quia harum. Molestias nemo sit magni accusantium consequatur sed deleniti.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.quora.com/','companies/6.png','43.783836','-75.003313','25520 Vicenta Highway\nWest Lorenza, DC 54102-6114',4,4,4,NULL,'+13046257484',1992,'John Doe',7,'7','7M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-03-31 20:13:04',1,'Company credentials confirmed','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(7,NULL,'Nintendo',NULL,'Magnam possimus rerum et doloribus libero quia praesentium. Minima possimus labore omnis provident voluptatibus consequuntur reprehenderit ut.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.nintendo.com/','companies/7.png','43.439146','-75.529246','2340 Terrill Gardens\nHerzogshire, WV 05298-5211',1,1,1,NULL,'+19253308063',2007,'Steve Jobs',8,'8','8M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-09-04 20:13:04',1,'Company credentials confirmed','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(8,NULL,'Periscope',NULL,'Vel aut aut sed. Voluptatem maxime nostrum voluptas harum aut voluptas et. Accusamus quia et molestias consequatur reprehenderit sint. Mollitia laudantium rerum hic repellendus porro soluta quae.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.pscp.tv/','companies/8.png','43.345334','-75.435967','33322 Jacinto Radial Suite 367\nNorth Ima, HI 49839-0799',6,6,6,NULL,'+17623251333',2010,'John Doe',4,'10','1M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-01-03 20:13:04',1,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(9,NULL,'NewSum',NULL,'Nihil ipsum quaerat veritatis sed praesentium laudantium a perferendis. Tempora animi cumque deleniti est autem ex. Neque consequatur dolor enim eaque nesciunt temporibus nemo maxime.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://newsum.us/','companies/4.png','42.928741','-76.04579','971 Brooklyn Inlet Suite 820\nRachelleview, OR 84168-7971',3,3,3,NULL,'+12764306862',1979,'John Doe',5,'2','9M',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(10,NULL,'PowerHome',NULL,'Velit sed quia aut eum consequatur dignissimos. Eveniet quia rerum corporis blanditiis voluptatem illum.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.pscp.tv/','companies/5.png','43.406581','-76.500639','89541 Dickens Pass Suite 823\nLake Anna, CT 57498-1327',1,1,1,NULL,'+16783621877',1995,'John Doe',5,'2','8M',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(11,NULL,'Whop.com',NULL,'Laudantium ratione nostrum porro voluptas eum. Cupiditate est atque atque exercitationem reiciendis in animi.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://whop.com/','companies/6.png','43.114525','-75.964499','94783 Fay Tunnel Apt. 747\nEast Russelfort, NM 43446-5092',4,4,4,NULL,'+17312016656',1995,'John Doe',7,'2','7M',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(12,NULL,'Greenwood',NULL,'Illum sed cumque nobis et necessitatibus ipsum repellendus. Debitis necessitatibus possimus ducimus reiciendis alias sequi. Tempore eos quae est perferendis expedita qui hic at.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.greenwoodjs.io/','companies/7.png','43.78578','-75.889819','346 Davis Trail Apt. 025\nNew Leannaland, IN 13493-1772',1,1,1,NULL,'+13174590405',2017,'John Doe',8,'6','7M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-09-24 20:13:04',1,'Documents verified successfully','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(13,NULL,'Kentucky',NULL,'Quam aperiam qui inventore. Aut et delectus natus sint. Officiis voluptas fugit modi ut est aut sapiente dolorum. Voluptatibus autem voluptatum facere nesciunt consequatur.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.kentucky.gov/','companies/8.png','42.834048','-76.366789','617 Brody Forges\nSchimmelland, KY 97474',2,2,2,NULL,'+18087822472',1992,'John Doe',5,'2','10M',NULL,NULL,NULL,NULL,NULL,1,0,NULL,NULL,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(14,NULL,'Equity',NULL,'Et odio vel porro non. Libero id perspiciatis enim eos nam tenetur explicabo nulla. Sed quas accusamus at veniam fuga quo. Est voluptas eveniet deserunt.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.equity.org.uk/','companies/6.png','43.179703','-75.753395','6129 Rosenbaum Island\nEast Aracelyshire, AK 59632-3202',5,5,5,NULL,'+13646804697',1985,'John Doe',9,'8','1M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-03-16 20:13:04',1,'Verified trusted partner','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(15,NULL,'Honda',NULL,'Quaerat nulla ut quia autem cupiditate. Quos molestiae aut iure aut. Asperiores non quasi nam.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.honda.com/','companies/9.png','43.253941','-75.479451','6067 Concepcion Pass\nSouth Domenic, TN 30142-5547',1,1,1,NULL,'+19016291640',1982,'John Doe',8,'7','8M',NULL,NULL,NULL,NULL,NULL,1,1,'2025-05-30 20:13:04',1,'Premium partner - verified','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(16,NULL,'Toyota',NULL,'Voluptatem totam iure est. Ut iste molestiae repudiandae ut.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.toyota.com/','companies/5.png','42.793246','-75.528245','41612 Ankunding Plain Suite 361\nLake Parkerchester, NE 05579',4,4,4,NULL,'+19849341208',1998,'John Doe',3,'5','3M',NULL,NULL,NULL,NULL,NULL,0,1,'2025-06-25 20:13:04',1,'Company credentials confirmed','published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(17,NULL,'Lexus',NULL,'Tempora ut explicabo soluta in et. Voluptas in non quia veritatis ut. Iure ut ut atque dicta ut.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://www.pscp.tv/','companies/3.png','43.802572','-76.013218','8300 Katarina Flats Apt. 411\nJenkinsland, GA 10963-6623',3,3,3,NULL,'+19896027301',2018,'John Doe',1,'1','7M',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(18,NULL,'Ondo',NULL,'Vitae quia ipsum maxime qui optio qui qui. Autem quos sint vel doloremque natus quia sed. Officiis cupiditate reiciendis accusamus iure.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://ondo.mn/','companies/6.png','43.278546','-74.834926','12732 Monahan Land Suite 653\nNorth Gianni, TN 63222-6694',4,4,4,NULL,'+15592129382',2011,'John Doe',3,'10','6M',NULL,NULL,NULL,NULL,NULL,0,0,NULL,NULL,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(19,NULL,'Square',NULL,'Dolorum repudiandae ipsa praesentium cumque iure. Labore velit repellat provident sit aut. Perspiciatis autem officia voluptatem voluptatem et sit.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://squareup.com/','companies/2.png','42.641877','-75.912103','3548 Destiney Trail Suite 705\nYundtview, DE 81744',4,4,4,NULL,'+15615010877',1981,'John Doe',6,'9','1M',NULL,NULL,NULL,NULL,NULL,0,1,'2025-10-01 20:13:04',1,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(20,NULL,'Visa',NULL,'Provident quaerat voluptas natus necessitatibus iste ut facere. Cupiditate sit nam sunt vitae tenetur officiis. Quidem consectetur consequatur aut numquam temporibus magni sit.','<p class=\"text-muted\"> Objectively pursue diverse catalysts for change for interoperable meta-services. Distinctively re-engineer\n                revolutionary meta-services and premium architectures. Intrinsically incubate intuitive opportunities and\n                real-time potentialities. Appropriately communicate one-to-one technology.</p>\n\n            <p class=\"text-muted\">Intrinsically incubate intuitive opportunities and real-time potentialities Appropriately communicate\n                one-to-one technology.</p>\n\n            <p class=\"text-muted\"> Exercitation photo booth stumptown tote bag Banksy, elit small batch freegan sed. Craft beer elit\n                seitan exercitation, photo booth et 8-bit kale chips proident chillwave deep v laborum. Aliquip veniam delectus, Marfa\n                eiusmod Pinterest in do umami readymade swag.</p>','https://visa.com/','companies/8.png','43.728374','-76.749041','928 O\'Reilly Burg Suite 078\nNorth Janiyaland, NV 41261',4,4,4,NULL,'+18047071198',2000,'John Doe',4,'5','9M',NULL,NULL,NULL,NULL,NULL,0,1,'2025-10-12 20:13:04',1,NULL,'published',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL);
/*!40000 ALTER TABLE `jb_companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_companies_accounts`
--

DROP TABLE IF EXISTS `jb_companies_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_companies_accounts` (
  `company_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  UNIQUE KEY `jb_companies_accounts_unique` (`company_id`,`account_id`),
  KEY `jb_companies_accounts_company_id_index` (`company_id`),
  KEY `jb_companies_accounts_account_id_index` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_companies_accounts`
--

LOCK TABLES `jb_companies_accounts` WRITE;
/*!40000 ALTER TABLE `jb_companies_accounts` DISABLE KEYS */;
INSERT INTO `jb_companies_accounts` VALUES (1,1),(1,4),(2,1),(2,4),(3,1),(3,4),(4,1),(4,4),(5,1),(5,4),(6,1),(6,4),(7,1),(7,4),(8,1),(8,4),(9,1),(9,4),(10,1),(10,4),(11,1),(11,4),(12,1),(12,4),(13,1),(13,4),(14,1),(14,4),(15,1),(15,4),(16,1),(16,4),(17,1),(17,4),(18,1),(18,4),(19,1),(19,4),(20,1),(20,4);
/*!40000 ALTER TABLE `jb_companies_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_companies_translations`
--

DROP TABLE IF EXISTS `jb_companies_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_companies_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_companies_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lang_code`,`jb_companies_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_companies_translations`
--

LOCK TABLES `jb_companies_translations` WRITE;
/*!40000 ALTER TABLE `jb_companies_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_companies_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_coupons`
--

DROP TABLE IF EXISTS `jb_coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(8,2) NOT NULL,
  `quantity` int DEFAULT NULL,
  `total_used` int unsigned NOT NULL DEFAULT '0',
  `expires_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jb_coupons_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_coupons`
--

LOCK TABLES `jb_coupons` WRITE;
/*!40000 ALTER TABLE `jb_coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_currencies`
--

DROP TABLE IF EXISTS `jb_currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_prefix_symbol` tinyint unsigned NOT NULL DEFAULT '0',
  `decimals` tinyint unsigned NOT NULL DEFAULT '0',
  `order` int unsigned NOT NULL DEFAULT '0',
  `is_default` tinyint NOT NULL DEFAULT '0',
  `exchange_rate` double NOT NULL DEFAULT '1',
  `number_format_style` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'western',
  `space_between_price_and_currency` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_currencies`
--

LOCK TABLES `jb_currencies` WRITE;
/*!40000 ALTER TABLE `jb_currencies` DISABLE KEYS */;
INSERT INTO `jb_currencies` VALUES (1,'USD','$',1,2,0,1,1,'western',0,'2025-10-26 20:13:04','2025-10-26 20:13:04'),(2,'EUR','€',0,2,1,0,0.91,'western',0,'2025-10-26 20:13:04','2025-10-26 20:13:04'),(3,'VND','₫',0,0,2,0,23717.5,'western',0,'2025-10-26 20:13:04','2025-10-26 20:13:04');
/*!40000 ALTER TABLE `jb_currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_custom_field_options`
--

DROP TABLE IF EXISTS `jb_custom_field_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_custom_field_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_field_id` bigint unsigned NOT NULL,
  `label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int NOT NULL DEFAULT '999',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_custom_field_options`
--

LOCK TABLES `jb_custom_field_options` WRITE;
/*!40000 ALTER TABLE `jb_custom_field_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_custom_field_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_custom_field_options_translations`
--

DROP TABLE IF EXISTS `jb_custom_field_options_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_custom_field_options_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_custom_field_options_id` bigint unsigned NOT NULL,
  `label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_custom_field_options_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_custom_field_options_translations`
--

LOCK TABLES `jb_custom_field_options_translations` WRITE;
/*!40000 ALTER TABLE `jb_custom_field_options_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_custom_field_options_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_custom_field_values`
--

DROP TABLE IF EXISTS `jb_custom_field_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_custom_field_values` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `custom_field_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_custom_field_values_reference_type_reference_id_index` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_custom_field_values`
--

LOCK TABLES `jb_custom_field_values` WRITE;
/*!40000 ALTER TABLE `jb_custom_field_values` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_custom_field_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_custom_field_values_translations`
--

DROP TABLE IF EXISTS `jb_custom_field_values_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_custom_field_values_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_custom_field_values_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_custom_field_values_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_custom_field_values_translations`
--

LOCK TABLES `jb_custom_field_values_translations` WRITE;
/*!40000 ALTER TABLE `jb_custom_field_values_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_custom_field_values_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_custom_fields`
--

DROP TABLE IF EXISTS `jb_custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_custom_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int NOT NULL DEFAULT '999',
  `is_global` tinyint(1) NOT NULL DEFAULT '0',
  `authorable_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `authorable_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_custom_fields_authorable_type_authorable_id_index` (`authorable_type`,`authorable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_custom_fields`
--

LOCK TABLES `jb_custom_fields` WRITE;
/*!40000 ALTER TABLE `jb_custom_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_custom_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_custom_fields_translations`
--

DROP TABLE IF EXISTS `jb_custom_fields_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_custom_fields_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_custom_fields_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_custom_fields_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_custom_fields_translations`
--

LOCK TABLES `jb_custom_fields_translations` WRITE;
/*!40000 ALTER TABLE `jb_custom_fields_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_custom_fields_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_degree_levels`
--

DROP TABLE IF EXISTS `jb_degree_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_degree_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_degree_levels`
--

LOCK TABLES `jb_degree_levels` WRITE;
/*!40000 ALTER TABLE `jb_degree_levels` DISABLE KEYS */;
INSERT INTO `jb_degree_levels` VALUES (1,'Non-Matriculation',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(2,'Matriculation/O-Level',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(3,'Intermediate/A-Level',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(4,'Bachelors',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(5,'Masters',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(6,'MPhil/MS',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(7,'PHD/Doctorate',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(8,'Certification',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(9,'Diploma',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(10,'Short Course',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03');
/*!40000 ALTER TABLE `jb_degree_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_degree_levels_translations`
--

DROP TABLE IF EXISTS `jb_degree_levels_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_degree_levels_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_degree_levels_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_degree_levels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_degree_levels_translations`
--

LOCK TABLES `jb_degree_levels_translations` WRITE;
/*!40000 ALTER TABLE `jb_degree_levels_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_degree_levels_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_degree_types`
--

DROP TABLE IF EXISTS `jb_degree_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_degree_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `degree_level_id` bigint unsigned NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_degree_types`
--

LOCK TABLES `jb_degree_types` WRITE;
/*!40000 ALTER TABLE `jb_degree_types` DISABLE KEYS */;
INSERT INTO `jb_degree_types` VALUES (1,'Matric in Arts',2,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(2,'Matric in Science',2,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(3,'O-Levels',2,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(4,'A-Levels',3,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(5,'Faculty of Arts',3,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(6,'Faculty of Science (Pre-medical)',3,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(7,'Faculty of Science (Pre-Engineering)',3,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(8,'Intermediate in Computer Science',3,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(9,'Intermediate in Commerce',3,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(10,'Intermediate in General Science',3,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(11,'Bachelors in Arts',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(12,'Bachelors in Architecture',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(13,'Bachelors in Business Administration',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(14,'Bachelors in Commerce',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(15,'Bachelors of Dental Surgery',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(16,'Bachelors of Education',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(17,'Bachelors in Engineering',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(18,'Bachelors in Pharmacy',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(19,'Bachelors in Science',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(20,'Bachelors of Science in Nursing (Registered Nursing)',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(21,'Bachelors in Law',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(22,'Bachelors in Technology',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(23,'BCS/BS',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(24,'Doctor of Veterinary Medicine',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(25,'MBBS',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(26,'Post Registered Nursing B.S.',4,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(27,'Masters in Arts',5,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(28,'Masters in Business Administration',5,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(29,'Masters in Commerce',5,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(30,'Masters of Education',5,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(31,'Masters in Law',5,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(32,'Masters in Science',5,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(33,'Executive Masters in Business Administration',5,0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03');
/*!40000 ALTER TABLE `jb_degree_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_degree_types_translations`
--

DROP TABLE IF EXISTS `jb_degree_types_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_degree_types_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_degree_types_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_degree_types_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_degree_types_translations`
--

LOCK TABLES `jb_degree_types_translations` WRITE;
/*!40000 ALTER TABLE `jb_degree_types_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_degree_types_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_functional_areas`
--

DROP TABLE IF EXISTS `jb_functional_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_functional_areas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_functional_areas`
--

LOCK TABLES `jb_functional_areas` WRITE;
/*!40000 ALTER TABLE `jb_functional_areas` DISABLE KEYS */;
INSERT INTO `jb_functional_areas` VALUES (1,'Accountant',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(2,'Accounts, Finance &amp; Financial Services',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(3,'Admin',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(4,'Admin Operation',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(5,'Administration',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(6,'Administration Clerical',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(7,'Advertising',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(8,'Advertising',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(9,'Advertisement',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(10,'Architects &amp; Construction',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(11,'Architecture',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(12,'Bank Operation',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(13,'Business Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(14,'Business Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(15,'Business Systems Analyst',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(16,'Clerical',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(17,'Client Services &amp; Customer Support',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(18,'Computer Hardware',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(19,'Computer Networking',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(20,'Consultant',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(21,'Content Writer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(22,'Corporate Affairs',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(23,'Creative Design',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(24,'Creative Writer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(25,'Customer Support',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(26,'Data Entry',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(27,'Data Entry Operator',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(28,'Database Administration (DBA)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(29,'Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(30,'Distribution &amp; Logistics',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(31,'Education &amp; Training',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(32,'Electronics Technician',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(33,'Engineering',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(34,'Engineering Construction',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(35,'Executive Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(36,'Executive Secretary',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(37,'Field Operations',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(38,'Front Desk Clerk',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(39,'Front Desk Officer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(40,'Graphic Design',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(41,'Hardware',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(42,'Health &amp; Medicine',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(43,'Health &amp; Safety',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(44,'Health Care',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(45,'Health Related',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(46,'Hotel Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(47,'Hotel/Restaurant Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(48,'HR',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(49,'Human Resources',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(50,'Import &amp; Export',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(51,'Industrial Production',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(52,'Installation &amp; Repair',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(53,'Interior Designers &amp; Architects',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(54,'Intern',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(55,'Internship',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(56,'Investment Operations',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(57,'IT Security',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(58,'IT Systems Analyst',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(59,'Legal &amp; Corporate Affairs',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(60,'Legal Affairs',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(61,'Legal Research',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(62,'Logistics &amp; Warehousing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(63,'Maintenance/Repair',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(64,'Management Consulting',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(65,'Management Information System (MIS)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(66,'Managerial',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(67,'Manufacturing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(68,'Manufacturing &amp; Operations',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(69,'Marketing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(70,'Marketing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(71,'Media - Print &amp; Electronic',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(72,'Media &amp; Advertising',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(73,'Medical',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(74,'Medicine',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(75,'Merchandising',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(76,'Merchandising &amp; Product Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(77,'Monitoring &amp; Evaluation (M&amp;E)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(78,'Network Administration',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(79,'Network Operation',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(80,'Online Advertising',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(81,'Online Marketing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(82,'Operations',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(83,'Planning',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(84,'Planning &amp; Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(85,'PR',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(86,'Print Media',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(87,'Printing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(88,'Procurement',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(89,'Product Developer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(90,'Product Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(91,'Product Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(92,'Product Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(93,'Production',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(94,'Production &amp; Quality Control',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(95,'Project Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(96,'Project Management Consultant',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(97,'Public Relations',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(98,'QA',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(99,'QC',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(100,'Qualitative Research',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(101,'Quality Assurance (QA)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(102,'Quality Control',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(103,'Quality Inspection',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(104,'Recruiting',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(105,'Recruitment',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(106,'Repair &amp; Overhaul',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(107,'Research &amp; Development (R&amp;D)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(108,'Research &amp; Evaluation',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(109,'Research &amp; Fellowships',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(110,'Researcher',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(111,'Restaurant Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(112,'Retail',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(113,'Retail &amp; Wholesale',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(114,'Retail Buyer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(115,'Retail Buying',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(116,'Retail Merchandising',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(117,'Safety &amp; Environment',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(118,'Sales',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(119,'Sales &amp; Business Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(120,'Sales Support',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(121,'Search Engine Optimization (SEO)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(122,'Secretarial, Clerical &amp; Front Office',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(123,'Security',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(124,'Security &amp; Environment',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(125,'Security Guard',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(126,'SEM',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(127,'SMO',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(128,'Software &amp; Web Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(129,'Software Engineer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(130,'Software Testing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(131,'Stores &amp; Warehousing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(132,'Supply Chain',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(133,'Supply Chain Management',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(134,'Systems Analyst',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(135,'Teachers/Education, Training &amp; Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(136,'Technical Writer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(137,'Tele Sale Representative',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(138,'Telemarketing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(139,'Training &amp; Development',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(140,'Transportation &amp; Warehousing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(141,'TSR',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(142,'Typing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(143,'Warehousing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(144,'Web Developer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(145,'Web Marketing',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(146,'Writer',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(147,'PR',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(148,'QA',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(149,'QC',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(150,'SEM',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(151,'SMO',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(152,'TSR',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(153,'HR',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(154,'QA',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(155,'QC',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(156,'SEM',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03');
/*!40000 ALTER TABLE `jb_functional_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_functional_areas_translations`
--

DROP TABLE IF EXISTS `jb_functional_areas_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_functional_areas_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_functional_areas_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_functional_areas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_functional_areas_translations`
--

LOCK TABLES `jb_functional_areas_translations` WRITE;
/*!40000 ALTER TABLE `jb_functional_areas_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_functional_areas_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_invoice_items`
--

DROP TABLE IF EXISTS `jb_invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_invoice_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `reference_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty` int unsigned NOT NULL,
  `sub_total` decimal(15,2) unsigned NOT NULL,
  `tax_amount` decimal(15,2) unsigned NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) unsigned NOT NULL DEFAULT '0.00',
  `amount` decimal(15,2) unsigned NOT NULL,
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_invoice_items_reference_type_reference_id_index` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_invoice_items`
--

LOCK TABLES `jb_invoice_items` WRITE;
/*!40000 ALTER TABLE `jb_invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_invoices`
--

DROP TABLE IF EXISTS `jb_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_logo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tax_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sub_total` decimal(15,2) unsigned NOT NULL,
  `tax_amount` decimal(15,2) unsigned NOT NULL DEFAULT '0.00',
  `shipping_amount` decimal(15,2) unsigned NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) unsigned NOT NULL DEFAULT '0.00',
  `coupon_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) unsigned NOT NULL,
  `payment_id` int unsigned DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jb_invoices_code_unique` (`code`),
  KEY `jb_invoices_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `jb_invoices_payment_id_index` (`payment_id`),
  KEY `jb_invoices_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_invoices`
--

LOCK TABLES `jb_invoices` WRITE;
/*!40000 ALTER TABLE `jb_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_job_experiences`
--

DROP TABLE IF EXISTS `jb_job_experiences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_job_experiences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_job_experiences_status_order_created_at_index` (`status`,`order`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_job_experiences`
--

LOCK TABLES `jb_job_experiences` WRITE;
/*!40000 ALTER TABLE `jb_job_experiences` DISABLE KEYS */;
INSERT INTO `jb_job_experiences` VALUES (1,'Fresh',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(2,'Less Than 1 Year',1,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(3,'1 Year',2,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(4,'2 Year',3,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(5,'3 Year',4,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(6,'4 Year',5,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(7,'5 Year',6,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(8,'6 Year',7,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(9,'7 Year',8,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(10,'8 Year',9,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(11,'9 Year',10,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(12,'10 Year',11,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03');
/*!40000 ALTER TABLE `jb_job_experiences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_job_experiences_translations`
--

DROP TABLE IF EXISTS `jb_job_experiences_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_job_experiences_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_job_experiences_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_job_experiences_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_job_experiences_translations`
--

LOCK TABLES `jb_job_experiences_translations` WRITE;
/*!40000 ALTER TABLE `jb_job_experiences_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_job_experiences_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_job_shifts`
--

DROP TABLE IF EXISTS `jb_job_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_job_shifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_job_shifts`
--

LOCK TABLES `jb_job_shifts` WRITE;
/*!40000 ALTER TABLE `jb_job_shifts` DISABLE KEYS */;
INSERT INTO `jb_job_shifts` VALUES (1,'First Shift (Day)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(2,'Second Shift (Afternoon)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(3,'Third Shift (Night)',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(4,'Rotating',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03');
/*!40000 ALTER TABLE `jb_job_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_job_shifts_translations`
--

DROP TABLE IF EXISTS `jb_job_shifts_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_job_shifts_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_job_shifts_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_job_shifts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_job_shifts_translations`
--

LOCK TABLES `jb_job_shifts_translations` WRITE;
/*!40000 ALTER TABLE `jb_job_shifts_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_job_shifts_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_job_skills`
--

DROP TABLE IF EXISTS `jb_job_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_job_skills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_job_skills`
--

LOCK TABLES `jb_job_skills` WRITE;
/*!40000 ALTER TABLE `jb_job_skills` DISABLE KEYS */;
INSERT INTO `jb_job_skills` VALUES (1,'JavaScript',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(2,'PHP',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(3,'Python',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(4,'Laravel',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(5,'CakePHP',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(6,'WordPress',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(7,'Flutter',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(8,'FilamentPHP',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(9,'React.js',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03');
/*!40000 ALTER TABLE `jb_job_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_job_skills_translations`
--

DROP TABLE IF EXISTS `jb_job_skills_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_job_skills_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_job_skills_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_job_skills_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_job_skills_translations`
--

LOCK TABLES `jb_job_skills_translations` WRITE;
/*!40000 ALTER TABLE `jb_job_skills_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_job_skills_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_job_types`
--

DROP TABLE IF EXISTS `jb_job_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_job_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_job_types`
--

LOCK TABLES `jb_job_types` WRITE;
/*!40000 ALTER TABLE `jb_job_types` DISABLE KEYS */;
INSERT INTO `jb_job_types` VALUES (1,'Contract',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(2,'Freelance',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(3,'Full Time',0,1,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(4,'Internship',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03'),(5,'Part Time',0,0,'published','2025-10-26 20:13:03','2025-10-26 20:13:03');
/*!40000 ALTER TABLE `jb_job_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_job_types_translations`
--

DROP TABLE IF EXISTS `jb_job_types_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_job_types_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_job_types_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_job_types_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_job_types_translations`
--

LOCK TABLES `jb_job_types_translations` WRITE;
/*!40000 ALTER TABLE `jb_job_types_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_job_types_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_jobs`
--

DROP TABLE IF EXISTS `jb_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `content` text COLLATE utf8mb4_unicode_ci,
  `apply_url` text COLLATE utf8mb4_unicode_ci,
  `external_apply_behavior` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_id` bigint unsigned DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT '1',
  `state_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `is_freelance` tinyint unsigned NOT NULL DEFAULT '0',
  `career_level_id` bigint unsigned DEFAULT NULL,
  `salary_from` decimal(15,2) unsigned DEFAULT NULL,
  `salary_to` decimal(15,2) unsigned DEFAULT NULL,
  `salary_range` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hour',
  `salary_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `currency_id` bigint unsigned DEFAULT NULL,
  `degree_level_id` bigint unsigned DEFAULT NULL,
  `job_shift_id` bigint unsigned DEFAULT NULL,
  `job_experience_id` bigint unsigned DEFAULT NULL,
  `functional_area_id` bigint unsigned DEFAULT NULL,
  `hide_salary` tinyint(1) NOT NULL DEFAULT '0',
  `number_of_positions` int unsigned NOT NULL DEFAULT '1',
  `expire_date` date DEFAULT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `author_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Botble\\ACL\\Models\\User',
  `views` int unsigned NOT NULL DEFAULT '0',
  `number_of_applied` int unsigned NOT NULL DEFAULT '0',
  `hide_company` tinyint(1) NOT NULL DEFAULT '0',
  `latitude` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '0',
  `external_apply_clicks` int unsigned NOT NULL DEFAULT '0',
  `never_expired` tinyint(1) NOT NULL DEFAULT '0',
  `is_featured` tinyint NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `moderation_status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `employer_colleagues` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `application_closing_date` date DEFAULT NULL,
  `zip_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jb_jobs_unique_id_unique` (`unique_id`),
  KEY `jb_jobs_active_jobs_index` (`moderation_status`,`status`,`expire_date`),
  KEY `jb_jobs_company_id_index` (`company_id`),
  KEY `jb_jobs_is_featured_index` (`is_featured`),
  KEY `jb_jobs_created_at_index` (`created_at`),
  KEY `jb_jobs_expire_date_index` (`expire_date`),
  KEY `jb_jobs_never_expired_index` (`never_expired`),
  KEY `jb_jobs_country_id_index` (`country_id`),
  KEY `jb_jobs_state_id_index` (`state_id`),
  KEY `jb_jobs_city_id_index` (`city_id`),
  KEY `jb_jobs_job_experience_id_index` (`job_experience_id`),
  KEY `jb_jobs_career_level_id_index` (`career_level_id`),
  KEY `jb_jobs_functional_area_id_index` (`functional_area_id`),
  KEY `jb_jobs_job_shift_id_index` (`job_shift_id`),
  KEY `jb_jobs_degree_level_id_index` (`degree_level_id`),
  KEY `jb_jobs_author_index` (`author_id`,`author_type`),
  KEY `jb_jobs_application_closing_date_index` (`application_closing_date`),
  KEY `jb_jobs_listing_optimized_index` (`moderation_status`,`status`,`created_at`,`never_expired`,`expire_date`,`application_closing_date`),
  KEY `jb_jobs_never_expired_status_index` (`never_expired`,`moderation_status`,`status`,`created_at`),
  KEY `jb_jobs_expire_date_listing_index` (`moderation_status`,`status`,`expire_date`,`created_at`),
  KEY `jb_jobs_views_index` (`views`),
  KEY `jb_jobs_experience_active_idx` (`job_experience_id`,`moderation_status`,`status`,`never_expired`,`expire_date`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_jobs`
--

LOCK TABLES `jb_jobs` WRITE;
/*!40000 ALTER TABLE `jb_jobs` DISABLE KEYS */;
INSERT INTO `jb_jobs` VALUES (1,NULL,'UI / UX Designer full-time','Aut exercitationem debitis blanditiis molestiae. Ut amet amet dicta est. Id praesentium ut enim beatae alias ea praesentium ipsam.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,15,NULL,1,1,1,0,3,1300.00,2300.00,'monthly','fixed',0,6,4,5,141,0,2,'2025-12-16',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.253941','-75.479451',0,0,1,1,'published','approved','2025-10-08 10:11:20','2025-10-26 20:13:04',NULL,NULL,'2025-12-26',NULL),(2,NULL,'Full Stack Engineer','Numquam recusandae fugiat sunt minima consequatur officiis assumenda. Cumque quod et quia consequatur ratione. Ex debitis modi maiores nihil tempora aut.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','https://google.com',NULL,8,NULL,6,6,6,0,2,1000.00,1600.00,'daily','fixed',0,2,1,3,53,0,10,'2025-11-05',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.345334','-75.435967',0,0,0,0,'published','approved','2025-09-25 03:50:45','2025-10-26 20:13:04',NULL,NULL,'2025-11-24',NULL),(3,NULL,'Java Software Engineer','Id nesciunt ut recusandae officiis. Fugit sit nihil cumque perferendis. Corporis reiciendis aliquam sed.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,9,NULL,3,3,3,1,5,900.00,2200.00,'yearly','fixed',0,3,1,2,156,0,3,'2025-11-18',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.928741','-76.04579',0,0,0,1,'published','approved','2025-10-08 13:42:11','2025-10-26 20:13:04',NULL,NULL,'2025-11-23',NULL),(4,NULL,'Digital Marketing Manager','Quos itaque quam aut aut accusamus officiis dolores. Et et cupiditate quae ut sit provident. Illo quisquam odit commodi laboriosam laborum nobis. Repudiandae sed repellendus alias modi.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,4,NULL,4,4,4,0,2,900.00,2000.00,'weekly','fixed',0,4,2,4,141,0,2,'2025-12-19',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.027509','-75.962785',0,0,0,0,'published','approved','2025-09-03 03:52:25','2025-10-26 20:13:04',NULL,NULL,'2025-12-09',NULL),(5,NULL,'Frontend Developer','Doloribus consequatur aliquam debitis et. Saepe ut deserunt reiciendis fugit consequatur commodi autem quasi. Qui delectus occaecati voluptates enim vel natus.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,17,NULL,3,3,3,0,1,500.00,1400.00,'hourly','fixed',0,1,2,4,72,0,4,'2025-12-19',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.802572','-76.013218',0,0,0,0,'published','approved','2025-09-11 12:46:09','2025-10-26 20:13:04',NULL,NULL,'2025-11-04',NULL),(6,NULL,'React Native Web Developer','Deleniti libero laborum ut. Sunt exercitationem dignissimos hic nisi qui. Mollitia eum architecto facilis non numquam totam voluptas.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,18,NULL,4,4,4,0,4,1200.00,1900.00,'monthly','fixed',1,10,3,5,155,0,2,'2025-11-22',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.278546','-74.834926',0,0,1,1,'published','approved','2025-10-02 05:18:01','2025-10-26 20:13:04',NULL,NULL,'2025-12-25',NULL),(7,NULL,'Senior System Engineer','Sunt quam repellat fuga aliquid. Nam voluptatem illo doloremque. Sapiente veritatis aspernatur possimus reiciendis laborum cum beatae officiis. Eveniet iste aut dolorum adipisci blanditiis.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,12,NULL,1,1,1,1,1,900.00,2300.00,'hourly','fixed',0,1,3,5,17,0,6,'2025-12-21',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.78578','-75.889819',0,0,0,0,'published','approved','2025-10-19 21:56:00','2025-10-26 20:13:04',NULL,NULL,'2025-11-10',NULL),(8,NULL,'Products Manager','Id non ut laudantium dicta ab iure magni. Officia nobis facilis et nam itaque placeat rerum. Sint hic laboriosam facilis eveniet. Atque minus tempore in velit sit voluptas ut.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,13,NULL,2,2,2,0,1,1100.00,2100.00,'hourly','fixed',1,10,2,4,24,0,6,'2025-12-14',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.834048','-76.366789',0,0,0,0,'published','approved','2025-09-14 04:34:56','2025-10-26 20:13:04',NULL,NULL,'2025-12-25',NULL),(9,NULL,'Lead Quality Control QA','Quis quis illo ad. Id aspernatur qui sint at. Dolore in molestias quia eligendi nulla.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,12,NULL,1,1,1,0,1,1400.00,2300.00,'weekly','fixed',1,5,4,5,47,0,3,'2025-11-06',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.78578','-75.889819',0,0,0,1,'published','approved','2025-10-13 18:36:10','2025-10-26 20:13:04',NULL,NULL,'2025-12-24',NULL),(10,NULL,'Principal Designer, Design Systems','Ipsam ex error vel molestiae itaque incidunt repudiandae et. Nobis deleniti sunt est tempore. Unde totam nulla aspernatur et assumenda sit aut.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,18,NULL,4,4,4,0,3,1000.00,2200.00,'hourly','fixed',0,9,4,4,1,0,3,'2025-12-05',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.278546','-74.834926',0,0,0,0,'published','approved','2025-09-10 10:45:06','2025-10-26 20:13:04',NULL,NULL,'2025-11-12',NULL),(11,NULL,'DevOps Architect','Non sapiente qui maxime fuga recusandae rerum qui non. Ut molestias quae pariatur quam vitae. In molestiae et in sunt eos et.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,12,NULL,1,1,1,0,5,1200.00,1900.00,'yearly','fixed',1,8,1,1,8,0,7,'2025-12-13',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.78578','-75.889819',0,0,0,0,'published','approved','2025-10-02 09:44:43','2025-10-26 20:13:04',NULL,NULL,'2025-12-03',NULL),(12,NULL,'Senior Software Engineer, npm CLI','Sit distinctio qui qui. Voluptate id voluptatem animi minus ea. Officiis quas et quis molestias dolore quaerat similique. Voluptate alias error omnis harum.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,8,NULL,6,6,6,1,2,1200.00,2600.00,'yearly','fixed',0,5,1,3,57,0,2,'2025-12-13',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.345334','-75.435967',0,0,0,0,'published','approved','2025-09-17 18:41:26','2025-10-26 20:13:04',NULL,NULL,'2025-12-03',NULL),(13,NULL,'Senior Systems Engineer','Excepturi quaerat cupiditate quidem id dolor aspernatur. Maiores at architecto totam nobis rerum. Ad est occaecati quia.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,14,NULL,5,5,5,1,3,1100.00,2100.00,'monthly','fixed',0,10,4,1,47,0,7,'2025-11-13',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.179703','-75.753395',0,0,0,1,'published','approved','2025-10-07 15:41:17','2025-10-26 20:13:04',NULL,NULL,'2025-11-08',NULL),(14,NULL,'Software Engineer Actions Platform','Sed suscipit aut magnam provident numquam. Voluptas velit ut perferendis qui non aliquid. Dolorem id eligendi molestiae impedit.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,9,NULL,3,3,3,0,1,1000.00,2500.00,'hourly','fixed',0,8,1,4,10,0,5,'2025-12-22',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.928741','-76.04579',0,0,0,1,'published','approved','2025-10-11 17:06:11','2025-10-26 20:13:04',NULL,NULL,'2025-11-01',NULL),(15,NULL,'Staff Engineering Manager, Actions','Soluta eligendi qui deserunt omnis sunt quasi aut. Quidem sequi voluptas provident sit. Ea temporibus illo et officiis ut quo sit. Recusandae sint eveniet molestias tempore dolores.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,3,NULL,3,3,3,0,1,600.00,2100.00,'weekly','fixed',0,3,3,2,123,0,8,'2025-11-10',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.064771','-75.991504',0,0,0,1,'published','approved','2025-09-07 06:27:36','2025-10-26 20:13:04',NULL,NULL,'2025-11-10',NULL),(16,NULL,'Staff Engineering Manager: Actions Runtime','Hic sunt consequatur perferendis libero a. Veritatis veritatis eum distinctio similique. Cum in ipsa voluptas consectetur dolorem. Dolore sed alias numquam magni.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,18,NULL,4,4,4,1,4,1100.00,1800.00,'monthly','fixed',0,4,2,2,99,0,6,'2025-11-29',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.278546','-74.834926',0,0,0,1,'published','approved','2025-10-20 02:58:08','2025-10-26 20:13:04',NULL,NULL,'2025-12-10',NULL),(17,NULL,'Staff Engineering Manager, Packages','Aliquam cumque eum assumenda culpa ab sunt in. Accusantium tempore ea quia fugiat voluptatem officiis. Qui et consequatur dignissimos aut. Rerum in suscipit voluptas tempora.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,18,NULL,4,4,4,1,4,1000.00,2400.00,'monthly','fixed',0,10,2,1,47,0,8,'2025-12-11',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.278546','-74.834926',0,0,1,1,'published','approved','2025-10-15 03:29:09','2025-10-26 20:13:04',NULL,NULL,'2025-11-23',NULL),(18,NULL,'Staff Software Engineer','Culpa vel doloribus blanditiis soluta. Voluptates non enim dolorem in amet. Nam distinctio minima quidem fugit hic eos explicabo.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,6,NULL,4,4,4,0,4,1400.00,2100.00,'monthly','fixed',1,4,3,4,124,0,2,'2025-12-10',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.783836','-75.003313',0,0,0,1,'published','approved','2025-09-24 03:22:01','2025-10-26 20:13:04',NULL,NULL,'2025-11-30',NULL),(19,NULL,'Systems Software Engineer','Adipisci quia voluptatem culpa ut suscipit. Iure sunt et voluptatum optio quod quidem. Quis ut excepturi ut.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,18,NULL,4,4,4,1,1,1000.00,2300.00,'daily','fixed',0,1,4,5,71,0,8,'2025-12-17',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.278546','-74.834926',0,0,1,1,'published','approved','2025-08-31 06:12:05','2025-10-26 20:13:04',NULL,NULL,'2025-11-08',NULL),(20,NULL,'Senior Compensation Analyst','Eos incidunt omnis ut. Sunt aut iusto veniam harum sit rerum. Praesentium impedit illum ad ea.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,5,NULL,4,4,4,0,3,900.00,2100.00,'yearly','fixed',1,2,2,2,20,0,7,'2025-11-18',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.905129','-75.226524',0,0,1,1,'published','approved','2025-09-24 00:25:34','2025-10-26 20:13:04',NULL,NULL,'2025-11-21',NULL),(21,NULL,'Senior Accessibility Program Manager','Quia ut quibusdam aut debitis. Ut laboriosam inventore repellat modi quaerat tempora qui sunt. Voluptatibus magni voluptate quos sunt. Non repellat itaque dignissimos molestias.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,1,NULL,5,5,5,1,2,1100.00,2600.00,'daily','fixed',1,2,3,5,6,0,5,'2025-11-07',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.493982','-75.397501',0,0,0,0,'published','approved','2025-10-09 04:16:13','2025-10-26 20:13:04',NULL,NULL,'2025-12-04',NULL),(22,NULL,'Analyst Relations Manager, Application Security','Incidunt dolor laudantium quas est ad. Atque veritatis voluptates ut odit non. Qui qui non necessitatibus. Autem autem quas impedit atque sint. Et nesciunt voluptas similique eos eum quam possimus.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,10,NULL,1,1,1,0,5,800.00,1600.00,'yearly','fixed',1,2,2,1,150,0,8,'2025-11-25',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.406581','-76.500639',0,0,0,1,'published','approved','2025-09-06 18:44:17','2025-10-26 20:13:04',NULL,NULL,'2025-12-22',NULL),(23,NULL,'Senior Enterprise Advocate, EMEA','Numquam qui debitis harum sapiente. Aperiam ab corrupti voluptatum odio eos voluptas. Magni eaque provident eveniet ut aut.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,11,NULL,4,4,4,1,2,1400.00,2500.00,'hourly','fixed',0,10,2,5,136,0,9,'2025-12-04',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.114525','-75.964499',0,0,0,1,'published','approved','2025-10-18 10:02:42','2025-10-26 20:13:04',NULL,NULL,'2025-12-09',NULL),(24,NULL,'Deal Desk Manager','Dolor eos eum et exercitationem voluptatem possimus maxime. Delectus illo nisi aut perspiciatis. Quis pariatur tempora ut aperiam cum omnis repellendus voluptas.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,3,NULL,3,3,3,0,4,1300.00,2600.00,'weekly','fixed',1,7,3,1,114,0,10,'2025-11-28',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.064771','-75.991504',0,0,0,0,'published','approved','2025-09-08 23:12:37','2025-10-26 20:13:04',NULL,NULL,'2025-12-06',NULL),(25,NULL,'Director, Revenue Compensation','Et debitis eaque asperiores. Perspiciatis placeat provident aspernatur illum. Tenetur aut et et quia. Explicabo necessitatibus amet possimus facilis sapiente in.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,5,NULL,4,4,4,0,5,1300.00,2000.00,'yearly','fixed',0,1,2,5,135,0,5,'2025-12-02',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.905129','-75.226524',0,0,1,1,'published','approved','2025-09-12 09:54:27','2025-10-26 20:13:04',NULL,NULL,'2025-12-07',NULL),(26,NULL,'Program Manager','Fugit et quia repudiandae ea et. Non consequatur et rem sit ea et perspiciatis. Magni recusandae libero est excepturi ut. Qui ut at voluptas iste.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,4,NULL,4,4,4,0,4,700.00,1900.00,'yearly','fixed',0,9,4,4,73,0,3,'2025-11-06',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.027509','-75.962785',0,0,0,1,'published','approved','2025-09-12 12:17:08','2025-10-26 20:13:04',NULL,NULL,'2025-11-10',NULL),(27,NULL,'Sr. Manager, Deal Desk - INTL','Qui officia voluptas iure voluptatem qui nemo reiciendis et. Aliquid et minima id occaecati sunt. Quos consequatur ipsam beatae sequi.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,18,NULL,4,4,4,0,1,1500.00,2300.00,'yearly','fixed',0,2,2,5,143,0,5,'2025-11-22',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.278546','-74.834926',0,0,1,1,'published','approved','2025-09-23 17:17:58','2025-10-26 20:13:04',NULL,NULL,'2025-11-27',NULL),(28,NULL,'Senior Director, Product Management, Actions Runners and Compute Services','Tempore omnis voluptatem rerum fuga inventore omnis tenetur. Quasi molestias optio facilis aut voluptates. Molestiae animi et quo quod corporis aut sint.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,6,NULL,4,4,4,1,3,1500.00,2500.00,'daily','fixed',1,6,3,1,29,0,5,'2025-11-07',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.783836','-75.003313',0,0,0,0,'published','approved','2025-10-14 22:58:27','2025-10-26 20:13:04',NULL,NULL,'2025-11-25',NULL),(29,NULL,'Alliances Director','Inventore aliquid error necessitatibus ipsum enim qui. Non eos ut et. Ut quia voluptatum odit occaecati accusantium esse.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,11,NULL,4,4,4,0,4,1000.00,1900.00,'monthly','fixed',1,7,1,2,119,0,9,'2025-11-03',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.114525','-75.964499',0,0,1,0,'published','approved','2025-10-06 12:18:37','2025-10-26 20:13:04',NULL,NULL,'2025-11-06',NULL),(30,NULL,'Corporate Sales Representative','Cupiditate itaque aut sunt aliquid et omnis quos. Ab quasi sed eos ea nulla ipsa est. Molestiae libero voluptatem et vero dignissimos quia. Nam dignissimos reprehenderit dignissimos error ratione.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,4,NULL,4,4,4,1,3,1200.00,1900.00,'yearly','fixed',0,6,3,1,155,0,3,'2025-11-12',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.027509','-75.962785',0,0,1,0,'published','approved','2025-09-27 03:46:50','2025-10-26 20:13:04',NULL,NULL,'2025-11-22',NULL),(31,NULL,'Country Leader','Sed deleniti ipsum dolores quasi omnis totam neque. Aut mollitia quibusdam ipsam voluptatum inventore autem. Aut eum voluptatem dolorum accusamus rem non nisi. Sint voluptatum totam reiciendis non.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,7,NULL,1,1,1,0,3,1100.00,1800.00,'monthly','fixed',1,1,2,5,27,0,8,'2025-12-18',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.439146','-75.529246',0,0,1,1,'published','approved','2025-09-27 05:10:26','2025-10-26 20:13:04',NULL,NULL,'2025-11-03',NULL),(32,NULL,'Customer Success Architect','Rem asperiores suscipit odio beatae et. Saepe dignissimos voluptatem et aliquam dolorum. Sapiente vitae repellat sequi assumenda alias.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,14,NULL,5,5,5,0,2,1100.00,2600.00,'hourly','fixed',1,5,3,2,97,0,7,'2025-12-08',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.179703','-75.753395',0,0,0,1,'published','approved','2025-10-12 17:53:56','2025-10-26 20:13:04',NULL,NULL,'2025-12-13',NULL),(33,NULL,'DevOps Account Executive - US Public Sector','Possimus sint temporibus voluptatum dolorum enim. Neque corrupti vel totam dolorem quia commodi aut. Nisi animi possimus dolorem qui sit.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,2,NULL,2,2,2,0,4,1400.00,2500.00,'hourly','fixed',0,6,2,1,155,0,6,'2025-11-04',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.649035','-75.390111',0,0,1,0,'published','approved','2025-09-17 20:00:35','2025-10-26 20:13:04',NULL,NULL,'2025-12-26',NULL),(34,NULL,'Enterprise Account Executive','Non a pariatur iure blanditiis. Adipisci ut est asperiores officiis. Aspernatur tenetur dolores ex debitis et iste.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,16,NULL,4,4,4,0,3,1400.00,2700.00,'weekly','fixed',0,8,4,3,108,0,6,'2025-11-20',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.793246','-75.528245',0,0,1,0,'published','approved','2025-10-02 13:45:42','2025-10-26 20:13:04',NULL,NULL,'2025-12-10',NULL),(35,NULL,'Senior Engineering Manager, Product Security Engineering - Paved Paths','Voluptatem sint distinctio natus voluptatibus porro consequatur vero. Voluptatem iste voluptas officia rerum. Autem dolore dolorem esse. Accusantium facilis assumenda non porro ea tempore iusto aut.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,17,NULL,3,3,3,0,1,600.00,2000.00,'weekly','fixed',1,9,3,5,99,0,8,'2025-11-30',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.802572','-76.013218',0,0,1,1,'published','approved','2025-10-13 13:12:20','2025-10-26 20:13:04',NULL,NULL,'2025-11-23',NULL),(36,NULL,'Customer Reliability Engineer III','Autem rerum sit vero ea odio mollitia veritatis cumque. Eveniet dolores accusamus at libero. Id error impedit culpa. Incidunt quidem consectetur porro et suscipit.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,18,NULL,4,4,4,0,3,800.00,1900.00,'weekly','fixed',1,8,2,2,129,0,9,'2025-12-14',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.278546','-74.834926',0,0,1,1,'published','approved','2025-09-15 23:49:02','2025-10-26 20:13:04',NULL,NULL,'2025-11-06',NULL),(37,NULL,'Support Engineer (Enterprise Support Japanese)','Cupiditate odio pariatur eos ex. Dolorem quas nobis aut odio magnam ut facere. Qui quia autem ducimus veritatis occaecati laborum reprehenderit.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,13,NULL,2,2,2,0,4,1100.00,2400.00,'weekly','fixed',0,9,4,1,106,0,4,'2025-12-26',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.834048','-76.366789',0,0,1,0,'published','approved','2025-10-01 12:20:53','2025-10-26 20:13:04',NULL,NULL,'2025-11-28',NULL),(38,NULL,'Technical Partner Manager','Et provident sunt exercitationem quas voluptatem. Et asperiores itaque praesentium tenetur earum et. Qui qui ut inventore consequuntur ut eius. Quis explicabo quo cum nam veniam.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,16,NULL,4,4,4,1,5,1200.00,2300.00,'yearly','fixed',0,2,4,4,52,0,2,'2025-11-03',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.793246','-75.528245',0,0,1,0,'published','approved','2025-10-05 18:45:22','2025-10-26 20:13:04',NULL,NULL,'2025-12-06',NULL),(39,NULL,'Sr Manager, Inside Account Management','Nihil est ut iure. Illo ducimus culpa assumenda et labore qui. Vel illo omnis repellendus voluptatibus velit.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,20,NULL,4,4,4,0,2,1000.00,2200.00,'monthly','fixed',0,5,3,3,9,0,7,'2025-11-20',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.728374','-76.749041',0,0,0,1,'published','approved','2025-09-10 22:34:01','2025-10-26 20:13:04',NULL,NULL,'2025-12-21',NULL),(40,NULL,'Services Sales Representative','Est eveniet aliquid aut dolore quia labore ut. Nihil eos commodi aut eligendi. Ipsum eius velit rerum dolorem. Asperiores ipsam impedit laboriosam officia consequatur quasi a. Ea quas dolorem id.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,1,NULL,5,5,5,0,2,900.00,2100.00,'daily','fixed',1,2,3,5,116,0,9,'2025-11-11',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.493982','-75.397501',0,0,1,0,'published','approved','2025-09-08 14:49:01','2025-10-26 20:13:04',NULL,NULL,'2025-11-20',NULL),(41,NULL,'Services Delivery Manager','Perferendis iusto accusantium velit ducimus quisquam. Sed aut in nobis magni nihil. Ea quia eveniet est id est commodi. Ratione minus saepe eligendi officia.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,14,NULL,5,5,5,0,3,800.00,1500.00,'hourly','fixed',1,7,3,3,154,0,9,'2025-12-12',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.179703','-75.753395',0,0,1,0,'published','approved','2025-09-27 23:50:53','2025-10-26 20:13:04',NULL,NULL,'2025-12-15',NULL),(42,NULL,'Senior Solutions Engineer','Aut atque nobis consequatur saepe et perferendis beatae. Soluta rerum laborum facere. Tempore qui ut accusamus qui dolor sit nihil.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,8,NULL,6,6,6,1,3,800.00,2300.00,'yearly','fixed',0,10,4,5,21,0,9,'2025-11-29',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.345334','-75.435967',0,0,1,1,'published','approved','2025-10-20 00:22:02','2025-10-26 20:13:04',NULL,NULL,'2025-11-27',NULL),(43,NULL,'Senior Service Delivery Engineer','Voluptatem voluptas esse eum soluta quos. Vero eum dolores et rem rerum recusandae. Veniam veniam velit et tenetur velit sunt. Et sunt ullam consequatur ullam mollitia earum est minima.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,11,NULL,4,4,4,1,3,1200.00,2600.00,'weekly','fixed',1,3,2,1,26,0,7,'2025-12-11',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.114525','-75.964499',0,0,1,1,'published','approved','2025-10-17 09:20:27','2025-10-26 20:13:04',NULL,NULL,'2025-11-26',NULL),(44,NULL,'Senior Director, Global Sales Development','Voluptate hic ex sapiente rerum iusto omnis non. Quasi soluta corporis laborum optio delectus et. Cupiditate molestiae fugiat voluptatem voluptatum illo.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,19,NULL,4,4,4,0,1,900.00,2100.00,'weekly','fixed',1,3,1,4,77,0,5,'2025-11-30',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.641877','-75.912103',0,0,1,1,'published','approved','2025-09-26 00:49:07','2025-10-26 20:13:04',NULL,NULL,'2025-11-10',NULL),(45,NULL,'Partner Program Manager','Aut voluptates et aperiam dignissimos ipsa. Accusamus quis aperiam tempora dicta praesentium laboriosam accusamus. Dolorum non consequuntur porro cum. Ut molestiae deserunt id at rem et.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,17,NULL,3,3,3,1,3,500.00,1500.00,'weekly','fixed',1,2,3,1,56,0,8,'2025-11-03',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.802572','-76.013218',0,0,1,1,'published','approved','2025-10-22 19:12:35','2025-10-26 20:13:04',NULL,NULL,'2025-11-20',NULL),(46,NULL,'Principal Cloud Solutions Engineer','Eos hic repellendus eveniet sunt repudiandae. Occaecati eum quas laudantium qui a est recusandae.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,20,NULL,4,4,4,0,4,1000.00,2100.00,'weekly','fixed',1,3,3,4,46,0,5,'2025-11-17',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.728374','-76.749041',0,0,1,1,'published','approved','2025-10-26 07:45:50','2025-10-26 20:13:04',NULL,NULL,'2025-12-03',NULL),(47,NULL,'Senior Cloud Solutions Engineer','Quasi ut sed possimus non. Fugit iste ducimus odio molestias voluptatum sunt voluptatem. Sint facere voluptas earum magni ullam provident.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,10,NULL,1,1,1,1,3,700.00,2000.00,'weekly','fixed',0,1,3,4,151,0,9,'2025-11-13',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.406581','-76.500639',0,0,1,1,'published','approved','2025-09-16 04:20:32','2025-10-26 20:13:04',NULL,NULL,'2025-12-22',NULL),(48,NULL,'Senior Customer Success Manager','Tenetur fugiat consequuntur a voluptas sapiente. Officiis ab non laborum dolor quasi sed ut. Nisi eum occaecati sint cum rerum voluptatum ipsa. Enim voluptas fugiat qui.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,11,NULL,4,4,4,0,4,1100.00,2200.00,'hourly','fixed',1,6,3,1,118,0,7,'2025-12-10',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.114525','-75.964499',0,0,0,0,'published','approved','2025-09-10 07:47:34','2025-10-26 20:13:04',NULL,NULL,'2025-11-15',NULL),(49,NULL,'Inside Account Manager','Qui suscipit rerum ut occaecati voluptas qui qui. In suscipit voluptatem dicta a cum ad. Occaecati et esse vel harum porro vel.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,8,NULL,6,6,6,1,5,600.00,1500.00,'weekly','fixed',1,7,2,1,74,0,3,'2025-11-14',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.345334','-75.435967',0,0,0,0,'published','approved','2025-10-17 15:33:55','2025-10-26 20:13:04',NULL,NULL,'2025-12-22',NULL),(50,NULL,'UX Jobs Board','Ut enim nihil tenetur et molestias corporis. Omnis dolor exercitationem voluptatem qui qui. Dolorem cumque ea distinctio occaecati consequatur qui magni.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,19,NULL,4,4,4,1,1,700.00,1800.00,'monthly','fixed',1,9,1,5,50,0,5,'2025-11-24',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'42.641877','-75.912103',0,0,0,0,'published','approved','2025-10-06 02:04:52','2025-10-26 20:13:04',NULL,NULL,'2025-11-22',NULL),(51,NULL,'Senior Laravel Developer (TALL Stack)','Rem qui officiis incidunt doloribus fuga. Quia odit veritatis dolor voluptas. Maiores quia ipsum ut qui.','<h5>Responsibilities</h5>\n                <div>\n                    <p>As a Product Designer, you will work within a Product Delivery Team fused with UX, engineering, product and data talent.</p>\n                    <ul>\n                        <li>Have sound knowledge of commercial activities.</li>\n                        <li>Build next-generation web applications with a focus on the client side</li>\n                        <li>Work on multiple projects at once, and consistently meet draft deadlines</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Revise the work of previous designers to create a unified aesthetic for our brand materials</li>\n                    </ul>\n                </div>\n                <h5>Qualification </h5>\n                <div>\n                    <ul>\n                        <li>B.C.A / M.C.A under National University course complete.</li>\n                        <li>3 or more years of professional design experience</li>\n                        <li>have already graduated or are currently in any year of study</li>\n                        <li>Advanced degree or equivalent experience in graphic and web design</li>\n                    </ul>\n                </div>','',NULL,6,NULL,4,4,4,0,1,1200.00,2700.00,'monthly','fixed',1,3,3,4,92,0,6,'2025-12-14',1,'Botble\\JobBoard\\Models\\Account',0,0,0,'43.783836','-75.003313',0,0,0,1,'published','approved','2025-09-21 13:28:39','2025-10-26 20:13:04',NULL,NULL,'2025-11-15',NULL);
/*!40000 ALTER TABLE `jb_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_jobs_categories`
--

DROP TABLE IF EXISTS `jb_jobs_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_jobs_categories` (
  `job_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  UNIQUE KEY `jb_jobs_categories_unique` (`job_id`,`category_id`),
  KEY `jb_jobs_categories_job_id_index` (`job_id`),
  KEY `jb_jobs_categories_category_id_index` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_jobs_categories`
--

LOCK TABLES `jb_jobs_categories` WRITE;
/*!40000 ALTER TABLE `jb_jobs_categories` DISABLE KEYS */;
INSERT INTO `jb_jobs_categories` VALUES (1,1),(1,3),(1,8),(2,1),(2,4),(2,10),(3,1),(3,4),(3,9),(4,1),(4,5),(4,6),(5,1),(5,4),(5,6),(6,1),(6,5),(6,7),(7,1),(7,3),(7,9),(8,1),(8,2),(8,10),(9,1),(9,2),(9,6),(10,1),(10,3),(10,8),(11,1),(11,3),(11,8),(12,1),(12,4),(12,6),(13,1),(13,5),(13,7),(14,1),(14,2),(14,7),(15,1),(15,2),(15,7),(16,1),(16,5),(16,7),(17,1),(17,4),(17,7),(18,1),(18,4),(18,10),(19,1),(19,4),(19,7),(20,1),(20,5),(20,7),(21,1),(21,5),(21,7),(22,1),(22,4),(22,6),(23,1),(23,5),(23,10),(24,1),(24,2),(24,9),(25,1),(25,3),(25,8),(26,1),(26,4),(26,9),(27,1),(27,3),(27,7),(28,1),(28,2),(28,8),(29,1),(29,5),(29,7),(30,1),(30,2),(30,8),(31,1),(31,5),(31,10),(32,1),(32,5),(32,6),(33,1),(33,3),(33,9),(34,1),(34,5),(34,7),(35,1),(35,2),(35,10),(36,1),(36,3),(36,7),(37,1),(37,4),(37,8),(38,1),(38,4),(38,10),(39,1),(39,5),(39,7),(40,1),(40,3),(40,8),(41,1),(41,5),(41,8),(42,1),(42,3),(42,7),(43,1),(43,5),(43,9),(44,1),(44,2),(44,8),(45,1),(45,2),(45,10),(46,1),(46,4),(46,7),(47,1),(47,4),(47,9),(48,1),(48,4),(48,8),(49,1),(49,4),(49,8),(50,1),(50,3),(50,7),(51,1),(51,3),(51,9);
/*!40000 ALTER TABLE `jb_jobs_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_jobs_skills`
--

DROP TABLE IF EXISTS `jb_jobs_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_jobs_skills` (
  `job_id` bigint unsigned NOT NULL,
  `job_skill_id` bigint unsigned NOT NULL,
  UNIQUE KEY `jb_jobs_skills_unique` (`job_id`,`job_skill_id`),
  KEY `jb_jobs_skills_job_id_index` (`job_id`),
  KEY `jb_jobs_skills_job_skill_id_index` (`job_skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_jobs_skills`
--

LOCK TABLES `jb_jobs_skills` WRITE;
/*!40000 ALTER TABLE `jb_jobs_skills` DISABLE KEYS */;
INSERT INTO `jb_jobs_skills` VALUES (1,5),(2,7),(3,1),(4,1),(5,5),(6,9),(7,7),(8,9),(9,1),(10,8),(11,7),(12,9),(13,7),(14,2),(15,2),(16,6),(17,7),(18,8),(19,2),(20,5),(21,4),(22,7),(23,3),(24,8),(25,8),(26,8),(27,6),(28,1),(29,6),(30,5),(31,1),(32,9),(33,3),(34,1),(35,5),(36,7),(37,9),(38,9),(39,7),(40,9),(41,7),(42,7),(43,2),(44,5),(45,3),(46,3),(47,6),(48,5),(49,8),(50,3),(51,8);
/*!40000 ALTER TABLE `jb_jobs_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_jobs_tags`
--

DROP TABLE IF EXISTS `jb_jobs_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_jobs_tags` (
  `job_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`job_id`,`tag_id`),
  KEY `jb_jobs_tags_job_id_index` (`job_id`),
  KEY `jb_jobs_tags_tag_id_index` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_jobs_tags`
--

LOCK TABLES `jb_jobs_tags` WRITE;
/*!40000 ALTER TABLE `jb_jobs_tags` DISABLE KEYS */;
INSERT INTO `jb_jobs_tags` VALUES (1,1),(1,5),(2,1),(2,8),(3,3),(3,6),(4,4),(4,7),(5,4),(5,8),(6,3),(6,7),(7,2),(7,7),(8,1),(8,6),(9,2),(9,7),(10,3),(10,7),(11,1),(11,5),(12,3),(12,5),(13,1),(13,8),(14,1),(14,7),(15,1),(15,8),(16,2),(16,8),(17,3),(17,6),(18,4),(18,6),(19,3),(19,6),(20,4),(20,7),(21,2),(21,8),(22,3),(22,8),(23,3),(23,8),(24,2),(24,6),(25,1),(25,5),(26,1),(26,5),(27,3),(27,5),(28,2),(28,7),(29,4),(29,6),(30,3),(30,5),(31,3),(31,5),(32,2),(32,8),(33,4),(33,7),(34,2),(34,5),(35,1),(35,8),(36,1),(36,5),(37,3),(37,8),(38,3),(38,7),(39,1),(39,7),(40,2),(40,7),(41,4),(41,8),(42,4),(42,7),(43,4),(43,6),(44,1),(44,8),(45,3),(45,7),(46,2),(46,5),(47,4),(47,5),(48,2),(48,6),(49,2),(49,7),(50,4),(50,6),(51,3),(51,8);
/*!40000 ALTER TABLE `jb_jobs_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_jobs_translations`
--

DROP TABLE IF EXISTS `jb_jobs_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_jobs_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_jobs_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `address` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lang_code`,`jb_jobs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_jobs_translations`
--

LOCK TABLES `jb_jobs_translations` WRITE;
/*!40000 ALTER TABLE `jb_jobs_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_jobs_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_jobs_types`
--

DROP TABLE IF EXISTS `jb_jobs_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_jobs_types` (
  `job_id` bigint unsigned NOT NULL,
  `job_type_id` bigint unsigned NOT NULL,
  UNIQUE KEY `jb_jobs_types_unique` (`job_id`,`job_type_id`),
  KEY `jb_jobs_types_job_id_index` (`job_id`),
  KEY `jb_jobs_types_job_type_id_index` (`job_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_jobs_types`
--

LOCK TABLES `jb_jobs_types` WRITE;
/*!40000 ALTER TABLE `jb_jobs_types` DISABLE KEYS */;
INSERT INTO `jb_jobs_types` VALUES (1,3),(2,4),(3,2),(4,5),(5,1),(6,4),(7,1),(8,4),(9,2),(10,2),(11,1),(12,2),(13,1),(14,1),(15,2),(16,5),(17,2),(18,4),(19,1),(20,5),(21,5),(22,2),(23,2),(24,2),(25,3),(26,3),(27,5),(28,2),(29,2),(30,3),(31,2),(32,3),(33,4),(34,3),(35,4),(36,2),(37,1),(38,1),(39,5),(40,2),(41,4),(42,5),(43,1),(44,5),(45,3),(46,3),(47,3),(48,5),(49,5),(50,4),(51,1);
/*!40000 ALTER TABLE `jb_jobs_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_language_levels`
--

DROP TABLE IF EXISTS `jb_language_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_language_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_language_levels`
--

LOCK TABLES `jb_language_levels` WRITE;
/*!40000 ALTER TABLE `jb_language_levels` DISABLE KEYS */;
INSERT INTO `jb_language_levels` VALUES (1,'Expert',0,0,'published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(2,'Intermediate',0,0,'published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(3,'Beginner',0,0,'published','2025-10-26 20:13:04','2025-10-26 20:13:04');
/*!40000 ALTER TABLE `jb_language_levels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_language_levels_translations`
--

DROP TABLE IF EXISTS `jb_language_levels_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_language_levels_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_language_levels_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_language_levels_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_language_levels_translations`
--

LOCK TABLES `jb_language_levels_translations` WRITE;
/*!40000 ALTER TABLE `jb_language_levels_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_language_levels_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_major_subjects`
--

DROP TABLE IF EXISTS `jb_major_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_major_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_major_subjects`
--

LOCK TABLES `jb_major_subjects` WRITE;
/*!40000 ALTER TABLE `jb_major_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_major_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_packages`
--

DROP TABLE IF EXISTS `jb_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` double unsigned NOT NULL,
  `currency_id` bigint unsigned NOT NULL,
  `percent_save` int unsigned DEFAULT '0',
  `number_of_listings` int unsigned NOT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `account_limit` int unsigned DEFAULT NULL,
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `features` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_packages`
--

LOCK TABLES `jb_packages` WRITE;
/*!40000 ALTER TABLE `jb_packages` DISABLE KEYS */;
INSERT INTO `jb_packages` VALUES (1,'Basic Package',0,1,0,1,0,1,0,'\"[[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Basic listing\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Standard support\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"No featured listing\\\"}]]\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL),(2,'Standard Package',250,1,0,1,0,NULL,1,'\"[[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Standard listing\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Standard support\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"No featured listing\\\"}]]\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL),(3,'Professional Package',1000,1,20,5,0,NULL,0,'\"[[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Professional listing\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Priority support\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"No featured listing\\\"}]]\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL),(4,'Premium Package',5000,1,20,50,0,NULL,0,'\"[[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Featured listing\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Top of search results\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Highlighted listing\\\"}],[{\\\"key\\\":\\\"text\\\",\\\"value\\\":\\\"Social media promotion\\\"}]]\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL);
/*!40000 ALTER TABLE `jb_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_packages_translations`
--

DROP TABLE IF EXISTS `jb_packages_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_packages_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_packages_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lang_code`,`jb_packages_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_packages_translations`
--

LOCK TABLES `jb_packages_translations` WRITE;
/*!40000 ALTER TABLE `jb_packages_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_packages_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_reviews`
--

DROP TABLE IF EXISTS `jb_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `star` double NOT NULL,
  `review` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reviewable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewable_id` bigint unsigned NOT NULL,
  `created_by_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reviews_unique` (`reviewable_id`,`reviewable_type`,`created_by_id`,`created_by_type`),
  KEY `jb_reviews_reviewable_type_reviewable_id_index` (`reviewable_type`,`reviewable_id`),
  KEY `jb_reviews_created_by_type_created_by_id_index` (`created_by_type`,`created_by_id`),
  KEY `jb_reviews_reviewable_id_reviewable_type_status_index` (`reviewable_id`,`reviewable_type`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_reviews`
--

LOCK TABLES `jb_reviews` WRITE;
/*!40000 ALTER TABLE `jb_reviews` DISABLE KEYS */;
INSERT INTO `jb_reviews` VALUES (1,1,'The best store template! Excellent coding! Very good support! Thank you so much for all the help, I really appreciated.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',66,'Botble\\JobBoard\\Models\\Company',14),(2,1,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',1,'Botble\\JobBoard\\Models\\Account',50),(3,2,'Ok good product. I have some issues in customizations. But its not correct to blame the developer. The product is good. Good luck for your business.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',8,'Botble\\JobBoard\\Models\\Account',6),(4,3,'Solution is too robust for our purpose so we didn\'t use it at the end. But I appreciate customer support during initial configuration.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',7,'Botble\\JobBoard\\Models\\Account',74),(5,1,'The script is the best of its class, fast, easy to implement and work with , and the most important thing is the great support team , Recommend with no doubt.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',7,'Botble\\JobBoard\\Models\\Account',2),(6,4,'Great system, great support, good job Botble. I\'m looking forward to more great functional plugins.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',2,'Botble\\JobBoard\\Models\\Company',19),(7,4,'Perfect +++++++++ i love it really also i get to fast ticket answers... Thanks Lot BOTBLE Teams','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',2,'Botble\\JobBoard\\Models\\Account',80),(8,4,'I Love this Script. I also found how to add other fees. Now I just wait the BIG update for the Marketplace with the Bulk Import. Just do not forget to make it to be Multi-language for us the Botble Fans.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',19,'Botble\\JobBoard\\Models\\Account',45),(9,2,'Great E-commerce system. And much more : Wonderful Customer Support.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',11,'Botble\\JobBoard\\Models\\Account',26),(10,1,'The script is the best of its class, fast, easy to implement and work with , and the most important thing is the great support team , Recommend with no doubt.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',16,'Botble\\JobBoard\\Models\\Account',91),(11,4,'It\'s not my first experience here on Codecanyon and I can honestly tell you all that Botble puts a LOT of effort into the support. They answer so fast, they helped me tons of times. REALLY by far THE BEST EXPERIENCE on Codecanyon. Those guys at Botble are so good that they deserve 5 stars. I recommend them, I trust them and I can\'t wait to see what they will sell in a near future. Thank you Botble :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',14,'Botble\\JobBoard\\Models\\Account',41),(12,1,'Cool template. Excellent code quality. The support responds very quickly, which is very rare on themeforest and codecanyon.net, I buy a lot of templates, and everyone will have a response from technical support for two or three days. Thanks to tech support. I recommend to buy.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',12,'Botble\\JobBoard\\Models\\Account',43),(13,5,'Great E-commerce system. And much more : Wonderful Customer Support.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',30,'Botble\\JobBoard\\Models\\Company',17),(14,1,'Perfect +++++++++ i love it really also i get to fast ticket answers... Thanks Lot BOTBLE Teams','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',45,'Botble\\JobBoard\\Models\\Company',10),(15,4,'Perfect +++++++++ i love it really also i get to fast ticket answers... Thanks Lot BOTBLE Teams','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',7,'Botble\\JobBoard\\Models\\Account',77),(16,1,'Very enthusiastic support! Excellent code is written. It\'s a true pleasure working with.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',85,'Botble\\JobBoard\\Models\\Company',15),(17,1,'This web app is really good in design, code quality & features. Besides, the customer support provided by the Botble team was really fast & helpful. You guys are awesome!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',13,'Botble\\JobBoard\\Models\\Account',82),(18,2,'This script is well coded and is super fast. The support is pretty quick. Very patient and helpful team. I strongly recommend it and they deserve more than 5 stars.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',91,'Botble\\JobBoard\\Models\\Company',3),(19,5,'Very enthusiastic support! Excellent code is written. It\'s a true pleasure working with.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',17,'Botble\\JobBoard\\Models\\Account',38),(20,5,'Second or third time that I buy a Botble product, happy with the products and support. You guys do a good job :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',70,'Botble\\JobBoard\\Models\\Company',12),(21,3,'These guys are amazing! Responses immediately, amazing support and help... I immediately feel at ease after Purchasing..','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',8,'Botble\\JobBoard\\Models\\Account',22),(22,2,'Amazing code, amazing support. Overall, im really confident in Botble and im happy I made the right choice! Thank you so much guys for coding this masterpiece','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',89,'Botble\\JobBoard\\Models\\Company',6),(23,5,'The code is good, in general, if you like it, can you give it 5 stars?','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',7,'Botble\\JobBoard\\Models\\Account',100),(24,2,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',96,'Botble\\JobBoard\\Models\\Company',12),(25,2,'This web app is really good in design, code quality & features. Besides, the customer support provided by the Botble team was really fast & helpful. You guys are awesome!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',7,'Botble\\JobBoard\\Models\\Company',13),(26,2,'Second or third time that I buy a Botble product, happy with the products and support. You guys do a good job :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',4,'Botble\\JobBoard\\Models\\Account',88),(27,4,'I Love this Script. I also found how to add other fees. Now I just wait the BIG update for the Marketplace with the Bulk Import. Just do not forget to make it to be Multi-language for us the Botble Fans.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',20,'Botble\\JobBoard\\Models\\Account',67),(28,4,'Very enthusiastic support! Excellent code is written. It\'s a true pleasure working with.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',97,'Botble\\JobBoard\\Models\\Company',16),(29,5,'Solution is too robust for our purpose so we didn\'t use it at the end. But I appreciate customer support during initial configuration.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',18,'Botble\\JobBoard\\Models\\Company',7),(30,4,'This script is well coded and is super fast. The support is pretty quick. Very patient and helpful team. I strongly recommend it and they deserve more than 5 stars.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',76,'Botble\\JobBoard\\Models\\Company',7),(31,3,'This web app is really good in design, code quality & features. Besides, the customer support provided by the Botble team was really fast & helpful. You guys are awesome!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',14,'Botble\\JobBoard\\Models\\Account',52),(32,2,'The code is good, in general, if you like it, can you give it 5 stars?','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',5,'Botble\\JobBoard\\Models\\Account',30),(33,5,'Good app, good backup service and support. Good documentation.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',37,'Botble\\JobBoard\\Models\\Company',14),(34,1,'We have received brilliant service support and will be expanding the features with the developer. Nice product!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',95,'Botble\\JobBoard\\Models\\Company',12),(35,4,'I Love this Script. I also found how to add other fees. Now I just wait the BIG update for the Marketplace with the Bulk Import. Just do not forget to make it to be Multi-language for us the Botble Fans.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',5,'Botble\\JobBoard\\Models\\Account',24),(36,2,'Ok good product. I have some issues in customizations. But its not correct to blame the developer. The product is good. Good luck for your business.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',4,'Botble\\JobBoard\\Models\\Company',9),(37,2,'Solution is too robust for our purpose so we didn\'t use it at the end. But I appreciate customer support during initial configuration.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',15,'Botble\\JobBoard\\Models\\Account',43),(38,3,'Very enthusiastic support! Excellent code is written. It\'s a true pleasure working with.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',6,'Botble\\JobBoard\\Models\\Account',41),(39,1,'It\'s not my first experience here on Codecanyon and I can honestly tell you all that Botble puts a LOT of effort into the support. They answer so fast, they helped me tons of times. REALLY by far THE BEST EXPERIENCE on Codecanyon. Those guys at Botble are so good that they deserve 5 stars. I recommend them, I trust them and I can\'t wait to see what they will sell in a near future. Thank you Botble :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',47,'Botble\\JobBoard\\Models\\Company',6),(40,5,'These guys are amazing! Responses immediately, amazing support and help... I immediately feel at ease after Purchasing..','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',20,'Botble\\JobBoard\\Models\\Account',35),(41,3,'Perfect +++++++++ i love it really also i get to fast ticket answers... Thanks Lot BOTBLE Teams','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',19,'Botble\\JobBoard\\Models\\Company',15),(42,3,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',14,'Botble\\JobBoard\\Models\\Company',8),(43,1,'This script is well coded and is super fast. The support is pretty quick. Very patient and helpful team. I strongly recommend it and they deserve more than 5 stars.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',86,'Botble\\JobBoard\\Models\\Company',14),(44,1,'Best ecommerce CMS online store!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',21,'Botble\\JobBoard\\Models\\Company',10),(45,4,'This script is well coded and is super fast. The support is pretty quick. Very patient and helpful team. I strongly recommend it and they deserve more than 5 stars.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',4,'Botble\\JobBoard\\Models\\Account',85),(46,1,'Amazing code, amazing support. Overall, im really confident in Botble and im happy I made the right choice! Thank you so much guys for coding this masterpiece','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',11,'Botble\\JobBoard\\Models\\Account',1),(47,3,'Very enthusiastic support! Excellent code is written. It\'s a true pleasure working with.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',60,'Botble\\JobBoard\\Models\\Company',4),(48,2,'These guys are amazing! Responses immediately, amazing support and help... I immediately feel at ease after Purchasing..','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',30,'Botble\\JobBoard\\Models\\Company',12),(49,2,'Very enthusiastic support! Excellent code is written. It\'s a true pleasure working with.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',57,'Botble\\JobBoard\\Models\\Company',14),(50,2,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',62,'Botble\\JobBoard\\Models\\Company',19),(51,1,'Great system, great support, good job Botble. I\'m looking forward to more great functional plugins.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',78,'Botble\\JobBoard\\Models\\Company',14),(52,1,'The script is the best of its class, fast, easy to implement and work with , and the most important thing is the great support team , Recommend with no doubt.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',72,'Botble\\JobBoard\\Models\\Company',2),(53,5,'For me the best eCommerce script on Envato at this moment: modern, clean code, a lot of great features. The customer support is great too: I always get an answer within hours!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',45,'Botble\\JobBoard\\Models\\Company',1),(54,2,'Customer Support are grade (A*), however the code is a way too over engineered for it\'s purpose.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',16,'Botble\\JobBoard\\Models\\Account',54),(55,4,'We have received brilliant service support and will be expanding the features with the developer. Nice product!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',6,'Botble\\JobBoard\\Models\\Account',17),(56,2,'Perfect +++++++++ i love it really also i get to fast ticket answers... Thanks Lot BOTBLE Teams','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',59,'Botble\\JobBoard\\Models\\Company',8),(57,3,'Cool template. Excellent code quality. The support responds very quickly, which is very rare on themeforest and codecanyon.net, I buy a lot of templates, and everyone will have a response from technical support for two or three days. Thanks to tech support. I recommend to buy.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',16,'Botble\\JobBoard\\Models\\Account',15),(58,2,'Customer Support are grade (A*), however the code is a way too over engineered for it\'s purpose.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',57,'Botble\\JobBoard\\Models\\Company',18),(59,5,'Those guys now what they are doing, the release such a good product that it\'s a pleasure to work with ! Even when I was stuck on the project, I created a ticket and the next day it was replied by the team. GOOD JOB guys. I love working with them :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',3,'Botble\\JobBoard\\Models\\Account',52),(60,3,'It\'s not my first experience here on Codecanyon and I can honestly tell you all that Botble puts a LOT of effort into the support. They answer so fast, they helped me tons of times. REALLY by far THE BEST EXPERIENCE on Codecanyon. Those guys at Botble are so good that they deserve 5 stars. I recommend them, I trust them and I can\'t wait to see what they will sell in a near future. Thank you Botble :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',12,'Botble\\JobBoard\\Models\\Account',29),(61,3,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',16,'Botble\\JobBoard\\Models\\Account',90),(62,5,'Ok good product. I have some issues in customizations. But its not correct to blame the developer. The product is good. Good luck for your business.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',2,'Botble\\JobBoard\\Models\\Account',42),(63,3,'Ok good product. I have some issues in customizations. But its not correct to blame the developer. The product is good. Good luck for your business.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',9,'Botble\\JobBoard\\Models\\Account',56),(64,5,'The best store template! Excellent coding! Very good support! Thank you so much for all the help, I really appreciated.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',51,'Botble\\JobBoard\\Models\\Company',12),(65,3,'Customer Support are grade (A*), however the code is a way too over engineered for it\'s purpose.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',10,'Botble\\JobBoard\\Models\\Account',49),(66,2,'The code is good, in general, if you like it, can you give it 5 stars?','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',20,'Botble\\JobBoard\\Models\\Company',17),(67,4,'Solution is too robust for our purpose so we didn\'t use it at the end. But I appreciate customer support during initial configuration.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',7,'Botble\\JobBoard\\Models\\Account',80),(68,2,'The code is good, in general, if you like it, can you give it 5 stars?','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',19,'Botble\\JobBoard\\Models\\Company',20),(69,5,'As a developer I reviewed this script. This is really awesome ecommerce script. I have convinced when I noticed that it\'s built on fully WordPress concept.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',7,'Botble\\JobBoard\\Models\\Account',25),(70,2,'The best store template! Excellent coding! Very good support! Thank you so much for all the help, I really appreciated.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',3,'Botble\\JobBoard\\Models\\Account',87),(71,5,'Those guys now what they are doing, the release such a good product that it\'s a pleasure to work with ! Even when I was stuck on the project, I created a ticket and the next day it was replied by the team. GOOD JOB guys. I love working with them :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',5,'Botble\\JobBoard\\Models\\Account',18),(72,4,'This web app is really good in design, code quality & features. Besides, the customer support provided by the Botble team was really fast & helpful. You guys are awesome!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',40,'Botble\\JobBoard\\Models\\Company',15),(73,5,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',3,'Botble\\JobBoard\\Models\\Account',15),(74,1,'The best store template! Excellent coding! Very good support! Thank you so much for all the help, I really appreciated.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',22,'Botble\\JobBoard\\Models\\Company',4),(75,1,'Ok good product. I have some issues in customizations. But its not correct to blame the developer. The product is good. Good luck for your business.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',2,'Botble\\JobBoard\\Models\\Company',12),(76,3,'This web app is really good in design, code quality & features. Besides, the customer support provided by the Botble team was really fast & helpful. You guys are awesome!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',14,'Botble\\JobBoard\\Models\\Account',62),(77,2,'The script is the best of its class, fast, easy to implement and work with , and the most important thing is the great support team , Recommend with no doubt.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',9,'Botble\\JobBoard\\Models\\Account',62),(79,5,'Great E-commerce system. And much more : Wonderful Customer Support.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',99,'Botble\\JobBoard\\Models\\Company',3),(80,3,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',36,'Botble\\JobBoard\\Models\\Company',8),(81,3,'Amazing code, amazing support. Overall, im really confident in Botble and im happy I made the right choice! Thank you so much guys for coding this masterpiece','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',1,'Botble\\JobBoard\\Models\\Account',51),(82,5,'The code is good, in general, if you like it, can you give it 5 stars?','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',15,'Botble\\JobBoard\\Models\\Account',92),(83,4,'Solution is too robust for our purpose so we didn\'t use it at the end. But I appreciate customer support during initial configuration.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',7,'Botble\\JobBoard\\Models\\Account',85),(84,1,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',2,'Botble\\JobBoard\\Models\\Account',30),(85,3,'This script is well coded and is super fast. The support is pretty quick. Very patient and helpful team. I strongly recommend it and they deserve more than 5 stars.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',63,'Botble\\JobBoard\\Models\\Company',18),(86,1,'Great system, great support, good job Botble. I\'m looking forward to more great functional plugins.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',66,'Botble\\JobBoard\\Models\\Company',7),(87,5,'It\'s not my first experience here on Codecanyon and I can honestly tell you all that Botble puts a LOT of effort into the support. They answer so fast, they helped me tons of times. REALLY by far THE BEST EXPERIENCE on Codecanyon. Those guys at Botble are so good that they deserve 5 stars. I recommend them, I trust them and I can\'t wait to see what they will sell in a near future. Thank you Botble :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',10,'Botble\\JobBoard\\Models\\Account',35),(88,1,'It\'s not my first experience here on Codecanyon and I can honestly tell you all that Botble puts a LOT of effort into the support. They answer so fast, they helped me tons of times. REALLY by far THE BEST EXPERIENCE on Codecanyon. Those guys at Botble are so good that they deserve 5 stars. I recommend them, I trust them and I can\'t wait to see what they will sell in a near future. Thank you Botble :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',3,'Botble\\JobBoard\\Models\\Account',22),(89,1,'As a developer I reviewed this script. This is really awesome ecommerce script. I have convinced when I noticed that it\'s built on fully WordPress concept.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',77,'Botble\\JobBoard\\Models\\Company',12),(90,4,'Good app, good backup service and support. Good documentation.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',6,'Botble\\JobBoard\\Models\\Account',77),(91,5,'The script is the best of its class, fast, easy to implement and work with , and the most important thing is the great support team , Recommend with no doubt.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',75,'Botble\\JobBoard\\Models\\Company',6),(92,1,'Customer Support are grade (A*), however the code is a way too over engineered for it\'s purpose.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',93,'Botble\\JobBoard\\Models\\Company',20),(93,2,'Clean & perfect source code','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',3,'Botble\\JobBoard\\Models\\Account',7),(94,4,'This web app is really good in design, code quality & features. Besides, the customer support provided by the Botble team was really fast & helpful. You guys are awesome!','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',12,'Botble\\JobBoard\\Models\\Account',4),(95,2,'Perfect +++++++++ i love it really also i get to fast ticket answers... Thanks Lot BOTBLE Teams','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',18,'Botble\\JobBoard\\Models\\Account',38),(96,3,'Perfect +++++++++ i love it really also i get to fast ticket answers... Thanks Lot BOTBLE Teams','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Company',5,'Botble\\JobBoard\\Models\\Account',14),(97,5,'It\'s not my first experience here on Codecanyon and I can honestly tell you all that Botble puts a LOT of effort into the support. They answer so fast, they helped me tons of times. REALLY by far THE BEST EXPERIENCE on Codecanyon. Those guys at Botble are so good that they deserve 5 stars. I recommend them, I trust them and I can\'t wait to see what they will sell in a near future. Thank you Botble :)','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',88,'Botble\\JobBoard\\Models\\Company',15),(98,2,'The code is good, in general, if you like it, can you give it 5 stars?','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',4,'Botble\\JobBoard\\Models\\Company',20),(100,5,'Solution is too robust for our purpose so we didn\'t use it at the end. But I appreciate customer support during initial configuration.','published','2025-10-26 20:13:26','2025-10-26 20:13:26','Botble\\JobBoard\\Models\\Account',91,'Botble\\JobBoard\\Models\\Company',4);
/*!40000 ALTER TABLE `jb_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_saved_jobs`
--

DROP TABLE IF EXISTS `jb_saved_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_saved_jobs` (
  `account_id` bigint unsigned NOT NULL,
  `job_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`account_id`,`job_id`),
  KEY `jb_saved_jobs_job_id_index` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_saved_jobs`
--

LOCK TABLES `jb_saved_jobs` WRITE;
/*!40000 ALTER TABLE `jb_saved_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_saved_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_tags`
--

DROP TABLE IF EXISTS `jb_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_tags`
--

LOCK TABLES `jb_tags` WRITE;
/*!40000 ALTER TABLE `jb_tags` DISABLE KEYS */;
INSERT INTO `jb_tags` VALUES (1,'Illustrator','','published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(2,'Adobe XD','','published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(3,'Figma','','published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(4,'Sketch','','published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(5,'Lunacy','','published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(6,'PHP','','published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(7,'Python','','published','2025-10-26 20:13:04','2025-10-26 20:13:04'),(8,'JavaScript','','published','2025-10-26 20:13:04','2025-10-26 20:13:04');
/*!40000 ALTER TABLE `jb_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_tags_translations`
--

DROP TABLE IF EXISTS `jb_tags_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_tags_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jb_tags_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`jb_tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_tags_translations`
--

LOCK TABLES `jb_tags_translations` WRITE;
/*!40000 ALTER TABLE `jb_tags_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_tags_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jb_transactions`
--

DROP TABLE IF EXISTS `jb_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jb_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `credits` int unsigned NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'add',
  `payment_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jb_transactions_account_id_index` (`account_id`),
  KEY `jb_transactions_user_id_index` (`user_id`),
  KEY `jb_transactions_payment_id_index` (`payment_id`),
  KEY `jb_transactions_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jb_transactions`
--

LOCK TABLES `jb_transactions` WRITE;
/*!40000 ALTER TABLE `jb_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `jb_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `language_meta`
--

DROP TABLE IF EXISTS `language_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `language_meta` (
  `lang_meta_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lang_meta_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lang_meta_origin` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `reference_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`lang_meta_id`),
  KEY `language_meta_reference_id_index` (`reference_id`),
  KEY `meta_code_index` (`lang_meta_code`),
  KEY `meta_origin_index` (`lang_meta_origin`),
  KEY `meta_reference_type_index` (`reference_type`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `language_meta`
--

LOCK TABLES `language_meta` WRITE;
/*!40000 ALTER TABLE `language_meta` DISABLE KEYS */;
INSERT INTO `language_meta` VALUES (1,'en_US','bb895f538f0d0ab218a9d6cdddf9b429',1,'Botble\\Menu\\Models\\MenuLocation'),(2,'en_US','6bf6809a45559bc717b86b82122de7cb',1,'Botble\\Menu\\Models\\Menu'),(3,'en_US','475cc0fd3fe190803965dc1e5bbd8533',2,'Botble\\Menu\\Models\\Menu'),(4,'en_US','3f60ccc9f67df95530f1af3794b92deb',3,'Botble\\Menu\\Models\\Menu'),(5,'en_US','98da1bea53a460fcf3eceaf947177751',4,'Botble\\Menu\\Models\\Menu'),(6,'en_US','de3a7b0a8048254eb5bed5f7a6ed3e37',5,'Botble\\Menu\\Models\\Menu');
/*!40000 ALTER TABLE `language_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `lang_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lang_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_locale` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lang_flag` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lang_is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `lang_order` int NOT NULL DEFAULT '0',
  `lang_is_rtl` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`lang_id`),
  KEY `lang_locale_index` (`lang_locale`),
  KEY `lang_code_index` (`lang_code`),
  KEY `lang_is_default_index` (`lang_is_default`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `languages`
--

LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES (1,'English','en','en_US','us',1,0,0);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media_files`
--

DROP TABLE IF EXISTS `media_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_files` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `folder_id` bigint unsigned NOT NULL DEFAULT '0',
  `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` int NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `visibility` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'public',
  PRIMARY KEY (`id`),
  KEY `media_files_user_id_index` (`user_id`),
  KEY `media_files_index` (`folder_id`,`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=202 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media_files`
--

LOCK TABLES `media_files` WRITE;
/*!40000 ALTER TABLE `media_files` DISABLE KEYS */;
INSERT INTO `media_files` VALUES (41,0,'acer','acer',3,'image/png',285,'our-partners/acer.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(42,0,'asus','asus',3,'image/png',314,'our-partners/asus.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(43,0,'dell','dell',3,'image/png',296,'our-partners/dell.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(44,0,'microsoft','microsoft',3,'image/png',287,'our-partners/microsoft.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(45,0,'nokia','nokia',3,'image/png',308,'our-partners/nokia.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(46,0,'1','1',4,'image/jpeg',9803,'news/1.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(47,0,'10','10',4,'image/jpeg',9803,'news/10.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(48,0,'11','11',4,'image/jpeg',9803,'news/11.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(49,0,'12','12',4,'image/jpeg',9803,'news/12.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(50,0,'13','13',4,'image/jpeg',9803,'news/13.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(51,0,'14','14',4,'image/jpeg',9803,'news/14.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(52,0,'15','15',4,'image/jpeg',9803,'news/15.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(53,0,'16','16',4,'image/jpeg',9803,'news/16.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(54,0,'2','2',4,'image/jpeg',9803,'news/2.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(55,0,'3','3',4,'image/jpeg',9803,'news/3.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(56,0,'4','4',4,'image/jpeg',9803,'news/4.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(57,0,'5','5',4,'image/jpeg',9803,'news/5.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(58,0,'6','6',4,'image/jpeg',9803,'news/6.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(59,0,'7','7',4,'image/jpeg',9803,'news/7.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(60,0,'8','8',4,'image/jpeg',9803,'news/8.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(61,0,'9','9',4,'image/jpeg',9803,'news/9.jpg','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(62,0,'cover-image1','cover-image1',4,'image/png',9803,'news/cover-image1.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(63,0,'cover-image2','cover-image2',4,'image/png',9803,'news/cover-image2.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(64,0,'cover-image3','cover-image3',4,'image/png',9803,'news/cover-image3.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(65,0,'img-news1','img-news1',4,'image/png',9803,'news/img-news1.png','[]','2025-10-26 20:12:59','2025-10-26 20:12:59',NULL,'public'),(66,0,'img-news2','img-news2',4,'image/png',9803,'news/img-news2.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(67,0,'img-news3','img-news3',4,'image/png',9803,'news/img-news3.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(68,0,'1','1',5,'image/jpeg',6977,'galleries/1.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(69,0,'10','10',5,'image/jpeg',9803,'galleries/10.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(70,0,'2','2',5,'image/jpeg',6977,'galleries/2.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(71,0,'3','3',5,'image/jpeg',6977,'galleries/3.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(72,0,'4','4',5,'image/jpeg',6977,'galleries/4.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(73,0,'5','5',5,'image/jpeg',6977,'galleries/5.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(74,0,'6','6',5,'image/jpeg',6977,'galleries/6.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(75,0,'7','7',5,'image/jpeg',6977,'galleries/7.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(76,0,'8','8',5,'image/jpeg',9803,'galleries/8.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(77,0,'9','9',5,'image/jpeg',9803,'galleries/9.jpg','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(78,0,'widget-banner','widget-banner',6,'image/png',11079,'widgets/widget-banner.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(79,0,'404','404',7,'image/png',10947,'general/404.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(80,0,'android','android',7,'image/png',477,'general/android.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(81,0,'app-store','app-store',7,'image/png',477,'general/app-store.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(82,0,'content','content',7,'image/png',1705,'general/content.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(83,0,'cover-image','cover-image',7,'image/png',8992,'general/cover-image.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(84,0,'customer','customer',7,'image/png',2794,'general/customer.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(85,0,'favicon','favicon',7,'image/png',709,'general/favicon.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(86,0,'finance','finance',7,'image/png',2483,'general/finance.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(87,0,'human','human',7,'image/png',2401,'general/human.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(88,0,'img-about2','img-about2',7,'image/png',36911,'general/img-about2.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(89,0,'lightning','lightning',7,'image/png',2768,'general/lightning.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(90,0,'logo-company','logo-company',7,'image/png',3164,'general/logo-company.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(91,0,'logo-light','logo-light',7,'image/png',2290,'general/logo-light.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(92,0,'logo','logo',7,'image/png',2516,'general/logo.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(93,0,'management','management',7,'image/png',1967,'general/management.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(94,0,'marketing','marketing',7,'image/png',2202,'general/marketing.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(95,0,'newsletter-background-image','newsletter-background-image',7,'image/png',9830,'general/newsletter-background-image.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(96,0,'newsletter-image-left','newsletter-image-left',7,'image/png',4177,'general/newsletter-image-left.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(97,0,'newsletter-image-right','newsletter-image-right',7,'image/png',2886,'general/newsletter-image-right.png','[]','2025-10-26 20:13:00','2025-10-26 20:13:00',NULL,'public'),(98,0,'research','research',7,'image/png',3200,'general/research.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(99,0,'retail','retail',7,'image/png',2827,'general/retail.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(100,0,'security','security',7,'image/png',2952,'general/security.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(101,0,'img-1','img-1',8,'image/png',2377,'authentication/img-1.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(102,0,'img-2','img-2',8,'image/png',5009,'authentication/img-2.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(103,0,'background-cover-candidate','background-cover-candidate',9,'image/png',436821,'pages/background-cover-candidate.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(104,0,'background_breadcrumb','background_breadcrumb',9,'image/png',6111,'pages/background-breadcrumb.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(105,0,'banner-section-search-box','banner-section-search-box',9,'image/png',20501,'pages/banner-section-search-box.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(106,0,'banner1','banner1',9,'image/png',7381,'pages/banner1.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(107,0,'banner2','banner2',9,'image/png',4920,'pages/banner2.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(108,0,'banner3','banner3',9,'image/png',2472,'pages/banner3.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(109,0,'banner4','banner4',9,'image/png',1952,'pages/banner4.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(110,0,'banner5','banner5',9,'image/png',1545,'pages/banner5.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(111,0,'banner6','banner6',9,'image/png',1609,'pages/banner6.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(112,0,'bg-breadcrumb','bg-breadcrumb',9,'image/png',14250,'pages/bg-breadcrumb.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(113,0,'bg-cat','bg-cat',9,'image/png',60674,'pages/bg-cat.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(114,0,'bg-left-hiring','bg-left-hiring',9,'image/png',1631,'pages/bg-left-hiring.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(115,0,'bg-newsletter','bg-newsletter',9,'image/png',4587,'pages/bg-newsletter.png','[]','2025-10-26 20:13:01','2025-10-26 20:13:01',NULL,'public'),(116,0,'bg-right-hiring','bg-right-hiring',9,'image/png',3074,'pages/bg-right-hiring.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(117,0,'controlcard','controlcard',9,'image/png',7404,'pages/controlcard.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(118,0,'home-page-4-banner','home-page-4-banner',9,'image/png',7596,'pages/home-page-4-banner.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(119,0,'icon-bottom-banner','icon-bottom-banner',9,'image/png',304,'pages/icon-bottom-banner.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(120,0,'icon-top-banner','icon-top-banner',9,'image/png',414,'pages/icon-top-banner.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(121,0,'img-banner','img-banner',9,'image/png',10542,'pages/img-banner.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(122,0,'img-chart','img-chart',9,'image/png',7549,'pages/img-chart.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(123,0,'img-job-search','img-job-search',9,'image/png',35569,'pages/img-job-search.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(124,0,'img-profile','img-profile',9,'image/png',9177,'pages/img-profile.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(125,0,'img-single','img-single',9,'image/png',13060,'pages/img-single.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(126,0,'img1','img1',9,'image/png',10246,'pages/img1.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(127,0,'job-tools','job-tools',9,'image/png',2216,'pages/job-tools.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(128,0,'left-job-head','left-job-head',9,'image/png',14956,'pages/left-job-head.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(129,0,'newsletter-left','newsletter-left',9,'image/png',4177,'pages/newsletter-left.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(130,0,'newsletter-right','newsletter-right',9,'image/png',2886,'pages/newsletter-right.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(131,0,'planning-job','planning-job',9,'image/png',1623,'pages/planning-job.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(132,0,'right-job-head','right-job-head',9,'image/png',10955,'pages/right-job-head.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(133,0,'facebook','facebook',10,'image/png',796,'socials/facebook.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(134,0,'linkedin','linkedin',10,'image/png',802,'socials/linkedin.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(135,0,'twitter','twitter',10,'image/png',1025,'socials/twitter.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(136,0,'location1','location1',11,'image/png',5149,'locations/location1.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(137,0,'location2','location2',11,'image/png',5921,'locations/location2.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(138,0,'location3','location3',11,'image/png',5276,'locations/location3.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(139,0,'location4','location4',11,'image/png',5259,'locations/location4.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(140,0,'location5','location5',11,'image/png',5140,'locations/location5.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(141,0,'location6','location6',11,'image/png',4891,'locations/location6.png','[]','2025-10-26 20:13:02','2025-10-26 20:13:02',NULL,'public'),(142,0,'1','1',12,'image/png',407,'job-categories/1.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(143,0,'10','10',12,'image/png',407,'job-categories/10.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(144,0,'11','11',12,'image/png',407,'job-categories/11.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(145,0,'12','12',12,'image/png',407,'job-categories/12.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(146,0,'13','13',12,'image/png',407,'job-categories/13.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(147,0,'14','14',12,'image/png',407,'job-categories/14.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(148,0,'15','15',12,'image/png',407,'job-categories/15.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(149,0,'16','16',12,'image/png',407,'job-categories/16.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(150,0,'17','17',12,'image/png',407,'job-categories/17.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(151,0,'18','18',12,'image/png',407,'job-categories/18.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(152,0,'19','19',12,'image/png',407,'job-categories/19.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(153,0,'2','2',12,'image/png',407,'job-categories/2.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(154,0,'3','3',12,'image/png',407,'job-categories/3.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(155,0,'4','4',12,'image/png',407,'job-categories/4.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(156,0,'5','5',12,'image/png',407,'job-categories/5.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(157,0,'6','6',12,'image/png',407,'job-categories/6.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(158,0,'7','7',12,'image/png',407,'job-categories/7.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(159,0,'8','8',12,'image/png',407,'job-categories/8.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(160,0,'9','9',12,'image/png',407,'job-categories/9.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(161,0,'img-cover-1','img-cover-1',12,'image/png',33918,'job-categories/img-cover-1.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(162,0,'img-cover-2','img-cover-2',12,'image/png',33918,'job-categories/img-cover-2.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(163,0,'img-cover-3','img-cover-3',12,'image/png',33918,'job-categories/img-cover-3.png','[]','2025-10-26 20:13:03','2025-10-26 20:13:03',NULL,'public'),(164,0,'1','1',13,'image/png',598,'companies/1.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(165,0,'2','2',13,'image/png',598,'companies/2.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(166,0,'3','3',13,'image/png',598,'companies/3.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(167,0,'4','4',13,'image/png',598,'companies/4.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(168,0,'5','5',13,'image/png',598,'companies/5.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(169,0,'6','6',13,'image/png',598,'companies/6.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(170,0,'7','7',13,'image/png',598,'companies/7.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(171,0,'8','8',13,'image/png',598,'companies/8.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(172,0,'9','9',13,'image/png',598,'companies/9.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(173,0,'company-cover-image','company-cover-image',13,'image/png',8992,'companies/company-cover-image.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(174,0,'img1','img1',14,'image/png',5706,'jobs/img1.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(175,0,'img2','img2',14,'image/png',5706,'jobs/img2.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(176,0,'img3','img3',14,'image/png',5706,'jobs/img3.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(177,0,'img4','img4',14,'image/png',5706,'jobs/img4.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(178,0,'img5','img5',14,'image/png',5706,'jobs/img5.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(179,0,'img6','img6',14,'image/png',5706,'jobs/img6.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(180,0,'img7','img7',14,'image/png',5706,'jobs/img7.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(181,0,'img8','img8',14,'image/png',5706,'jobs/img8.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(182,0,'img9','img9',14,'image/png',5706,'jobs/img9.png','[]','2025-10-26 20:13:04','2025-10-26 20:13:04',NULL,'public'),(183,0,'01','01',15,'application/pdf',43496,'resume/01.pdf','[]','2025-10-26 20:13:05','2025-10-26 20:13:05',NULL,'public'),(184,0,'1','1',16,'image/png',3030,'avatars/1.png','[]','2025-10-26 20:13:05','2025-10-26 20:13:05',NULL,'public'),(185,0,'2','2',16,'image/png',2754,'avatars/2.png','[]','2025-10-26 20:13:05','2025-10-26 20:13:05',NULL,'public'),(186,0,'3','3',16,'image/png',2703,'avatars/3.png','[]','2025-10-26 20:13:05','2025-10-26 20:13:05',NULL,'public'),(187,0,'1','1',17,'image/png',395380,'covers/1.png','[]','2025-10-26 20:13:05','2025-10-26 20:13:05',NULL,'public'),(188,0,'2','2',17,'image/png',1308067,'covers/2.png','[]','2025-10-26 20:13:05','2025-10-26 20:13:05',NULL,'public'),(189,0,'3','3',17,'image/png',301502,'covers/3.png','[]','2025-10-26 20:13:05','2025-10-26 20:13:05',NULL,'public'),(190,0,'1','1',18,'image/png',4294,'teams/1.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(191,0,'2','2',18,'image/png',4294,'teams/2.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(192,0,'3','3',18,'image/png',4294,'teams/3.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(193,0,'4','4',18,'image/png',4294,'teams/4.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(194,0,'5','5',18,'image/png',4294,'teams/5.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(195,0,'6','6',18,'image/png',4294,'teams/6.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(196,0,'7','7',18,'image/png',4294,'teams/7.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(197,0,'8','8',18,'image/png',4294,'teams/8.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(198,0,'1','1',19,'image/png',3943,'testimonials/1.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(199,0,'2','2',19,'image/png',3943,'testimonials/2.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(200,0,'3','3',19,'image/png',3943,'testimonials/3.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public'),(201,0,'4','4',19,'image/png',3943,'testimonials/4.png','[]','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,'public');
/*!40000 ALTER TABLE `media_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media_folders`
--

DROP TABLE IF EXISTS `media_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_folders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `media_folders_user_id_index` (`user_id`),
  KEY `media_folders_index` (`parent_id`,`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media_folders`
--

LOCK TABLES `media_folders` WRITE;
/*!40000 ALTER TABLE `media_folders` DISABLE KEYS */;
INSERT INTO `media_folders` VALUES (3,0,'our-partners',NULL,'our-partners',0,'2025-10-26 20:12:59','2025-10-26 20:12:59',NULL),(4,0,'news',NULL,'news',0,'2025-10-26 20:12:59','2025-10-26 20:12:59',NULL),(5,0,'galleries',NULL,'galleries',0,'2025-10-26 20:13:00','2025-10-26 20:13:00',NULL),(6,0,'widgets',NULL,'widgets',0,'2025-10-26 20:13:00','2025-10-26 20:13:00',NULL),(7,0,'general',NULL,'general',0,'2025-10-26 20:13:00','2025-10-26 20:13:00',NULL),(8,0,'authentication',NULL,'authentication',0,'2025-10-26 20:13:01','2025-10-26 20:13:01',NULL),(9,0,'pages',NULL,'pages',0,'2025-10-26 20:13:01','2025-10-26 20:13:01',NULL),(10,0,'socials',NULL,'socials',0,'2025-10-26 20:13:02','2025-10-26 20:13:02',NULL),(11,0,'locations',NULL,'locations',0,'2025-10-26 20:13:02','2025-10-26 20:13:02',NULL),(12,0,'job-categories',NULL,'job-categories',0,'2025-10-26 20:13:03','2025-10-26 20:13:03',NULL),(13,0,'companies',NULL,'companies',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(14,0,'jobs',NULL,'jobs',0,'2025-10-26 20:13:04','2025-10-26 20:13:04',NULL),(15,0,'resume',NULL,'resume',0,'2025-10-26 20:13:05','2025-10-26 20:13:05',NULL),(16,0,'avatars',NULL,'avatars',0,'2025-10-26 20:13:05','2025-10-26 20:13:05',NULL),(17,0,'covers',NULL,'covers',0,'2025-10-26 20:13:05','2025-10-26 20:13:05',NULL),(18,0,'teams',NULL,'teams',0,'2025-10-26 20:13:26','2025-10-26 20:13:26',NULL),(19,0,'testimonials',NULL,'testimonials',0,'2025-10-26 20:13:26','2025-10-26 20:13:26',NULL);
/*!40000 ALTER TABLE `media_folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media_settings`
--

DROP TABLE IF EXISTS `media_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `media_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media_settings`
--

LOCK TABLES `media_settings` WRITE;
/*!40000 ALTER TABLE `media_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `media_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_locations`
--

DROP TABLE IF EXISTS `menu_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint unsigned NOT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_locations_menu_id_created_at_index` (`menu_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_locations`
--

LOCK TABLES `menu_locations` WRITE;
/*!40000 ALTER TABLE `menu_locations` DISABLE KEYS */;
INSERT INTO `menu_locations` VALUES (1,1,'main-menu','2025-10-26 20:13:26','2025-10-26 20:13:26');
/*!40000 ALTER TABLE `menu_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_nodes`
--

DROP TABLE IF EXISTS `menu_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_nodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned NOT NULL DEFAULT '0',
  `reference_id` bigint unsigned DEFAULT NULL,
  `reference_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_font` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` tinyint unsigned NOT NULL DEFAULT '0',
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `css_class` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '_self',
  `has_child` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_nodes_menu_id_index` (`menu_id`),
  KEY `menu_nodes_parent_id_index` (`parent_id`),
  KEY `reference_id` (`reference_id`),
  KEY `reference_type` (`reference_type`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_nodes`
--

LOCK TABLES `menu_nodes` WRITE;
/*!40000 ALTER TABLE `menu_nodes` DISABLE KEYS */;
INSERT INTO `menu_nodes` VALUES (1,1,0,NULL,NULL,'/',NULL,0,'Home',NULL,'_self',1,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(2,1,1,1,'Botble\\Page\\Models\\Page','/homepage-1','fi fi-rr-home',1,'Home 1',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(3,1,1,2,'Botble\\Page\\Models\\Page','/homepage-2','fi fi-rr-home',2,'Home 2',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(4,1,1,3,'Botble\\Page\\Models\\Page','/homepage-3','fi fi-rr-home',3,'Home 3',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(5,1,1,4,'Botble\\Page\\Models\\Page','/homepage-4','fi fi-rr-home',4,'Home 4',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(6,1,1,5,'Botble\\Page\\Models\\Page','/homepage-5','fi fi-rr-home',5,'Home 5',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(7,1,1,6,'Botble\\Page\\Models\\Page','/homepage-6','fi fi-rr-home',6,'Home 6',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(8,1,0,8,'Botble\\Page\\Models\\Page','/companies',NULL,0,'Find a Job',NULL,'_self',1,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(9,1,8,NULL,NULL,'/jobs?layout=grid','fi fi-rr-briefcase',0,'Jobs Grid',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(10,1,8,NULL,NULL,'/jobs','fi fi-rr-briefcase',0,'Jobs List',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(11,1,8,NULL,NULL,'','fi fi-rr-briefcase',0,'Job Details',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(12,1,8,NULL,NULL,'','fi fi-rr-briefcase',0,'Job External',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(13,1,8,NULL,NULL,'','fi fi-rr-briefcase',0,'Job Hide Company',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(14,1,0,8,'Botble\\Page\\Models\\Page','/companies',NULL,0,'Companies',NULL,'_self',1,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(15,1,14,8,'Botble\\Page\\Models\\Page','/companies','fi fi-rr-briefcase',0,'Companies',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(16,1,14,NULL,NULL,'','fi fi-rr-info',0,'Company Details',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(17,1,0,9,'Botble\\Page\\Models\\Page','/candidates',NULL,0,'Candidates',NULL,'_self',1,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(18,1,17,9,'Botble\\Page\\Models\\Page','/candidates','fi fi-rr-user',0,'Candidates Grid',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(19,1,17,NULL,NULL,'','fi fi-rr-info',0,'Candidate Details',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(20,1,0,NULL,NULL,'#',NULL,0,'Pages',NULL,'_self',1,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(21,1,20,10,'Botble\\Page\\Models\\Page','/about-us','fi fi-rr-star',0,'About Us',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(22,1,20,11,'Botble\\Page\\Models\\Page','/pricing-plan','fi fi-rr-database',0,'Pricing Plan',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(23,1,20,10,'Botble\\Page\\Models\\Page','/about-us','fi fi-rr-paper-plane',0,'Contact Us',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(24,1,20,NULL,NULL,'/register','fi fi-rr-user-add',0,'Register',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(25,1,20,NULL,NULL,'/login','fi fi-rr-fingerprint',0,'Sign in',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(26,1,20,NULL,NULL,'/password/request','fi fi-rr-settings',0,'Reset Password',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(27,1,0,13,'Botble\\Page\\Models\\Page','/blog',NULL,0,'Blog',NULL,'_self',1,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(28,1,27,13,'Botble\\Page\\Models\\Page','/blog','fi fi-rr-edit',0,'Blog Grid',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(29,1,27,NULL,NULL,'','fi fi-rr-document-signed',0,'Blog Single',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(30,2,0,10,'Botble\\Page\\Models\\Page','/about-us',NULL,0,'About Us',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(31,2,0,NULL,NULL,'#',NULL,0,'Our Team',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(32,2,0,NULL,NULL,'#',NULL,0,'Products',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(33,2,0,12,'Botble\\Page\\Models\\Page','/contact',NULL,0,'Contact',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(34,3,0,10,'Botble\\Page\\Models\\Page','/about-us',NULL,0,'Feature',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(35,3,0,11,'Botble\\Page\\Models\\Page','/pricing-plan',NULL,0,'Pricing',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(36,3,0,NULL,NULL,'#',NULL,0,'Credit',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(37,3,0,15,'Botble\\Page\\Models\\Page','/faqs',NULL,0,'FAQ',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(38,4,0,NULL,NULL,'#',NULL,0,'iOS',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(39,4,0,NULL,NULL,'#',NULL,0,'Android',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(40,4,0,NULL,NULL,'#',NULL,0,'Microsoft',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(41,4,0,NULL,NULL,'#',NULL,0,'Desktop',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(42,5,0,14,'Botble\\Page\\Models\\Page','/cookie-policy',NULL,0,'Cookie Policy',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(43,5,0,17,'Botble\\Page\\Models\\Page','/terms',NULL,0,'Terms',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26'),(44,5,0,15,'Botble\\Page\\Models\\Page','/faqs',NULL,0,'FAQ',NULL,'_self',0,'2025-10-26 20:13:26','2025-10-26 20:13:26');
/*!40000 ALTER TABLE `menu_nodes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menus_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menus`
--

LOCK TABLES `menus` WRITE;
/*!40000 ALTER TABLE `menus` DISABLE KEYS */;
INSERT INTO `menus` VALUES (1,'Main menu','main-menu','published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(2,'Resources','resources','published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(3,'Community','community','published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(4,'Quick links','quick-links','published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(5,'More','more','published','2025-10-26 20:13:26','2025-10-26 20:13:26');
/*!40000 ALTER TABLE `menus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta_boxes`
--

DROP TABLE IF EXISTS `meta_boxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta_boxes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `meta_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_value` text COLLATE utf8mb4_unicode_ci,
  `reference_id` bigint unsigned NOT NULL,
  `reference_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meta_boxes_reference_id_index` (`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta_boxes`
--

LOCK TABLES `meta_boxes` WRITE;
/*!40000 ALTER TABLE `meta_boxes` DISABLE KEYS */;
INSERT INTO `meta_boxes` VALUES (1,'background_breadcrumb','[\"pages\\/background-breadcrumb.png\"]',10,'Botble\\Page\\Models\\Page','2025-10-26 20:12:59','2025-10-26 20:12:59'),(2,'background_breadcrumb','[\"pages\\/background-breadcrumb.png\"]',12,'Botble\\Page\\Models\\Page','2025-10-26 20:12:59','2025-10-26 20:12:59'),(3,'cover_image','[\"news\\/cover-image1.png\"]',1,'Botble\\Blog\\Models\\Post','2025-10-26 20:13:00','2025-10-26 20:13:00'),(4,'cover_image','[\"news\\/cover-image2.png\"]',2,'Botble\\Blog\\Models\\Post','2025-10-26 20:13:00','2025-10-26 20:13:00'),(5,'cover_image','[\"news\\/cover-image3.png\"]',3,'Botble\\Blog\\Models\\Post','2025-10-26 20:13:00','2025-10-26 20:13:00'),(6,'icon_image','[\"general\\/content.png\"]',1,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(7,'job_category_image','[\"job-categories\\/img-cover-3.png\"]',1,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(8,'icon_image','[\"general\\/research.png\"]',2,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(9,'job_category_image','[\"job-categories\\/img-cover-2.png\"]',2,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(10,'icon_image','[\"general\\/marketing.png\"]',3,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(11,'job_category_image','[\"job-categories\\/img-cover-1.png\"]',3,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(12,'icon_image','[\"general\\/customer.png\"]',4,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(13,'job_category_image','[\"job-categories\\/img-cover-1.png\"]',4,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(14,'icon_image','[\"general\\/finance.png\"]',5,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(15,'job_category_image','[\"job-categories\\/img-cover-1.png\"]',5,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(16,'icon_image','[\"general\\/lightning.png\"]',6,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(17,'job_category_image','[\"job-categories\\/img-cover-1.png\"]',6,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(18,'icon_image','[\"general\\/human.png\"]',7,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(19,'job_category_image','[\"job-categories\\/img-cover-1.png\"]',7,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(20,'icon_image','[\"general\\/management.png\"]',8,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(21,'job_category_image','[\"job-categories\\/img-cover-2.png\"]',8,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(22,'icon_image','[\"general\\/retail.png\"]',9,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(23,'job_category_image','[\"job-categories\\/img-cover-2.png\"]',9,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(24,'icon_image','[\"general\\/security.png\"]',10,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(25,'job_category_image','[\"job-categories\\/img-cover-3.png\"]',10,'Botble\\JobBoard\\Models\\Category','2025-10-26 20:13:03','2025-10-26 20:13:03'),(26,'cover_image','[\"companies\\/company-cover-image.png\"]',1,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(27,'cover_image','[\"companies\\/company-cover-image.png\"]',2,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(28,'cover_image','[\"companies\\/company-cover-image.png\"]',3,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(29,'cover_image','[\"companies\\/company-cover-image.png\"]',4,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(30,'cover_image','[\"companies\\/company-cover-image.png\"]',5,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(31,'cover_image','[\"companies\\/company-cover-image.png\"]',6,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(32,'cover_image','[\"companies\\/company-cover-image.png\"]',7,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(33,'cover_image','[\"companies\\/company-cover-image.png\"]',8,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(34,'cover_image','[\"companies\\/company-cover-image.png\"]',9,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(35,'cover_image','[\"companies\\/company-cover-image.png\"]',10,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(36,'cover_image','[\"companies\\/company-cover-image.png\"]',11,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(37,'cover_image','[\"companies\\/company-cover-image.png\"]',12,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(38,'cover_image','[\"companies\\/company-cover-image.png\"]',13,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(39,'cover_image','[\"companies\\/company-cover-image.png\"]',14,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(40,'cover_image','[\"companies\\/company-cover-image.png\"]',15,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(41,'cover_image','[\"companies\\/company-cover-image.png\"]',16,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(42,'cover_image','[\"companies\\/company-cover-image.png\"]',17,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(43,'cover_image','[\"companies\\/company-cover-image.png\"]',18,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(44,'cover_image','[\"companies\\/company-cover-image.png\"]',19,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(45,'cover_image','[\"companies\\/company-cover-image.png\"]',20,'Botble\\JobBoard\\Models\\Company','2025-10-26 20:13:04','2025-10-26 20:13:04'),(46,'featured_image','[\"jobs\\/img1.png\"]',1,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(47,'featured_image','[\"jobs\\/img2.png\"]',2,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(48,'featured_image','[\"jobs\\/img3.png\"]',3,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(49,'featured_image','[\"jobs\\/img4.png\"]',4,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(50,'featured_image','[\"jobs\\/img5.png\"]',5,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(51,'featured_image','[\"jobs\\/img6.png\"]',6,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(52,'featured_image','[\"jobs\\/img7.png\"]',7,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(53,'featured_image','[\"jobs\\/img8.png\"]',8,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(54,'featured_image','[\"jobs\\/img9.png\"]',9,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(55,'featured_image','[\"jobs\\/img3.png\"]',10,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(56,'featured_image','[\"jobs\\/img6.png\"]',11,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(57,'featured_image','[\"jobs\\/img7.png\"]',12,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(58,'featured_image','[\"jobs\\/img1.png\"]',13,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(59,'featured_image','[\"jobs\\/img5.png\"]',14,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(60,'featured_image','[\"jobs\\/img5.png\"]',15,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(61,'featured_image','[\"jobs\\/img9.png\"]',16,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(62,'featured_image','[\"jobs\\/img6.png\"]',17,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(63,'featured_image','[\"jobs\\/img4.png\"]',18,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(64,'featured_image','[\"jobs\\/img7.png\"]',19,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(65,'featured_image','[\"jobs\\/img1.png\"]',20,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(66,'featured_image','[\"jobs\\/img3.png\"]',21,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(67,'featured_image','[\"jobs\\/img2.png\"]',22,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(68,'featured_image','[\"jobs\\/img3.png\"]',23,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(69,'featured_image','[\"jobs\\/img5.png\"]',24,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(70,'featured_image','[\"jobs\\/img8.png\"]',25,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(71,'featured_image','[\"jobs\\/img6.png\"]',26,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(72,'featured_image','[\"jobs\\/img1.png\"]',27,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(73,'featured_image','[\"jobs\\/img7.png\"]',28,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(74,'featured_image','[\"jobs\\/img8.png\"]',29,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(75,'featured_image','[\"jobs\\/img2.png\"]',30,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(76,'featured_image','[\"jobs\\/img2.png\"]',31,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(77,'featured_image','[\"jobs\\/img9.png\"]',32,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(78,'featured_image','[\"jobs\\/img3.png\"]',33,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(79,'featured_image','[\"jobs\\/img9.png\"]',34,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(80,'featured_image','[\"jobs\\/img2.png\"]',35,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(81,'featured_image','[\"jobs\\/img4.png\"]',36,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(82,'featured_image','[\"jobs\\/img6.png\"]',37,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(83,'featured_image','[\"jobs\\/img5.png\"]',38,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(84,'featured_image','[\"jobs\\/img8.png\"]',39,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(85,'featured_image','[\"jobs\\/img3.png\"]',40,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(86,'featured_image','[\"jobs\\/img2.png\"]',41,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(87,'featured_image','[\"jobs\\/img8.png\"]',42,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(88,'featured_image','[\"jobs\\/img5.png\"]',43,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(89,'featured_image','[\"jobs\\/img2.png\"]',44,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(90,'featured_image','[\"jobs\\/img3.png\"]',45,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(91,'featured_image','[\"jobs\\/img6.png\"]',46,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(92,'featured_image','[\"jobs\\/img3.png\"]',47,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(93,'featured_image','[\"jobs\\/img7.png\"]',48,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(94,'featured_image','[\"jobs\\/img6.png\"]',49,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(95,'featured_image','[\"jobs\\/img5.png\"]',50,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(96,'featured_image','[\"jobs\\/img1.png\"]',51,'Botble\\JobBoard\\Models\\Job','2025-10-26 20:13:04','2025-10-26 20:13:04'),(97,'cover_image','[\"covers\\/1.png\"]',1,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:05','2025-10-26 20:13:05'),(98,'cover_image','[\"covers\\/2.png\"]',2,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:05','2025-10-26 20:13:05'),(99,'cover_image','[\"covers\\/3.png\"]',3,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:06','2025-10-26 20:13:06'),(100,'cover_image','[\"covers\\/3.png\"]',4,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:06','2025-10-26 20:13:06'),(101,'cover_image','[\"covers\\/3.png\"]',5,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:06','2025-10-26 20:13:06'),(102,'cover_image','[\"covers\\/3.png\"]',6,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:06','2025-10-26 20:13:06'),(103,'cover_image','[\"covers\\/3.png\"]',7,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:06','2025-10-26 20:13:06'),(104,'cover_image','[\"covers\\/1.png\"]',8,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:07','2025-10-26 20:13:07'),(105,'cover_image','[\"covers\\/2.png\"]',9,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:07','2025-10-26 20:13:07'),(106,'cover_image','[\"covers\\/1.png\"]',10,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:07','2025-10-26 20:13:07'),(107,'cover_image','[\"covers\\/3.png\"]',11,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:07','2025-10-26 20:13:07'),(108,'cover_image','[\"covers\\/1.png\"]',12,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:07','2025-10-26 20:13:07'),(109,'cover_image','[\"covers\\/3.png\"]',13,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:08','2025-10-26 20:13:08'),(110,'cover_image','[\"covers\\/3.png\"]',14,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:08','2025-10-26 20:13:08'),(111,'cover_image','[\"covers\\/2.png\"]',15,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:08','2025-10-26 20:13:08'),(112,'cover_image','[\"covers\\/2.png\"]',16,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:08','2025-10-26 20:13:08'),(113,'cover_image','[\"covers\\/3.png\"]',17,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:08','2025-10-26 20:13:08'),(114,'cover_image','[\"covers\\/2.png\"]',18,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:09','2025-10-26 20:13:09'),(115,'cover_image','[\"covers\\/1.png\"]',19,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:09','2025-10-26 20:13:09'),(116,'cover_image','[\"covers\\/1.png\"]',20,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:09','2025-10-26 20:13:09'),(117,'cover_image','[\"covers\\/2.png\"]',21,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:09','2025-10-26 20:13:09'),(118,'cover_image','[\"covers\\/3.png\"]',22,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:10','2025-10-26 20:13:10'),(119,'cover_image','[\"covers\\/1.png\"]',23,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:10','2025-10-26 20:13:10'),(120,'cover_image','[\"covers\\/1.png\"]',24,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:10','2025-10-26 20:13:10'),(121,'cover_image','[\"covers\\/3.png\"]',25,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:10','2025-10-26 20:13:10'),(122,'cover_image','[\"covers\\/2.png\"]',26,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:10','2025-10-26 20:13:10'),(123,'cover_image','[\"covers\\/3.png\"]',27,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:11','2025-10-26 20:13:11'),(124,'cover_image','[\"covers\\/1.png\"]',28,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:11','2025-10-26 20:13:11'),(125,'cover_image','[\"covers\\/1.png\"]',29,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:11','2025-10-26 20:13:11'),(126,'cover_image','[\"covers\\/2.png\"]',30,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:11','2025-10-26 20:13:11'),(127,'cover_image','[\"covers\\/2.png\"]',31,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:11','2025-10-26 20:13:11'),(128,'cover_image','[\"covers\\/1.png\"]',32,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:12','2025-10-26 20:13:12'),(129,'cover_image','[\"covers\\/2.png\"]',33,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:12','2025-10-26 20:13:12'),(130,'cover_image','[\"covers\\/2.png\"]',34,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:12','2025-10-26 20:13:12'),(131,'cover_image','[\"covers\\/2.png\"]',35,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:12','2025-10-26 20:13:12'),(132,'cover_image','[\"covers\\/1.png\"]',36,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:12','2025-10-26 20:13:12'),(133,'cover_image','[\"covers\\/1.png\"]',37,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:13','2025-10-26 20:13:13'),(134,'cover_image','[\"covers\\/2.png\"]',38,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:13','2025-10-26 20:13:13'),(135,'cover_image','[\"covers\\/1.png\"]',39,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:13','2025-10-26 20:13:13'),(136,'cover_image','[\"covers\\/1.png\"]',40,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:13','2025-10-26 20:13:13'),(137,'cover_image','[\"covers\\/1.png\"]',41,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:13','2025-10-26 20:13:13'),(138,'cover_image','[\"covers\\/2.png\"]',42,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:14','2025-10-26 20:13:14'),(139,'cover_image','[\"covers\\/3.png\"]',43,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:14','2025-10-26 20:13:14'),(140,'cover_image','[\"covers\\/3.png\"]',44,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:14','2025-10-26 20:13:14'),(141,'cover_image','[\"covers\\/1.png\"]',45,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:14','2025-10-26 20:13:14'),(142,'cover_image','[\"covers\\/2.png\"]',46,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:14','2025-10-26 20:13:14'),(143,'cover_image','[\"covers\\/1.png\"]',47,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:15','2025-10-26 20:13:15'),(144,'cover_image','[\"covers\\/3.png\"]',48,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:15','2025-10-26 20:13:15'),(145,'cover_image','[\"covers\\/3.png\"]',49,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:15','2025-10-26 20:13:15'),(146,'cover_image','[\"covers\\/3.png\"]',50,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:15','2025-10-26 20:13:15'),(147,'cover_image','[\"covers\\/3.png\"]',51,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:15','2025-10-26 20:13:15'),(148,'cover_image','[\"covers\\/1.png\"]',52,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:16','2025-10-26 20:13:16'),(149,'cover_image','[\"covers\\/1.png\"]',53,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:16','2025-10-26 20:13:16'),(150,'cover_image','[\"covers\\/1.png\"]',54,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:16','2025-10-26 20:13:16'),(151,'cover_image','[\"covers\\/1.png\"]',55,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:16','2025-10-26 20:13:16'),(152,'cover_image','[\"covers\\/2.png\"]',56,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:16','2025-10-26 20:13:16'),(153,'cover_image','[\"covers\\/2.png\"]',57,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:17','2025-10-26 20:13:17'),(154,'cover_image','[\"covers\\/2.png\"]',58,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:17','2025-10-26 20:13:17'),(155,'cover_image','[\"covers\\/2.png\"]',59,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:17','2025-10-26 20:13:17'),(156,'cover_image','[\"covers\\/1.png\"]',60,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:17','2025-10-26 20:13:17'),(157,'cover_image','[\"covers\\/2.png\"]',61,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:18','2025-10-26 20:13:18'),(158,'cover_image','[\"covers\\/2.png\"]',62,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:18','2025-10-26 20:13:18'),(159,'cover_image','[\"covers\\/1.png\"]',63,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:18','2025-10-26 20:13:18'),(160,'cover_image','[\"covers\\/1.png\"]',64,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:18','2025-10-26 20:13:18'),(161,'cover_image','[\"covers\\/2.png\"]',65,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:18','2025-10-26 20:13:18'),(162,'cover_image','[\"covers\\/1.png\"]',66,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:19','2025-10-26 20:13:19'),(163,'cover_image','[\"covers\\/2.png\"]',67,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:19','2025-10-26 20:13:19'),(164,'cover_image','[\"covers\\/3.png\"]',68,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:19','2025-10-26 20:13:19'),(165,'cover_image','[\"covers\\/3.png\"]',69,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:19','2025-10-26 20:13:19'),(166,'cover_image','[\"covers\\/2.png\"]',70,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:20','2025-10-26 20:13:20'),(167,'cover_image','[\"covers\\/1.png\"]',71,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:20','2025-10-26 20:13:20'),(168,'cover_image','[\"covers\\/2.png\"]',72,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:20','2025-10-26 20:13:20'),(169,'cover_image','[\"covers\\/3.png\"]',73,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:20','2025-10-26 20:13:20'),(170,'cover_image','[\"covers\\/2.png\"]',74,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:20','2025-10-26 20:13:20'),(171,'cover_image','[\"covers\\/1.png\"]',75,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:21','2025-10-26 20:13:21'),(172,'cover_image','[\"covers\\/2.png\"]',76,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:21','2025-10-26 20:13:21'),(173,'cover_image','[\"covers\\/2.png\"]',77,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:21','2025-10-26 20:13:21'),(174,'cover_image','[\"covers\\/3.png\"]',78,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:21','2025-10-26 20:13:21'),(175,'cover_image','[\"covers\\/2.png\"]',79,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:21','2025-10-26 20:13:21'),(176,'cover_image','[\"covers\\/1.png\"]',80,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:22','2025-10-26 20:13:22'),(177,'cover_image','[\"covers\\/3.png\"]',81,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:22','2025-10-26 20:13:22'),(178,'cover_image','[\"covers\\/3.png\"]',82,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:22','2025-10-26 20:13:22'),(179,'cover_image','[\"covers\\/3.png\"]',83,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:22','2025-10-26 20:13:22'),(180,'cover_image','[\"covers\\/3.png\"]',84,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:22','2025-10-26 20:13:22'),(181,'cover_image','[\"covers\\/2.png\"]',85,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:23','2025-10-26 20:13:23'),(182,'cover_image','[\"covers\\/3.png\"]',86,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:23','2025-10-26 20:13:23'),(183,'cover_image','[\"covers\\/2.png\"]',87,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:23','2025-10-26 20:13:23'),(184,'cover_image','[\"covers\\/2.png\"]',88,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:23','2025-10-26 20:13:23'),(185,'cover_image','[\"covers\\/3.png\"]',89,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:23','2025-10-26 20:13:23'),(186,'cover_image','[\"covers\\/3.png\"]',90,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:24','2025-10-26 20:13:24'),(187,'cover_image','[\"covers\\/1.png\"]',91,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:24','2025-10-26 20:13:24'),(188,'cover_image','[\"covers\\/1.png\"]',92,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:24','2025-10-26 20:13:24'),(189,'cover_image','[\"covers\\/3.png\"]',93,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:24','2025-10-26 20:13:24'),(190,'cover_image','[\"covers\\/1.png\"]',94,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:24','2025-10-26 20:13:24'),(191,'cover_image','[\"covers\\/3.png\"]',95,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:25','2025-10-26 20:13:25'),(192,'cover_image','[\"covers\\/1.png\"]',96,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:25','2025-10-26 20:13:25'),(193,'cover_image','[\"covers\\/1.png\"]',97,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:25','2025-10-26 20:13:25'),(194,'cover_image','[\"covers\\/3.png\"]',98,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:25','2025-10-26 20:13:25'),(195,'cover_image','[\"covers\\/3.png\"]',99,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:25','2025-10-26 20:13:25'),(196,'cover_image','[\"covers\\/2.png\"]',100,'Botble\\JobBoard\\Models\\Account','2025-10-26 20:13:26','2025-10-26 20:13:26');
/*!40000 ALTER TABLE `meta_boxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=166 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'2013_04_09_032329_create_base_tables',1),(3,'2013_04_09_062329_create_revisions_table',1),(4,'2014_10_12_000000_create_users_table',1),(5,'2014_10_12_100000_create_password_reset_tokens_table',1),(6,'2016_06_10_230148_create_acl_tables',1),(7,'2016_06_14_230857_create_menus_table',1),(8,'2016_06_28_221418_create_pages_table',1),(9,'2016_10_05_074239_create_setting_table',1),(10,'2016_11_28_032840_create_dashboard_widget_tables',1),(11,'2016_12_16_084601_create_widgets_table',1),(12,'2017_05_09_070343_create_media_tables',1),(13,'2017_11_03_070450_create_slug_table',1),(14,'2019_01_05_053554_create_jobs_table',1),(15,'2019_08_19_000000_create_failed_jobs_table',1),(16,'2019_12_14_000001_create_personal_access_tokens_table',1),(17,'2022_04_20_100851_add_index_to_media_table',1),(18,'2022_04_20_101046_add_index_to_menu_table',1),(19,'2022_07_10_034813_move_lang_folder_to_root',1),(20,'2022_08_04_051940_add_missing_column_expires_at',1),(21,'2022_09_01_000001_create_admin_notifications_tables',1),(22,'2022_10_14_024629_drop_column_is_featured',1),(23,'2022_11_18_063357_add_missing_timestamp_in_table_settings',1),(24,'2022_12_02_093615_update_slug_index_columns',1),(25,'2023_01_30_024431_add_alt_to_media_table',1),(26,'2023_02_16_042611_drop_table_password_resets',1),(27,'2023_04_10_103353_fix_social_links',1),(28,'2023_04_23_005903_add_column_permissions_to_admin_notifications',1),(29,'2023_05_10_075124_drop_column_id_in_role_users_table',1),(30,'2023_07_19_152743_migrate_old_city_state_image',1),(31,'2023_08_21_090810_make_page_content_nullable',1),(32,'2023_09_14_021936_update_index_for_slugs_table',1),(33,'2023_12_07_095130_add_color_column_to_media_folders_table',1),(34,'2023_12_17_162208_make_sure_column_color_in_media_folders_nullable',1),(35,'2023_12_20_034718_update_invoice_amount',1),(36,'2024_04_04_110758_update_value_column_in_user_meta_table',1),(37,'2024_05_12_091229_add_column_visibility_to_table_media_files',1),(38,'2024_07_07_091316_fix_column_url_in_menu_nodes_table',1),(39,'2024_07_12_100000_change_random_hash_for_media',1),(40,'2024_09_30_024515_create_sessions_table',1),(41,'2024_12_19_000001_create_device_tokens_table',1),(42,'2024_12_19_000002_create_push_notifications_table',1),(43,'2024_12_19_000003_create_push_notification_recipients_table',1),(44,'2024_12_30_000001_create_user_settings_table',1),(45,'2025_07_06_030754_add_phone_to_users_table',1),(46,'2025_07_31_add_performance_indexes_to_slugs_table',1),(47,'2020_11_18_150916_ads_create_ads_table',2),(48,'2021_12_02_035301_add_ads_translations_table',2),(49,'2023_04_17_062645_add_open_in_new_tab',2),(50,'2023_11_07_023805_add_tablet_mobile_image',2),(51,'2024_04_01_043317_add_google_adsense_slot_id_to_ads_table',2),(52,'2024_04_27_100730_improve_analytics_setting',3),(53,'2015_06_29_025744_create_audit_history',4),(54,'2023_11_14_033417_change_request_column_in_table_audit_histories',4),(55,'2025_05_05_000001_add_user_type_to_audit_histories_table',4),(56,'2015_06_18_033822_create_blog_table',5),(57,'2021_02_16_092633_remove_default_value_for_author_type',5),(58,'2021_12_03_030600_create_blog_translations',5),(59,'2022_04_19_113923_add_index_to_table_posts',5),(60,'2023_08_29_074620_make_column_author_id_nullable',5),(61,'2024_07_30_091615_fix_order_column_in_categories_table',5),(62,'2025_01_06_033807_add_default_value_for_categories_author_type',5),(63,'2016_06_17_091537_create_contacts_table',6),(64,'2023_11_10_080225_migrate_contact_blacklist_email_domains_to_core',6),(65,'2024_03_20_080001_migrate_change_attribute_email_to_nullable_form_contacts_table',6),(66,'2024_03_25_000001_update_captcha_settings_for_contact',6),(67,'2024_04_19_063914_create_custom_fields_table',6),(68,'2018_07_09_221238_create_faq_table',7),(69,'2021_12_03_082134_create_faq_translations',7),(70,'2023_11_17_063408_add_description_column_to_faq_categories_table',7),(71,'2016_10_13_150201_create_galleries_table',8),(72,'2021_12_03_082953_create_gallery_translations',8),(73,'2022_04_30_034048_create_gallery_meta_translations_table',8),(74,'2023_08_29_075308_make_column_user_id_nullable',8),(75,'2022_06_20_093259_create_job_board_tables',9),(76,'2022_09_12_061845_update_table_activity_logs',9),(77,'2022_09_13_042407_create_table_jb_jobs_types',9),(78,'2022_09_15_030017_update_jb_jobs_table',9),(79,'2022_09_15_094840_add_job_employer_colleagues',9),(80,'2022_09_27_000001_create_jb_invoices_tables',9),(81,'2022_09_30_144924_update_jobs_table',9),(82,'2022_10_04_085631_add_company_logo_to_jb_invoices',9),(83,'2022_10_10_030606_create_reviews_table',9),(84,'2022_11_09_065056_add_missing_jobs_page',9),(85,'2022_11_10_065056_add_columns_to_accounts',9),(86,'2022_11_16_034756_add_column_cover_letter_to_accounts',9),(87,'2022_11_29_304756_create_jb_account_favorite_skills_table',9),(88,'2022_11_29_304757_create_jb_account_favorite_tags',9),(89,'2022_12_26_304758_create_table_jb_experiences',9),(90,'2022_12_26_304759_create_table_jb_education',9),(91,'2023_01_31_023233_create_jb_custom_fields_table',9),(92,'2023_02_06_024257_add_package_translations',9),(93,'2023_02_08_062457_add_custom_fields_translation_table',9),(94,'2023_04_03_126927_add_parent_id_to_jb_categories_table',9),(95,'2023_05_04_000001_add_hide_cv_to_jb_accounts_table',9),(96,'2023_05_09_062031_unique_reviews_table',9),(97,'2023_05_13_180010_make_jb_reviews_table_morphable',9),(98,'2023_05_16_113126_fix_account_confirmed_at',9),(99,'2023_07_03_135746_add_zip_code_to_jb_jobs_table',9),(100,'2023_07_06_022808_create_jb_coupons_table',9),(101,'2023_07_14_045213_add_coupon_code_column_to_jb_invoices_table',9),(102,'2024_01_31_022842_add_description_to_jb_packages_table',9),(103,'2024_02_01_080657_add_tax_id_column_to_jb_companies_table',9),(104,'2024_05_02_030658_add_field_unique_id_to_jb_accounts_and_jb_companies_table',9),(105,'2024_07_22_122219_create_jb_account_languages_table',9),(106,'2024_09_06_070120_update_jb_packages_table',9),(107,'2024_09_23_075542_add_accounts_translations',9),(108,'2024_10_28_062842_add_unique_field_to_jb_jobs_table',9),(109,'2025_01_07_020057_create_jb_companies_translations',9),(110,'2025_01_14_035040_add_features_to_packages_translations',9),(111,'2025_01_25_081129_add_address_to_jobs_translations',9),(112,'2025_02_03_035948_update_field_apply_url_of_jb_jobs_table',9),(113,'2025_06_07_000000_add_salary_type_to_jb_jobs_table',9),(114,'2025_06_08_000000_add_external_apply_behavior_to_jb_jobs_table',9),(115,'2025_08_12_075650_add_verification_fields_to_jb_companies_table',9),(116,'2025_10_06_100000_add_indexes_to_jb_jobs_table',9),(117,'2025_10_06_100001_add_indexes_to_jb_jobs_categories_table',9),(118,'2025_10_06_100002_add_indexes_to_jb_categories_table',9),(119,'2025_10_06_100003_add_indexes_to_jb_companies_table',9),(120,'2025_10_06_100004_add_indexes_to_other_job_board_tables',9),(121,'2025_10_06_100005_add_indexes_to_jb_jobs_types_table',9),(122,'2025_10_06_100006_add_application_closing_date_index',9),(123,'2025_10_06_100007_add_views_index_to_jb_jobs_table',9),(124,'2025_10_06_125234_add_indexes_to_job_board_tables_for_performance',9),(125,'2025_10_10_100000_add_advanced_fields_to_jb_currencies_table',9),(126,'2025_10_10_123745_add_number_format_style_and_space_to_jb_currencies_table',9),(127,'2016_10_03_032336_create_languages_table',10),(128,'2023_09_14_022423_add_index_for_language_table',10),(129,'2021_10_25_021023_fix-priority-load-for-language-advanced',11),(130,'2021_12_03_075608_create_page_translations',11),(131,'2023_07_06_011444_create_slug_translations_table',11),(132,'2019_11_18_061011_create_country_table',12),(133,'2021_12_03_084118_create_location_translations',12),(134,'2021_12_03_094518_migrate_old_location_data',12),(135,'2021_12_10_034440_switch_plugin_location_to_use_language_advanced',12),(136,'2022_01_16_085908_improve_plugin_location',12),(137,'2022_08_04_052122_delete_location_backup_tables',12),(138,'2023_04_23_061847_increase_state_translations_abbreviation_column',12),(139,'2023_07_26_041451_add_more_columns_to_location_table',12),(140,'2023_07_27_041451_add_more_columns_to_location_translation_table',12),(141,'2023_08_15_073307_drop_unique_in_states_cities_translations',12),(142,'2023_10_21_065016_make_state_id_in_table_cities_nullable',12),(143,'2024_08_17_094600_add_image_into_countries',12),(144,'2025_01_08_093652_add_zip_code_to_cities',12),(145,'2025_07_31_083459_add_indexes_for_location_search_performance',12),(146,'2017_10_24_154832_create_newsletter_table',13),(147,'2024_03_25_000001_update_captcha_settings_for_newsletter',13),(148,'2017_05_18_080441_create_payment_tables',14),(149,'2021_03_27_144913_add_customer_type_into_table_payments',14),(150,'2021_05_24_034720_make_column_currency_nullable',14),(151,'2021_08_09_161302_add_metadata_column_to_payments_table',14),(152,'2021_10_19_020859_update_metadata_field',14),(153,'2022_06_28_151901_activate_paypal_stripe_plugin',14),(154,'2022_07_07_153354_update_charge_id_in_table_payments',14),(155,'2024_07_04_083133_create_payment_logs_table',14),(156,'2025_04_12_000003_add_payment_fee_to_payments_table',14),(157,'2025_05_22_000001_add_payment_fee_type_to_settings_table',14),(158,'2025_04_08_040931_create_social_logins_table',15),(159,'2022_11_02_092723_team_create_team_table',16),(160,'2023_08_11_094574_update_team_table',16),(161,'2023_11_30_085354_add_missing_description_to_team',16),(162,'2018_07_09_214610_create_testimonial_table',17),(163,'2021_12_03_083642_create_testimonials_translations',17),(164,'2016_10_07_193005_create_translations_table',18),(165,'2023_12_12_105220_drop_translations_table',18);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletters`
--

DROP TABLE IF EXISTS `newsletters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'subscribed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletters`
--

LOCK TABLES `newsletters` WRITE;
/*!40000 ALTER TABLE `newsletters` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned DEFAULT NULL,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pages_user_id_index` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'Homepage 1','<div>[search-box title=\"The Easiest Way to Get Your New Job\" highlight_text=\"Easiest Way\" description=\"Each month, more than 3 million job seekers turn to website in their search for work, making over 140,000 applications every single day\" banner_image_1=\"pages/banner1.png\" icon_top_banner=\"pages/icon-top-banner.png\" banner_image_2=\"pages/banner2.png\" icon_bottom_banner=\"pages/icon-bottom-banner.png\" style=\"style-1\" trending_keywords=\"Design,Development,Manager,Senior\"][/search-box]</div><div>[featured-job-categories title=\"Browse by category\" subtitle=\"Find the job that’s perfect for you. about 800+ new jobs everyday\"][/featured-job-categories]</div><div>[apply-banner subtitle=\"Let’s Work Together &lt;br\\&gt;&amp; Explore Opportunities\" highlight_sub_title_text=\"Work, Explore\" title_1=\"We are\" title_2=\"HIRING\" button_apply_text=\"Apply\" button_apply_link=\"#\" apply_image_left=\"pages/bg-left-hiring.png\" apply_image_right=\"pages/bg-right-hiring.png\"][/apply-banner]</div><div>[job-of-the-day title=\"Jobs of the day\" subtitle=\"Search and connect with the right candidates faster.\" job_categories=\"4,9,1,3,5,7\" style=\"style-1\"][/job-of-the-day]</div><div>[job-grid title=\"Find The One That’s Right For You\" high_light_title_text=\"Right\" subtitle=\"Millions Of Jobs.\" description=\"Search all the open positions on the web. Get your own personalized salary estimate. Read reviews on over 600,000 companies worldwide. The right job is out there.\" image_job_1=\"pages/img-chart.png\" image_job_2=\"pages/controlcard.png\" image=\"pages/img1.png\" button_text=\"Search jobs\" button_url=\"#\" link_text=\"Learn more\" link_text_url=\"#\"][/job-grid]</div><div>[top-companies title=\"Top Recruiters\" description=\"Discover your next career move, freelance gig, or internship\"][/top-companies]</div><div>[job-by-location title=\"Jobs by Location\" description=\"Find your favourite jobs and get the benefits of yourself\" city=\"1,2,3,4,5,6\"][/job-by-location]</div><div>[news-and-blogs title=\"News and Blog\" subtitle=\"Get the latest news, updates and tips\"][/news-and-blogs]</div>',1,NULL,'homepage',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(2,'Homepage 2','<div>[search-box subtitle=\"We have 150,000+ live jobs\" title=\"The #1 Job Board for Hiring or Find your next job\" highlight_text=\"Job Board for\" description=\"Each month, more than 3 million job seekers turn to website in their search for work, making over 140,000 applications every single day\" counter_title_1=\"Daily Jobs Posted\" counter_number_1=\"265\" counter_title_2=\"Recruiters\" counter_number_2=\"17\" counter_title_3=\"Freelancers\" counter_number_3=\"15\" counter_title_4=\"Blog Tips\" counter_number_4=\"28\" background_image=\"pages/banner-section-search-box.png\" style=\"style-2\" trending_keywords=\"Design,Development,Manager,Senior\"][/search-box]</div><div>[job-of-the-day title=\"Jobs of the day\" subtitle=\"Search and connect with the right candidates faster.\" job_categories=\"1,2,5,4,7,8\" style=\"style-2\"][/job-of-the-day]</div><div>[popular-category title=\"Popular category\" subtitle=\"Search and connect with the right candidates faster.\"][/popular-category]</div><div>[job-by-location title=\"Jobs by Location\" description=\"Find your favourite jobs and get the benefits of yourself\" city=\"12,46,69,111,121,116,62\" style=\"style-2\"][/job-by-location]</div><div>[counter-section counter_title_1=\"Completed Cases\" counter_description_1=\"We always provide people a complete solution upon focused of any business\" counter_number_1=\"1000\" counter_title_2=\"Our Office\" counter_description_2=\"We always provide people a complete solution upon focused of any business\" counter_number_2=\"1\" counter_title_3=\"Skilled People\" counter_description_3=\"We always provide people a complete solution upon focused of any business\" counter_number_3=\"6\" counter_title_4=\"Happy Clients\" counter_description_4=\"We always provide people a complete solution upon focused of any business\" counter_number_4=\"2\"][/counter-section]</div><div>[top-companies title=\"Top Recruiters\" description=\"Discover your next career move, freelance gig, or internship\" style=\"style-2\"][/top-companies]</div><div>[advertisement-banner first_title=\"Job Tools Services\" first_description=\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam laoreet rutrum quam, id faucibus erat interdum a. Curabitur eget tortor a nulla interdum semper.\" load_more_first_content_text=\"Find Out More\" load_more_link_first_content=\"#\" image_of_first_content=\"pages/job-tools.png\" second_title=\"Planning a Job?\" second_description=\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam laoreet rutrum quam, id faucibus erat interdum a. Curabitur eget tortor a nulla interdum semper.\" load_more_second_content_text=\"Find Out More\" load_more_link_second_content=\"#\" image_of_second_content=\"pages/planning-job.png\"][/advertisement-banner]</div><div>[news-and-blogs title=\"News and Blog\" subtitle=\"Get the latest news, updates and tips\" button_text=\"Load More Posts\" button_link=\"#\" style=\"style-2\"][/news-and-blogs]</div>',1,NULL,'homepage',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(3,'Homepage 3','<div>[search-box title=\"The #1 Job Board for Hiring or Find your next job\" highlight_text=\"Job Board for\" description=\"Each month, more than 3 million job seekers turn to website in their search for work, making over 140,000 applications every single day\" style=\"style-3\" trending_keywords=\"Design,Development,Manager,Senior\"][/search-box]</div><div>[job-of-the-day title=\"Jobs of the day\" subtitle=\"Search and connect with the right candidates faster.\" job_categories=\"1,2,5,4,7,8\" style=\"style-3\"][/job-of-the-day]</div><div>[top-candidates title=\"Top Candidates\" description=\"Jobs is a curated job board of the best jobs for developers, designers and marketers in the tech industry.\" limit=\"8\" style=\"style-3\"][/top-candidates]</div><div>[top-companies title=\"Top Recruiters\" description=\"Discover your next career move, freelance gig, or internship\" style=\"style-3\"][/top-companies]</div><div>[apply-banner subtitle=\"Let’s Work Together &lt;br\\&gt;&amp; Explore Opportunities\" highlight_sub_title_text=\"Work, Explore\" title_1=\"We are\" title_2=\"HIRING\" button_apply_text=\"Apply\" button_apply_link=\"#\" apply_image_left=\"pages/bg-left-hiring.png\" apply_image_right=\"pages/bg-right-hiring.png\" style=\"style-3\"][/apply-banner]</div><div>[our-partners title=\"Trusted by\" name_1=\"Asus\" url_1=\"https://www.asus.com\" image_1=\"our-partners/asus.png\" name_2=\"Dell\" url_2=\"https://www.dell.com\" image_2=\"our-partners/dell.png\" name_3=\"Microsoft\" url_3=\"https://www.microsoft.com\" image_3=\"our-partners/microsoft.png\" name_4=\"Acer\" url_4=\"https://www.acer.com\" image_4=\"our-partners/acer.png\" name_5=\"Nokia\" url_5=\"https://www.nokia.com\" image_5=\"our-partners/nokia.png\"][/our-partners]</div><div>[news-and-blogs title=\"News and Blog\" subtitle=\"Get the latest news, updates and tips\" button_text=\"Load More Posts\" button_link=\"#\" style=\"style-3\"][/news-and-blogs]</div>',1,NULL,'homepage',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(4,'Homepage 4','<div>[search-box title=\"Get The Right Job You Deserve\" highlight_text=\"Right Job\" banner_image_1=\"pages/home-page-4-banner.png\" style=\"style-1\" trending_keywords=\"Designer, Web, IOS, Developer, PHP, Senior, Engineer\" background_color=\"#000\"][/search-box]</div><div>[job-of-the-day title=\"Latest Jobs Post\" subtitle=\"Explore the different types of available jobs to apply discover which is right for you.\" job_categories=\"1,2,3,4,5,6,8,7\" style=\"style-3\"][/job-of-the-day]</div><div>[featured-job-categories title=\"Browse by category\" subtitle=\"Find the job that’s perfect for you. about 800+ new jobs everyday\" limit_category=\"10\" background_image=\"pages/bg-cat.png\" style=\"style-2\"][/featured-job-categories]</div><div>[[testimonials title=\"See Some Words\" description=\"Thousands of employee get their ideal jobs and feed back to us!\" style=\"style-2\"][/testimonials]</div><div>[our-partners title=\"Trusted by\" name_1=\"Asus\" url_1=\"https://www.asus.com\" image_1=\"our-partners/asus.png\" name_2=\"Dell\" url_2=\"https://www.dell.com\" image_2=\"our-partners/dell.png\" name_3=\"Microsoft\" url_3=\"https://www.microsoft.com\" image_3=\"our-partners/microsoft.png\" name_4=\"Acer\" url_4=\"https://www.acer.com\" image_4=\"our-partners/acer.png\" name_5=\"Nokia\" url_5=\"https://www.nokia.com\" image_5=\"our-partners/nokia.png\"][/our-partners]</div><div>[popular-category title=\"Popular category\" subtitle=\"Search and connect with the right candidates faster.\"][/popular-category]</div><div>[job-by-location title=\"Jobs by Location\" description=\"Find your favourite jobs and get the benefits of yourself\" city=\"12,46,69,111,121,116,62\" style=\"style-2\"][/job-by-location]</div><div>[news-and-blogs title=\"News and Blog\" subtitle=\"Get the latest news, updates and tips\" button_text=\"Load More Posts\" button_link=\"#\"][/news-and-blogs]</div>',1,NULL,'homepage',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(5,'Homepage 5','<div>[search-box title=\"Find Jobs, &#x3C;br&#x3E; Hire Creatives\" description=\"Each month, more than 3 million job seekers turn to website in their search for work, making over 140,000 applications every single day\" banner_image_1=\"pages/banner1.png\" banner_image_2=\"pages/banner2.png\" banner_image_3=\"pages/banner3.png\" banner_image_4=\"pages/banner4.png\" banner_image_5=\"pages/banner5.png\" banner_image_6=\"pages/banner6.png\" style=\"style-5\"][/search-box]</div><div>[counter-section counter_title_1=\"Completed Cases\" counter_description_1=\"We always provide people a complete solution upon focused of any business\" counter_number_1=\"1000\" counter_title_2=\"Our Office\" counter_description_2=\"We always provide people a complete solution upon focused of any business\" counter_number_2=\"1\" counter_title_3=\"Skilled People\" counter_description_3=\"We always provide people a complete solution upon focused of any business\" counter_number_3=\"6\" counter_title_4=\"Happy Clients\" counter_description_4=\"We always provide people a complete solution upon focused of any business\" counter_number_4=\"2\"][/counter-section]</div><div>[popular-category title=\"Explore the Marketplace\" subtitle=\"Search and connect with the right candidates faster. Tell us what you’ve looking for and we’ll get to work for you.\" style=\"style-5\"][/popular-category]</div><div>[job-of-the-day title=\"Latest Jobs Post\" subtitle=\"Explore the different types of available jobs to apply &#x3C;br&#x3E; discover which is right for you.\" job_categories=\"1,2,5,4,7,8\" style=\"style-2\"][/job-of-the-day]</div><div>[job-grid style=\"style-2\" title=\"Create Your Personal Account Profile\" subtitle=\"Create Profile\" description=\"Work Profile is a personality assessment that measures an individual\'s work personality through their workplace traits, social and emotional traits; as well as the values and aspirations that drive them forward.\" image=\"pages/img-profile.png\" button_text=\"Create Profile\" button_url=\"/register\"][/job-grid]</div><div>[how-it-works title=\"How It Works\" description=\"Just via some simple steps, you will find your ideal candidates you’r looking for!\" step_label_1=\"Register an &#x3C;br&#x3E; account to start\" step_help_1=\"Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do\" step_label_2=\"Explore over &#x3C;br&#x3E; thousands of resumes\" step_help_2=\"Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do\" step_label_3=\"Find the most &#x3C;br&#x3E; suitable candidate\" step_help_3=\"Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do\" button_label=\"Get Started\" button_url=\"#\"][/how-it-works]</div><div>[top-candidates title=\"Top Candidates\" description=\"Jobs is a curated job board of the best jobs for developers, designers &#x3C;br&#x3E; and marketers in the tech industry.\" limit=\"8\" style=\"style-5\"][/top-candidates]</div><div>[news-and-blogs title=\"News and Blog\" subtitle=\"Get the latest news, updates and tips\" button_text=\"Load More Posts\" button_link=\"#\" style=\"style-2\"][/news-and-blogs]</div>',1,NULL,'homepage',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(6,'Homepage 6','<div>[search-box title=\"There Are 102,256 Postings Here For you!\" highlight_text=\"102,256\" description=\"Find Jobs, Employment & Career Opportunities\" style=\"style-4\" trending_keywords=\"Design,Development,Manager,Senior,,\" background_color=\"#000\"][/search-box]</div><div>[gallery image_1=\"galleries/1.jpg\" image_2=\"galleries/2.jpg\" image_3=\"galleries/3.jpg\" image_4=\"galleries/4.jpg\" image_5=\"galleries/5.jpg\"][/gallery]</div><div>[featured-job-categories title=\"Browse by category\" subtitle=\"Find the job that’s perfect for you. about 800+ new jobs everyday\"][/featured-job-categories]</div><div>[job-grid style=\"style-2\" title=\"Create Your Personal Account Profile\" subtitle=\"Create Profile\" description=\"Work Profile is a personality assessment that measures an individual\'s work personality through their workplace traits, social and emotional traits; as well as the values and aspirations that drive them forward.\" image=\"pages/img-profile.png\" button_text=\"Create Profile\" button_url=\"/register\"][/job-grid]</div><div>[job-of-the-day title=\"Latest Jobs Post\" subtitle=\"Explore the different types of available jobs to apply discover which is right for you.\" job_categories=\"1,2,3,4,5,6\" style=\"style-2\"][/job-of-the-day]</div><div>[job-search-banner title=\"Job search for people passionate about startup\" background_image=\"pages/img-job-search.png\" checkbox_title_1=\"Create an account\" checkbox_description_1=\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec nec justo a quam varius maximus. Maecenas sodales tortor quis tincidunt commodo.\" checkbox_title_2=\"Search for Jobs\" checkbox_description_2=\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec nec justo a quam varius maximus. Maecenas sodales tortor quis tincidunt commodo.\" checkbox_title_3=\"Save & Apply\" checkbox_description_3=\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec nec justo a quam varius maximus. Maecenas sodales tortor quis tincidunt commodo.\"][/job-search-banner]</div>',1,NULL,'homepage',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(7,'Jobs','<div>[search-box title=\"The official IT Jobs site\" highlight_text=\"IT Jobs\" description=\"“JobBox is our first stop whenever we\'re hiring a PHP role. We\'ve hired 10 PHP developers in the last few years, all thanks to JobBox.” — Andrew Hall, Elite JSC.\" banner_image_1=\"pages/left-job-head.png\" banner_image_2=\"pages/right-job-head.png\" style=\"style-3\" background_color=\"#000\"][/search-box]</div><div>[job-list max_salary_range=\"10000\"][/job-list]</div>',1,NULL,'default',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(8,'Companies','<div>[job-companies title=\"Browse Companies\" subtitle=\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Vero repellendus magni, atque delectus molestias quis?\"][/job-companies]</div>',1,NULL,'default',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(9,'Candidates','<div>[job-candidates title=\"Browse Candidates\" description=\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Vero repellendus magni, atque &#x3C;br&#x3E; delectus molestias quis?\" number_per_page=\"9\" style=\"grid\"][/job-candidates]</div><div>[news-and-blogs title=\"News and Blog\" subtitle=\"Get the latest news, updates and tips\" style=\"style-2\"][/news-and-blogs]</div>',1,NULL,'default',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(10,'About us','<div>[company-about title=\"About Our Company\" description=\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque ligula ante, dictum non aliquet eu, dapibus ac quam. Morbi vel ante viverra orci tincidunt tempor eu id ipsum. Sed consectetur, risus a blandit tempor, velit magna pellentesque risus, at congue tellus dui quis nisl.\" title_box=\"What we can do?\" image=\"general/img-about2.png\" description_box=\"Aenean sollicituin, lorem quis bibendum auctor nisi elit consequat ipsum sagittis sem nibh id elit. Duis sed odio sit amet nibh vulputate cursus a sit amet maurisorbi accumsan ipsum velit. Nam nec tellus a odio tincidunt auctora ornare odio. Aenean sollicituin, lorem quis bibendum auctor nisi elit consequat ipsum sagittis sem nibh id elit. Duis sed odio sit amet nibh vulputate cursus a sit amet maurisorbi accumsan ipsum velit. Nam nec tellus a odio tincidunt auctora ornare odio. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Duis non nisi purus. Integer sit nostra, per inceptos himenaeos. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Duis non nisi purus. Integer sit nostra, per inceptos himenaeos.\" url=\"/\" text_button_box=\"Read more\"][/company-about]</div><div>[team title=\"About Our Company\" sub_title=\"OUR COMPANY\" description=\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque ligula ante, dictum non aliquet eu, dapibus ac quam. Morbi vel ante viverra orci tincidunt tempor eu id ipsum. Sed consectetur, risus a blandit tempor, velit magna pellentesque risus, at congue tellus dui quis nisl.\" number_of_people=\"8\"][/team]</div><div>[news-and-blogs title=\"News and Blog\" subtitle=\"Get the latest news, updates and tips\" button_text=\"View More\" button_link=\"/blog\" style=\"style-2\"][/news-and-blogs]</div><div>[testimonials title=\"Our Happy Customer\" description=\"When it comes to choosing the right web hosting provider, we know how easy it is to get overwhelmed with the number.\"][/testimonials]</div>',1,NULL,'page-detail','Get the latest news, updates and tips','published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(11,'Pricing Plan','<div>[pricing-table title=\"Pricing Table\" subtitle=\"Choose The Best Plan That’s For You\" number_of_package=\"3\"][/pricing-table]</div><div>[faq title=\"Frequently Asked Questions\" subtitle=\"Aliquam a augue suscipit, luctus neque purus ipsum neque dolor primis a libero tempus, blandit and cursus varius and magnis sapien\" number_of_faq=\"4\"][/faq]</div><div>[testimonials title=\"Our Happy Customer\" subtitle=\"When it comes to choosing the right web hosting provider, we know how easy it is to get overwhelmed with the number.\"][/testimonials]</div>',1,NULL,'default',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(12,'Contact','<div>[company-information company_name=\"Jobbox Company\" logo_company=\"general/logo-company.png\" company_address=\"205 North Michigan Avenue, Suite 810 Chicago, 60601, US\" company_phone=\"0543213336\" company_email=\"contact@jobbox.com\" branch_company_name_0=\"London\" branch_company_address_0=\"2118 Thornridge Cir. Syracuse, Connecticut 35624\" branch_company_name_1=\"New York\" branch_company_address_1=\"4517 Washington Ave. Manchester, Kentucky 39495\" branch_company_name_2=\"Chicago\" branch_company_address_2=\"3891 Ranchview Dr. Richardson, California 62639\" branch_company_name_3=\"San Francisco\" branch_company_address_3=\"4140 Parker Rd. Allentown, New Mexico 31134\" branch_company_name_4=\"Sysney\" branch_company_address_4=\"3891 Ranchview Dr. Richardson, California 62639\" branch_company_name_5=\"Singapore\" branch_company_address_5=\"4140 Parker Rd. Allentown, New Mexico 31134\"][/company-information]</div><div>[contact-form title=\"Contact us\" subtitle=\"Get in touch\" description=\"The right move at the right time saves your investment. live the dream of expanding your business.\" image=\"image-contact.png\" show_newsletter=\"yes\"][/contact-form]</div><div>[team title=\"Meet Our Team\" subtitle=\"OUR COMPANY\" description=\"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque ligula ante, dictum non aliquet eu, dapibus ac quam. Morbi vel ante viverra orci tincidunt tempor eu id ipsum. Sed consectetur, risus a blandit tempor, velit magna pellentesque risus, at congue tellus dui quis nisl.\" number_of_people=\"8\"][/team]</div><div>[news-and-blogs title=\"News and Blog\" subtitle=\"Get the latest news, updates and tips\" button_text=\"View More\" button_link=\"/blog\" style=\"style-2\"][/news-and-blogs]</div><div>[testimonials title=\"Our Happy Customer\" subtitle=\"When it comes to choosing the right web hosting provider, we know how easy it is to get overwhelmed with the number.\"][/testimonials]</div>',1,NULL,'page-detail','Get the latest news, updates and tips','published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(13,'Blog','---',1,NULL,'page-detail','Get the latest news, updates and tips','published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(14,'Cookie Policy','<h3>EU Cookie Consent</h3><p>To use this website we are using Cookies and collecting some Data. To be compliant with the EU GDPR we give you to choose if you allow us to use certain Cookies and to collect some Data.</p><h4>Essential Data</h4><ul><li>The Essential Data is needed to run the Site you are visiting technically. You can not deactivate them.</li><li>Session Cookie: PHP uses a Cookie to identify user sessions. Without this Cookie the Website is not working.</li><li>XSRF-Token Cookie: Laravel automatically generates a CSRF \"token\" for each active user session managed by the application. This token is used to verify that the authenticated user is the one actually making the requests to the application.</li></ul>',1,NULL,'page-detail-boxed',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(15,'FAQs','<div>[faq title=\"Frequently Asked Questions\" number_of_faq=\"4\"][/faq]</div>',1,NULL,'page-detail',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(16,'Services','<p>Alice. The poor little thing howled so, that Alice said; \'there\'s a large mushroom growing near her, about the right way to fly up into a sort of life! I do wonder what they said. The executioner\'s argument was, that she ought to have no sort of idea that they could not tell whether they were gardeners, or soldiers, or courtiers, or three times over to the dance. Would not, could not remember ever having heard of uglifying!\' it exclaimed. \'You know what to do, and in his note-book, cackled out.</p><p>Alice, who was talking. Alice could not stand, and she soon found an opportunity of saying to her head, and she tried the effect of lying down on one of the hall: in fact she was quite pale (with passion, Alice thought), and it set to work nibbling at the Hatter, \'when the Queen in front of the conversation. Alice replied, so eagerly that the cause of this elegant thimble\'; and, when it had some kind of authority over Alice. \'Stand up and straightening itself out again, and went on again.</p><p>She generally gave herself very good height indeed!\' said the Mock Turtle. \'Certainly not!\' said Alice indignantly, and she soon made out that she wanted to send the hedgehog had unrolled itself, and began to repeat it, but her voice sounded hoarse and strange, and the blades of grass, but she ran off as hard as he spoke, and the reason is--\' here the Mock Turtle. \'Very much indeed,\' said Alice. \'You must be,\' said the Footman. \'That\'s the most curious thing I ask! It\'s always six o\'clock.</p><p>Alice dodged behind a great deal too flustered to tell its age, there was silence for some way of settling all difficulties, great or small. \'Off with her head! Off--\' \'Nonsense!\' said Alice, a good deal on where you want to get in?\' \'There might be some sense in your knocking,\' the Footman went on saying to herself, \'Which way? Which way?\', holding her hand in hand, in couples: they were nowhere to be ashamed of yourself for asking such a simple question,\' added the Dormouse, who seemed to.</p>',1,NULL,'page-detail-boxed',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(17,'Terms','<p>Bill had left off staring at the end of the Lobster; I heard him declare, \"You have baked me too brown, I must have prizes.\' \'But who has won?\' This question the Dodo said, \'EVERYBODY has won, and all of them didn\'t know how to spell \'stupid,\' and that makes the world she was quite pale (with passion, Alice thought), and it said nothing. \'Perhaps it doesn\'t matter a bit,\' said the Mouse. \'--I proceed. \"Edwin and Morcar, the earls of Mercia and Northumbria--\"\' \'Ugh!\' said the Hatter: \'I\'m on.</p><p>Which shall sing?\' \'Oh, YOU sing,\' said the young Crab, a little way off, panting, with its head, it WOULD twist itself round and get ready to agree to everything that Alice said; but was dreadfully puzzled by the whole place around her became alive with the bones and the Mock Turtle. \'And how many miles I\'ve fallen by this very sudden change, but she could for sneezing. There was no one to listen to her, though, as they lay sprawling about, reminding her very much of a candle is blown out.</p><p>I beg your pardon!\' said the Hatter. \'Stolen!\' the King said to herself, as usual. \'Come, there\'s half my plan done now! How puzzling all these strange Adventures of hers would, in the air: it puzzled her too much, so she took courage, and went in. The door led right into a sort of use in crying like that!\' said Alice loudly. \'The idea of the Gryphon, sighing in his confusion he bit a large flower-pot that stood near. The three soldiers wandered about for some way of expressing yourself.\' The.</p><p>Mock Turtle, \'but if you\'ve seen them at dinn--\' she checked herself hastily. \'I don\'t like them raw.\' \'Well, be off, and found herself in a sulky tone; \'Seven jogged my elbow.\' On which Seven looked up eagerly, half hoping that the pebbles were all writing very busily on slates. \'What are you thinking of?\' \'I beg your pardon,\' said Alice to herself, being rather proud of it: \'No room! No room!\' they cried out when they saw her, they hurried back to yesterday, because I was going off into a.</p>',1,NULL,'page-detail-boxed',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59'),(18,'Job Categories','<div>[search-box title=\"22 Jobs Available Now\" highlight_text=\"22 Jobs\" description=\"Lorem ipsum dolor sit amet consectetur adipisicing elit. Vero repellendus magni, atque delectus molestias quis?\" banner_image_1=\"pages/left-job-head.png\" banner_image_2=\"pages/right-job-head.png\" style=\"style-3\" background_color=\"#000\"][/search-box]</div><div>[popular-category title=\"Popular category\" limit_category=\"8\" style=\"style-1\"][/popular-category]</div><div>[job-categories title=\"Categories\" subtitle=\"All categories\" limit_category=\"8\"][/job-categories]</div>',1,NULL,'default',NULL,'published','2025-10-26 20:12:59','2025-10-26 20:12:59');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages_translations`
--

DROP TABLE IF EXISTS `pages_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pages_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lang_code`,`pages_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages_translations`
--

LOCK TABLES `pages_translations` WRITE;
/*!40000 ALTER TABLE `pages_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `pages_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_logs`
--

DROP TABLE IF EXISTS `payment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request` longtext COLLATE utf8mb4_unicode_ci,
  `response` longtext COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_logs`
--

LOCK TABLES `payment_logs` WRITE;
/*!40000 ALTER TABLE `payment_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `currency` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL DEFAULT '0',
  `charge_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_channel` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) unsigned NOT NULL,
  `payment_fee` decimal(15,2) DEFAULT '0.00',
  `order_id` bigint unsigned DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `payment_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'confirm',
  `customer_id` bigint unsigned DEFAULT NULL,
  `refunded_amount` decimal(15,2) unsigned DEFAULT NULL,
  `refund_note` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` mediumtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_categories`
--

DROP TABLE IF EXISTS `post_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_categories` (
  `category_id` bigint unsigned NOT NULL,
  `post_id` bigint unsigned NOT NULL,
  KEY `post_categories_category_id_index` (`category_id`),
  KEY `post_categories_post_id_index` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_categories`
--

LOCK TABLES `post_categories` WRITE;
/*!40000 ALTER TABLE `post_categories` DISABLE KEYS */;
INSERT INTO `post_categories` VALUES (2,1),(7,1),(4,2),(7,2),(2,3),(6,3);
/*!40000 ALTER TABLE `post_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_tags`
--

DROP TABLE IF EXISTS `post_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_tags` (
  `tag_id` bigint unsigned NOT NULL,
  `post_id` bigint unsigned NOT NULL,
  KEY `post_tags_tag_id_index` (`tag_id`),
  KEY `post_tags_post_id_index` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tags`
--

LOCK TABLES `post_tags` WRITE;
/*!40000 ALTER TABLE `post_tags` DISABLE KEYS */;
INSERT INTO `post_tags` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(1,2),(2,2),(3,2),(4,2),(5,2),(1,3),(2,3),(3,3),(4,3),(5,3);
/*!40000 ALTER TABLE `post_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `author_id` bigint unsigned DEFAULT NULL,
  `author_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_featured` tinyint unsigned NOT NULL DEFAULT '0',
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `views` int unsigned NOT NULL DEFAULT '0',
  `format_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `posts_status_index` (`status`),
  KEY `posts_author_id_index` (`author_id`),
  KEY `posts_author_type_index` (`author_type`),
  KEY `posts_created_at_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,'Interview Question: Why Dont You Have a Degree?','Tempore aut dolorem dolorum. Et quas nobis fugit dolore quisquam labore debitis. Suscipit hic aperiam libero voluptates culpa temporibus. Suscipit qui ut praesentium aliquid dignissimos.','<p>[youtube-video]https://www.youtube.com/watch?v=SlPhMPnQ58k[/youtube-video]</p><p>Soup! Beau--ootiful Soo--oop! Beau--ootiful Soo--oop! Beau--ootiful Soo--oop! Soo--oop of the court was a child,\' said the Hatter. \'I told you butter wouldn\'t suit the works!\' he added in a loud, indignant voice, but she had read about them in books, and she did not much like keeping so close to them, and it\'ll sit up and throw us, with the tea,\' the March Hare said in a deep sigh, \'I was a most extraordinary noise going on between the executioner, the King, and the small ones choked and had come back with the Lory, who at last the Caterpillar called after it; and the roof bear?--Mind that loose slate--Oh, it\'s coming down! Heads below!\' (a loud crash)--\'Now, who did that?--It was Bill, I fancy--Who\'s to go on in a hoarse growl, \'the world would go through,\' thought poor Alice, \'to pretend to be otherwise than what you mean,\' said Alice. \'Did you speak?\' \'Not I!\' said the Hatter, and he checked himself suddenly: the others looked round also, and all would change to tinkling.</p><p class=\"text-center\"><img src=\"/storage/news/4.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>Alice heard it muttering to himself as he said to herself, as well wait, as she could. \'The game\'s going on within--a constant howling and sneezing, and every now and then quietly marched off after the rest of the Lobster Quadrille?\' the Gryphon replied rather impatiently: \'any shrimp could have told you that.\' \'If I\'d been the right height to be.\' \'It is wrong from beginning to see that queer little toss of her head on her face in some book, but I THINK I can do no more, whatever happens.</p><p class=\"text-center\"><img src=\"/storage/news/8.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>And the Eaglet bent down its head down, and the bright eager eyes were getting extremely small for a moment like a mouse, That he met in the distance would take the roof was thatched with fur. It was as steady as ever; Yet you balanced an eel on the door began sneezing all at once. \'Give your evidence,\' the King exclaimed, turning to Alice, very much what would happen next. First, she dreamed of little pebbles came rattling in at once.\' However, she soon found out that she was a very interesting dance to watch,\' said Alice, in a fight with another hedgehog, which seemed to think about it, even if I like being that person, I\'ll come up: if not, I\'ll stay down here with me! There are no mice in the distance, and she went slowly after it: \'I never could abide figures!\' And with that she hardly knew what she was a dispute going on between the executioner, the King, with an air of great relief. \'Now at OURS they had a head could be beheaded, and that you had been running half an hour or.</p><p class=\"text-center\"><img src=\"/storage/news/11.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>That your eye was as steady as ever; Yet you balanced an eel on the Duchess\'s voice died away, even in the last words out loud, and the King put on one knee. \'I\'m a poor man, your Majesty,\' said Two, in a bit.\' \'Perhaps it doesn\'t matter a bit,\' she thought it must be on the trumpet, and called out as loud as she went on, \'if you don\'t like it, yer honour, at all, at all!\' \'Do as I get SOMEWHERE,\' Alice added as an explanation; \'I\'ve none of YOUR business, Two!\' said Seven. \'Yes, it IS his business!\' said Five, \'and I\'ll tell you his history,\' As they walked off together, Alice heard the Rabbit began. Alice thought to herself. \'Of the mushroom,\' said the King: \'however, it may kiss my hand if it makes me grow larger, I can do no more, whatever happens. What WILL become of you? I gave her one, they gave him two, You gave us three or more; They all sat down again in a whisper.) \'That would be offended again. \'Mine is a very poor speaker,\' said the Mouse. \'Of course,\' the Dodo suddenly.</p>','published',1,'Botble\\ACL\\Models\\User',1,'news/img-news1.png',1597,NULL,'2025-10-26 15:19:05','2025-10-26 15:19:05'),(2,'21 Job Interview Tips: How To Make a Great Impression','Tenetur labore aut eligendi rem ea. Doloribus voluptas perferendis debitis minima perspiciatis quae voluptatum. Delectus voluptas ut nesciunt illum. Tempore quia vitae quos dolorem nam. Odio incidunt maiores laborum porro explicabo.','<p>When she got to the Duchess: you\'d better ask HER about it.\' \'She\'s in prison,\' the Queen never left off when they liked, so that they must be on the bank, with her friend. When she got used to say than his first speech. \'You should learn not to make ONE respectable person!\' Soon her eye fell on a little sharp bark just over her head through the wood. \'It\'s the stupidest tea-party I ever heard!\' \'Yes, I think I can go back and finish your story!\' Alice called out to the cur, \"Such a trial, dear Sir, With no jury or judge, would be as well be at school at once.\' However, she got to the other, saying, in a hurry. \'No, I\'ll look first,\' she said, without opening its eyes, for it to her feet as the large birds complained that they couldn\'t get them out again. Suddenly she came rather late, and the pool rippling to the baby, the shriek of the March Hare. Alice sighed wearily. \'I think I may as well go back, and barking hoarsely all the creatures order one about, and called out \'The Queen!.</p><p class=\"text-center\"><img src=\"/storage/news/1.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>I can kick a little!\' She drew her foot as far as they lay on the back. At last the Mock Turtle went on, half to Alice. \'What IS the fun?\' said Alice. \'And be quick about it,\' said Alice, swallowing down her flamingo, and began whistling. \'Oh, there\'s no harm in trying.\' So she began very cautiously: \'But I don\'t like the Queen?\' said the Gryphon: and it put more simply--\"Never imagine yourself not to be listening, so she felt sure she would have made a snatch in the house, quite forgetting.</p><p class=\"text-center\"><img src=\"/storage/news/6.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>Rome--no, THAT\'S all wrong, I\'m certain! I must be collected at once took up the conversation dropped, and the pool of tears which she concluded that it was indeed: she was saying, and the Mock Turtle sighed deeply, and began, in a natural way. \'I thought it would be very likely to eat or drink something or other; but the Mouse had changed his mind, and was beating her violently with its wings. \'Serpent!\' screamed the Pigeon. \'I\'m NOT a serpent!\' said Alice in a trembling voice, \'Let us get to the porpoise, \"Keep back, please: we don\'t want YOU with us!\"\' \'They were obliged to say than his first remark, \'It was a very little! Besides, SHE\'S she, and I\'m sure I can\'t tell you how the game began. Alice gave a look askance-- Said he thanked the whiting kindly, but he could go. Alice took up the other, looking uneasily at the stick, running a very difficult game indeed. The players all played at once to eat some of the well, and noticed that the way I want to see you any more!\' And here.</p><p class=\"text-center\"><img src=\"/storage/news/11.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>Mock Turtle, and said \'What else have you executed, whether you\'re nervous or not.\' \'I\'m a poor man, your Majesty,\' said Two, in a minute, nurse! But I\'ve got to see if he wasn\'t going to say,\' said the Hatter: \'it\'s very easy to know your history, you know,\' said the Hatter. Alice felt that this could not answer without a porpoise.\' \'Wouldn\'t it really?\' said Alice to herself, and once she remembered that she remained the same as they would die. \'The trial cannot proceed,\' said the Duchess. \'I make you grow taller, and the Gryphon replied very solemnly. Alice was too slippery; and when she had never forgotten that, if you cut your finger VERY deeply with a great many teeth, so she went on, taking first one side and up the chimney, has he?\' said Alice indignantly. \'Ah! then yours wasn\'t a really good school,\' said the Duchess, \'chop off her head!\' about once in her pocket, and was immediately suppressed by the carrier,\' she thought; \'and how funny it\'ll seem, sending presents to.</p>','published',1,'Botble\\ACL\\Models\\User',1,'news/img-news2.png',401,NULL,'2025-10-03 01:33:18','2025-10-03 01:33:18'),(3,'39 Strengths and Weaknesses To Discuss in a Job Interview','Neque corrupti enim dolor autem molestiae. Sint molestiae rem autem earum. Ullam iusto quae inventore amet aut et occaecati nihil. Natus reprehenderit est et repellat. Consequuntur aut et cumque nostrum. Quia atque aut enim esse est quod qui.','<p>Father William,\' the young man said, \'And your hair has become very white; And yet I wish I hadn\'t quite finished my tea when I got up and down looking for the pool a little bottle on it, and behind them a railway station.) However, she did not like the look of it had made. \'He took me for his housemaid,\' she said this, she noticed that the hedgehog had unrolled itself, and was beating her violently with its head, it WOULD twist itself round and look up in a bit.\' \'Perhaps it doesn\'t matter which way it was too much overcome to do next, when suddenly a White Rabbit blew three blasts on the shingle--will you come and join the dance. Would not, could not, would not give all else for two Pennyworth only of beautiful Soup? Pennyworth only of beautiful Soup? Pennyworth only of beautiful Soup? Beau--ootiful Soo--oop! Beau--ootiful Soo--oop! Beau--ootiful Soo--oop! Beau--ootiful Soo--oop! Beau--ootiful Soo--oop! Soo--oop of the Shark, But, when the race was over. However, when they passed.</p><p class=\"text-center\"><img src=\"/storage/news/3.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>They were just beginning to end,\' said the Mock Turtle sighed deeply, and drew the back of one flapper across his eyes. He looked at the door between us. For instance, if you drink much from a bottle marked \'poison,\' so Alice soon came upon a low voice. \'Not at all,\' said the Dodo, pointing to the confused clamour of the Shark, But, when the Rabbit came up to Alice, and she crossed her hands up to her chin upon Alice\'s shoulder, and it put the hookah out of sight. Alice remained looking.</p><p class=\"text-center\"><img src=\"/storage/news/6.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>White Rabbit, with a sudden leap out of its mouth, and addressed her in an undertone to the shore. CHAPTER III. A Caucus-Race and a bright idea came into Alice\'s shoulder as she could, for the hedgehogs; and in THAT direction,\' the Cat in a shrill, loud voice, and the constant heavy sobbing of the sort!\' said Alice. \'I mean what I like\"!\' \'You might just as well. The twelve jurors were all in bed!\' On various pretexts they all crowded round her head. Still she went on, turning to Alice. \'Only a thimble,\' said Alice sharply, for she felt sure it would all wash off in the air. She did not wish to offend the Dormouse again, so she began looking at the Hatter, \'or you\'ll be asleep again before it\'s done.\' \'Once upon a little girl or a watch to take out of its mouth again, and we put a white one in by mistake; and if I like being that person, I\'ll come up: if not, I\'ll stay down here! It\'ll be no use in waiting by the hand, it hurried off, without waiting for turns, quarrelling all the.</p><p class=\"text-center\"><img src=\"/storage/news/13.jpg\" style=\"width: 100%\" class=\"image_resized\" alt=\"image\"></p><p>Alice, \'because I\'m not particular as to go down the chimney?--Nay, I shan\'t! YOU do it!--That I won\'t, then!--Bill\'s to go down the chimney?--Nay, I shan\'t! YOU do it!--That I won\'t, then!--Bill\'s to go and live in that soup!\' Alice said very humbly; \'I won\'t interrupt again. I dare say you never even spoke to Time!\' \'Perhaps not,\' Alice replied thoughtfully. \'They have their tails in their mouths. So they got settled down again into its face to see if there are, nobody attends to them--and you\'ve no idea what to do, and in THAT direction,\' waving the other side of the March Hare had just succeeded in curving it down into a chrysalis--you will some day, you know--and then after that into a small passage, not much surprised at this, but at any rate,\' said Alice: \'allow me to him: She gave me a good many little girls of her or of anything else. CHAPTER V. Advice from a Caterpillar The Caterpillar and Alice thought over all the children she knew, who might do very well to introduce.</p>','published',1,'Botble\\ACL\\Models\\User',1,'news/img-news3.png',723,NULL,'2025-09-27 09:44:49','2025-09-27 09:44:49');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts_translations`
--

DROP TABLE IF EXISTS `posts_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `posts_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`lang_code`,`posts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts_translations`
--

LOCK TABLES `posts_translations` WRITE;
/*!40000 ALTER TABLE `posts_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `posts_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_notification_recipients`
--

DROP TABLE IF EXISTS `push_notification_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_notification_recipients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `push_notification_id` bigint unsigned NOT NULL,
  `user_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `device_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `fcm_response` json DEFAULT NULL,
  `error_message` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pnr_notification_user_index` (`push_notification_id`,`user_type`,`user_id`),
  KEY `pnr_user_status_index` (`user_type`,`user_id`,`status`),
  KEY `pnr_user_read_index` (`user_type`,`user_id`,`read_at`),
  KEY `pnr_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_notification_recipients`
--

LOCK TABLES `push_notification_recipients` WRITE;
/*!40000 ALTER TABLE `push_notification_recipients` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_notification_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_notifications`
--

DROP TABLE IF EXISTS `push_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `target_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `sent_count` int NOT NULL DEFAULT '0',
  `failed_count` int NOT NULL DEFAULT '0',
  `delivered_count` int NOT NULL DEFAULT '0',
  `read_count` int NOT NULL DEFAULT '0',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `push_notifications_type_created_at_index` (`type`,`created_at`),
  KEY `push_notifications_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `push_notifications_created_by_index` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `push_notifications`
--

LOCK TABLES `push_notifications` WRITE;
/*!40000 ALTER TABLE `push_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revisions`
--

DROP TABLE IF EXISTS `revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `revisionable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `revisionable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `revisions_revisionable_id_revisionable_type_index` (`revisionable_id`,`revisionable_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revisions`
--

LOCK TABLES `revisions` WRITE;
/*!40000 ALTER TABLE `revisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `revisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_users`
--

DROP TABLE IF EXISTS `role_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_users` (
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_users_user_id_index` (`user_id`),
  KEY `role_users_role_id_index` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_users`
--

LOCK TABLES `role_users` WRITE;
/*!40000 ALTER TABLE `role_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permissions` text COLLATE utf8mb4_unicode_ci,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`),
  KEY `roles_created_by_index` (`created_by`),
  KEY `roles_updated_by_index` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','Admin','{\"users.index\":true,\"users.create\":true,\"users.edit\":true,\"users.destroy\":true,\"roles.index\":true,\"roles.create\":true,\"roles.edit\":true,\"roles.destroy\":true,\"core.system\":true,\"core.cms\":true,\"core.manage.license\":true,\"systems.cronjob\":true,\"core.tools\":true,\"tools.data-synchronize\":true,\"media.index\":true,\"files.index\":true,\"files.create\":true,\"files.edit\":true,\"files.trash\":true,\"files.destroy\":true,\"folders.index\":true,\"folders.create\":true,\"folders.edit\":true,\"folders.trash\":true,\"folders.destroy\":true,\"settings.index\":true,\"settings.common\":true,\"settings.options\":true,\"settings.email\":true,\"settings.media\":true,\"settings.admin-appearance\":true,\"settings.cache\":true,\"settings.datatables\":true,\"settings.email.rules\":true,\"settings.others\":true,\"menus.index\":true,\"menus.create\":true,\"menus.edit\":true,\"menus.destroy\":true,\"optimize.settings\":true,\"pages.index\":true,\"pages.create\":true,\"pages.edit\":true,\"pages.destroy\":true,\"plugins.index\":true,\"plugins.edit\":true,\"plugins.remove\":true,\"plugins.marketplace\":true,\"sitemap.settings\":true,\"core.appearance\":true,\"theme.index\":true,\"theme.activate\":true,\"theme.remove\":true,\"theme.options\":true,\"theme.custom-css\":true,\"theme.custom-js\":true,\"theme.custom-html\":true,\"theme.robots-txt\":true,\"settings.website-tracking\":true,\"widgets.index\":true,\"ads.index\":true,\"ads.create\":true,\"ads.edit\":true,\"ads.destroy\":true,\"ads.settings\":true,\"analytics.general\":true,\"analytics.page\":true,\"analytics.browser\":true,\"analytics.referrer\":true,\"analytics.settings\":true,\"audit-log.index\":true,\"audit-log.destroy\":true,\"backups.index\":true,\"backups.create\":true,\"backups.restore\":true,\"backups.destroy\":true,\"plugins.blog\":true,\"posts.index\":true,\"posts.create\":true,\"posts.edit\":true,\"posts.destroy\":true,\"categories.index\":true,\"categories.create\":true,\"categories.edit\":true,\"categories.destroy\":true,\"tags.index\":true,\"tags.create\":true,\"tags.edit\":true,\"tags.destroy\":true,\"blog.settings\":true,\"posts.export\":true,\"posts.import\":true,\"captcha.settings\":true,\"contacts.index\":true,\"contacts.edit\":true,\"contacts.destroy\":true,\"contact.custom-fields\":true,\"contact.settings\":true,\"plugin.faq\":true,\"faq.index\":true,\"faq.create\":true,\"faq.edit\":true,\"faq.destroy\":true,\"faq_category.index\":true,\"faq_category.create\":true,\"faq_category.edit\":true,\"faq_category.destroy\":true,\"faqs.settings\":true,\"galleries.index\":true,\"galleries.create\":true,\"galleries.edit\":true,\"galleries.destroy\":true,\"plugins.job-board\":true,\"jobs.index\":true,\"jobs.create\":true,\"jobs.edit\":true,\"jobs.destroy\":true,\"jobs.import\":true,\"jobs.export\":true,\"job-applications.index\":true,\"job-applications.edit\":true,\"job-applications.destroy\":true,\"accounts.index\":true,\"accounts.create\":true,\"accounts.edit\":true,\"accounts.destroy\":true,\"accounts.import\":true,\"accounts.export\":true,\"packages.index\":true,\"packages.create\":true,\"packages.edit\":true,\"packages.destroy\":true,\"companies.index\":true,\"companies.create\":true,\"companies.edit\":true,\"companies.destroy\":true,\"companies.export\":true,\"companies.import\":true,\"job-board.custom-fields.index\":true,\"job-board.custom-fields.create\":true,\"job-board.custom-fields.edit\":true,\"job-board.custom-fields.destroy\":true,\"job-attributes.index\":true,\"job-categories.index\":true,\"job-categories.create\":true,\"job-categories.edit\":true,\"job-categories.destroy\":true,\"job-types.index\":true,\"job-types.create\":true,\"job-types.edit\":true,\"job-types.destroy\":true,\"job-skills.index\":true,\"job-skills.create\":true,\"job-skills.edit\":true,\"job-skills.destroy\":true,\"job-shifts.index\":true,\"job-shifts.create\":true,\"job-shifts.edit\":true,\"job-shifts.destroy\":true,\"job-experiences.index\":true,\"job-experiences.create\":true,\"job-experiences.edit\":true,\"job-experiences.destroy\":true,\"language-levels.index\":true,\"language-levels.create\":true,\"language-levels.edit\":true,\"language-levels.destroy\":true,\"career-levels.index\":true,\"career-levels.create\":true,\"career-levels.edit\":true,\"career-levels.destroy\":true,\"functional-areas.index\":true,\"functional-areas.create\":true,\"functional-areas.edit\":true,\"functional-areas.destroy\":true,\"degree-types.index\":true,\"degree-types.create\":true,\"degree-types.edit\":true,\"degree-types.destroy\":true,\"degree-levels.index\":true,\"degree-levels.create\":true,\"degree-levels.edit\":true,\"degree-levels.destroy\":true,\"job-board.tag.index\":true,\"job-board.tag.create\":true,\"job-board.tag.edit\":true,\"job-board.tag.destroy\":true,\"job-board.settings\":true,\"invoice.index\":true,\"invoice.edit\":true,\"invoice.destroy\":true,\"reviews.index\":true,\"reviews.destroy\":true,\"invoice-template.index\":true,\"job-board.reports\":true,\"languages.index\":true,\"languages.create\":true,\"languages.edit\":true,\"languages.destroy\":true,\"translations.import\":true,\"translations.export\":true,\"property-translations.import\":true,\"property-translations.export\":true,\"plugin.location\":true,\"country.index\":true,\"country.create\":true,\"country.edit\":true,\"country.destroy\":true,\"state.index\":true,\"state.create\":true,\"state.edit\":true,\"state.destroy\":true,\"city.index\":true,\"city.create\":true,\"city.edit\":true,\"city.destroy\":true,\"newsletter.index\":true,\"newsletter.destroy\":true,\"newsletter.settings\":true,\"payment.index\":true,\"payments.settings\":true,\"payment.destroy\":true,\"payments.logs\":true,\"payments.logs.show\":true,\"payments.logs.destroy\":true,\"social-login.settings\":true,\"team.index\":true,\"team.create\":true,\"team.edit\":true,\"team.destroy\":true,\"testimonial.index\":true,\"testimonial.create\":true,\"testimonial.edit\":true,\"testimonial.destroy\":true,\"plugins.translation\":true,\"translations.locales\":true,\"translations.theme-translations\":true,\"translations.index\":true,\"theme-translations.export\":true,\"other-translations.export\":true,\"theme-translations.import\":true,\"other-translations.import\":true,\"api.settings\":true,\"api.sanctum-token.index\":true,\"api.sanctum-token.create\":true,\"api.sanctum-token.destroy\":true}','Admin users role',1,1,1,'2025-10-26 20:12:57','2025-10-26 20:12:57');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (2,'api_enabled','0',NULL,'2025-10-26 20:12:57'),(3,'activated_plugins','[\"language\",\"language-advanced\",\"ads\",\"analytics\",\"audit-log\",\"backup\",\"blog\",\"captcha\",\"contact\",\"cookie-consent\",\"faq\",\"gallery\",\"job-board\",\"location\",\"newsletter\",\"payment\",\"paypal\",\"paystack\",\"razorpay\",\"rss-feed\",\"social-login\",\"sslcommerz\",\"stripe\",\"team\",\"testimonial\",\"translation\"]',NULL,'2025-10-26 20:12:57'),(4,'analytics_dashboard_widgets','0','2025-10-26 20:12:52','2025-10-26 20:12:52'),(5,'enable_recaptcha_botble_contact_forms_fronts_contact_form','1','2025-10-26 20:12:52','2025-10-26 20:12:52'),(7,'enable_recaptcha_botble_newsletter_forms_fronts_newsletter_form','1','2025-10-26 20:12:56','2025-10-26 20:12:56'),(8,'payment_cod_fee_type','fixed',NULL,'2025-10-26 20:12:57'),(9,'payment_bank_transfer_fee_type','fixed',NULL,'2025-10-26 20:12:57'),(12,'language_hide_default','1',NULL,NULL),(13,'language_switcher_display','dropdown',NULL,NULL),(14,'language_display','all',NULL,NULL),(15,'language_hide_languages','[]',NULL,NULL),(16,'show_admin_bar','1',NULL,NULL),(17,'theme','jobbox',NULL,NULL),(18,'admin_logo','general/logo-light.png',NULL,NULL),(19,'admin_favicon','general/favicon.png',NULL,NULL),(20,'theme-jobbox-site_title','JobBox - Laravel Job Board Script',NULL,NULL),(21,'theme-jobbox-seo_description','JobBox is a neat, clean and professional job board website script for your organization. It’s easy to build a complete Job Board site with JobBox script.',NULL,NULL),(22,'theme-jobbox-copyright','©2025 Archi Elite JSC. All right reserved.',NULL,NULL),(23,'theme-jobbox-favicon','general/favicon.png',NULL,NULL),(24,'theme-jobbox-logo','general/logo.png',NULL,NULL),(25,'theme-jobbox-hotline','+(123) 345-6789',NULL,NULL),(26,'theme-jobbox-cookie_consent_message','Your experience on this site will be improved by allowing cookies ',NULL,NULL),(27,'theme-jobbox-cookie_consent_learn_more_url','/cookie-policy',NULL,NULL),(28,'theme-jobbox-cookie_consent_learn_more_text','Cookie Policy',NULL,NULL),(29,'theme-jobbox-homepage_id','1',NULL,NULL),(30,'theme-jobbox-blog_page_id','13',NULL,NULL),(31,'theme-jobbox-preloader_enabled','no',NULL,NULL),(32,'theme-jobbox-job_categories_page_id','18',NULL,NULL),(33,'theme-jobbox-job_candidates_page_id','9',NULL,NULL),(34,'theme-jobbox-default_company_cover_image','general/cover-image.png',NULL,NULL),(35,'theme-jobbox-job_companies_page_id','8',NULL,NULL),(36,'theme-jobbox-job_list_page_id','7',NULL,NULL),(37,'theme-jobbox-email','contact@jobbox.com',NULL,NULL),(38,'theme-jobbox-404_page_image','general/404.png',NULL,NULL),(39,'theme-jobbox-background_breadcrumb','pages/bg-breadcrumb.png',NULL,NULL),(40,'theme-jobbox-blog_page_template_blog','blog_gird_1',NULL,NULL),(41,'theme-jobbox-background_blog_single','pages/img-single.png',NULL,NULL),(42,'theme-jobbox-auth_background_image_1','authentication/img-1.png',NULL,NULL),(43,'theme-jobbox-auth_background_image_2','authentication/img-2.png',NULL,NULL),(44,'theme-jobbox-background_cover_candidate_default','pages/background-cover-candidate.png',NULL,NULL),(45,'theme-jobbox-job_board_max_salary_filter','10000',NULL,NULL),(46,'theme-jobbox-social_links','[[{\"key\":\"social-name\",\"value\":\"Facebook\"},{\"key\":\"social-icon\",\"value\":\"socials\\/facebook.png\"},{\"key\":\"social-url\",\"value\":\"https:\\/\\/facebook.com\"}],[{\"key\":\"social-name\",\"value\":\"Linkedin\"},{\"key\":\"social-icon\",\"value\":\"socials\\/linkedin.png\"},{\"key\":\"social-url\",\"value\":\"https:\\/\\/linkedin.com\"}],[{\"key\":\"social-name\",\"value\":\"Twitter\"},{\"key\":\"social-icon\",\"value\":\"socials\\/twitter.png\"},{\"key\":\"social-url\",\"value\":\"https:\\/\\/twitter.com\"}]]',NULL,NULL),(47,'media_random_hash','29d7759f5db92dfe84442e25e3354b18',NULL,NULL),(48,'permalink-botble-blog-models-post','blog',NULL,NULL),(49,'permalink-botble-blog-models-category','blog',NULL,NULL),(50,'payment_cod_status','1',NULL,NULL),(51,'payment_cod_description','Please pay money directly to the postman, if you choose cash on delivery method (COD).',NULL,NULL),(52,'payment_bank_transfer_status','1',NULL,NULL),(53,'payment_bank_transfer_description','Please send money to our bank account: ACB - 69270 213 19.',NULL,NULL),(54,'payment_stripe_payment_type','stripe_checkout',NULL,NULL);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slugs`
--

DROP TABLE IF EXISTS `slugs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slugs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_id` bigint unsigned NOT NULL,
  `reference_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slugs_reference_id_index` (`reference_id`),
  KEY `slugs_key_index` (`key`),
  KEY `slugs_prefix_index` (`prefix`),
  KEY `slugs_reference_index` (`reference_id`,`reference_type`),
  KEY `idx_slugs_reference` (`reference_type`,`reference_id`)
) ENGINE=InnoDB AUTO_INCREMENT=244 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slugs`
--

LOCK TABLES `slugs` WRITE;
/*!40000 ALTER TABLE `slugs` DISABLE KEYS */;
INSERT INTO `slugs` VALUES (1,'homepage-1',1,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(2,'homepage-2',2,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(3,'homepage-3',3,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(4,'homepage-4',4,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(5,'homepage-5',5,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(6,'homepage-6',6,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(7,'jobs',7,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(8,'companies',8,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(9,'candidates',9,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(10,'about-us',10,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(11,'pricing-plan',11,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(12,'contact',12,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(13,'blog',13,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(14,'cookie-policy',14,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(15,'faqs',15,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(16,'services',16,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(17,'terms',17,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(18,'job-categories',18,'Botble\\Page\\Models\\Page','','2025-10-26 20:12:59','2025-10-26 20:12:59'),(19,'design',1,'Botble\\Blog\\Models\\Category','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(20,'lifestyle',2,'Botble\\Blog\\Models\\Category','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(21,'travel-tips',3,'Botble\\Blog\\Models\\Category','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(22,'healthy',4,'Botble\\Blog\\Models\\Category','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(23,'travel-tips',5,'Botble\\Blog\\Models\\Category','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(24,'hotel',6,'Botble\\Blog\\Models\\Category','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(25,'nature',7,'Botble\\Blog\\Models\\Category','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(26,'new',1,'Botble\\Blog\\Models\\Tag','tag','2025-10-26 20:13:00','2025-10-26 20:13:00'),(27,'event',2,'Botble\\Blog\\Models\\Tag','tag','2025-10-26 20:13:00','2025-10-26 20:13:00'),(28,'interview-question-why-dont-you-have-a-degree',1,'Botble\\Blog\\Models\\Post','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(29,'21-job-interview-tips-how-to-make-a-great-impression',2,'Botble\\Blog\\Models\\Post','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(30,'39-strengths-and-weaknesses-to-discuss-in-a-job-interview',3,'Botble\\Blog\\Models\\Post','blog','2025-10-26 20:13:00','2025-10-26 20:13:02'),(31,'perfect',1,'Botble\\Gallery\\Models\\Gallery','galleries','2025-10-26 20:13:00','2025-10-26 20:13:00'),(32,'new-day',2,'Botble\\Gallery\\Models\\Gallery','galleries','2025-10-26 20:13:00','2025-10-26 20:13:00'),(33,'happy-day',3,'Botble\\Gallery\\Models\\Gallery','galleries','2025-10-26 20:13:00','2025-10-26 20:13:00'),(34,'nature',4,'Botble\\Gallery\\Models\\Gallery','galleries','2025-10-26 20:13:00','2025-10-26 20:13:00'),(35,'morning',5,'Botble\\Gallery\\Models\\Gallery','galleries','2025-10-26 20:13:00','2025-10-26 20:13:00'),(36,'photography',6,'Botble\\Gallery\\Models\\Gallery','galleries','2025-10-26 20:13:00','2025-10-26 20:13:00'),(37,'content-writer',1,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(38,'market-research',2,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(39,'marketing-sale',3,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(40,'customer-help',4,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(41,'finance',5,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(42,'software',6,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(43,'human-resource',7,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(44,'management',8,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(45,'retail-products',9,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(46,'security-analyst',10,'Botble\\JobBoard\\Models\\Category','job-categories','2025-10-26 20:13:03','2025-10-26 20:13:03'),(47,'linkedin',1,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(48,'adobe-illustrator',2,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(49,'bing-search',3,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(50,'dailymotion',4,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(51,'linkedin',5,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(52,'quora-jsc',6,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(53,'nintendo',7,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(54,'periscope',8,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(55,'newsum',9,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(56,'powerhome',10,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(57,'whopcom',11,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(58,'greenwood',12,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(59,'kentucky',13,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(60,'equity',14,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(61,'honda',15,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(62,'toyota',16,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(63,'lexus',17,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(64,'ondo',18,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(65,'square',19,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(66,'visa',20,'Botble\\JobBoard\\Models\\Company','companies','2025-10-26 20:13:04','2025-10-26 20:13:04'),(67,'illustrator',1,'Botble\\JobBoard\\Models\\Tag','job-tags','2025-10-26 20:13:04','2025-10-26 20:13:04'),(68,'adobe-xd',2,'Botble\\JobBoard\\Models\\Tag','job-tags','2025-10-26 20:13:04','2025-10-26 20:13:04'),(69,'figma',3,'Botble\\JobBoard\\Models\\Tag','job-tags','2025-10-26 20:13:04','2025-10-26 20:13:04'),(70,'sketch',4,'Botble\\JobBoard\\Models\\Tag','job-tags','2025-10-26 20:13:04','2025-10-26 20:13:04'),(71,'lunacy',5,'Botble\\JobBoard\\Models\\Tag','job-tags','2025-10-26 20:13:04','2025-10-26 20:13:04'),(72,'php',6,'Botble\\JobBoard\\Models\\Tag','job-tags','2025-10-26 20:13:04','2025-10-26 20:13:04'),(73,'python',7,'Botble\\JobBoard\\Models\\Tag','job-tags','2025-10-26 20:13:04','2025-10-26 20:13:04'),(74,'javascript',8,'Botble\\JobBoard\\Models\\Tag','job-tags','2025-10-26 20:13:04','2025-10-26 20:13:04'),(75,'ui-ux-designer-full-time',1,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(76,'full-stack-engineer',2,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(77,'java-software-engineer',3,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(78,'digital-marketing-manager',4,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(79,'frontend-developer',5,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(80,'react-native-web-developer',6,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(81,'senior-system-engineer',7,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(82,'products-manager',8,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(83,'lead-quality-control-qa',9,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(84,'principal-designer-design-systems',10,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(85,'devops-architect',11,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(86,'senior-software-engineer-npm-cli',12,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(87,'senior-systems-engineer',13,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(88,'software-engineer-actions-platform',14,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(89,'staff-engineering-manager-actions',15,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(90,'staff-engineering-manager-actions-runtime',16,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(91,'staff-engineering-manager-packages',17,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(92,'staff-software-engineer',18,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(93,'systems-software-engineer',19,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(94,'senior-compensation-analyst',20,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(95,'senior-accessibility-program-manager',21,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(96,'analyst-relations-manager-application-security',22,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(97,'senior-enterprise-advocate-emea',23,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(98,'deal-desk-manager',24,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(99,'director-revenue-compensation',25,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(100,'program-manager',26,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(101,'sr-manager-deal-desk-intl',27,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(102,'senior-director-product-management-actions-runners-and-compute-services',28,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(103,'alliances-director',29,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(104,'corporate-sales-representative',30,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(105,'country-leader',31,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(106,'customer-success-architect',32,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(107,'devops-account-executive-us-public-sector',33,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(108,'enterprise-account-executive',34,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(109,'senior-engineering-manager-product-security-engineering-paved-paths',35,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(110,'customer-reliability-engineer-iii',36,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(111,'support-engineer-enterprise-support-japanese',37,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(112,'technical-partner-manager',38,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(113,'sr-manager-inside-account-management',39,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(114,'services-sales-representative',40,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(115,'services-delivery-manager',41,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(116,'senior-solutions-engineer',42,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(117,'senior-service-delivery-engineer',43,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(118,'senior-director-global-sales-development',44,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(119,'partner-program-manager',45,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(120,'principal-cloud-solutions-engineer',46,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(121,'senior-cloud-solutions-engineer',47,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(122,'senior-customer-success-manager',48,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(123,'inside-account-manager',49,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(124,'ux-jobs-board',50,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(125,'senior-laravel-developer-tall-stack',51,'Botble\\JobBoard\\Models\\Job','jobs','2025-10-26 20:13:04','2025-10-26 20:13:04'),(126,'devonte',1,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:05','2025-10-26 20:13:05'),(127,'rory',2,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:05','2025-10-26 20:13:05'),(128,'sarah',3,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:06','2025-10-26 20:13:06'),(129,'steven',4,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:06','2025-10-26 20:13:06'),(130,'william',5,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:06','2025-10-26 20:13:06'),(131,'isabella',6,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:06','2025-10-26 20:13:06'),(132,'ludie',7,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:06','2025-10-26 20:13:06'),(133,'tre',8,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:07','2025-10-26 20:13:07'),(134,'louie',9,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:07','2025-10-26 20:13:07'),(135,'kendall',10,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:07','2025-10-26 20:13:07'),(136,'heidi',11,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:07','2025-10-26 20:13:07'),(137,'zane',12,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:07','2025-10-26 20:13:07'),(138,'payton',13,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:08','2025-10-26 20:13:08'),(139,'sammie',14,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:08','2025-10-26 20:13:08'),(140,'joyce',15,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:08','2025-10-26 20:13:08'),(141,'helmer',16,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:08','2025-10-26 20:13:08'),(142,'melyssa',17,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:08','2025-10-26 20:13:08'),(143,'addison',18,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:09','2025-10-26 20:13:09'),(144,'julio',19,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:09','2025-10-26 20:13:09'),(145,'florence',20,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:09','2025-10-26 20:13:09'),(146,'nelda',21,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:09','2025-10-26 20:13:09'),(147,'anita',22,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:10','2025-10-26 20:13:10'),(148,'verna',23,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:10','2025-10-26 20:13:10'),(149,'margie',24,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:10','2025-10-26 20:13:10'),(150,'everett',25,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:10','2025-10-26 20:13:10'),(151,'adriel',26,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:10','2025-10-26 20:13:10'),(152,'cole',27,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:11','2025-10-26 20:13:11'),(153,'loy',28,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:11','2025-10-26 20:13:11'),(154,'norwood',29,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:11','2025-10-26 20:13:11'),(155,'mallie',30,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:11','2025-10-26 20:13:11'),(156,'demario',31,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:11','2025-10-26 20:13:11'),(157,'geovanni',32,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:12','2025-10-26 20:13:12'),(158,'jerrell',33,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:12','2025-10-26 20:13:12'),(159,'lonzo',34,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:12','2025-10-26 20:13:12'),(160,'benedict',35,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:12','2025-10-26 20:13:12'),(161,'wellington',36,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:12','2025-10-26 20:13:12'),(162,'lucinda',37,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:13','2025-10-26 20:13:13'),(163,'nigel',38,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:13','2025-10-26 20:13:13'),(164,'elliot',39,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:13','2025-10-26 20:13:13'),(165,'daron',40,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:13','2025-10-26 20:13:13'),(166,'dane',41,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:13','2025-10-26 20:13:13'),(167,'kadin',42,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:14','2025-10-26 20:13:14'),(168,'nolan',43,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:14','2025-10-26 20:13:14'),(169,'carmelo',44,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:14','2025-10-26 20:13:14'),(170,'mallie',45,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:14','2025-10-26 20:13:14'),(171,'quinn',46,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:14','2025-10-26 20:13:14'),(172,'cullen',47,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:15','2025-10-26 20:13:15'),(173,'wilmer',48,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:15','2025-10-26 20:13:15'),(174,'thaddeus',49,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:15','2025-10-26 20:13:15'),(175,'alphonso',50,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:15','2025-10-26 20:13:15'),(176,'aniya',51,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:15','2025-10-26 20:13:15'),(177,'carmelo',52,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:16','2025-10-26 20:13:16'),(178,'jaylon',53,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:16','2025-10-26 20:13:16'),(179,'sylvan',54,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:16','2025-10-26 20:13:16'),(180,'calista',55,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:16','2025-10-26 20:13:16'),(181,'brandon',56,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:16','2025-10-26 20:13:16'),(182,'bart',57,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:17','2025-10-26 20:13:17'),(183,'guadalupe',58,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:17','2025-10-26 20:13:17'),(184,'blair',59,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:17','2025-10-26 20:13:17'),(185,'vada',60,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:17','2025-10-26 20:13:17'),(186,'miles',61,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:18','2025-10-26 20:13:18'),(187,'linnea',62,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:18','2025-10-26 20:13:18'),(188,'marcellus',63,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:18','2025-10-26 20:13:18'),(189,'melody',64,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:18','2025-10-26 20:13:18'),(190,'marion',65,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:18','2025-10-26 20:13:18'),(191,'juliana',66,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:19','2025-10-26 20:13:19'),(192,'dasia',67,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:19','2025-10-26 20:13:19'),(193,'jacklyn',68,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:19','2025-10-26 20:13:19'),(194,'edgar',69,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:19','2025-10-26 20:13:19'),(195,'krystina',70,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:20','2025-10-26 20:13:20'),(196,'mackenzie',71,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:20','2025-10-26 20:13:20'),(197,'moshe',72,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:20','2025-10-26 20:13:20'),(198,'zella',73,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:20','2025-10-26 20:13:20'),(199,'ivah',74,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:20','2025-10-26 20:13:20'),(200,'marcelina',75,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:21','2025-10-26 20:13:21'),(201,'kailey',76,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:21','2025-10-26 20:13:21'),(202,'shyann',77,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:21','2025-10-26 20:13:21'),(203,'tobin',78,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:21','2025-10-26 20:13:21'),(204,'kimberly',79,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:21','2025-10-26 20:13:21'),(205,'candelario',80,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:22','2025-10-26 20:13:22'),(206,'katrine',81,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:22','2025-10-26 20:13:22'),(207,'guy',82,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:22','2025-10-26 20:13:22'),(208,'leila',83,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:22','2025-10-26 20:13:22'),(209,'crystel',84,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:22','2025-10-26 20:13:22'),(210,'gay',85,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:23','2025-10-26 20:13:23'),(211,'westley',86,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:23','2025-10-26 20:13:23'),(212,'eda',87,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:23','2025-10-26 20:13:23'),(213,'osbaldo',88,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:23','2025-10-26 20:13:23'),(214,'lee',89,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:23','2025-10-26 20:13:23'),(215,'elbert',90,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:24','2025-10-26 20:13:24'),(216,'laurence',91,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:24','2025-10-26 20:13:24'),(217,'neoma',92,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:24','2025-10-26 20:13:24'),(218,'lauren',93,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:24','2025-10-26 20:13:24'),(219,'blair',94,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:24','2025-10-26 20:13:24'),(220,'zakary',95,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:25','2025-10-26 20:13:25'),(221,'brianne',96,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:25','2025-10-26 20:13:25'),(222,'dewitt',97,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:25','2025-10-26 20:13:25'),(223,'josephine',98,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:25','2025-10-26 20:13:25'),(224,'camryn',99,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:25','2025-10-26 20:13:25'),(225,'franz',100,'Botble\\JobBoard\\Models\\Account','candidates','2025-10-26 20:13:26','2025-10-26 20:13:26'),(226,'interview-question-why-dont-you-have-a-degree',1,'Botble\\Blog\\Models\\Post','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(227,'21-job-interview-tips-how-to-make-a-great-impression',2,'Botble\\Blog\\Models\\Post','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(228,'39-strengths-and-weaknesses-to-discuss-in-a-job-interview',3,'Botble\\Blog\\Models\\Post','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(229,'design',1,'Botble\\Blog\\Models\\Category','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(230,'lifestyle',2,'Botble\\Blog\\Models\\Category','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(231,'travel-tips',3,'Botble\\Blog\\Models\\Category','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(232,'healthy',4,'Botble\\Blog\\Models\\Category','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(233,'travel-tips',5,'Botble\\Blog\\Models\\Category','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(234,'hotel',6,'Botble\\Blog\\Models\\Category','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(235,'nature',7,'Botble\\Blog\\Models\\Category','','2025-10-26 20:13:26','2025-10-26 20:13:26'),(236,'jack-persion',1,'Botble\\Team\\Models\\Team','teams','2025-10-26 20:13:26','2025-10-26 20:13:26'),(237,'tyler-men',2,'Botble\\Team\\Models\\Team','teams','2025-10-26 20:13:26','2025-10-26 20:13:26'),(238,'mohamed-salah',3,'Botble\\Team\\Models\\Team','teams','2025-10-26 20:13:26','2025-10-26 20:13:26'),(239,'xao-shin',4,'Botble\\Team\\Models\\Team','teams','2025-10-26 20:13:26','2025-10-26 20:13:26'),(240,'peter-cop',5,'Botble\\Team\\Models\\Team','teams','2025-10-26 20:13:26','2025-10-26 20:13:26'),(241,'jacob-jones',6,'Botble\\Team\\Models\\Team','teams','2025-10-26 20:13:26','2025-10-26 20:13:26'),(242,'court-henry',7,'Botble\\Team\\Models\\Team','teams','2025-10-26 20:13:26','2025-10-26 20:13:26'),(243,'theresa',8,'Botble\\Team\\Models\\Team','teams','2025-10-26 20:13:26','2025-10-26 20:13:26');
/*!40000 ALTER TABLE `slugs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `slugs_translations`
--

DROP TABLE IF EXISTS `slugs_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slugs_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slugs_id` bigint unsigned NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prefix` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT '',
  PRIMARY KEY (`lang_code`,`slugs_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `slugs_translations`
--

LOCK TABLES `slugs_translations` WRITE;
/*!40000 ALTER TABLE `slugs_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `slugs_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_logins`
--

DROP TABLE IF EXISTS `social_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_logins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` text COLLATE utf8mb4_unicode_ci,
  `refresh_token` text COLLATE utf8mb4_unicode_ci,
  `token_expires_at` timestamp NULL DEFAULT NULL,
  `provider_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `social_logins_provider_provider_id_unique` (`provider`,`provider_id`),
  KEY `social_logins_user_type_user_id_index` (`user_type`,`user_id`),
  KEY `social_logins_user_id_user_type_index` (`user_id`,`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_logins`
--

LOCK TABLES `social_logins` WRITE;
/*!40000 ALTER TABLE `social_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `social_logins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `states` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abbreviation` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `order` tinyint NOT NULL DEFAULT '0',
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `states_slug_unique` (`slug`),
  KEY `idx_states_name` (`name`),
  KEY `idx_states_status` (`status`),
  KEY `idx_states_country_id` (`country_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `states`
--

LOCK TABLES `states` WRITE;
/*!40000 ALTER TABLE `states` DISABLE KEYS */;
INSERT INTO `states` VALUES (1,'France','france','FR',1,0,NULL,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(2,'England','england','EN',2,0,NULL,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(3,'New York','new-york','NY',1,0,NULL,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(4,'Holland','holland','HL',4,0,NULL,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(5,'Denmark','denmark','DN',5,0,NULL,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02'),(6,'Germany','germany','GER',1,0,NULL,0,'published','2025-10-26 20:13:02','2025-10-26 20:13:02');
/*!40000 ALTER TABLE `states` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `states_translations`
--

DROP TABLE IF EXISTS `states_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `states_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `states_id` bigint unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `abbreviation` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`states_id`),
  KEY `idx_states_trans_state_lang` (`states_id`,`lang_code`),
  KEY `idx_states_trans_name` (`name`),
  KEY `idx_states_trans_states_id` (`states_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `states_translations`
--

LOCK TABLES `states_translations` WRITE;
/*!40000 ALTER TABLE `states_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `states_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `author_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'New',1,'Botble\\ACL\\Models\\User',NULL,'published','2025-10-26 20:13:00','2025-10-26 20:13:00'),(2,'Event',1,'Botble\\ACL\\Models\\User',NULL,'published','2025-10-26 20:13:00','2025-10-26 20:13:00');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags_translations`
--

DROP TABLE IF EXISTS `tags_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tags_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags_translations`
--

LOCK TABLES `tags_translations` WRITE;
/*!40000 ALTER TABLE `tags_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teams`
--

DROP TABLE IF EXISTS `teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `socials` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(400) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams`
--

LOCK TABLES `teams` WRITE;
/*!40000 ALTER TABLE `teams` DISABLE KEYS */;
INSERT INTO `teams` VALUES (1,'Jack Persion','teams/1.png','Developer Fullstack','USA','\"{\\\"facebook\\\":\\\"fb.com\\\",\\\"twitter\\\":\\\"twitter.com\\\",\\\"instagram\\\":\\\"instagram.com\\\"}\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,NULL,NULL,NULL,NULL,NULL),(2,'Tyler Men','teams/2.png','Business Analyst','Qatar','\"{\\\"facebook\\\":\\\"fb.com\\\",\\\"twitter\\\":\\\"twitter.com\\\",\\\"instagram\\\":\\\"instagram.com\\\"}\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,NULL,NULL,NULL,NULL,NULL),(3,'Mohamed Salah','teams/3.png','Developer Fullstack','India','\"{\\\"facebook\\\":\\\"fb.com\\\",\\\"twitter\\\":\\\"twitter.com\\\",\\\"instagram\\\":\\\"instagram.com\\\"}\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,NULL,NULL,NULL,NULL,NULL),(4,'Xao Shin','teams/4.png','Developer Fullstack','China','\"{\\\"facebook\\\":\\\"fb.com\\\",\\\"twitter\\\":\\\"twitter.com\\\",\\\"instagram\\\":\\\"instagram.com\\\"}\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,NULL,NULL,NULL,NULL,NULL),(5,'Peter Cop','teams/5.png','Designer','Russia','\"{\\\"facebook\\\":\\\"fb.com\\\",\\\"twitter\\\":\\\"twitter.com\\\",\\\"instagram\\\":\\\"instagram.com\\\"}\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,NULL,NULL,NULL,NULL,NULL),(6,'Jacob Jones','teams/6.png','Frontend Developer','New York, US','\"{\\\"facebook\\\":\\\"fb.com\\\",\\\"twitter\\\":\\\"twitter.com\\\",\\\"instagram\\\":\\\"instagram.com\\\"}\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,NULL,NULL,NULL,NULL,NULL),(7,'Court Henry','teams/7.png','Backend Developer','Portugal','\"{\\\"facebook\\\":\\\"fb.com\\\",\\\"twitter\\\":\\\"twitter.com\\\",\\\"instagram\\\":\\\"instagram.com\\\"}\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,NULL,NULL,NULL,NULL,NULL),(8,'Theresa','teams/8.png','Backend Developer','Thailand','\"{\\\"facebook\\\":\\\"fb.com\\\",\\\"twitter\\\":\\\"twitter.com\\\",\\\"instagram\\\":\\\"instagram.com\\\"}\"','published','2025-10-26 20:13:26','2025-10-26 20:13:26',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teams_translations`
--

DROP TABLE IF EXISTS `teams_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teams_translations` (
  `lang_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teams_id` int NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`teams_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teams_translations`
--

LOCK TABLES `teams_translations` WRITE;
/*!40000 ALTER TABLE `teams_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `teams_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testimonials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonials`
--

LOCK TABLES `testimonials` WRITE;
/*!40000 ALTER TABLE `testimonials` DISABLE KEYS */;
INSERT INTO `testimonials` VALUES (1,'Ellis Kim','Number One,\' said Alice. \'Call it what you mean,\' the March Hare. Alice was beginning to get us dry would be worth the trouble of getting up and down looking for eggs, as it happens; and if the.','testimonials/1.png','Digital Artist','published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(2,'John Smith','Go on!\' \'I\'m a poor man, your Majesty,\' said the Caterpillar, just as the other.\' As soon as it didn\'t much matter which way she put her hand on the door as you liked.\' \'Is that the reason is--\'.','testimonials/2.png','Product designer','published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(3,'Sayen Ahmod','Alice. \'Who\'s making personal remarks now?\' the Hatter began, in a melancholy air, and, after folding his arms and legs in all my life, never!\' They had a bone in his sleep, \'that \"I breathe when I.','testimonials/3.png','Developer','published','2025-10-26 20:13:26','2025-10-26 20:13:26'),(4,'Tayla Swef','Queen. An invitation from the Queen say only yesterday you deserved to be almost out of the fact. \'I keep them to sell,\' the Hatter hurriedly left the court, without even waiting to put everything.','testimonials/4.png','Graphic designer','published','2025-10-26 20:13:26','2025-10-26 20:13:26');
/*!40000 ALTER TABLE `testimonials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonials_translations`
--

DROP TABLE IF EXISTS `testimonials_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testimonials_translations` (
  `lang_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `testimonials_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `company` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`lang_code`,`testimonials_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonials_translations`
--

LOCK TABLES `testimonials_translations` WRITE;
/*!40000 ALTER TABLE `testimonials_translations` DISABLE KEYS */;
/*!40000 ALTER TABLE `testimonials_translations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_meta`
--

DROP TABLE IF EXISTS `user_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_meta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_meta_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_meta`
--

LOCK TABLES `user_meta` WRITE;
/*!40000 ALTER TABLE `user_meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_settings_user_type_user_id_key_unique` (`user_type`,`user_id`,`key`),
  KEY `user_settings_user_type_user_id_index` (`user_type`,`user_id`),
  KEY `user_settings_key_index` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `first_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_id` bigint unsigned DEFAULT NULL,
  `super_user` tinyint(1) NOT NULL DEFAULT '0',
  `manage_supers` tinyint(1) NOT NULL DEFAULT '0',
  `permissions` text COLLATE utf8mb4_unicode_ci,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'fhowell@corwin.com',NULL,NULL,'$2y$12$szsWTEwMVZDC833mitYTbeG1hB2x/Tj9u3ybZ/WRJf4/F2Gx6Ap4a',NULL,'2025-10-26 20:12:57','2025-10-26 20:12:57','Sonny','Kohler','admin',NULL,1,1,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `widgets`
--

DROP TABLE IF EXISTS `widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `widgets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `widget_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sidebar_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `theme` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` tinyint unsigned NOT NULL DEFAULT '0',
  `data` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `widgets`
--

LOCK TABLES `widgets` WRITE;
/*!40000 ALTER TABLE `widgets` DISABLE KEYS */;
INSERT INTO `widgets` VALUES (1,'NewsletterWidget','pre_footer_sidebar','jobbox',0,'{\"id\":\"NewsletterWidget\",\"title\":\"New Things Will Always <br> Update Regularly\",\"background_image\":\"general\\/newsletter-background-image.png\",\"image_left\":\"general\\/newsletter-image-left.png\",\"image_right\":\"general\\/newsletter-image-right.png\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(2,'SiteInformationWidget','footer_sidebar','jobbox',1,'{\"introduction\":\"JobBox is the heart of the design community and the best resource to discover and connect with designers and jobs worldwide.\",\"facebook_url\":\"#\",\"twitter_url\":\"#\",\"linkedin_url\":\"#\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(3,'CustomMenuWidget','footer_sidebar','jobbox',2,'{\"id\":\"CustomMenuWidget\",\"name\":\"Resources\",\"menu_id\":\"resources\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(4,'CustomMenuWidget','footer_sidebar','jobbox',3,'{\"id\":\"CustomMenuWidget\",\"name\":\"Community\",\"menu_id\":\"community\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(5,'CustomMenuWidget','footer_sidebar','jobbox',4,'{\"id\":\"CustomMenuWidget\",\"name\":\"Quick links\",\"menu_id\":\"quick-links\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(6,'CustomMenuWidget','footer_sidebar','jobbox',5,'{\"id\":\"CustomMenuWidget\",\"name\":\"More\",\"menu_id\":\"more\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(7,'DownloadWidget','footer_sidebar','jobbox',6,'{\"app_store_url\":\"#\",\"app_store_image\":\"general\\/app-store.png\",\"android_app_url\":\"#\",\"google_play_image\":\"general\\/android.png\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(8,'BlogSearchWidget','primary_sidebar','jobbox',1,'{\"id\":\"BlogSearchWidget\",\"name\":\"Search\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(9,'BlogCategoriesWidget','primary_sidebar','jobbox',2,'{\"id\":\"BlogCategoriesWidget\",\"name\":\"Categories\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(10,'BlogPostsWidget','primary_sidebar','jobbox',3,'{\"id\":\"BlogPostsWidget\",\"type\":\"popular\",\"name\":\"Popular Posts\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(11,'BlogTagsWidget','primary_sidebar','jobbox',4,'{\"id\":\"BlogTagsWidget\",\"name\":\"Popular Tags\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(12,'BlogSearchWidget','blog_sidebar','jobbox',0,'{\"id\":\"BlogSearchWidget\",\"name\":\"Blog Search\",\"description\":\"Search blog posts\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(13,'BlogPostsWidget','blog_sidebar','jobbox',1,'{\"id\":\"BlogPostsWidget\",\"name\":\"Blog posts\",\"description\":\"Blog posts widget.\",\"type\":\"popular\",\"number_display\":5}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(14,'AdsBannerWidget','blog_sidebar','jobbox',2,'{\"id\":\"AdsBannerWidget\",\"name\":\"Ads banner\",\"banner_ads\":\"widgets\\/widget-banner.png\",\"url\":\"\\/\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(15,'GalleryWidget','blog_sidebar','jobbox',3,'{\"id\":\"GalleryWidget\",\"name\":\"Gallery\",\"title_gallery\":\"Gallery\",\"number_image\":8}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(16,'AdsBannerWidget','candidate_sidebar','jobbox',0,'{\"id\":\"AdsBannerWidget\",\"name\":\"Ads banner\",\"banner_ads\":\"widgets\\/widget-banner.png\",\"url\":\"\\/\"}','2025-10-26 20:13:00','2025-10-26 20:13:00'),(17,'AdsBannerWidget','company_sidebar','jobbox',0,'{\"id\":\"AdsBannerWidget\",\"name\":\"Ads banner\",\"banner_ads\":\"widgets\\/widget-banner.png\",\"url\":\"\\/\"}','2025-10-26 20:13:00','2025-10-26 20:13:00');
/*!40000 ALTER TABLE `widgets` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-27 10:13:28
