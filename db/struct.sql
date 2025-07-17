START TRANSACTION;

CREATE TABLE `employees` (
	`id` varchar(64) NOT NULL,
	`name` text NOT NULL,
	`contact_number` text NOT NULL,
	`email` text NOT NULL,
	`messenger_id` text,
	`avatar` text,
	`description` text,
	`working` boolean NOT NULL DEFAULT true,
	`update_at` timestamp(6) NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP(6),
	`create_at` timestamp(6) NOT NULL DEFAULT (now()),
	CONSTRAINT `employees_id` PRIMARY KEY(`id`)
);

CREATE TABLE `customers` (
	`id` varchar(64) NOT NULL,
	`name` text NOT NULL,
	`contact_number` text NOT NULL,
	`email` text NOT NULL,
	`messenger_id` text,
	`description` text,
	`update_at` timestamp(6) NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP(6),
	`create_at` timestamp(6) NOT NULL DEFAULT (now()),
	CONSTRAINT `customers_id` PRIMARY KEY(`id`)
);

CREATE TABLE `items` (
	`id` varchar(64) NOT NULL,
	`belonged_customer_id` varchar(64) NOT NULL,
	`brand` text,
	`model` text,
	`name` text,
	`serial` text,
	`update_at` timestamp(6) NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP(6),
	`create_at` timestamp(6) NOT NULL DEFAULT (now()),
	CONSTRAINT `items_id` PRIMARY KEY(`id`)
);

CREATE TABLE `order_item_map` (
	`order_id` varchar(64) NOT NULL,
	`item_id` varchar(64) NOT NULL,
	CONSTRAINT `order_item_map_order_id_item_id_pk` PRIMARY KEY(`order_id`,`item_id`)
);

CREATE TABLE `orders` (
	`id` varchar(64) NOT NULL,
	`rms_code` varchar(35),
	`description` text,
	`update_at` timestamp(6) NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP(6),
	`create_at` timestamp(6) NOT NULL DEFAULT (now()),
	CONSTRAINT `orders_id` PRIMARY KEY(`id`)
);

CREATE TABLE `procedures` (
	`id` varchar(64) NOT NULL,
	`order_id` varchar(64) NOT NULL,
	`state_code` smallint NOT NULL,
	`update_at` timestamp(6) NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP(6),
	`create_at` timestamp(6) NOT NULL DEFAULT (now()),
	CONSTRAINT `procedures_id` PRIMARY KEY(`id`)
);

CREATE TABLE `sessions` (
	`id` varchar(64) NOT NULL,
	`employee_id` varchar(64) NOT NULL,
	`update_at` timestamp(6) NOT NULL DEFAULT (now()) ON UPDATE CURRENT_TIMESTAMP(6),
	`create_at` timestamp(6) NOT NULL DEFAULT (now()),
	CONSTRAINT `sessions_id` PRIMARY KEY(`id`)
);

CREATE TABLE `state_incompletes` (
	`state_id` varchar(64) NOT NULL,
	`reason` text,
	CONSTRAINT `state_incompletes_state_id` PRIMARY KEY(`state_id`)
);

CREATE TABLE `state_payments` (
	`state_id` varchar(64) NOT NULL,
	`amount` real NOT NULL,
	CONSTRAINT `state_payments_state_id` PRIMARY KEY(`state_id`)
);

CREATE TABLE `state_processings` (
	`state_id` varchar(64) NOT NULL,
	`employee_id` varchar(64) NOT NULL,
	`reason` text,
	CONSTRAINT `state_processings_state_id` PRIMARY KEY(`state_id`)
);

CREATE TABLE `state_user_cancels` (
	`state_id` varchar(64) NOT NULL,
	`reason` text,
	CONSTRAINT `state_user_cancels_state_id` PRIMARY KEY(`state_id`)
);

ALTER TABLE `items` ADD CONSTRAINT `items_belonged_customer_id_customers_id_fk` FOREIGN KEY (`belonged_customer_id`) REFERENCES `customers`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `order_item_map` ADD CONSTRAINT `order_item_map_order_id_orders_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `order_item_map` ADD CONSTRAINT `order_item_map_item_id_items_id_fk` FOREIGN KEY (`item_id`) REFERENCES `items`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `procedures` ADD CONSTRAINT `procedures_order_id_orders_id_fk` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `sessions` ADD CONSTRAINT `sessions_employee_id_employees_id_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `state_incompletes` ADD CONSTRAINT `state_incompletes_state_id_procedures_id_fk` FOREIGN KEY (`state_id`) REFERENCES `procedures`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `state_payments` ADD CONSTRAINT `state_payments_state_id_procedures_id_fk` FOREIGN KEY (`state_id`) REFERENCES `procedures`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `state_processings` ADD CONSTRAINT `state_processings_state_id_procedures_id_fk` FOREIGN KEY (`state_id`) REFERENCES `procedures`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `state_processings` ADD CONSTRAINT `state_processings_employee_id_employees_id_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE cascade ON UPDATE no action;
ALTER TABLE `state_user_cancels` ADD CONSTRAINT `state_user_cancels_state_id_procedures_id_fk` FOREIGN KEY (`state_id`) REFERENCES `procedures`(`id`) ON DELETE cascade ON UPDATE no action;

COMMIT;