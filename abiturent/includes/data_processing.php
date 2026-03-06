<?php
// Обработка данных программ
function processProgramData($raw_bundles_data) {
    $links_data = [];
    foreach ($raw_bundles_data as $bundle) {
        $link_item = [];
        $link_item['id'] = $bundle['bundle_id'];
        $link_item['college_name'] = $bundle['establishment_name'];
        $link_item['college_logo_path'] = $bundle['establishment_logo_path']
            ? "./uploads/establishments/" . $bundle['establishment_logo_path']
            : "uploads/establishments/placeholder.svg";
        $link_item['program_name_in_bundle'] = $bundle['program_name'];
        $link_item['show_program_name_in_card'] = false;
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
        $link_item['cluster_name_on_card'] = $bundle['actual_cluster_name'];
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
                ? " профессионалитет " . ($bundle['actual_cluster_name'] ?? '')
                : "")
        );
        $links_data[] = $link_item;
    }
    return $links_data;
}
?>