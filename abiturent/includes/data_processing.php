<?php
/**
 * Обработка данных программ для отображения
 * 
 * @param array $raw_bundles_data Данные из getProgramsByEstablishment()
 * @return array Обработанные данные для отображения
 */
function processProgramData($raw_bundles_data) {
    $links_data = [];
    
    foreach ($raw_bundles_data as $bundle) {
        global $conn;
        
        // Разделяем адреса на категории
        $admission_addresses = [];
        $regular_addresses = [];
        $all_map_points = [];
        
        $all_addresses = $bundle['all_addresses'] ?? [];
        
        echo "<!-- Отладка: establishment_id = " . ($bundle['establishment_id'] ?? 'null') . ", адресов: " . count($all_addresses) . " -->\n";
        
        foreach ($all_addresses as $addr) {
            $point = [
                'address' => $addr['address'],
                'latitude' => $addr['latitude'] ? floatval($addr['latitude']) : null,
                'longitude' => $addr['longitude'] ? floatval($addr['longitude']) : null,
                'type' => $addr['admissions_committee'] == 1 ? 'admission' : 'regular'
            ];
            
            // Добавляем в соответствующую категорию
            if ($addr['admissions_committee'] == 1) {
                $admission_addresses[] = $point;
            } else {
                $regular_addresses[] = $point;
            }
            
            // Добавляем в список всех точек для карты (только если есть координаты)
            if (!empty($point['latitude']) && !empty($point['longitude'])) {
                $all_map_points[] = $point;
                echo "<!-- Добавлена точка с координатами: " . $point['address'] . " (" . $point['latitude'] . ", " . $point['longitude'] . ") -->\n";
            }
        }
        
        // Если нет точек с координатами из адресов, используем координаты программы
        if (empty($all_map_points) && !empty($bundle['program_latitude']) && !empty($bundle['program_longitude'])) {
            $all_map_points[] = [
                'address' => $bundle['program_address'] ?? 'Адрес программы',
                'latitude' => floatval($bundle['program_latitude']),
                'longitude' => floatval($bundle['program_longitude']),
                'type' => 'program'
            ];
            echo "<!-- Использованы координаты программы: " . ($bundle['program_address'] ?? '') . " -->\n";
        }
        
        // Получаем телефоны приёмной комиссии
        $admission_phones = [];
        $regular_phones = [];
        $all_phones = $bundle['all_phones'] ?? [];
        
        foreach ($all_phones as $phone) {
            if ($phone['admissions_committee'] == 1) {
                $admission_phones[] = $phone['phone'];
            } else {
                $regular_phones[] = $phone['phone'];
            }
        }
        
        $links_data[] = [
            'id' => $bundle['bundle_id'],
            'program_id' => $bundle['program_id'],
            'establishment_id' => $bundle['establishment_id'],
            'college_name' => $bundle['establishment_name'] ?? '',
            'college_logo_path' => !empty($bundle['establishment_logo_path']) ? '../uploads/establishments/' . $bundle['establishment_logo_path'] : '',
            'program_name' => $bundle['program_name'] ?? '',
            'program_name_in_bundle' => $bundle['program_name'] ?? '',
            'website' => $bundle['establishment_website'] ?? '',
            'education_type' => $bundle['education_type'] ?? '',
            'base_level' => $bundle['education_base'] ?? '',
            'duration' => $bundle['duration'] ?? '',
            'program_address' => $bundle['program_address'] ?? '',
            'admission_addresses' => $admission_addresses,
            'regular_addresses' => $regular_addresses,
            'admission_phones' => $admission_phones,
            'regular_phones' => $regular_phones,
            'map_points' => $all_map_points,
            'program_attributes_array' => !empty($bundle['program_attributes']) ? array_map('trim', explode(',', $bundle['program_attributes'])) : [],
            'is_professionalitet' => !empty($bundle['cluster_id']),
            'cluster_name' => $bundle['actual_cluster_name'] ?? '',
            'latitude' => !empty($all_map_points) ? $all_map_points[0]['latitude'] : null,
            'longitude' => !empty($all_map_points) ? $all_map_points[0]['longitude'] : null,
            'search_text' => ($bundle['establishment_name'] ?? '') . ' ' . ($bundle['program_name'] ?? '')
        ];
    }
    
    return $links_data;
}

// Остальные функции остаются без изменений...


/**
 * Обработка данных учебных заведений
 */
function processEstablishmentData($raw_bundles_data, $cluster_id_filter = null, $program_code_filter = null) {
    $links_data = [];
    foreach ($raw_bundles_data as $bundle) {
        $link_item = [];
        $link_item['id'] = $bundle['bundle_id'];
        $link_item['college_name'] = $bundle['establishment_name'];
        $link_item['college_logo_path'] = $bundle['establishment_logo_path']
            ? "uploads/establishments/" . $bundle['establishment_logo_path']
            : "uploads/establishments/placeholder.svg";

        $link_item['program_name_in_bundle'] = $bundle['program_name'];
        $link_item['show_program_name_in_card'] = ($cluster_id_filter && !$program_code_filter);

        $link_item['education_type'] = $bundle['education_type'];
        $link_item['base_level'] = $bundle['education_base'];
        $link_item['duration'] = $bundle['duration'];
        $link_item['program_address'] = $bundle['program_address'];

        $link_item['commission_address'] = $bundle['establishment_main_address'];
        $link_item['phone'] = $bundle['establishment_phone'];
        $link_item['website'] = $bundle['establishment_website'];

        $link_item['program_attributes_array'] = !empty($bundle['program_attributes'])
            ? array_map('trim', explode(',', $bundle['program_attributes']))
            : [];

        $link_item['is_professionalitet'] = !empty($bundle['cluster_id']);
        $link_item['cluster_name'] = $bundle['cluster_name'];

        $link_item['latitude'] = $bundle['program_latitude']
            ? $bundle['program_latitude']
            : $bundle['establishment_main_latitude'];
        $link_item['longitude'] = $bundle['program_longitude']
            ? $bundle['program_longitude']
            : $bundle['establishment_main_longitude'];
        $link_item['map_address'] = $bundle['program_address']
            ? $bundle['program_address']
            : $bundle['establishment_main_address'];

        $link_item['search_text'] = strtolower(
            ($bundle['establishment_name'] ?? '') . " " .
            ($bundle['program_name'] ?? '') . " " .
            ($bundle['education_type'] ?? '') . " " .
            ($bundle['education_base'] ?? '') . " " .
            ($bundle['program_address'] ?? '') . " " .
            ($bundle['establishment_main_address'] ?? '') . " " .
            implode(' ', $link_item['program_attributes_array']) .
            ($link_item['is_professionalitet']
                ? " профессионалитет " . ($bundle['cluster_name'] ?? '')
                : "")
        );
        $links_data[] = $link_item;
    }
    return $links_data;
}

/**
 * Обработка данных программ
 */
function processProgramsDatas($programs) {
    $processedPrograms = [];
    foreach ($programs as $program) {
        $program['image_path'] = $program['image_path'] ? "uploads/programs/" . $program['image_path'] : "uploads/programs/placeholder.svg";
        $program['attributes_array'] = !empty($program['attributes']) ? array_map('trim', explode(',', $program['attributes'])) : [];
        $processedPrograms[] = $program;
    }
    return $processedPrograms;
}
?>