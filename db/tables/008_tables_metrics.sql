-- Структура таблицы `metrics`
CREATE TABLE `metrics` (
  `id` int(11) NOT NULL,
  `timestamp` int(11) DEFAULT NULL,
  `agent` text DEFAULT NULL,
  `refer` text DEFAULT NULL,
  `country` text DEFAULT NULL,
  `lastOnline` int(11) DEFAULT NULL,
  `IPs` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
