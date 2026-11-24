
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(60) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `supplier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `products` (`id`, `sku`, `name`, `category`, `price`, `stock`, `low_stock_threshold`, `supplier_id`, `created_at`) VALUES
(1, 'SKU-1001', 'Wireless Mouse', 'Accessories', 450.00, 25, 5, 1, '2026-10-27 23:48:40'),
(2, 'SKU-1002', 'USB Keyboard', 'Accessories', 650.00, 12, 4, 1, '2026-10-27 23:48:40'),
(3, 'SKU-1003', 'USB-C Cable', 'Cables', 180.00, 15, 5, 1, '2026-10-27 23:48:40'),
(4, 'SKU-1004', 'Wired Mouse', 'Accessories', 300.00, 15, 5, NULL, '2026-10-28 00:29:43');



CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `sku` varchar(60) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `sold_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `contact_person` varchar(120) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `suppliers` (`id`, `company_name`, `contact_person`, `phone`, `email`, `address`, `created_at`) VALUES
(2, 'Arnold Washington', 'Dex Yui', '12342244124', 'arnoldwashington@dc.docx', 'Batangas City, Batangas', '2026-10-28 00:23:49');



CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`);


ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;


ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;


ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

