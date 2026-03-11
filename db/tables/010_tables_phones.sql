CREATE TABLE `phones` (
  `id` int(11) NOT NULL,
  `establishment_id` int(11) NOT NULL,
  `phone` text NOT NULL,
  `admissions_committee` BOOL DEFAULT FALSE,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
