<?php
// Функции для работы с данными
function getEstablishmentName($conn, $id) {
    $stmt = $conn->prepare("SELECT name FROM establishments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['name'] ?? null;
}

function getProgramsByEstablishment($conn, $establishment_id) {
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
            e.address as establishment_main_address,
            e.latitude as establishment_main_latitude,
            e.longitude as establishment_main_longitude,
            e.phone as establishment_phone,
            e.website as establishment_website,
            p.id as program_id,
            p.name as program_name,
            p.program_code as program_code_val,
            p.attributes as program_attributes,
            cls.name as actual_cluster_name
        FROM bundles b
        JOIN establishments e ON b.establishment_id = e.id
        JOIN programs p ON b.program_id = p.id
        LEFT JOIN clusters cls ON b.cluster_id = cls.id
        WHERE b.establishment_id = ?
        ORDER BY p.name
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $establishment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}
?>