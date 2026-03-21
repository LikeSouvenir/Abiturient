CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `establishment_id` int(11) NOT NULL,
  `address` text NOT NULL,
  `admissions_committee` BOOL DEFAULT FALSE,
  `latitude` text DEFAULT NULL,
  `longitude` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
