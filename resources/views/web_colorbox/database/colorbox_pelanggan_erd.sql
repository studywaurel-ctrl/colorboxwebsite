-- Skema database colorbox_pelanggan
-- Dibuat otomatis dari berkas migration Laravel pada 25-09-2026 17:44
-- Buka di MySQL Workbench: File > Import > Reverse Engineer MySQL Create Script
-- Atau impor langsung lewat phpMyAdmin.

CREATE DATABASE IF NOT EXISTS `colorbox_pelanggan`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `colorbox_pelanggan`;

SET FOREIGN_KEY_CHECKS = 0;

create table `users` (`id` bigint unsigned not null auto_increment primary key, `name` varchar(100) not null, `email` varchar(100) not null, `email_verified_at` timestamp null, `password` varchar(191) not null, `phone` varchar(20) null, `birth_date` date null, `gender` varchar(20) null, `photo` varchar(191) null, `role` varchar(20) not null default 'pelanggan', `store_id` bigint unsigned null, `points` int not null default '0', `member_code` varchar(20) null, `member_level` varchar(30) not null default 'Classic', `remember_token` varchar(100) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `users` add unique `users_email_unique`(`email`);

alter table `users` add unique `users_member_code_unique`(`member_code`);

create table `password_reset_tokens` (`email` varchar(100) not null, `token` varchar(191) not null, `created_at` timestamp null, primary key (`email`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

create table `sessions` (`id` varchar(191) not null, `user_id` bigint unsigned null, `ip_address` varchar(45) null, `user_agent` text null, `payload` longtext not null, `last_activity` int not null, primary key (`id`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `sessions` add index `sessions_user_id_index`(`user_id`);

alter table `sessions` add index `sessions_last_activity_index`(`last_activity`);

create table `cache` (`key` varchar(191) not null, `value` mediumtext not null, `expiration` int not null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

create table `cache_locks` (`key` varchar(191) not null, `owner` varchar(191) not null, `expiration` int not null, primary key (`key`)) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

create table `settings` (`id` bigint unsigned not null auto_increment primary key, `key` varchar(60) not null, `value` varchar(255) null, `description` varchar(191) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `settings` add unique `settings_key_unique`(`key`);

create table `categories` (`id` bigint unsigned not null auto_increment primary key, `parent_id` bigint unsigned null, `name` varchar(60) not null, `slug` varchar(80) not null, `sort_order` int not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `categories` add constraint `categories_parent_id_foreign` foreign key (`parent_id`) references `categories` (`id`) on delete set null;

alter table `categories` add unique `categories_slug_unique`(`slug`);

create table `products` (`id` bigint unsigned not null auto_increment primary key, `category_id` bigint unsigned not null, `name` varchar(150) not null, `slug` varchar(180) not null, `sku` varchar(40) null, `price` int not null, `discount_price` int null, `description` text null, `material` varchar(150) null, `care` varchar(191) null, `collection` varchar(80) null, `is_new` tinyint(1) not null default '0', `is_active` tinyint(1) not null default '1', `weight` int not null default '300', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `products` add constraint `products_category_id_foreign` foreign key (`category_id`) references `categories` (`id`) on delete cascade;

alter table `products` add unique `products_slug_unique`(`slug`);

create table `product_variants` (`id` bigint unsigned not null auto_increment primary key, `product_id` bigint unsigned not null, `color` varchar(40) not null, `color_hex` varchar(10) null, `size` varchar(10) not null, `sku` varchar(50) null, `stock` int not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `product_variants` add constraint `product_variants_product_id_foreign` foreign key (`product_id`) references `products` (`id`) on delete cascade;

create table `product_images` (`id` bigint unsigned not null auto_increment primary key, `product_id` bigint unsigned not null, `color` varchar(40) null, `path` varchar(191) not null, `source_url` varchar(500) null, `sort_order` int not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `product_images` add constraint `product_images_product_id_foreign` foreign key (`product_id`) references `products` (`id`) on delete cascade;

create table `stores` (`id` bigint unsigned not null auto_increment primary key, `code` varchar(10) not null, `name` varchar(120) not null, `city` varchar(60) not null, `address` varchar(191) not null, `floor` varchar(40) null, `phone` varchar(20) null, `latitude` decimal(10, 6) null, `longitude` decimal(10, 6) null, `open_time` time not null default '10:00:00', `close_time` time not null default '22:00:00', `has_ttc` tinyint(1) not null default '1', `has_donation` tinyint(1) not null default '1', `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `stores` add unique `stores_code_unique`(`code`);

create table `store_stocks` (`id` bigint unsigned not null auto_increment primary key, `store_id` bigint unsigned not null, `product_variant_id` bigint unsigned not null, `stock` int not null default '0', `reserved` int not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `store_stocks` add constraint `store_stocks_store_id_foreign` foreign key (`store_id`) references `stores` (`id`) on delete cascade;

alter table `store_stocks` add constraint `store_stocks_product_variant_id_foreign` foreign key (`product_variant_id`) references `product_variants` (`id`) on delete cascade;

alter table `store_stocks` add unique `store_stocks_store_id_product_variant_id_unique`(`store_id`, `product_variant_id`);

create table `addresses` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `label` varchar(40) not null default 'Rumah', `recipient` varchar(100) not null, `phone` varchar(20) not null, `province` varchar(60) not null, `city` varchar(60) not null, `district` varchar(60) null, `postal_code` varchar(10) null, `detail` varchar(191) not null, `landmark` varchar(191) null, `is_primary` tinyint(1) not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `addresses` add constraint `addresses_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

create table `carts` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned null, `session_id` varchar(100) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `carts` add constraint `carts_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

create table `cart_items` (`id` bigint unsigned not null auto_increment primary key, `cart_id` bigint unsigned not null, `product_variant_id` bigint unsigned not null, `quantity` int not null default '1', `selected` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `cart_items` add constraint `cart_items_cart_id_foreign` foreign key (`cart_id`) references `carts` (`id`) on delete cascade;

alter table `cart_items` add constraint `cart_items_product_variant_id_foreign` foreign key (`product_variant_id`) references `product_variants` (`id`) on delete cascade;

alter table `cart_items` add unique `cart_items_cart_id_product_variant_id_unique`(`cart_id`, `product_variant_id`);

create table `wishlists` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `product_id` bigint unsigned not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `wishlists` add constraint `wishlists_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

alter table `wishlists` add constraint `wishlists_product_id_foreign` foreign key (`product_id`) references `products` (`id`) on delete cascade;

alter table `wishlists` add unique `wishlists_user_id_product_id_unique`(`user_id`, `product_id`);

create table `vouchers` (`id` bigint unsigned not null auto_increment primary key, `code` varchar(30) not null, `name` varchar(100) not null, `type` varchar(20) not null default 'percent', `value` int not null, `max_discount` int null, `min_purchase` int not null default '0', `quota` int not null default '100', `used` int not null default '0', `expired_at` date null, `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `vouchers` add unique `vouchers_code_unique`(`code`);

create table `shipping_rates` (`id` bigint unsigned not null auto_increment primary key, `courier` varchar(40) not null, `service` varchar(60) not null, `cost` int not null, `etd_min` int not null default '1', `etd_max` int not null default '3', `is_active` tinyint(1) not null default '1', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

create table `orders` (`id` bigint unsigned not null auto_increment primary key, `order_code` varchar(30) not null, `user_id` bigint unsigned not null, `address_id` bigint unsigned null, `store_id` bigint unsigned null, `fulfillment_method` varchar(20) not null default 'DELIVERY', `voucher_id` bigint unsigned null, `subtotal` int not null default '0', `shipping_cost` int not null default '0', `discount` int not null default '0', `points_used` int not null default '0', `total` int not null default '0', `status` varchar(30) not null default 'PENDING_PAYMENT', `note` varchar(191) null, `paid_at` timestamp null, `picked_up_at` timestamp null, `follow_up_action` varchar(20) null, `follow_up_at` timestamp null, `completed_at` timestamp null, `cancelled_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `orders` add constraint `orders_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

alter table `orders` add constraint `orders_address_id_foreign` foreign key (`address_id`) references `addresses` (`id`) on delete set null;

alter table `orders` add constraint `orders_store_id_foreign` foreign key (`store_id`) references `stores` (`id`) on delete set null;

alter table `orders` add constraint `orders_voucher_id_foreign` foreign key (`voucher_id`) references `vouchers` (`id`) on delete set null;

alter table `orders` add unique `orders_order_code_unique`(`order_code`);

create table `order_items` (`id` bigint unsigned not null auto_increment primary key, `order_id` bigint unsigned not null, `product_variant_id` bigint unsigned null, `product_name` varchar(150) not null, `color` varchar(40) null, `size` varchar(10) null, `image_path` varchar(191) null, `price` int not null, `quantity` int not null, `subtotal` int not null, `reviewed` tinyint(1) not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `order_items` add constraint `order_items_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete cascade;

alter table `order_items` add constraint `order_items_product_variant_id_foreign` foreign key (`product_variant_id`) references `product_variants` (`id`) on delete set null;

create table `payments` (`id` bigint unsigned not null auto_increment primary key, `order_id` bigint unsigned not null, `payment_method` varchar(40) null, `transaction_id` varchar(100) null, `snap_token` varchar(191) null, `snap_url` varchar(255) null, `gross_amount` int not null, `transaction_status` varchar(30) not null default 'pending', `payment_status` varchar(20) not null default 'menunggu', `raw_response` text null, `paid_at` timestamp null, `expired_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `payments` add constraint `payments_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete cascade;

create table `shipments` (`id` bigint unsigned not null auto_increment primary key, `order_id` bigint unsigned not null, `courier` varchar(40) null, `service` varchar(60) null, `tracking_number` varchar(60) null, `cost` int not null default '0', `status` varchar(30) not null default 'menunggu', `shipped_at` timestamp null, `delivered_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `shipments` add constraint `shipments_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete cascade;

create table `tracking_events` (`id` bigint unsigned not null auto_increment primary key, `order_id` bigint unsigned not null, `status` varchar(30) not null, `description` varchar(191) not null, `happened_at` timestamp not null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `tracking_events` add constraint `tracking_events_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete cascade;

create table `reviews` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `product_id` bigint unsigned not null, `order_item_id` bigint unsigned null, `rating` int not null, `comment` text null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `reviews` add constraint `reviews_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

alter table `reviews` add constraint `reviews_product_id_foreign` foreign key (`product_id`) references `products` (`id`) on delete cascade;

alter table `reviews` add constraint `reviews_order_item_id_foreign` foreign key (`order_item_id`) references `order_items` (`id`) on delete set null;

create table `return_requests` (`id` bigint unsigned not null auto_increment primary key, `return_code` varchar(30) not null, `user_id` bigint unsigned not null, `order_id` bigint unsigned null, `store_id` bigint unsigned null, `purchase_code` varchar(40) null, `type` varchar(20) not null default 'tukar', `method` varchar(20) not null default 'online', `reason` varchar(191) not null, `photo` varchar(191) null, `status` varchar(30) not null default 'menunggu_validasi', `admin_note` varchar(191) null, `approved_at` timestamp null, `completed_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `return_requests` add constraint `return_requests_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

alter table `return_requests` add constraint `return_requests_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete set null;

alter table `return_requests` add constraint `return_requests_store_id_foreign` foreign key (`store_id`) references `stores` (`id`) on delete set null;

alter table `return_requests` add unique `return_requests_return_code_unique`(`return_code`);

create table `return_items` (`id` bigint unsigned not null auto_increment primary key, `return_request_id` bigint unsigned not null, `order_item_id` bigint unsigned null, `product_variant_id` bigint unsigned null, `product_name` varchar(150) not null, `quantity` int not null default '1', `note` varchar(191) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `return_items` add constraint `return_items_return_request_id_foreign` foreign key (`return_request_id`) references `return_requests` (`id`) on delete cascade;

alter table `return_items` add constraint `return_items_order_item_id_foreign` foreign key (`order_item_id`) references `order_items` (`id`) on delete set null;

alter table `return_items` add constraint `return_items_product_variant_id_foreign` foreign key (`product_variant_id`) references `product_variants` (`id`) on delete set null;

create table `complaints` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `order_id` bigint unsigned null, `subject` varchar(120) not null, `message` text not null, `status` varchar(20) not null default 'baru', `response` text null, `responded_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `complaints` add constraint `complaints_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

alter table `complaints` add constraint `complaints_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete set null;

create table `donation_slots` (`id` bigint unsigned not null auto_increment primary key, `store_id` bigint unsigned not null, `date` date not null, `start_time` time not null, `end_time` time not null, `quota` int not null default '5', `booked_count` int not null default '0', `status` varchar(20) not null default 'aktif', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `donation_slots` add constraint `donation_slots_store_id_foreign` foreign key (`store_id`) references `stores` (`id`) on delete cascade;

alter table `donation_slots` add unique `slot_donasi_unik`(`store_id`, `date`, `start_time`);

create table `donations` (`id` bigint unsigned not null auto_increment primary key, `donation_code` varchar(30) not null, `user_id` bigint unsigned not null, `donation_slot_id` bigint unsigned not null, `store_id` bigint unsigned not null, `estimated_items` int not null, `accepted_items` int null, `rejected_items` int null, `condition_note` varchar(191) null, `verify_note` varchar(191) null, `status` varchar(30) not null default 'SCHEDULED', `points_awarded` int not null default '0', `verified_by_user_id` bigint unsigned null, `checked_in_at` timestamp null, `verified_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `donations` add constraint `donations_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

alter table `donations` add constraint `donations_donation_slot_id_foreign` foreign key (`donation_slot_id`) references `donation_slots` (`id`) on delete cascade;

alter table `donations` add constraint `donations_store_id_foreign` foreign key (`store_id`) references `stores` (`id`) on delete cascade;

alter table `donations` add constraint `donations_verified_by_user_id_foreign` foreign key (`verified_by_user_id`) references `users` (`id`) on delete set null;

alter table `donations` add unique `donations_donation_code_unique`(`donation_code`);

create table `tap_try_collect_slots` (`id` bigint unsigned not null auto_increment primary key, `store_id` bigint unsigned not null, `date` date not null, `start_time` time not null, `end_time` time not null, `quota` int not null default '4', `booked_count` int not null default '0', `status` varchar(20) not null default 'aktif', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `tap_try_collect_slots` add constraint `tap_try_collect_slots_store_id_foreign` foreign key (`store_id`) references `stores` (`id`) on delete cascade;

alter table `tap_try_collect_slots` add unique `slot_ttc_unik`(`store_id`, `date`, `start_time`);

create table `tap_try_collects` (`id` bigint unsigned not null auto_increment primary key, `booking_code` varchar(30) not null, `user_id` bigint unsigned not null, `tap_try_collect_slot_id` bigint unsigned not null, `store_id` bigint unsigned not null, `source` varchar(20) not null default 'TTC', `order_id` bigint unsigned null, `status` varchar(30) not null default 'SCHEDULED', `purchase_code` varchar(40) null, `total_paid` int not null default '0', `handled_by_user_id` bigint unsigned null, `ready_at` timestamp null, `collected_at` timestamp null, `completed_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `tap_try_collects` add constraint `tap_try_collects_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

alter table `tap_try_collects` add constraint `tap_try_collects_tap_try_collect_slot_id_foreign` foreign key (`tap_try_collect_slot_id`) references `tap_try_collect_slots` (`id`) on delete cascade;

alter table `tap_try_collects` add constraint `tap_try_collects_store_id_foreign` foreign key (`store_id`) references `stores` (`id`) on delete cascade;

alter table `tap_try_collects` add constraint `tap_try_collects_order_id_foreign` foreign key (`order_id`) references `orders` (`id`) on delete set null;

alter table `tap_try_collects` add constraint `tap_try_collects_handled_by_user_id_foreign` foreign key (`handled_by_user_id`) references `users` (`id`) on delete set null;

alter table `tap_try_collects` add unique `tap_try_collects_booking_code_unique`(`booking_code`);

create table `tap_try_collect_items` (`id` bigint unsigned not null auto_increment primary key, `tap_try_collect_id` bigint unsigned not null, `product_variant_id` bigint unsigned not null, `quantity` int not null default '1', `purchased` tinyint(1) not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `tap_try_collect_items` add constraint `tap_try_collect_items_tap_try_collect_id_foreign` foreign key (`tap_try_collect_id`) references `tap_try_collects` (`id`) on delete cascade;

alter table `tap_try_collect_items` add constraint `tap_try_collect_items_product_variant_id_foreign` foreign key (`product_variant_id`) references `product_variants` (`id`) on delete cascade;

create table `point_transactions` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `type` varchar(20) not null, `source` varchar(30) not null, `amount` int not null, `note` varchar(191) null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `point_transactions` add constraint `point_transactions_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

create table `notifications` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned not null, `title` varchar(120) not null, `body` varchar(255) not null, `url` varchar(191) null, `read_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `notifications` add constraint `notifications_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete cascade;

create table `virtual_try_ons` (`id` bigint unsigned not null auto_increment primary key, `user_id` bigint unsigned null, `product_id` bigint unsigned null, `color` varchar(40) null, `size` varchar(10) null, `look_name` varchar(80) null, `saved` tinyint(1) not null default '0', `photo_path` varchar(191) null, `result_path` varchar(191) null, `status` varchar(20) not null default 'DRAFT', `driver` varchar(20) null, `external_id` varchar(100) null, `error_message` varchar(191) null, `processed_at` timestamp null, `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `virtual_try_ons` add constraint `virtual_try_ons_user_id_foreign` foreign key (`user_id`) references `users` (`id`) on delete set null;

alter table `virtual_try_ons` add constraint `virtual_try_ons_product_id_foreign` foreign key (`product_id`) references `products` (`id`) on delete set null;

create table `virtual_try_on_items` (`id` bigint unsigned not null auto_increment primary key, `virtual_try_on_id` bigint unsigned not null, `product_id` bigint unsigned not null, `product_variant_id` bigint unsigned null, `category_slot` varchar(30) not null, `sort_order` int not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci' engine = InnoDB;

alter table `virtual_try_on_items` add constraint `virtual_try_on_items_virtual_try_on_id_foreign` foreign key (`virtual_try_on_id`) references `virtual_try_ons` (`id`) on delete cascade;

alter table `virtual_try_on_items` add constraint `virtual_try_on_items_product_id_foreign` foreign key (`product_id`) references `products` (`id`) on delete cascade;

alter table `virtual_try_on_items` add constraint `virtual_try_on_items_product_variant_id_foreign` foreign key (`product_variant_id`) references `product_variants` (`id`) on delete set null;

SET FOREIGN_KEY_CHECKS = 1;
