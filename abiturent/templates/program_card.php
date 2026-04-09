<div class="link-item"
    data-id="<?= $link['id'] ?>"
    data-search-text="<?= htmlspecialchars($link['search_text'] ?? '') ?>"
    data-map-points='<?= json_encode($link['map_points'] ?? []) ?>'
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
            <strong>Приемная комиссия</strong>

            <?php
            $has_contacts = false;
            $admission_addresses = $link['admission_addresses'] ?? [];
            $admission_phones = $link['admission_phones'] ?? [];
            
            // Определяем максимальное количество элементов
            $max_count = max(count($admission_addresses), count($admission_phones), 1);
            
            for ($i = 0; $i < $max_count; $i++):
                $address_data = $admission_addresses[$i] ?? null;
                $address = $address_data ? $address_data['address'] : '';
                $phone = $admission_phones[$i] ?? '';
                
                // Обработка адреса (замена СПб)
                if (!empty($address)) {
                    $address = str_replace(['г. Санкт-Петербург', 'Санкт-Петербург'], 'СПб', $address);
                }
                
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
            
            // Если остались телефоны без адресов
            for ($i = $max_count; $i < count($admission_phones); $i++):
                if (!empty($admission_phones[$i])):
                    $has_contacts = true;
                ?>
                    <p><?= htmlspecialchars($admission_phones[$i]) ?></p>
            <?php
                endif;
            endfor;
            
            // Если остались адреса без телефонов (дополнительные)
            for ($i = $max_count; $i < count($admission_addresses); $i++):
                $address_data = $admission_addresses[$i];
                if (!empty($address_data['address'])):
                    $has_contacts = true;
                    $address = str_replace(['г. Санкт-Петербург', 'Санкт-Петербург'], 'СПб', $address_data['address']);
                ?>
                    <p><?= htmlspecialchars($address) ?></p>
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
        <?php if (!empty($link['education_type']) && strpos(mb_strtolower($link['education_type']), 'бюджет') !== false): ?>
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

        <!-- Адреса проведения программы (ВОЗВРАЩЁН ПРЕЖНИЙ ВИД С СОКРАЩЕНИЕМ СПб) -->
        <?php if (!empty($link['program_address'])): ?>
            <p>
                <strong>Обучение по адресу:</strong>
                <?php 
                $address = $link['program_address'];
                // Сокращаем СПб
                $address = str_replace(['г. Санкт-Петербург', 'Санкт-Петербург'], 'СПб', $address);
                echo htmlspecialchars($address);
                ?>
            </p>
        <?php endif; ?>

        <!-- Теги (лейблы) -->
        <div class="tags-container">
            <?php if (!empty($link['education_type'])): ?>
                <?php
                $education_type_lower = mb_strtolower($link['education_type']);
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