SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for classrooms
-- ----------------------------
DROP TABLE IF EXISTS `classrooms`;
CREATE TABLE `classrooms`  (
  `classroom` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  PRIMARY KEY (`classroom`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of classrooms
-- ----------------------------
INSERT INTO `classrooms` VALUES ('A123');

-- ----------------------------
-- Table structure for course
-- ----------------------------
DROP TABLE IF EXISTS `course`;
CREATE TABLE `course`  (
  `courseid` int(11) NOT NULL,
  `termid` int(11) NULL DEFAULT NULL,
  `courseno` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `coursename` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `teacherno` char(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `teachername` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `credit` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`courseid`) USING BTREE,
  INDEX `termid`(`termid`) USING BTREE,
  INDEX `courseno`(`courseno`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of course
-- ----------------------------
INSERT INTO `course` VALUES (1, 20222, '01234567', '数据库原理', '1000', '张三(10001234)', '4.0');
INSERT INTO `course` VALUES (2, 20222, '01234568', '数据结构', '1000', '张三(10001234)', '4.0');

-- ----------------------------
-- Table structure for score
-- ----------------------------
DROP TABLE IF EXISTS `score`;
CREATE TABLE `score`  (
  `termid` int(11) NULL DEFAULT NULL,
  `classid` int(11) NOT NULL,
  `stuno` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `scoremid` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `scorefin` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `score` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `gpa` varchar(4) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`classid`, `stuno`) USING BTREE,
  INDEX `idx1`(`stuno`, `termid`) USING BTREE,
  CONSTRAINT `score_ibfk_1` FOREIGN KEY (`classid`) REFERENCES `course` (`courseid`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `score_ibfk_2` FOREIGN KEY (`stuno`) REFERENCES `student` (`stuno`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of score
-- ----------------------------
INSERT INTO `score` VALUES (20222, 1, '23120000', '80', '100', '90', '4.0');
INSERT INTO `score` VALUES (20222, 2, '23120000', '60', '58', '59', '0.0');
INSERT INTO `score` VALUES (20222, 2, '23120001', '60', '病缓', '病缓', '0.0');

-- ----------------------------
-- Table structure for student
-- ----------------------------
DROP TABLE IF EXISTS `student`;
CREATE TABLE `student`  (
  `stuno` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `stuname` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `college` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `major` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `submajor` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `status` char(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `grade` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`stuno`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of student
-- ----------------------------
INSERT INTO `student` VALUES ('23120000', '艾AA', '计算机学院', '计算机科学与技术', '直招', '1', NULL, '2023');
INSERT INTO `student` VALUES ('23120001', '拜BB', '计算机学院', '人工智能', NULL, '1', NULL, '2023');

-- ----------------------------
-- Table structure for vod
-- ----------------------------
DROP TABLE IF EXISTS `vod`;
CREATE TABLE `vod`  (
  `vid` int(11) NOT NULL,
  `courseid` int(11) NULL DEFAULT NULL,
  `beginTime` bigint(20) NULL DEFAULT NULL,
  `endTime` bigint(20) NULL DEFAULT NULL,
  `url1` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `url2` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  `classroom` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
  PRIMARY KEY (`vid`) USING BTREE,
  INDEX `fk1`(`courseid`) USING BTREE,
  INDEX `classroom`(`classroom`) USING BTREE,
  CONSTRAINT `fk1` FOREIGN KEY (`courseid`) REFERENCES `course` (`courseid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of vod
-- ----------------------------
INSERT INTO `vod` VALUES (1, 1, 1676358000, 1676360700, 'https://example.com/1.mp4', 'https://example.com/2.mp4', 'A123');
INSERT INTO `vod` VALUES (2, 1, 1676360700, 1676370700, 'https://example.com/1.mp4', 'https://example.com/2.mp4', 'A123');

SET FOREIGN_KEY_CHECKS = 1;
