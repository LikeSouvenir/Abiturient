<script type="text/javascript">
    <?php if ($GLOBALS['current_tab'] == 'bundles'): ?>
    ymaps.ready(initBundleMap);
    
    function initBundleMap() {
        const addressInput = document.getElementById('program_address_bundle');
        const latitudeInput = document.getElementById('program_latitude_bundle');
        const longitudeInput = document.getElementById('program_longitude_bundle');
        const mapPlaceholder = document.getElementById('map-placeholder-bundle');
        
        if (!addressInput || !mapPlaceholder) return;
        
        const initialLat = parseFloat('<?php echo $bundle_to_edit && $bundle_to_edit['program_latitude'] ? $bundle_to_edit['program_latitude'] : '59.9343'; ?>');
        const initialLon = parseFloat('<?php echo $bundle_to_edit && $bundle_to_edit['program_longitude'] ? $bundle_to_edit['program_longitude'] : '30.3351'; ?>');
        
        mapPlaceholder.innerHTML = '';
        
        let myMap;
        try {
            myMap = new ymaps.Map(mapPlaceholder, {
                center: [initialLat, initialLon],
                zoom: <?php echo $bundle_to_edit && ($bundle_to_edit['program_latitude'] || $bundle_to_edit['program_longitude']) ? '15' : '10'; ?>,
                controls: ['zoomControl', 'searchControl', 'typeSelector', 'fullscreenControl']
            });
            
            if (addressInput.value && initialLat && initialLon) {
                const initialPlacemark = new ymaps.Placemark([initialLat, initialLon], {
                    balloonContentHeader: addressInput.value.split(',').slice(0,2).join(','),
                    balloonContentBody: addressInput.value,
                    hintContent: addressInput.value
                }, { preset: 'islands#blueDotIconWithCaption' });
                myMap.geoObjects.add(initialPlacemark);
            }
        } catch (e) {
            mapPlaceholder.innerHTML = "Не удалось загрузить карту. Проверьте API ключ и подключение к интернету.";
            console.error("Map init error:", e);
            return;
        }
        
        var suggestView = new ymaps.SuggestView('program_address_bundle', {
            provider: { suggest: (request, options) => ymaps.suggest("Санкт-Петербург, " + request) },
            results: 5
        });
        
        suggestView.events.add('select', function (e) {
            var selectedAddress = e.get('item').value;
            addressInput.value = selectedAddress;
            geocodeAddress(selectedAddress);
        });
        
        addressInput.addEventListener('change', function() {
            if (this.value.length > 5) geocodeAddress(this.value);
        });
        
        function geocodeAddress(addrToGeocode) {
            ymaps.geocode(addrToGeocode, { results: 1 }).then(function (res) {
                var firstGeoObject = res.geoObjects.get(0);
                if (firstGeoObject) {
                    var coords = firstGeoObject.geometry.getCoordinates();
                    latitudeInput.value = coords[0].toPrecision(8);
                    longitudeInput.value = coords[1].toPrecision(8);
                    
                    var preciseAddress = firstGeoObject.getAddressLine();
                    addressInput.value = preciseAddress;
                    
                    myMap.setCenter(coords, 15);
                    myMap.geoObjects.removeAll();
                    const placemark = new ymaps.Placemark(coords, {
                        balloonContentHeader: preciseAddress.split(',').slice(0,2).join(','),
                        balloonContentBody: preciseAddress,
                        hintContent: preciseAddress
                    }, { preset: 'islands#blueDotIconWithCaption' });
                    myMap.geoObjects.add(placemark);
                    placemark.balloon.open();
                }
            }).catch(function(err){
                console.warn("Geocoding error for " + addrToGeocode + ": ", err);
            });
        }
        
        myMap.events.add('click', function (e) {
            var coords = e.get('coords');
            latitudeInput.value = coords[0].toPrecision(8);
            longitudeInput.value = coords[1].toPrecision(8);
            
            ymaps.geocode(coords, { results: 1 }).then(function (res) {
                var firstGeoObject = res.geoObjects.get(0);
                if (firstGeoObject) {
                    var clickedAddress = firstGeoObject.getAddressLine();
                    addressInput.value = clickedAddress;
                    myMap.geoObjects.removeAll();
                    const placemark = new ymaps.Placemark(coords, {
                        balloonContentHeader: clickedAddress.split(',').slice(0,2).join(','),
                        balloonContentBody: clickedAddress,
                        hintContent: clickedAddress
                    }, { preset: 'islands#blueDotIconWithCaption' });
                    myMap.geoObjects.add(placemark);
                    placemark.balloon.open();
                }
            });
        });
    }
    <?php endif; ?>
</script>