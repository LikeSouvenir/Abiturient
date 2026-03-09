-- Структура таблицы `programs`
CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `direction_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `program_code` text NOT NULL,
  `keywords` text DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `attributes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
