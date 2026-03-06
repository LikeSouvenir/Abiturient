<div class="right-column">
    <h3 class="map-title">Карта программ учебного заведения</h3>
    <div id="map" class="map-container">
        <?php if (!$establishment_id_filter || empty($links_data) || !array_filter($links_data, function($link_item) { return !empty($link_item['latitude']) && !empty($link_item['longitude']) || !empty($link_item['map_address']); })): ?>
            <p class="no-results php-map-message" style="padding-top: 40px;">Нет данных для отображения на карте.</p>
        <?php endif; ?>
    </div>
</div>