<script type="text/javascript">
    <?php if ($GLOBALS['current_tab'] == 'establishments' || $GLOBALS['current_tab'] == 'bundles'): ?>
    ymaps.ready(initMaps);
    function initMaps() {
        <?php if ($GLOBALS['current_tab'] == 'establishments'): ?>
        setupMap('address_admin', 'latitude_admin', 'longitude_admin', 'map-placeholder', 
            '<?php echo $establishment_to_edit && $establishment_to_edit['latitude'] ? $establishment_to_edit['latitude'] : '59.9343'; ?>',
            '<?php echo $establishment_to_edit && $establishment_to_edit['longitude'] ? $establishment_to_edit['longitude'] : '30.3351'; ?>',
            '<?php echo $establishment_to_edit && ($establishment_to_edit['latitude'] || $establishment_to_edit['longitude']) ? '15' : '10'; ?>',
            '<?php echo $establishment_to_edit ? htmlspecialchars(addslashes($establishment_to_edit['address'])) : ''; ?>'
        );
        <?php endif; ?>

        <?php if ($GLOBALS['current_tab'] == 'bundles'): ?>
        setupMap('program_address_bundle', 'program_latitude_bundle', 'program_longitude_bundle', 'map-placeholder-bundle',
            '<?php echo $bundle_to_edit && $bundle_to_edit['program_latitude'] ? $bundle_to_edit['program_latitude'] : '59.9343'; ?>',
            '<?php echo $bundle_to_edit && $bundle_to_edit['program_longitude'] ? $bundle_to_edit['program_longitude'] : '30.3351'; ?>',
            '<?php echo $bundle_to_edit && ($bundle_to_edit['program_latitude'] || $bundle_to_edit['program_longitude']) ? '15' : '10'; ?>',
            '<?php echo $bundle_to_edit ? htmlspecialchars(addslashes($bundle_to_edit['program_address'])) : ''; ?>'
        );
        <?php endif; ?>
    }

    function setupMap(addressInputId, latInputId, lonInputId, mapPlaceholderId, initialLat, initialLon, initialZoom, initialAddress) {
        const addressInput = document.getElementById(addressInputId);
        const latitudeInput = document.getElementById(latInputId);
        const longitudeInput = document.getElementById(lonInputId);
        const mapPlaceholder = document.getElementById(mapPlaceholderId);

        if (!addressInput || !mapPlaceholder) return;
        mapPlaceholder.innerHTML = '';

        let myMap;
        try {
            myMap = new ymaps.Map(mapPlaceholderId, {
                center: [parseFloat(initialLat), parseFloat(initialLon)],
                zoom: parseInt(initialZoom),
                controls: ['zoomControl', 'searchControl', 'typeSelector',  'fullscreenControl', 'routeButtonControl']
            });

            if (initialAddress && parseFloat(initialLat) && parseFloat(initialLon)) {
                const initialPlacemark = new ymaps.Placemark([parseFloat(initialLat), parseFloat(initialLon)], {
                    balloonContentHeader: initialAddress.split(',').slice(0,2).join(','),
                    balloonContentBody: initialAddress,
                    hintContent: initialAddress
                }, { preset: 'islands#blueDotIconWithCaption' });
                myMap.geoObjects.add(initialPlacemark);
            }
        } catch (e) {
            mapPlaceholder.innerHTML = "Не удалось загрузить карту. Проверьте API ключ и подключение к интернету.";
            console.error("Map init error:", e);
            return;
        }

        var suggestView = new ymaps.SuggestView(addressInputId, {
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
                    if (latitudeInput) latitudeInput.value = coords[0].toPrecision(8);
                    if (longitudeInput) longitudeInput.value = coords[1].toPrecision(8);
                    
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
            if (latitudeInput) latitudeInput.value = coords[0].toPrecision(8);
            if (longitudeInput) longitudeInput.value = coords[1].toPrecision(8);

            ymaps.geocode(coords, { results: 1 }).then(function (res) {
                var firstGeoObject = res.geoObjects.get(0);
                if (firstGeoObject) {
                    var clickedAddress = firstGeoObject.getAddressLine();
                    if (addressInput) addressInput.value = clickedAddress;
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