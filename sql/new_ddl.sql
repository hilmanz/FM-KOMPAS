
CREATE TABLE ffgame.newsletter (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `subject` varchar(140) DEFAULT NULL,
  `content` text,
  `created_dt` datetime DEFAULT NULL,
  `last_send` datetime DEFAULT NULL,
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->pending, 1->sent, 2->resent',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `fantasy`.`teams` ADD INDEX `IDX_USER_ID` (`user_id`);

ALTER TABLE `fantasy`.`merchandise_items`     ADD COLUMN `merchandise_type` INT(3) DEFAULT '0' NULL COMMENT '0-> non-digital, 1-> digital' AFTER `stock`;
ALTER TABLE `fantasy`.`merchandise_items`     ADD COLUMN `perk_id` INT(3) DEFAULT '0' NULL AFTER `merchandise_type`;

CREATE TABLE ffgame_stats.rank_update_history (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `last_update` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ffgame_stats.job_queue_rank (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `game_id` varchar(31) DEFAULT NULL,
  `since_id` bigint(21) DEFAULT '0',
  `until_id` bigint(21) DEFAULT '0',
  `worker_id` int(11) DEFAULT '0',
  `queue_dt` datetime DEFAULT NULL,
  `finished_dt` datetime DEFAULT NULL,
  `current_id` bigint(21) DEFAULT '0' COMMENT 'latest id been processed.',
  `n_done` int(11) DEFAULT '0',
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->pending, 1-> in process, 2->done',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IDX_JOB_CLUSTER` (`game_id`,`since_id`,`until_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#index ini diperlukan untuk memproses rank_and_points.worker.js
ALTER TABLE `ffgame_stats`.`game_team_extra_points` ADD INDEX `IDX_GAME_TEAM_ID` (`game_team_id`);


CREATE TABLE ffgame.digital_perks (
  `id` BIGINT(21) NOT NULL AUTO_INCREMENT,
  `game_team_id` BIGINT(21) DEFAULT NULL,
  `master_perk_id` BIGINT(21) DEFAULT NULL,
  `redeem_dt` DATETIME DEFAULT NULL,
  `last_use_dt` DATETIME DEFAULT NULL,
  `available` INT(3) DEFAULT '1' COMMENT 'how many weeks these perk can be used.',
  `n_status` TINYINT(3) DEFAULT '1' COMMENT '0-> disabled, 1->enabled',
  PRIMARY KEY (`id`),
  UNIQUE KEY `IDX_GAME_TEAM_ID` (`game_team_id`,`master_perk_id`),
  KEY `IDX_AVAILABLE` (`game_team_id`,`available`,`n_status`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


CREATE TABLE ffgame.coupon_codes (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint(21) DEFAULT NULL,
  `coupon_code` varchar(13) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `redeem_dt` datetime DEFAULT NULL,
  `paid_dt` datetime DEFAULT NULL,
  `game_team_id` bigint(21) DEFAULT NULL,
  `paid` tinyint(3) DEFAULT '0' COMMENT '0->unpaid, 1->paid',
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->unused, 1->used',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_CODE` (`coupon_code`),
  KEY `IDX_GAME_TEAM_ID` (`game_team_id`),
  KEY `IDX_STATUS` (`coupon_code`,`n_status`,`coupon_id`,`paid`),
  KEY `IDX_AVAILABLE` (`coupon_id`,`paid`,`n_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE ffgame.coupons (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `vendor_name` varchar(140) DEFAULT NULL,
  `service_name` varchar(140) DEFAULT NULL,
  `description` text,
  `coin_amount` int(11) DEFAULT '0',
  `ss_dollar` int(11) DEFAULT '0',
  `img` varchar(140) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `n_status` tinyint(3) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `IDX_CREATOR` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `ffgame_stats`.`job_queue` ADD INDEX `IDX_STATUS` (`n_status`);
ALTER TABLE `ffgame_stats`.`job_queue_rank` ADD INDEX `IDX_STATUS` (`n_status`);

ALTER TABLE `fantasy`.`merchandise_orders` ADD COLUMN `email` VARCHAR(140) NULL AFTER `last_name`;

ALTER TABLE `ffgame_stats`.`master_match_player_points` ADD INDEX `IDX_PLAYER_ID` (`player_id`);


CREATE TABLE `ffgame`.`digital_perks_group`(     `id` BIGINT(21) NOT NULL AUTO_INCREMENT ,     `master_perk_id` BIGINT(21) ,     `category` VARCHAR(32) ,     PRIMARY KEY (`id`)  );

ALTER TABLE `ffgame`.`digital_perks_group` ADD UNIQUE `UNIQUE_GROUP` (`master_perk_id`, `category`);


ALTER TABLE `fantasy`.`merchandise_orders` ADD COLUMN `data` TEXT NULL AFTER `notes`;
ALTER TABLE `fantasy`.`merchandise_orders` ADD COLUMN `payment_method` VARCHAR(12) DEFAULT 'coins' NULL AFTER `data`,     ADD COLUMN `total_sale` INT(11) DEFAULT '0' NULL AFTER `payment_method`;

ALTER TABLE `fantasy`.`merchandise_orders` ADD COLUMN `trace_code` VARCHAR(30) NULL AFTER `total_sale`;

ALTER TABLE `fantasy`.`merchandise_orders`     ADD COLUMN `fb_id` BIGINT(21) NULL AFTER `id`;

ALTER TABLE `fantasy`.`merchandise_orders` ADD INDEX `IDX_FB` (`fb_id`);

ALTER TABLE `fantasy`.`merchandise_orders`    ADD COLUMN `ongkir_id` INT(11) DEFAULT '0' NULL AFTER `trace_code`;

ALTER TABLE `fantasy`.`merchandise_orders` ADD INDEX `IDX_CITY_ID` (`ongkir_id`);
ALTER TABLE `fantasy`.`merchandise_orders`     ADD COLUMN `ongkir_value` INT(11) DEFAULT '0' NULL AFTER `ongkir_id`;

CREATE TABLE fantasy.ongkir (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city` varchar(64) DEFAULT NULL,
  `kecamatan` varchar(64) DEFAULT NULL,
  `province` varchar(64) DEFAULT NULL,
  `cost` int(11) DEFAULT '10000',
  PRIMARY KEY (`id`),
  KEY `IDX_CITY` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `fantasy`.`notifications` ADD COLUMN `msg_id` VARCHAR(140) NULL AFTER `game_team_id`;
ALTER TABLE `fantasy`.`notifications` ADD UNIQUE `UNIQUE_MSG_ID` (`game_team_id`, `msg_id`);

ALTER TABLE `fantasy`.`merchandise_items`     ADD COLUMN `weight` FLOAT(4,2) DEFAULT '1.0' NULL AFTER `stock`;


CREATE TABLE ffgame.game_bet_winners (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `game_id` varchar(32) DEFAULT NULL,
  `game_team_id` bigint(21) DEFAULT NULL,
  `score` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_USER` (`game_id`,`game_team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE ffgame.game_bets (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `game_id` varchar(32) DEFAULT NULL,
  `game_team_id` bigint(21) DEFAULT NULL,
  `bet_name` varchar(64) DEFAULT NULL,
  `home` int(3) DEFAULT '0',
  `away` int(3) DEFAULT '0',
  `coins` int(5) DEFAULT '0',
  `submit_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_BET` (`game_id`,`game_team_id`,`bet_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE fantasy.merchandise_item_perks (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `merchandise_item_id` bigint(21) DEFAULT NULL,
  `perk_id` bigint(21) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_ITEM_PERK` (`merchandise_item_id`,`perk_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `fantasy`.`merchandise_items` ADD COLUMN `enable_admin_fee` TINYINT(3) DEFAULT '1' NULL AFTER `perk_id`;

ALTER TABLE `fantasy`.`merchandise_items`     ADD COLUMN `admin_fee` INT(8) DEFAULT '0' NULL AFTER `enable_admin_fee`,     ADD COLUMN `enable_ongkir` TINYINT(3) DEFAULT '1' NULL AFTER `admin_fee`;


CREATE TABLE ffgame.add_coin_history (
  `id` BIGINT(21) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(140) DEFAULT NULL,
  `team_ids` LONGTEXT,
  `amount` INT(11) DEFAULT '0',
  `post_dt` DATETIME DEFAULT NULL,
  `n_status` TINYINT(3) DEFAULT '0' COMMENT '0->failed, 1->success',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;


ALTER TABLE `fantasy`.`merchandise_items`     ADD COLUMN `data` TEXT NULL AFTER `weight`;
ALTER TABLE `fantasy`.`merchandise_orders`     ADD COLUMN `ktp` VARCHAR(30) NULL AFTER `last_name`;




CREATE TABLE fantasy.merchandise_vouchers (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `merchandise_order_id` bigint(21) DEFAULT NULL,
  `merchandise_item_id` bigint(21) DEFAULT NULL,
  `voucher_code` varchar(24) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->blm di download, 1-> sudah didownload',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_VOUCHER_CODE` (`voucher_code`),
  KEY `IDX_VOUCHER` (`merchandise_order_id`,`merchandise_item_id`,`voucher_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `fantasy`.`merchandise_vouchers`     CHANGE `voucher_code` `voucher_code` VARCHAR(140) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;


ALTER TABLE `fantasy`.`merchandise_items`     ADD COLUMN `parent_id` BIGINT(21) DEFAULT '0' NULL AFTER `id`;
ALTER TABLE `fantasy`.`merchandise_items` ADD INDEX `IDX_PARENT_ID` (`parent_id`);


CREATE TABLE fantasy.agents (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(42) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `secret` varchar(64) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `address` text,
  `n_status` tinyint(3) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_EMAIL` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE fantasy.agent_vouchers (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `agent_order_id` bigint(21) DEFAULT NULL,
  `merchandise_item_id` bigint(21) DEFAULT NULL,
  `voucher_code` varchar(140) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->blm di download, 1-> sudah didownload',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_VOUCHER_CODE` (`voucher_code`),
  KEY `IDX_VOUCHER` (`agent_order_id`,`merchandise_item_id`,`voucher_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE fantasy.agent_orders (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) DEFAULT NULL,
  `fb_id` bigint(21) DEFAULT NULL,
  `po_number` varchar(40) DEFAULT NULL,
  `transaction_id` varchar(40) DEFAULT NULL COMMENT 'this is the actual Purchase Order Number',
  `merchandise_item_id` bigint(21) DEFAULT NULL,
  `game_team_id` bigint(21) DEFAULT NULL,
  `user_id` bigint(21) DEFAULT NULL,
  `first_name` varchar(30) DEFAULT NULL,
  `last_name` varchar(30) DEFAULT NULL,
  `ktp` varchar(30) DEFAULT NULL,
  `email` varchar(140) DEFAULT NULL,
  `address` text,
  `phone` varchar(40) DEFAULT NULL,
  `city` varchar(40) DEFAULT NULL,
  `province` varchar(40) DEFAULT NULL,
  `country` varchar(40) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `order_type` tinyint(3) DEFAULT '0' COMMENT '0->using in-game currency, 1-> using credit, 2->using real money',
  `order_date` datetime DEFAULT NULL,
  `notes` text,
  `data` text,
  `payment_method` varchar(12) DEFAULT 'coins',
  `total_sale` int(11) DEFAULT '0',
  `trace_code` varchar(30) DEFAULT NULL,
  `ongkir_id` bigint(21) DEFAULT NULL,
  `ongkir_value` int(21) DEFAULT '0',
  `n_status` tinyint(3) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_PO` (`po_number`),
  KEY `IDX_ORDER_ITEMS` (`po_number`,`merchandise_item_id`,`game_team_id`,`user_id`),
  KEY `IDX_TRANSACTION_ID` (`transaction_id`),
  KEY `IDX_FB` (`fb_id`),
  KEY `IDX_ONGKIR_CITY_ID` (`ongkir_id`),
  KEY `IDX_AGENT_ID` (`agent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE fantasy.agent_vouchers (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `agent_order_id` bigint(21) DEFAULT NULL,
  `merchandise_item_id` bigint(21) DEFAULT NULL,
  `voucher_code` varchar(140) DEFAULT NULL,
  `created_dt` datetime DEFAULT NULL,
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->blm di download, 1-> sudah didownload',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_VOUCHER_CODE` (`voucher_code`),
  KEY `IDX_VOUCHER` (`agent_order_id`,`merchandise_item_id`,`voucher_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;




CREATE TABLE fantasy.agent_requests (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) DEFAULT NULL,
  `merchandise_item_id` bigint(11) DEFAULT NULL,
  `request_quota` int(5) DEFAULT '0',
  `request_date` datetime DEFAULT NULL,
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->pending, 1->approved, 2->rejected',
  PRIMARY KEY (`id`),
  KEY `IDX_AGENT_ITEM_REQUEST` (`agent_id`,`merchandise_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE fantasy.agent_items (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) DEFAULT NULL,
  `merchandise_item_id` bigint(11) DEFAULT NULL,
  `qty` int(5) DEFAULT '0',
  `n_status` tinyint(3) DEFAULT '0',
  `last_update` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_ITEMS` (`agent_id`,`merchandise_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `fantasy`.`agent_vouchers`     ADD COLUMN `agent_id` INT(11) NULL AFTER `id`;
ALTER TABLE `fantasy`.`agent_vouchers` DROP KEY `IDX_VOUCHER`, ADD INDEX `IDX_VOUCHER` (`agent_id`, `agent_order_id`, `merchandise_item_id`, `voucher_code`);

CREATE TABLE fantasy.agent_returned_stocks (
  `id` bigint(21) NOT NULL AUTO_INCREMENT,
  `agent_id` int(11) DEFAULT NULL,
  `merchandise_item_id` bigint(11) DEFAULT NULL,
  `returned_quota` int(5) DEFAULT '0',
  `request_date` datetime DEFAULT NULL,
  `n_status` tinyint(3) DEFAULT '0' COMMENT '0->pending, 1->approved, 2->rejected',
  PRIMARY KEY (`id`),
  KEY `IDX_AGENT_ITEM_REQUEST` (`agent_id`,`merchandise_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `fantasy`.`users` 
ADD COLUMN `password` VARCHAR(64) NULL AFTER `email`;

ALTER TABLE `fantasy`.`users` 
ADD COLUMN `secret` VARCHAR(32) NULL AFTER `password`;

ALTER TABLE `fantasy`.`users` 
ADD COLUMN `fb_id_ori` VARCHAR(32) NULL AFTER `id`;

ALTER TABLE `fantasy`.`users` 
ADD UNIQUE INDEX `fb_id_ori_UNIQUE` (`fb_id_ori` ASC);

ALTER TABLE `ffgame`.`game_fixtures` 
ADD COLUMN `group_name` VARCHAR(3) NULL AFTER `match_date`;

ALTER TABLE `fantasy`.`users` 
ADD COLUMN `activation_code` VARCHAR(64) NULL AFTER `birthdate`;