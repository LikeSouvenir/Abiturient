-- Индексы таблицы `admin_users`
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `atributes`
ALTER TABLE `atributes`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `bundles`
ALTER TABLE `bundles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `establishment_id` (`establishment_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `cluster_id` (`cluster_id`);

-- Индексы таблицы `clusters`
ALTER TABLE `clusters`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `directions`
ALTER TABLE `directions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code_identifier` (`program_code_identifier`) USING HASH;

-- Индексы таблицы `establishments`
ALTER TABLE `establishments`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `metrics`
ALTER TABLE `metrics`
  ADD PRIMARY KEY (`id`);

-- Индексы таблицы `programs`
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`) USING HASH,
  ADD KEY `direction_id` (`direction_id`);
