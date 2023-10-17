-- MariaDB dump 10.19-11.1.2-MariaDB, for Linux (x86_64)
--
-- Host: 192.168.122.105    Database: restock
-- ------------------------------------------------------
-- Server version	10.11.3-MariaDB-1

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
-- Table structure for table `apiauth`
--

DROP TABLE IF EXISTS User;
CREATE TABLE User (
                      user_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                      name VARCHAR(100) NOT NULL,
                      password VARCHAR(100) NOT NULL,
                      email VARCHAR(255) UNIQUE,
    -- other user attributes...
                      INDEX index_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;

DROP TABLE IF EXISTS `ApiAuth`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApiAuth` (
                           `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                           `user_id` int(10) unsigned NOT NULL,
                           `token` varchar(100) NOT NULL,
                           `create_date` datetime NOT NULL,
                           `last_use_date` datetime NOT NULL,
                           PRIMARY KEY (`id`),
                           KEY `apiauth_FK` (`user_id`),
                           CONSTRAINT `apiauth_FK` FOREIGN KEY (`user_id`) REFERENCES `User` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `Group`;
CREATE TABLE `Group` (
                         group_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                         group_name VARCHAR(50) NOT NULL,
    -- other group attributes...
                         INDEX index_group_id (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;

DROP TABLE IF EXISTS Recipe;
CREATE TABLE Recipe (
                        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                        user_id INT UNSIGNED NOT NULL,
                        recipe_name VARCHAR(200) NOT NULL,
                        instructions TEXT NOT NULL,
    -- other recipe attributes...
                        FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;

DROP TABLE IF EXISTS Session;
CREATE TABLE Session (
                         id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                         user_id INT UNSIGNED NOT NULL,
                         token VARCHAR(100) NOT NULL,
                         create_date DATETIME NOT NULL,
                         last_use_date DATETIME NOT NULL,
    -- other session attributes...
                         FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;

DROP TABLE IF EXISTS GroupMember;
CREATE TABLE GroupMember (
                             id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                             group_id INT UNSIGNED NOT NULL,
                             user_id INT UNSIGNED NOT NULL,
                             role ENUM('Member', 'Admin') NOT NULL,
    -- other group member attributes...
                             FOREIGN KEY (group_id) REFERENCES `Group`(group_id) ON DELETE CASCADE ON UPDATE CASCADE,
                             FOREIGN KEY (user_id) REFERENCES User(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;

DROP TABLE IF EXISTS Item;
CREATE TABLE Item (
                      item_id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                      group_id INT UNSIGNED NOT NULL,
                      item_name VARCHAR(255) NOT NULL,
                      description VARCHAR(255) NOT NULL,
                      category VARCHAR(255) NOT NULL,
    -- other item attributes...
                      FOREIGN KEY (group_id) REFERENCES `Group`(group_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;

DROP TABLE IF EXISTS ActionLog;
CREATE TABLE ActionLog (
                           id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                           group_id INT UNSIGNED NOT NULL,
                           log_message TEXT NOT NULL,
                           timestamp DATETIME NOT NULL,
    -- other log attributes...
                           FOREIGN KEY (group_id) REFERENCES `Group`(group_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;

DROP TABLE IF EXISTS Pantry;
CREATE TABLE Pantry (
                        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                        item_id INT UNSIGNED NOT NULL,
                        quantity INT NOT NULL,
                        minimum_threshold INT NOT NULL,
                        auto_add_to_shopping_list BOOLEAN NOT NULL,
    -- other pantry attributes...
                        FOREIGN KEY (item_id) REFERENCES Item(item_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;

DROP TABLE IF EXISTS ShoppingList;
CREATE TABLE ShoppingList (
                              id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT NOT NULL,
                              item_id INT UNSIGNED NOT NULL,
                              quantity INT NOT NULL,
                              dont_add_to_pantry BOOLEAN NOT NULL,
    -- other shopping list attributes...
                              FOREIGN KEY (item_id) REFERENCES Item(item_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE= utf8mb4_general_ci;