-- Структура таблицы `bundles`
CREATE TABLE `bundles` (
  `id` int(11) NOT NULL,
  `establishment_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `education_type` text DEFAULT NULL,
  `education_base` text DEFAULT NULL,
  `duration` text DEFAULT NULL,
  `program_address` text DEFAULT NULL,
  `program_latitude` text DEFAULT NULL,
  `program_longitude` text DEFAULT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
