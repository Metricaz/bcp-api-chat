/*
 Navicat Premium Data Transfer

 Source Server         : Felipe
 Source Server Type    : MySQL
 Source Server Version : 50732
 Source Host           : primario.mysql.dbaas.com.br:3306
 Source Schema         : primario

 Target Server Type    : MySQL
 Target Server Version : 50732
 File Encoding         : 65001

 Date: 06/03/2023 12:33:08
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for borrower_metas
-- ----------------------------
DROP TABLE IF EXISTS `borrower_metas`;
CREATE TABLE `borrower_metas`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_borrowers` int(11) NOT NULL,
  `field` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `value` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `created_at` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `id_borrowers`(`id_borrowers`) USING BTREE,
  CONSTRAINT `fk` FOREIGN KEY (`id_borrowers`) REFERENCES `borrowers` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB AUTO_INCREMENT = 199 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for borrowers
-- ----------------------------
DROP TABLE IF EXISTS `borrowers`;
CREATE TABLE `borrowers`  (
  `id` int(32) NOT NULL AUTO_INCREMENT,
  `name` tinytext CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `cpf` varchar(12) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `proposal` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `proposaId` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `created_at` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `cpf`(`cpf`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 214 CHARACTER SET = latin1 COLLATE = latin1_swedish_ci ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
