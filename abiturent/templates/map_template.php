<div class="right-column">
    <h3 class="map-title">Карта учебных заведений</h3>
    <div id="map" class="map-container" data-links='<?= addslashes(json_encode($links_data, $json_options)) ?>'>
        <?php if (empty($links_data) || !array_filter($links_data, function($link_item) { return !empty($link_item['latitude']) && !empty($link_item['longitude']) || !empty($link_item['map_address']); })): ?>
            <p class="no-results" style="padding-top: 40px;">Нет данных для отображения на карте.</p>
        <?php endif; ?>
    </div>
</div>