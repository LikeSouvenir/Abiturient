<div class="link-item"
     data-id="<?= $link['id'] ?>"
     data-search-text="<?= htmlspecialchars($link['search_text']) ?>"
     data-lat="<?= htmlspecialchars($link['latitude']) ?>"
     data-lon="<?= htmlspecialchars($link['longitude']) ?>">
    <div class="college-logo">
        <img src="<?= htmlspecialchars($link['college_logo_path']) ?>"
             alt="Логотип <?= htmlspecialchars($link['college_name']) ?>">
    </div>
    <div class="link-details">
        <h3><?= htmlspecialchars($link['college_name']) ?></h3>
        <?php if (isset($link['show_program_name_in_card']) && $link['show_program_name_in_card']): ?>
            <span class="program-name-in-card"><?= htmlspecialchars($link['program_name_in_bundle']) ?></span>
        <?php endif; ?>
        <?php if (!empty($link['education_type'])): ?>
            <p><strong>Образование:</strong> <?= htmlspecialchars($link['education_type']) ?></p>
        <?php endif; ?>
        <?php if (!empty($link['base_level'])): ?>
            <p><strong>На базе:</strong> <?= htmlspecialchars($link['base_level']) ?></p>
        <?php endif; ?>
        <?php if (!empty($link['duration'])): ?>
            <p><strong>Срок обучения:</strong> <?= htmlspecialchars($link['duration']) ?></p>
        <?php endif; ?>
        <?php if (!empty($link['program_address'])): ?>
            <p><strong>Адрес проведения программы:</strong> <?= htmlspecialchars($link['program_address']) ?></p>
        <?php endif; ?>
        <?php if (!empty($link['commission_address']) && $link['commission_address'] !== $link['program_address']): ?>
            <p><strong>Адрес приемной комиссии:</strong> <?= htmlspecialchars($link['commission_address']) ?></p>
        <?php endif; ?>
        <?php if (!empty($link['phone'])): ?>
            <p><strong>Телефон:</strong> <?= htmlspecialchars($link['phone']) ?></p>
        <?php endif; ?>
        <?php if (!empty($link['website'])): ?>
            <p class="college-website">
                <strong>Сайт:</strong>
                <a href="<?= htmlspecialchars($link['website']) ?>" target="_blank" rel="noopener noreferrer">
                    <?= htmlspecialchars($link['website']) ?>
                </a>
            </p>
        <?php endif; ?>
        <div class="tags-container">
            <?php if (!empty($link['program_attributes_array'])): ?>
                <?php foreach ($link['program_attributes_array'] as $attr): ?>
                    <?php
                        $attr_lower = mb_strtolower(trim($attr));
                        $tag_class = 'attribute-tag';
                        if (strpos($attr_lower, '2 огэ') !== false) {
                            $tag_class .= ' oge-attribute';
                        }
                    ?>
                    <span class="<?= $tag_class ?>"><?= htmlspecialchars($attr) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($link['is_professionalitet']): ?>
                <span class="attribute-tag professionalitet-tag">
                    Профессионалитет<?= !empty($link['cluster_name']) ? ': ' . htmlspecialchars($link['cluster_name']) : '' ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>