<?php
// Функции для работы с данными
function getEstablishmentName($conn, $id)
{
    $stmt = $conn->prepare("SELECT name FROM establishments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['name'] ?? null;
}

/**
 * Получение всех программ учебного заведения с адресами и телефонами
 */
function getProgramsByEstablishment($conn, $establishment_id) {
    $bundles = [];
    
    // Основной запрос (используем program_code)
    $sql = "SELECT 
        b.id as bundle_id,
        b.establishment_id,
        b.program_id,
        b.education_type,
        b.education_base,
        b.duration,
        b.cluster_id,
        b.program_address,
        b.program_latitude,
        b.program_longitude,
        b.created_at,
        e.name as establishment_name,
        e.logo_path as establishment_logo_path,
        e.website as establishment_website,
        p.name as program_name,
        p.program_code,
        p.attributes as program_attributes,
        p.image_path as program_image_path,
        c.name as cluster_name
    FROM bundles b
    JOIN establishments e ON b.establishment_id = e.id
    JOIN programs p ON b.program_id = p.id
    LEFT JOIN clusters c ON b.cluster_id = c.id
    WHERE b.establishment_id = ?
    ORDER BY p.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $establishment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Получаем адреса из таблицы addresses
        $addresses_sql = "SELECT 
            id,
            address,
            latitude,
            longitude,
            admissions_committee
        FROM addresses 
        WHERE establishment_id = ?";
        
        $addr_stmt = $conn->prepare($addresses_sql);
        $addr_stmt->bind_param("i", $establishment_id);
        $addr_stmt->execute();
        $addr_result = $addr_stmt->get_result();
        
        $addresses = [];
        while ($addr = $addr_result->fetch_assoc()) {
            $addresses[] = [
                'address' => $addr['address'],
                'latitude' => $addr['latitude'],
                'longitude' => $addr['longitude'],
                'admissions_committee' => $addr['admissions_committee']
            ];
        }
        $addr_stmt->close();
        
        // Получаем телефоны из таблицы phones
        $phones_sql = "SELECT 
            id,
            phone,
            admissions_committee
        FROM phones 
        WHERE establishment_id = ?";
        
        $phone_stmt = $conn->prepare($phones_sql);
        $phone_stmt->bind_param("i", $establishment_id);
        $phone_stmt->execute();
        $phone_result = $phone_stmt->get_result();
        
        $phones = [];
        while ($phone = $phone_result->fetch_assoc()) {
            $phones[] = [
                'phone' => $phone['phone'],
                'admissions_committee' => $phone['admissions_committee']
            ];
        }
        $phone_stmt->close();
        
        $bundles[] = [
            'bundle_id' => $row['bundle_id'],
            'establishment_id' => $row['establishment_id'],
            'program_id' => $row['program_id'],
            'establishment_name' => $row['establishment_name'],
            'establishment_logo_path' => $row['establishment_logo_path'],
            'establishment_website' => $row['establishment_website'],
            'program_name' => $row['program_name'],
            'program_code' => $row['program_code'],
            'program_attributes' => $row['program_attributes'],
            'program_image_path' => $row['program_image_path'],
            'education_type' => $row['education_type'],
            'education_base' => $row['education_base'],
            'duration' => $row['duration'],
            'program_address' => $row['program_address'],
            'program_latitude' => $row['program_latitude'],
            'program_longitude' => $row['program_longitude'],
            'cluster_id' => $row['cluster_id'],
            'cluster_name' => $row['cluster_name'],
            'created_at' => $row['created_at'],
            'all_addresses' => $addresses,
            'all_phones' => $phones
        ];
    }
    $stmt->close();
    
    return $bundles;
}
function getProgramNameByCode($conn, $program_code) {
    $stmt = $conn->prepare("SELECT name FROM programs WHERE program_code = ?");
    $stmt->bind_param("s", $program_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['name'] ?? null;
}

// Функция для получения данных учебных заведений по фильтрам
function getEstablishmentsByFilters($conn, $program_code_filter, $cluster_id_filter) {
    $base_sql = "
        SELECT DISTINCT
            b.id as bundle_id,
            b.education_type,
            b.education_base,
            b.duration,
            b.program_address,
            b.program_latitude,
            b.program_longitude,
            b.cluster_id,
            e.id as establishment_id,
            e.name as establishment_name,
            e.logo_path as establishment_logo_path,
            e.website as establishment_website,
            p.id as program_id,
            p.name as program_name,
            p.program_code as program_code_val,
            p.attributes as program_attributes,
            cls.name as cluster_name
        FROM bundles b
        JOIN establishments e ON b.establishment_id = e.id
        JOIN programs p ON b.program_id = p.id
        LEFT JOIN clusters cls ON b.cluster_id = cls.id
    ";

    $where_clauses = [];
    $bind_params_types = "";
    $bind_params_values = [];

    if ($program_code_filter) {
        $where_clauses[] = "p.program_code = ?";
        $bind_params_types .= "s";
        $bind_params_values[] = $program_code_filter;
    } elseif ($cluster_id_filter) {
        $where_clauses[] = "b.cluster_id = ?";
        $bind_params_types .= "i";
        $bind_params_values[] = $cluster_id_filter;
    }

    $sql_query = $base_sql;
    if (!empty($where_clauses)) {
        $sql_query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $sql_query .= " ORDER BY e.name, p.name";

    $stmt = $conn->prepare($sql_query);
    if (!empty($bind_params_values)) {
        $stmt->bind_param($bind_params_types, ...$bind_params_values);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Для каждой записи получаем адреса и телефоны
    foreach ($data as &$item) {
        $est_id = $item['establishment_id'];
        
        // Получаем адреса приёмной комиссии (для отображения)
        $stmt_addr = $conn->prepare("SELECT address, latitude, longitude FROM addresses WHERE establishment_id = ? AND admissions_committee = 1");
        $stmt_addr->bind_param("i", $est_id);
        $stmt_addr->execute();
        $result_addr = $stmt_addr->get_result();
        $addresses = [];
        while ($row = $result_addr->fetch_assoc()) {
            $addresses[] = $row['address'];
        }
        $stmt_addr->close();
        $item['establishment_main_address'] = implode('; ', $addresses);
        
        // Получаем телефоны приёмной комиссии
        $stmt_phone = $conn->prepare("SELECT phone FROM phones WHERE establishment_id = ? AND admissions_committee = 1");
        $stmt_phone->bind_param("i", $est_id);
        $stmt_phone->execute();
        $result_phone = $stmt_phone->get_result();
        $phones = [];
        while ($row = $result_phone->fetch_assoc()) {
            $phones[] = $row['phone'];
        }
        $stmt_phone->close();
        $item['establishment_phone'] = implode(', ', $phones);
        
        // Получаем координаты для карты (из первого адреса приёмной комиссии)
        $stmt_coord = $conn->prepare("SELECT latitude, longitude FROM addresses WHERE establishment_id = ? AND admissions_committee = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1");
        $stmt_coord->bind_param("i", $est_id);
        $stmt_coord->execute();
        $result_coord = $stmt_coord->get_result();
        $coord = $result_coord->fetch_assoc();
        $stmt_coord->close();
        
        $item['establishment_main_latitude'] = $coord['latitude'] ?? null;
        $item['establishment_main_longitude'] = $coord['longitude'] ?? null;
    }
    
    return $data;
}

/**
 * Получить название кластера по ID
 */
function getClusterName($conn, $cluster_id) {
    $stmt = $conn->prepare("SELECT name FROM clusters WHERE id = ?");
    $stmt->bind_param("i", $cluster_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['name'];
    }
    return null;
}

/**
 * Получить программы по ID кластера
 */
function getProgramsByCluster($conn, $cluster_id) {
    $stmt = $conn->prepare("
        SELECT
            b.id as bundle_id,
            b.education_type,
            b.education_base,
            b.duration,
            b.program_address,
            b.program_latitude,
            b.program_longitude,
            b.cluster_id,
            e.id as establishment_id,
            e.name as establishment_name,
            e.logo_path as establishment_logo_path,
            e.website as establishment_website,
            p.id as program_id,
            p.name as program_name,
            p.program_code as program_code_val,
            p.attributes as program_attributes,
            cls.name as cluster_name
        FROM bundles b
        JOIN establishments e ON b.establishment_id = e.id
        JOIN programs p ON b.program_id = p.id
        LEFT JOIN clusters cls ON b.cluster_id = cls.id
        WHERE b.cluster_id = ?
        ORDER BY p.name
    ");
    
    $stmt->bind_param("i", $cluster_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $est_id = $row['establishment_id'];
        
        // Получаем адреса приёмной комиссии
        $stmt_addr = $conn->prepare("SELECT address, latitude, longitude FROM addresses WHERE establishment_id = ? AND admissions_committee = 1");
        $stmt_addr->bind_param("i", $est_id);
        $stmt_addr->execute();
        $result_addr = $stmt_addr->get_result();
        $addresses = [];
        while ($addr_row = $result_addr->fetch_assoc()) {
            $addresses[] = $addr_row['address'];
        }
        $stmt_addr->close();
        $row['establishment_addresses'] = implode('; ', $addresses);
        
        // Получаем телефоны приёмной комиссии
        $stmt_phone = $conn->prepare("SELECT phone FROM phones WHERE establishment_id = ? AND admissions_committee = 1");
        $stmt_phone->bind_param("i", $est_id);
        $stmt_phone->execute();
        $result_phone = $stmt_phone->get_result();
        $phones = [];
        while ($phone_row = $result_phone->fetch_assoc()) {
            $phones[] = $phone_row['phone'];
        }
        $stmt_phone->close();
        $row['establishment_phones'] = implode(', ', $phones);
        
        // Получаем координаты
        $stmt_coord = $conn->prepare("SELECT latitude, longitude FROM addresses WHERE establishment_id = ? AND admissions_committee = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL LIMIT 1");
        $stmt_coord->bind_param("i", $est_id);
        $stmt_coord->execute();
        $result_coord = $stmt_coord->get_result();
        $coord = $result_coord->fetch_assoc();
        $stmt_coord->close();
        
        $row['establishment_main_latitude'] = $coord['latitude'] ?? null;
        $row['establishment_main_longitude'] = $coord['longitude'] ?? null;
        
        $data[] = $row;
    }
    
    return $data;
}

/**
 * Получить информацию о направлении по коду
 */
function getDirectionByCode($conn, $direction_code_identifier) {
    $stmt_dir = $conn->prepare("SELECT id, name FROM directions WHERE program_code_identifier = ?");
    $stmt_dir->bind_param("s", $direction_code_identifier);
    $stmt_dir->execute();
    $direction_result = $stmt_dir->get_result();
    $direction = $direction_result->fetch_assoc();
    
    $direction_result->close();
    $stmt_dir->close();
    
    return $direction;
}

/**
 * Получить программы по ID направления
 */
function getProgramsByDirection($conn, $direction_id) {
    $programs_list = [];
    
    $stmt_prog = $conn->prepare("
        SELECT
            p.id, p.name, p.program_code, p.keywords, p.attributes, p.image_path,
            COUNT(DISTINCT b.establishment_id) as num_establishments,
            SUM(CASE WHEN b.cluster_id IS NOT NULL THEN 1 ELSE 0 END) > 0 as is_professionalitet_related
        FROM
            programs p
        LEFT JOIN
            bundles b ON p.id = b.program_id
        WHERE
            p.direction_id = ?
        GROUP BY
            p.id, p.name, p.program_code, p.keywords, p.attributes, p.image_path
        HAVING
            COUNT(DISTINCT b.establishment_id) > 0
        ORDER BY
            p.name
    ");
    
    $stmt_prog->bind_param("i", $direction_id);
    $stmt_prog->execute();
    $programs_result = $stmt_prog->get_result();
    
    while ($program_row = $programs_result->fetch_assoc()) {
        $program_row['image_path'] = $program_row['image_path'] 
            ? "uploads/programs/" . $program_row['image_path'] 
            : "uploads/programs/placeholder.svg";
        $program_row['attributes_array'] = !empty($program_row['attributes']) 
            ? array_map('trim', explode(',', $program_row['attributes'])) 
            : [];
        $programs_list[] = $program_row;
    }
    
    $programs_result->close();
    $stmt_prog->close();
    
    return $programs_list;
}

/**
 * Получить теги для отображения
 */
function getProgramTags($program) {
    $tags_html = '';
    
    if ($program['is_professionalitet_related']) {
        $tags_html .= '<span class="attribute-tag professionalitet-tag">Профессионалитет</span>';
    }
    
    if (!empty($program['attributes_array'])) {
        foreach ($program['attributes_array'] as $attr) {
            $attr_lower = mb_strtolower(trim($attr));
            $tag_class = 'attribute-tag';
            
            if (strpos($attr_lower, '2 огэ') !== false) {
                continue;
            }
            
            if (strpos($attr_lower, "профессия") !== false) {
                $tag_class .= ' profession';
            }
            
            $tags_html .= '<span class="' . $tag_class . '">' . htmlspecialchars($attr) . '</span>';
        }
    }
    
    return $tags_html;
}
?>