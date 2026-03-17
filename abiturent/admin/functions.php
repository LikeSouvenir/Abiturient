<?php
/**
 * Обработка загрузки файлов
 */
function handle_upload($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
       
        $filename_original = basename($_FILES[$file_input_name]["name"]);
        $filename_safe = preg_replace("/[^a-zA-Z0-9\._-]/", "", $filename_original);
        if (empty($filename_safe)) $filename_safe = "uploaded_file";
        $extension = pathinfo($filename_safe, PATHINFO_EXTENSION);
        $filename_base = pathinfo($filename_safe, PATHINFO_FILENAME);
        $filename = uniqid() . "-" . $filename_base . "." . $extension;
        
        $target_file = rtrim($upload_dir, '/') . '/' . $filename;

        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                 error_log("Failed to create upload directory: " . $upload_dir);
                 return null;
            }
        }
        if (move_uploaded_file($_FILES[$file_input_name]["tmp_name"], $target_file)) {
            return $filename;
        } else {
            error_log("Failed to move uploaded file to: " . $target_file . " from " . $_FILES[$file_input_name]["tmp_name"]);
        }
    } else if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] != UPLOAD_ERR_NO_FILE) {
        error_log("File upload error for " . $file_input_name . ": " . $_FILES[$file_input_name]['error']);
    }
    return null;
}

/**
 * Удаление файла из системы
 */
function delete_file_from_system($relative_path_from_uploads_constant, $upload_dir_constant_value) {
    if (!empty($relative_path_from_uploads_constant)) {
        
        $file_path = $relative_path_from_uploads_constant;
        
        if (strpos($relative_path_from_uploads_constant, basename($upload_dir_constant_value)) !== false && strpos($relative_path_from_uploads_constant, 'uploads/') !== false) {
           
             if (file_exists(BASE_PATH . '/' . $relative_path_from_uploads_constant)) {
                $file_path = BASE_PATH . '/' . $relative_path_from_uploads_constant;
            } elseif (file_exists($relative_path_from_uploads_constant)) {
                 $file_path = $relative_path_from_uploads_constant;
            } else {
               
                $file_path = rtrim($upload_dir_constant_value, '/') . '/' . $relative_path_from_uploads_constant;
            }
        } else {
             $file_path = rtrim($upload_dir_constant_value, '/') . '/' . $relative_path_from_uploads_constant;
        }

        if (file_exists($file_path) && is_writable(dirname($file_path))) {
            if (unlink($file_path)) {
                return true;
            } else {
                error_log("Failed to delete file: " . $file_path . " - Check permissions.");
                return false;
            }
        } else {
            error_log("File not found or directory not writable for deletion: " . $file_path);
        }
    }
    return false;
}

/**
 * Получение данных для редактирования
 */
function getEditData($conn, $table, $id, $fields = '*') {
    $stmt = $conn->prepare("SELECT $fields FROM $table WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}
?>