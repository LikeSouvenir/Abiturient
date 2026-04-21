<div class="link-item"
    data-id="<?= $link['id'] ?>"
    data-search-text="<?= htmlspecialchars($link['search_text'] ?? '') ?>"
    data-lat="<?= htmlspecialchars($link['latitude'] ?? '') ?>"
    data-lon="<?= htmlspecialchars($link['longitude'] ?? '') ?>">

    <div class="college-logo">
        <img src="<?= htmlspecialchars($link['college_logo_path'] ?? '') ?>"
            alt="Логотип <?= htmlspecialchars($link['college_name'] ?? '') ?>">
    </div>

    <div class="link-details">
        <!-- Название колледжа -->
        <span class="program-name-in-card"><?= htmlspecialchars($link['program_name_in_bundle'] ?? '') ?></span>

        <!-- Блок контактов приемной комиссии -->
        <div class="contacts-block">
            <strong>Контакты приемной комиссии</strong>

            <?php
            $has_contacts = false;

            // Собираем адреса и телефоны в пары (если есть структура "адрес - телефон")
            $addresses = !empty($link['program_address']) ? explode(';', $link['program_address']) : [];
            $phones = !empty($link['phone']) ? explode("\n", $link['phone']) : [];

            // Очищаем адреса от лишних пробелов и сокращаем СПб
            $addresses = array_map(function ($addr) {
                $addr = trim($addr);
                $addr = str_replace(['г. Санкт-Петербург', 'Санкт-Петербург'], 'СПб', $addr);
                return $addr;
            }, array_filter($addresses));

            // Очищаем телефоны
            $phones = array_map('trim', array_filter($phones));

            // Определяем максимальное количество элементов
            $max_count = max(count($addresses), count($phones), 1);

            for ($i = 0; $i < $max_count; $i++):
                $address = $addresses[$i] ?? '';
                $phone = $phones[$i] ?? '';

                if (!empty($address) || !empty($phone)):
                    $has_contacts = true;
                    $parts = [];
                    if (!empty($address)):
                        $parts[] = $address;
                    endif;
                    if (!empty($phone)):
                        $parts[] = $phone;
                    endif;
            ?>
                    <p><?= htmlspecialchars(implode(', ', $parts)) ?></p>
                <?php
                endif;
            endfor;

            // Если остались телефоны без адресов (если телефонов больше чем адресов)
            for ($i = $max_count; $i < count($phones); $i++):
                if (!empty($phones[$i])):
                    $has_contacts = true;
                ?>
                    <p><?= htmlspecialchars($phones[$i]) ?></p>
            <?php
                endif;
            endfor;

            if (!$has_contacts):
                echo '<p style="color: #999;">Контактная информация отсутствует</p>';
            endif;
            ?>

            <!-- Сайт -->
            <?php if (!empty($link['website'])): ?>
                <p class="college-website">
                    <a href="<?= htmlspecialchars($link['website']) ?>" target="_blank" rel="noopener noreferrer">
                        <?= htmlspecialchars($link['website']) ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Пустая строка (отбивка) -->
        <?php if (!empty($link['website']) || $has_contacts): ?>
            <div style="height: 1em;"></div>
        <?php endif; ?>

        <!-- Финансирование -->
        <?php if (!empty($link['education_type']) && strpos($link['education_type'], 'бюджет') !== false): ?>
            <p><strong>Обучение по программе финансируется за счет бюджетных средств</strong></p>
        <?php endif; ?>

        <!-- На базе -->
        <?php if (!empty($link['base_level'])): ?>
            <p><strong>На базе:</strong> <?= htmlspecialchars($link['base_level']) ?></p>
        <?php endif; ?>

        <!-- Срок обучения -->
        <?php if (!empty($link['duration'])): ?>
            <p><strong>Срок обучения:</strong> <?= htmlspecialchars($link['duration']) ?></p>
        <?php endif; ?>

        <!-- Адреса проведения программы (оставляем как отдельный блок) -->
        <?php if (!empty($link['program_address'])): ?>
            <p>
                <strong>Обучение по адресу:</strong>
                <?php
                $addresses = explode(';', $link['program_address']);
                $processed_addresses = array_map(function ($addr) {
                    $addr = trim($addr);
                    $addr = str_replace(['г. Санкт-Петербург', 'Санкт-Петербург'], 'СПб', $addr);
                    return $addr;
                }, array_filter($addresses));
                echo htmlspecialchars(implode('; ', $processed_addresses));
                ?>
            </p>
        <?php endif; ?>

        <!-- Теги (лейблы) -->
        <div class="tags-container">
            <?php if (!empty($link['education_type'])): ?>
                <?php
                $education_type_lower = mb_strtolower($link['education_type']);
                // Пропускаем теги, содержащие слово "бюджет"
                if (strpos($education_type_lower, 'бюджет') === false):
                    $tag_class = 'attribute-tag';
                    if (strpos($education_type_lower, 'профессия') !== false) {
                        $tag_class .= ' profession-tag';
                    } elseif (strpos($education_type_lower, 'специальность') !== false) {
                        $tag_class .= ' specialty-tag';
                    }
                ?>
                    <span class="<?= $tag_class ?>"><?= htmlspecialchars($link['education_type']) ?></span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($link['program_attributes_array'])): ?>
                <?php foreach ($link['program_attributes_array'] as $attr): ?>
                    <?php
                    $attr_lower = mb_strtolower(trim($attr));
                    // Пропускаем атрибуты, содержащие слово "бюджет"
                    if (strpos($attr_lower, 'бюджет') !== false) {
                        continue;
                    }
                    $tag_class = 'attribute-tag';
                    if (strpos($attr_lower, '2 огэ') !== false) {
                        $tag_class .= ' oge-attribute';
                    }
                    ?>
                    <span class="<?= $tag_class ?>"><?= htmlspecialchars($attr) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($link['is_professionalitet']) || !empty($link['cluster_name'])): ?>
                <span class="attribute-tag professionalitet-tag">
                    Профессионалитет<?= !empty($link['cluster_name']) ? ': ' . htmlspecialchars($link['cluster_name']) : '' ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>