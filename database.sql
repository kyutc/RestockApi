-- MariaDB dump 10.19-11.1.2-MariaDB, for Linux (x86_64)
--
-- Host: 192.168.122.105    Database: restock
-- ------------------------------------------------------
-- Server version	10.11.3-MariaDB-1

# /*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
# /*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
# /*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
# /*!40101 SET NAMES utf8mb4 */;
# /*!40103 SET @OLD_TIME_ZONE = @@TIME_ZONE */;
# /*!40103 SET TIME_ZONE = '+00:00' */;
# /*!40014 SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0 */;
# /*!40014 SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0 */;
# /*!40101 SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO' */;
# /*!40111 SET @OLD_SQL_NOTES = @@SQL_NOTES, SQL_NOTES = 0 */;

--
-- Dumping routines for database 'restock'
--
CREATE DATABASE IF NOT EXISTS restock;

CREATE TABLE restock.apiauth
(
    id            INT AUTO_INCREMENT NOT NULL,
    user_id       INT DEFAULT NULL,
    token         VARCHAR(100)       NOT NULL,
    created_at    DATETIME           NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    last_use_date DATETIME           NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    UNIQUE INDEX UNIQ_F168A126A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.user
(
    id       INT AUTO_INCREMENT NOT NULL,
    name     VARCHAR(100)       NOT NULL,
    password VARCHAR(100)       NOT NULL,
    email    VARCHAR(255)       NOT NULL,
    UNIQUE INDEX email (email),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.session
(
    id             INT AUTO_INCREMENT NOT NULL,
    user_id        INT DEFAULT NULL,
    token          VARCHAR(100)       NOT NULL,
    create_date    DATETIME           NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    last_used_date DATETIME           NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_D71B9B65A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.`group`
(
    id   INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(100)       NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.group_member
(
    id       INT AUTO_INCREMENT NOT NULL,
    group_id INT DEFAULT NULL,
    user_id  INT DEFAULT NULL,
    role     VARCHAR(255)       NOT NULL,
    INDEX IDX_DDAFD348FE54D947 (group_id),
    INDEX IDX_DDAFD348A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.pantry
(
    id                        INT AUTO_INCREMENT NOT NULL,
    item_id                   INT DEFAULT NULL,
    quantity                  INT                NOT NULL,
    minimum_threshold         INT                NOT NULL,
    auto_add_to_shopping_list TINYINT(1)         NOT NULL,
    UNIQUE INDEX UNIQ_90E9C8CB126F525E (item_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.recipe
(
    id           INT AUTO_INCREMENT NOT NULL,
    user_id      INT DEFAULT NULL,
    name         VARCHAR(100)       NOT NULL,
    instructions LONGTEXT           NOT NULL,
    INDEX IDX_E8021933A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.action_log
(
    id          INT AUTO_INCREMENT NOT NULL,
    group_id    INT DEFAULT NULL,
    log_message LONGTEXT           NOT NULL,
    timestamp   DATETIME           NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_E3774C4BFE54D947 (group_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.shopping_list
(
    id                 INT AUTO_INCREMENT NOT NULL,
    item_id            INT DEFAULT NULL,
    quantity           INT                NOT NULL,
    dont_add_to_pantry TINYINT(1)         NOT NULL,
    UNIQUE INDEX UNIQ_9DB58BD0126F525E (item_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
CREATE TABLE restock.item
(
    id          INT AUTO_INCREMENT NOT NULL,
    group_id    INT DEFAULT NULL,
    name        VARCHAR(255)       NOT NULL,
    description VARCHAR(255)       NOT NULL,
    category    VARCHAR(255)       NOT NULL,
    INDEX IDX_B31E3FADFE54D947 (group_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8
  COLLATE `utf8_unicode_ci`
  ENGINE = InnoDB;
ALTER TABLE restock.apiauth
    ADD CONSTRAINT FK_F168A126A76ED395 FOREIGN KEY (user_id) REFERENCES restock.user (id);
ALTER TABLE restock.session
    ADD CONSTRAINT FK_D71B9B65A76ED395 FOREIGN KEY (user_id) REFERENCES restock.user (id);
ALTER TABLE restock.group_member
    ADD CONSTRAINT FK_DDAFD348FE54D947 FOREIGN KEY (group_id) REFERENCES restock.`group` (id);
ALTER TABLE restock.group_member
    ADD CONSTRAINT FK_DDAFD348A76ED395 FOREIGN KEY (user_id) REFERENCES restock.user (id);
ALTER TABLE restock.pantry
    ADD CONSTRAINT FK_90E9C8CB126F525E FOREIGN KEY (item_id) REFERENCES restock.item (id);
ALTER TABLE restock.recipe
    ADD CONSTRAINT FK_E8021933A76ED395 FOREIGN KEY (user_id) REFERENCES restock.user (id);
ALTER TABLE restock.action_log
    ADD CONSTRAINT FK_E3774C4BFE54D947 FOREIGN KEY (group_id) REFERENCES restock.`group` (id);
ALTER TABLE restock.shopping_list
    ADD CONSTRAINT FK_9DB58BD0126F525E FOREIGN KEY (item_id) REFERENCES restock.item (id);
ALTER TABLE restock.item
    ADD CONSTRAINT FK_B31E3FADFE54D947 FOREIGN KEY (group_id) REFERENCES restock.`group` (id);
