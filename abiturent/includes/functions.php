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

function getProgramsByEstablishment($conn, $establishment_id)
{
    $sql = "
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
            ad.address as establishment_main_address,
            e.latitude as establishment_main_latitude,
            e.longitude as establishment_main_longitude,
            ph.phone as establishment_phone,
            e.website as establishment_website,
            p.id as program_id,
            p.name as program_name,
            p.program_code as program_code_val,
            p.attributes as program_attributes,
            cls.name as actual_cluster_name
    FROM bundles b
    JOIN establishments e ON b.establishment_id = e.id
    JOIN programs p ON b.program_id = p.id
    LEFT JOIN (
        SELECT
            establishment_id,
            GROUP_CONCAT(phone SEPARATOR ', ') as phone
        FROM phones
        GROUP BY establishment_id
    ) ph ON e.id = ph.establishment_id
    LEFT JOIN (
        SELECT
            establishment_id,
            GROUP_CONCAT(address SEPARATOR ', ') as address
        FROM addresses
        GROUP BY establishment_id
    ) ad ON e.id = ad.establishment_id
    LEFT JOIN clusters cls ON b.cluster_id = cls.id
    WHERE b.establishment_id = ?
    ORDER BY p.name;

    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $establishment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// Функция для получения названия программы по коду
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
            ad.address as establishment_main_address,
            e.latitude as establishment_main_latitude,
            e.longitude as establishment_main_longitude,
            ph.phone as establishment_phone,
            e.website as establishment_website,
            p.id as program_id,
            p.name as program_name,
            p.program_code as program_code_val,
            p.attributes as program_attributes,
            cls.name as cluster_name
        FROM bundles b
        JOIN establishments e ON b.establishment_id = e.id
        JOIN programs p ON b.program_id = p.id
        JOIN addresses ad ON e.id = ad.establishment_id
        JOIN phones ph ON e.id = ph.establishment_id
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
            e.latitude as establishment_main_latitude,
            e.longitude as establishment_main_longitude,
            e.website as establishment_website,
            p.id as program_id,
            p.name as program_name,
            p.program_code as program_code_val,
            p.attributes as program_attributes,
            cls.name as cluster_name,
            GROUP_CONCAT(DISTINCT a.address SEPARATOR ', ') as establishment_addresses,
            GROUP_CONCAT(DISTINCT ph.phone SEPARATOR ', ') as establishment_phones
        FROM bundles b
        JOIN establishments e ON b.establishment_id = e.id
        JOIN programs p ON b.program_id = p.id
        LEFT JOIN addresses a ON e.id = a.establishment_id
        LEFT JOIN phones ph ON e.id = ph.establishment_id
        LEFT JOIN clusters cls ON b.cluster_id = cls.id
        WHERE b.cluster_id = ?
        GROUP BY b.id, e.id, p.id, cls.id
        ORDER BY p.name
    ");
    
    $stmt->bind_param("i", $cluster_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}
?>
