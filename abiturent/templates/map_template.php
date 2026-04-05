<div class="right-column">
    <h3 class="map-title">Карта учебных заведений</h3>
    <div id="map" class="map-container" style="width: 100%; height: 500px;"></div>
</div>

<script>
    // Передаем данные для карты в глобальную переменную
    window.mapData = <?= json_encode($links_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
    console.log('map_template: передано данных:', window.mapData.length);
</script>