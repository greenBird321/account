-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2017-04-10 07:28:33
-- 服务器版本： 5.7.9
-- PHP Version: 5.6.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `phalcon_account`
--

-- --------------------------------------------------------

--
-- 表的结构 `accounts`
--

CREATE TABLE `accounts` (
  `id` int(10) UNSIGNED NOT NULL,
  `account` varchar(64) NOT NULL DEFAULT '',
  `password` varchar(64) DEFAULT '',
  `name` varchar(32) DEFAULT '',
  `status` tinyint(3) UNSIGNED DEFAULT '1',
  `secret_key` varchar(32) DEFAULT '',
  `gender` tinyint(3) UNSIGNED DEFAULT '0',
  `mobile` varchar(32) DEFAULT '',
  `photo` varchar(512) DEFAULT '',
  `birthday` date DEFAULT '0000-01-01',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `blacklist`
--

CREATE TABLE `blacklist` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) DEFAULT '0' COMMENT '用户ID',
  `time` int(10) DEFAULT '0' COMMENT '截止时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `oauth_default`
--

CREATE TABLE `oauth_default` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT '0',
  `platform` varchar(16) DEFAULT NULL,
  `open_id` varchar(64) DEFAULT '',
  `open_name` varchar(32) DEFAULT '',
  `access_token` varchar(128) DEFAULT '',
  `refresh_token` varchar(128) DEFAULT '',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='默认登录';

-- --------------------------------------------------------

--
-- 表的结构 `oauth_device`
--

CREATE TABLE `oauth_device` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT '0',
  `open_id` varchar(40) DEFAULT '',
  `open_name` varchar(32) DEFAULT '',
  `access_token` varchar(16) DEFAULT '',
  `refresh_token` varchar(16) DEFAULT '',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备登录';

-- --------------------------------------------------------

--
-- 表的结构 `oauth_facebook`
--

CREATE TABLE `oauth_facebook` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT '0',
  `open_id` bigint(20) DEFAULT '0',
  `open_name` varchar(32) DEFAULT '',
  `access_token` varchar(128) DEFAULT '',
  `refresh_token` varchar(128) DEFAULT '',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Facebook登录';

-- --------------------------------------------------------

--
-- 表的结构 `oauth_google`
--

CREATE TABLE `oauth_google` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT '0',
  `open_id` bigint(20) DEFAULT '0',
  `open_name` varchar(32) DEFAULT '',
  `access_token` varchar(128) DEFAULT '',
  `refresh_token` varchar(128) DEFAULT '',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Google登录';

-- --------------------------------------------------------

--
-- 表的结构 `oauth_mobile`
--

CREATE TABLE `oauth_mobile` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT '0',
  `open_id` varchar(32) DEFAULT '',
  `open_name` varchar(32) DEFAULT '',
  `access_token` varchar(16) DEFAULT '',
  `refresh_token` varchar(16) DEFAULT '',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='手机登录';

-- --------------------------------------------------------

--
-- 表的结构 `oauth_weibo`
--

CREATE TABLE `oauth_weibo` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT '0',
  `open_id` bigint(20) DEFAULT '0',
  `open_name` varchar(32) DEFAULT '',
  `access_token` varchar(128) DEFAULT '',
  `refresh_token` varchar(128) DEFAULT '',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微博登录';

-- --------------------------------------------------------

--
-- 表的结构 `oauth_weixin`
--

CREATE TABLE `oauth_weixin` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT '0',
  `open_id` bigint(20) DEFAULT '0',
  `open_name` varchar(32) DEFAULT '',
  `access_token` varchar(128) DEFAULT '',
  `refresh_token` varchar(128) DEFAULT '',
  `create_time` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `update_time` timestamp NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信登录';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account` (`account`);

--
-- Indexes for table `blacklist`
--
ALTER TABLE `blacklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `oauth_default`
--
ALTER TABLE `oauth_default`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account` (`open_id`,`platform`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `oauth_device`
--
ALTER TABLE `oauth_device`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `open_id` (`open_id`) USING BTREE;

--
-- Indexes for table `oauth_facebook`
--
ALTER TABLE `oauth_facebook`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `open_id` (`open_id`) USING BTREE;

--
-- Indexes for table `oauth_google`
--
ALTER TABLE `oauth_google`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `open_id` (`open_id`) USING BTREE;

--
-- Indexes for table `oauth_mobile`
--
ALTER TABLE `oauth_mobile`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `open_id` (`open_id`) USING BTREE;

--
-- Indexes for table `oauth_weibo`
--
ALTER TABLE `oauth_weibo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `open_id` (`open_id`) USING BTREE;

--
-- Indexes for table `oauth_weixin`
--
ALTER TABLE `oauth_weixin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `open_id` (`open_id`) USING BTREE;

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `blacklist`
--
ALTER TABLE `blacklist`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `oauth_default`
--
ALTER TABLE `oauth_default`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `oauth_device`
--
ALTER TABLE `oauth_device`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `oauth_facebook`
--
ALTER TABLE `oauth_facebook`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `oauth_google`
--
ALTER TABLE `oauth_google`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `oauth_mobile`
--
ALTER TABLE `oauth_mobile`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `oauth_weibo`
--
ALTER TABLE `oauth_weibo`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- 使用表AUTO_INCREMENT `oauth_weixin`
--
ALTER TABLE `oauth_weixin`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
