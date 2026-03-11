1)

                INSERT INTO `atributes` (`id`, `title`, `location`) VALUES
                (101, 'Специальность', 'programs'),
                (102, 'Профессия', 'programs'),
    drop -->    (103, 'Поступление с 2 ОГЭ', 'programs');

2) 

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
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    add -->   `program_swith_2_oge' BOOL DEFAULT FALSE;
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

3)
        
            CREATE TABLE `establishments` (
              `id` int(11) NOT NULL,
              `name` text NOT NULL,
              `logo_path` text DEFAULT NULL,
    drop -->  `address`
              `latitude` text DEFAULT NULL,
              `longitude` text DEFAULT NULL,
    drop -->  `phone` text DEFAULT NULL,
              `website` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp()
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            

    append--> CREATE TABLE `addresses` (
              `id` int(11) NOT NULL,
              `establishment_id` int(11) NOT NULL,
              `address` text NOT NULL,
              `admissions_committee` BOOL DEFAULT FALSE,
              FOREIGN KEY(establishment_id) REFERENCES establishments(id)
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    
    append--> CREATE TABLE `phones` (
              `id` int(11) NOT NULL,
              `establishment_id` int(11) NOT NULL,
              `phone` text NOT NULL,
              `admissions_committee` BOOL DEFAULT FALSE,
              FOREIGN KEY(establishment_id) REFERENCES establishments(id)
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
