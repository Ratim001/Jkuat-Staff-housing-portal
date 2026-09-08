-- Initial housing schema for an EMPTY development database.
-- Structure only: no accounts, passwords, applicants, tenants, or transaction records.
-- After importing, run php migrations/run_migrations.php once for current features.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `applicants` (
  `applicant_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each applicant',
  `pf_no` varchar(100) NOT NULL COMMENT 'PF number of the applicant',
  `name` varchar(100) NOT NULL COMMENT 'Name of the applicant',
  `email` varchar(100) NOT NULL COMMENT 'Email of the applicant',
  `contact` varchar(100) NOT NULL COMMENT 'Contact of the applicant',
  `password` varchar(225) NOT NULL COMMENT 'The applicant’s hashed password',
  `username` varchar(100) NOT NULL COMMENT 'username of the applicant',
  `date_created` datetime DEFAULT current_timestamp() COMMENT 'Date the applicant was created',
  `next_of_kin_name` varchar(255) NOT NULL DEFAULT '',
  `next_of_kin_contact` varchar(50) NOT NULL DEFAULT '',
  `is_email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `name of next of kin` varchar(255) DEFAULT NULL,
  `next of kin contact` varchar(50) DEFAULT NULL,
  `ballot_number` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`applicant_id`),
  UNIQUE KEY `ux_applicants_pf_no` (`pf_no`),
  UNIQUE KEY `ux_applicants_username` (`username`),
  UNIQUE KEY `idx_ballot_number` (`ballot_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `applications` (
  `application_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each application',
  `applicant_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each applicant as foreign key',
  `category` varchar(100) NOT NULL COMMENT 'The type of house they are applying for',
  `house_no` varchar(100) NOT NULL COMMENT 'House number of the house applied for',
  `status` enum('pending','approved','rejected','cancelled','won') DEFAULT 'pending',
  `date` datetime NOT NULL COMMENT 'Date the application was applied',
  PRIMARY KEY (`application_id`),
  KEY `applicationt_id_ibfk_1` (`applicant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `balloting` (
  `ballot_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each ballot entry',
  `applicant_id` varchar(100) NOT NULL COMMENT 'References the applicant from the Applicant table',
  `house_id` varchar(100) NOT NULL COMMENT 'References the house from the House table',
  `ballot_no` varchar(100) NOT NULL COMMENT 'A unique ballot number',
  `date_of_ballot` datetime NOT NULL COMMENT 'The date on which the ballot will be conducted',
  `status` enum('declined','accepted','') NOT NULL COMMENT 'Status of the ballot (declined, accepted)',
  PRIMARY KEY (`ballot_id`),
  UNIQUE KEY `unique ballot no` (`ballot_no`),
  KEY `applicant_id_FK` (`applicant_id`),
  KEY `house_id_FK` (`house_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ballot_control` (
  `id` int(11) NOT NULL DEFAULT 1,
  `is_open` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bills` (
  `bill_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each bill',
  `service_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each service',
  `type_of_bill` varchar(100) NOT NULL COMMENT 'The of bill (from a billable service requested by a tenant)',
  `amount` int(11) NOT NULL COMMENT 'The amount to be paid to settle the bill',
  `date_billed` datetime NOT NULL COMMENT 'The date a tenant is billed',
  `date_settled` date DEFAULT NULL,
  `status` enum('paid','not paid','') NOT NULL COMMENT 'The status of the bill (paid, not paid)',
  `statuses` enum('active','disputed') DEFAULT 'active' COMMENT 'current status of the bill',
  PRIMARY KEY (`bill_id`),
  KEY `service_id_FK` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bill_update_logs` (
  `bill_update_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each bill updated',
  `user_id` varchar(100) NOT NULL COMMENT 'Unique identifier of the user (the admin involved in the update of the house)',
  `bill_id` varchar(100) NOT NULL COMMENT 'Unique identifier for the bills',
  `device_type` varchar(100) NOT NULL COMMENT 'The identification and type of device involved in the update (computer, laptop, phone)',
  `details` text NOT NULL COMMENT 'The whole details of what the update was all about (if the bill was settled, if the amount to be paid was increased, if the bill to the tenant was revoked)',
  `old_amount` decimal(10,2) NOT NULL COMMENT 'The previous amount before update',
  `new_amount` decimal(10,2) NOT NULL COMMENT 'New amount after updating the bill amount',
  `date_updated` datetime NOT NULL COMMENT 'The date and the exact time the bill was updated',
  PRIMARY KEY (`bill_update_id`),
  KEY `bill_id_FK` (`bill_id`),
  KEY `user_id_FK2` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `houses` (
  `house_id` varchar(100) NOT NULL COMMENT 'Unique identifier for the houses',
  `house_no` varchar(100) NOT NULL COMMENT 'unique house number for each house',
  `category` varchar(100) NOT NULL COMMENT 'The type of house according to number of bedrooms it has',
  `date` datetime NOT NULL COMMENT 'The date each house was created',
  `creator` varchar(100) NOT NULL COMMENT 'The one responsible for the creation of the house',
  `rent` int(11) NOT NULL COMMENT 'The amount to be paid as rent for each house',
  `status` enum('vacant','occupied','reserved','') NOT NULL COMMENT 'Current availability status of each house (vacant, occupied, reserved)',
  PRIMARY KEY (`house_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `house_update_logs` (
  `house_update_id` varchar(100) NOT NULL,
  `user_id` varchar(100) NOT NULL,
  `house_id` varchar(100) NOT NULL,
  `device_type` varchar(255) DEFAULT NULL,
  `details` text NOT NULL,
  `date_updated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`house_update_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notices` (
  `notice_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each notice sent',
  `tenant_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each tenant',
  `details` varchar(500) NOT NULL COMMENT 'The content of the notice sent',
  `date_sent` datetime NOT NULL COMMENT 'The date the notice was sent ',
  `date_received` datetime NOT NULL COMMENT 'The date the notice was received ',
  `notice_end_date` datetime NOT NULL COMMENT 'The date a tenant prefers to vacate',
  `status` enum('active','revoked') NOT NULL COMMENT 'Current status of the notice',
  PRIMARY KEY (`notice_id`),
  KEY `tenant_id_FK2` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` varchar(100) NOT NULL COMMENT 'Unique identifier of each notification',
  `user_id` varchar(100) NOT NULL COMMENT 'Sender’s id as a foreign key',
  `recipient_type` varchar(100) NOT NULL COMMENT 'Type of recipient (tenant or applicant)',
  `recipient_id` varchar(100) NOT NULL COMMENT 'The recipient’s id',
  `message` varchar(300) NOT NULL COMMENT 'The details of the message ',
  `date_sent` datetime NOT NULL COMMENT 'Date notification sent',
  `date_received` datetime NOT NULL COMMENT 'Date notification received',
  `status` enum('unread','read') NOT NULL DEFAULT 'unread' COMMENT 'Status of notification',
  PRIMARY KEY (`notification_id`),
  KEY `user_id_FK` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `service_requests` (
  `service_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each service requested',
  `tenant_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each tenant',
  `type_of_service` varchar(200) NOT NULL COMMENT 'The type of service requested (a tenant will be able to input the particular service)',
  `bill_amount` int(11) NOT NULL COMMENT 'The amount to be paid if the service requested is deemed payable',
  `date` datetime NOT NULL COMMENT 'The date the service was requested',
  `status` enum('pending','done','') NOT NULL COMMENT 'The status of the service requested (pending, done)',
  `details` varchar(100) NOT NULL COMMENT 'Details for the service requested',
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tenants` (
  `tenant_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each tenant',
  `applicant_id` varchar(100) NOT NULL COMMENT 'Unique identifier for each applicant',
  `house_no` varchar(100) NOT NULL COMMENT 'A unique number for each house',
  `move_in_date` datetime NOT NULL COMMENT 'The date a tenant moves in to occupy the house',
  `move_out_date` datetime NOT NULL COMMENT 'The date a tenant decides to vacate the house',
  `status` enum('active','terminated') NOT NULL COMMENT 'Current status of the tenant',
  PRIMARY KEY (`tenant_id`),
  KEY `applicant_id_FK2` (`applicant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` varchar(100) NOT NULL COMMENT 'Unique identifier of the user',
  `pf_no` varchar(100) NOT NULL COMMENT 'PF number of the user',
  `username` varchar(225) NOT NULL COMMENT 'The username of the user',
  `name` varchar(225) NOT NULL COMMENT 'The name of the user',
  `email` varchar(255) NOT NULL COMMENT 'The email of the user',
  `role` varchar(100) NOT NULL COMMENT 'Role of the user (ICT admin, CS admin, SUPER admin)',
  `password` varchar(225) NOT NULL COMMENT 'Hashed Password of the user',
  `date_created` datetime NOT NULL COMMENT 'The date a user was created',
  `status` varchar(100) NOT NULL COMMENT 'Current state of the user (active,\r\n  De active)\r\n',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `applications`
  ADD CONSTRAINT `applicationt_id_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `balloting`
  ADD CONSTRAINT `applicant_id_FK` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `house_id_FK` FOREIGN KEY (`house_id`) REFERENCES `houses` (`house_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `bills`
  ADD CONSTRAINT `service_id_FK` FOREIGN KEY (`service_id`) REFERENCES `service_requests` (`service_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `bill_update_logs`
  ADD CONSTRAINT `bill_id_FK` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`bill_id`),
  ADD CONSTRAINT `user_id_FK2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `notices`
  ADD CONSTRAINT `tenant_id_FK2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`tenant_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `user_id_FK` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tenants`
  ADD CONSTRAINT `applicant_id_FK2` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`applicant_id`) ON UPDATE CASCADE;
