-- Структура таблицы `establishments`
CREATE TABLE `establishments` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `logo_path` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` text DEFAULT NULL,
  `longitude` text DEFAULT NULL,
  `phone` text DEFAULT NULL,
  `website` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
