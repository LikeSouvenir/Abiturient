-- Структура таблицы `atributes`
CREATE TABLE `atributes` (
  `id` int(11) NOT NULL,
  `title` text DEFAULT NULL,
  `location` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
