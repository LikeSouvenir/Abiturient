-- Структура таблицы `directions`
CREATE TABLE `directions` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `program_code_identifier` text NOT NULL,
  `image_path` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
