<?php
require_once 'config.php';

$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'directions';
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : null;
unset($_SESSION['message']);
unset($_SESSION['message_type']);

$error_message = '';


if (isset($_POST['admin_login'])) {
    $username = trim($_POST['username']);
    $password = md5(trim($_POST['password']));
    $user = $conn->query("SELECT * FROM admin_users WHERE username='$username' AND password='$password' LIMIT 1")->fetch_assoc();
    if($user) {
        $_SESSION['admin_loggedin'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        header("Location: index.php?tab=" . ($current_tab == 'login' ? 'directions' : $current_tab) );
        exit;
    } else {
        $_SESSION['login_error'] = "Неверный логин или пароль.";
        header("Location: index.php?tab=login");
        exit;
    }
}

if ($current_tab == 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: index.php?tab=login");
    exit;
}

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    if ($current_tab !== 'login') {
      header("Location: index.php?tab=login");
      exit;
    }
}



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


if ($current_tab == 'directions') {
    $edit_direction_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $direction_to_edit = null;
    
    if ($edit_direction_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, program_code_identifier, image_path FROM directions WHERE id = ?");
        $stmt->bind_param("i", $edit_direction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $direction_to_edit = $result->fetch_assoc();
        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_direction'])) {
            $name = trim($_POST['direction_name']);
            $code = trim($_POST['direction_code']);
            $image_filename = null;

            if (!empty($name) && !empty($code)) {
                $uploaded_filename = handle_upload('direction_image', UPLOAD_DIR_DIRECTIONS);
                if ($uploaded_filename) $image_filename = $uploaded_filename;

                $stmt = $conn->prepare("INSERT INTO directions (name, program_code_identifier, image_path) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $code, $image_filename);
                if($stmt->execute()){
                    $_SESSION['message'] = "Направление добавлено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Название и код направления обязательны.";
                $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=directions");
            exit;
        }

        if (isset($_POST['edit_direction'])) {
            $id = intval($_POST['direction_id']);
            $name = trim($_POST['direction_name']);
            $code = trim($_POST['direction_code']);
            $current_image_filename = trim($_POST['current_image_path']);
            $image_filename_to_save = $current_image_filename;

            if (!empty($name) && !empty($code)) {
                $new_uploaded_filename = handle_upload('direction_image', UPLOAD_DIR_DIRECTIONS);
                if ($new_uploaded_filename) {
                    if (!empty($current_image_filename)) delete_file_from_system($current_image_filename, UPLOAD_DIR_DIRECTIONS);
                    $image_filename_to_save = $new_uploaded_filename;
                } elseif (isset($_POST['delete_current_image'])) {
                    if (!empty($current_image_filename)) delete_file_from_system($current_image_filename, UPLOAD_DIR_DIRECTIONS);
                    $image_filename_to_save = null;
                }

                $stmt = $conn->prepare("UPDATE directions SET name = ?, program_code_identifier = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $code, $image_filename_to_save, $id);
                 if($stmt->execute()){
                    $_SESSION['message'] = "Направление обновлено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка обновления: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Название и код направления обязательны.";
                $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=directions");
            exit;
        }

        if (isset($_POST['delete_direction'])) {
            $id = intval($_POST['direction_id']);
           
            $prog_check_stmt = $conn->prepare("SELECT id FROM programs WHERE direction_id = ?");
            $prog_check_stmt->bind_param("i", $id);
            $prog_check_stmt->execute();
            $prog_result = $prog_check_stmt->get_result();
            if($prog_result->num_rows > 0) {
                $_SESSION['message'] = "Удаление невозможно! Существуют дочерние программы для этого направления.";
                $_SESSION['message_type'] = "error";
            } else {
                $dir_data_stmt = $conn->prepare("SELECT image_path FROM directions WHERE id = ?");
                $dir_data_stmt->bind_param("i", $id);
                $dir_data_stmt->execute();
                $dir_data_res = $dir_data_stmt->get_result();
                $dir_data = $dir_data_res->fetch_assoc();
                if ($dir_data && !empty($dir_data['image_path'])) {
                    delete_file_from_system($dir_data['image_path'], UPLOAD_DIR_DIRECTIONS);
                }
                $dir_data_stmt->close();

                $delete_stmt = $conn->prepare("DELETE FROM directions WHERE id = ?");
                $delete_stmt->bind_param("i", $id);
                if($delete_stmt->execute()){
                    $_SESSION['message'] = "Направление удалено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                     $_SESSION['message'] = "Ошибка удаления: " . $delete_stmt->error;
                     $_SESSION['message_type'] = "error";
                }
                $delete_stmt->close();
            }
            $prog_check_stmt->close();
            header("Location: index.php?tab=directions");
            exit;
        }
    }
}



if ($current_tab == 'programs') {
    $edit_program_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $program_to_edit = null;

    if ($edit_program_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, program_code, direction_id, keywords, attributes, image_path FROM programs WHERE id = ?");
        $stmt->bind_param("i", $edit_program_id);
        $stmt->execute();
        $program_to_edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_program'])) {
            $name = trim($_POST['program_name']);
            $code = trim($_POST['program_code']);
            $direction_id = intval($_POST['direction_id']);
            $keywords = trim($_POST['program_keywords']);
            $attributes = isset($_POST['program_attributes']) ? implode(', ', $_POST['program_attributes']) : '';
            $image_filename = null;

            if (!empty($name) && !empty($code) && $direction_id > 0) {
                $uploaded_filename = handle_upload('program_image', UPLOAD_DIR_PROGRAMS);
                if ($uploaded_filename) $image_filename = $uploaded_filename;

                $stmt = $conn->prepare("INSERT INTO programs (name, program_code, direction_id, keywords, attributes, image_path) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisss", $name, $code, $direction_id, $keywords, $attributes, $image_filename);
                if($stmt->execute()){
                     $_SESSION['message'] = "Программа добавлена успешно!";
                     $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка добавления программы: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Название, код и выбор направления обязательны для программы.";
                $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=programs");
            exit;
        }

        if (isset($_POST['edit_program'])) {
            $id = intval($_POST['program_id']);
            $name = trim($_POST['program_name']);
            $code = trim($_POST['program_code']);
            $direction_id = intval($_POST['direction_id']);
            $keywords = trim($_POST['program_keywords']);
            $attributes = isset($_POST['program_attributes']) ? implode(', ', $_POST['program_attributes']) : '';
            $current_image_filename = trim($_POST['current_image_path']);
            $image_filename_to_save = $current_image_filename;

            if (!empty($name) && !empty($code) && $direction_id > 0) {
                $new_uploaded_filename = handle_upload('program_image', UPLOAD_DIR_PROGRAMS);
                if ($new_uploaded_filename) {
                    if(!empty($current_image_filename)) delete_file_from_system($current_image_filename, UPLOAD_DIR_PROGRAMS);
                    $image_filename_to_save = $new_uploaded_filename;
                } elseif (isset($_POST['delete_current_image'])) {
                     if(!empty($current_image_filename)) delete_file_from_system($current_image_filename, UPLOAD_DIR_PROGRAMS);
                    $image_filename_to_save = null;
                }

                $stmt = $conn->prepare("UPDATE programs SET name = ?, program_code = ?, direction_id = ?, keywords = ?, attributes = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("ssisssi", $name, $code, $direction_id, $keywords, $attributes, $image_filename_to_save, $id);
                if($stmt->execute()){
                    $_SESSION['message'] = "Программа обновлена успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка обновления программы: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                 $_SESSION['message'] = "Название, код и выбор направления обязательны для программы.";
                 $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=programs");
            exit;
        }

         if (isset($_POST['delete_program'])) {
            $id = intval($_POST['program_id']);
           
            $bundle_check_stmt = $conn->prepare("SELECT id FROM bundles WHERE program_id = ?");
            $bundle_check_stmt->bind_param("i", $id);
            $bundle_check_stmt->execute();
            $bundle_result = $bundle_check_stmt->get_result();

            if($bundle_result->num_rows > 0) {
                $_SESSION['message'] = "Удаление невозможно! Существуют связки для этой программы.";
                $_SESSION['message_type'] = "error";
            } else {
                $prog_data_stmt = $conn->prepare("SELECT image_path FROM programs WHERE id = ?");
                $prog_data_stmt->bind_param("i", $id);
                $prog_data_stmt->execute();
                $prog_data = $prog_data_stmt->get_result()->fetch_assoc();
                if ($prog_data && !empty($prog_data['image_path'])) {
                    delete_file_from_system($prog_data['image_path'], UPLOAD_DIR_PROGRAMS);
                }
                $prog_data_stmt->close();

                $delete_stmt = $conn->prepare("DELETE FROM programs WHERE id = ?");
                $delete_stmt->bind_param("i", $id);
                 if($delete_stmt->execute()){
                    $_SESSION['message'] = "Программа удалена успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка удаления программы: " . $delete_stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $delete_stmt->close();
            }
            $bundle_check_stmt->close();
            header("Location: index.php?tab=programs");
            exit;
        }
    }
    
   
}



if ($current_tab == 'establishments') {
    $edit_establishment_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $establishment_to_edit = null;

    if ($edit_establishment_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, address, latitude, longitude, phone, website, logo_path FROM establishments WHERE id = ?");
        $stmt->bind_param("i", $edit_establishment_id);
        $stmt->execute();
        $establishment_to_edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_establishment'])) {
            $name = trim($_POST['establishment_name']);
            $address = trim($_POST['address']);
            $latitude = trim($_POST['latitude']);
            $longitude = trim($_POST['longitude']);
            $phone = trim($_POST['phone']);
            $website = trim($_POST['website']);
            $logo_filename = null;

            if (!empty($name)) {
                $uploaded_filename = handle_upload('establishment_logo', UPLOAD_DIR_ESTABLISHMENTS);
                if ($uploaded_filename) $logo_filename = $uploaded_filename;
                
                $stmt = $conn->prepare("INSERT INTO establishments (name, address, latitude, longitude, phone, website, logo_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $name, $address, $latitude, $longitude, $phone, $website, $logo_filename);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Учебное заведение добавлено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка добавления заведения: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Название заведения обязательно.";
                $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=establishments");
            exit;
        }

        if (isset($_POST['edit_establishment'])) {
            $id = intval($_POST['establishment_id']);
            $name = trim($_POST['establishment_name']);
            $address = trim($_POST['address']);
            $latitude = trim($_POST['latitude']);
            $longitude = trim($_POST['longitude']);
            $phone = trim($_POST['phone']);
            $website = trim($_POST['website']);
            $current_logo_filename = trim($_POST['current_logo_path']);
            $logo_filename_to_save = $current_logo_filename;

            if (!empty($name)) {
                $new_uploaded_filename = handle_upload('establishment_logo', UPLOAD_DIR_ESTABLISHMENTS);
                if ($new_uploaded_filename) {
                    if(!empty($current_logo_filename)) delete_file_from_system($current_logo_filename, UPLOAD_DIR_ESTABLISHMENTS);
                    $logo_filename_to_save = $new_uploaded_filename;
                } elseif (isset($_POST['delete_current_logo'])) {
                    if(!empty($current_logo_filename)) delete_file_from_system($current_logo_filename, UPLOAD_DIR_ESTABLISHMENTS);
                    $logo_filename_to_save = null;
                }

                $stmt = $conn->prepare("UPDATE establishments SET name = ?, address = ?, latitude = ?, longitude = ?, phone = ?, website = ?, logo_path = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $name, $address, $latitude, $longitude, $phone, $website, $logo_filename_to_save, $id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Учебное заведение обновлено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка обновления заведения: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                 $_SESSION['message'] = "Название заведения обязательно.";
                 $_SESSION['message_type'] = "error";
            }
             header("Location: index.php?tab=establishments");
             exit;
        }

        if (isset($_POST['delete_establishment'])) {
            $id = intval($_POST['establishment_id']);
           
            $bundle_check_stmt = $conn->prepare("SELECT id FROM bundles WHERE establishment_id = ?");
            $bundle_check_stmt->bind_param("i", $id);
            $bundle_check_stmt->execute();
            $bundle_result = $bundle_check_stmt->get_result();

            if($bundle_result->num_rows > 0) {
                $_SESSION['message'] = "Удаление невозможно! Существуют связки для этого учебного заведения.";
                $_SESSION['message_type'] = "error";
            } else {
                $est_data_stmt = $conn->prepare("SELECT logo_path FROM establishments WHERE id = ?");
                $est_data_stmt->bind_param("i", $id);
                $est_data_stmt->execute();
                $est_data = $est_data_stmt->get_result()->fetch_assoc();
                if ($est_data && !empty($est_data['logo_path'])) {
                    delete_file_from_system($est_data['logo_path'], UPLOAD_DIR_ESTABLISHMENTS);
                }
                $est_data_stmt->close();

                $delete_stmt = $conn->prepare("DELETE FROM establishments WHERE id = ?");
                $delete_stmt->bind_param("i", $id);
                if ($delete_stmt->execute()) {
                    $_SESSION['message'] = "Учебное заведение удалено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка удаления: " . $delete_stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $delete_stmt->close();
            }
            $bundle_check_stmt->close();
            header("Location: index.php?tab=establishments");
            exit;
        }
    }
}


if ($current_tab == 'clusters') {
    $edit_cluster_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $cluster_to_edit = null;

    if ($edit_cluster_id > 0) {
        $stmt = $conn->prepare("SELECT id, name, image_path FROM clusters WHERE id = ?");
        $stmt->bind_param("i", $edit_cluster_id);
        $stmt->execute();
        $cluster_to_edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_cluster'])) {
            $name = trim($_POST['cluster_name']);
            $image_filename = null;

            if (!empty($name)) {
                $uploaded_filename = handle_upload('cluster_image', UPLOAD_DIR_CLUSTERS);
                if ($uploaded_filename) $image_filename = $uploaded_filename;

                $stmt = $conn->prepare("INSERT INTO clusters (name, image_path) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $image_filename);
                if($stmt->execute()){
                    $_SESSION['message'] = "Кластер добавлен успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка добавления кластера: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Название кластера обязательно.";
                $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=clusters");
            exit;
        }

        if (isset($_POST['edit_cluster'])) {
            $id = intval($_POST['cluster_id']);
            $name = trim($_POST['cluster_name']);
            $current_image_filename = trim($_POST['current_image_path']);
            $image_filename_to_save = $current_image_filename;

            if (!empty($name)) {
                 $new_uploaded_filename = handle_upload('cluster_image', UPLOAD_DIR_CLUSTERS);
                if ($new_uploaded_filename) {
                    if(!empty($current_image_filename)) delete_file_from_system($current_image_filename, UPLOAD_DIR_CLUSTERS);
                    $image_filename_to_save = $new_uploaded_filename;
                } elseif (isset($_POST['delete_current_image'])) {
                    if(!empty($current_image_filename)) delete_file_from_system($current_image_filename, UPLOAD_DIR_CLUSTERS);
                    $image_filename_to_save = null;
                }

                $stmt = $conn->prepare("UPDATE clusters SET name = ?, image_path = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $image_filename_to_save, $id);
                 if($stmt->execute()){
                    $_SESSION['message'] = "Кластер обновлен успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка обновления кластера: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Название кластера обязательно.";
                $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=clusters");
            exit;
        }

        if (isset($_POST['delete_cluster'])) {
            $id = intval($_POST['cluster_id']);
           
            $bundle_check_stmt = $conn->prepare("SELECT id FROM bundles WHERE cluster_id = ?");
            $bundle_check_stmt->bind_param("i", $id);
            $bundle_check_stmt->execute();
            if ($bundle_check_stmt->get_result()->num_rows > 0) {
                 $_SESSION['message'] = "Удаление невозможно! Кластер используется в связках. Сначала удалите его из всех связок.";
                 $_SESSION['message_type'] = "error";
            } else {
                $cluster_data_stmt = $conn->prepare("SELECT image_path FROM clusters WHERE id = ?");
                $cluster_data_stmt->bind_param("i", $id);
                $cluster_data_stmt->execute();
                $cluster_data = $cluster_data_stmt->get_result()->fetch_assoc();
                if ($cluster_data && !empty($cluster_data['image_path'])) {
                    delete_file_from_system($cluster_data['image_path'], UPLOAD_DIR_CLUSTERS);
                }
                $cluster_data_stmt->close();

                $delete_stmt = $conn->prepare("DELETE FROM clusters WHERE id = ?");
                $delete_stmt->bind_param("i", $id);
                if($delete_stmt->execute()){
                    $_SESSION['message'] = "Кластер удален успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка удаления кластера: " . $delete_stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $delete_stmt->close();
            }
            $bundle_check_stmt->close();
            header("Location: index.php?tab=clusters");
            exit;
        }
    }
}


if ($current_tab == 'bundles') {
    $edit_bundle_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $bundle_to_edit = null;

    if ($edit_bundle_id > 0) {
        $stmt = $conn->prepare("SELECT * FROM bundles WHERE id = ?");
        $stmt->bind_param("i", $edit_bundle_id);
        $stmt->execute();
        $bundle_to_edit = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    
    $establishments_for_select = $conn->query("SELECT id, name FROM establishments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    $programs_for_select = $conn->query("SELECT id, name FROM programs ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    $clusters_for_select = $conn->query("SELECT id, name FROM clusters ORDER BY name")->fetch_all(MYSQLI_ASSOC);


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_bundle'])) {
            $establishment_id = intval($_POST['establishment_id']);
            $program_id = intval($_POST['program_id']);
            $education_type = trim($_POST['education_type']);
            $education_base = trim($_POST['education_base']);
            $duration = trim($_POST['duration']);
            $program_address = trim($_POST['program_address']);
            $program_latitude = trim($_POST['program_latitude']);
            $program_longitude = trim($_POST['program_longitude']);
            $cluster_id = !empty($_POST['cluster_id']) ? intval($_POST['cluster_id']) : NULL;


            if ($establishment_id > 0 && $program_id > 0) {
                $stmt = $conn->prepare("INSERT INTO bundles (establishment_id, program_id, education_type, education_base, duration, program_address, program_latitude, program_longitude, cluster_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissssssi", $establishment_id, $program_id, $education_type, $education_base, $duration, $program_address, $program_latitude, $program_longitude, $cluster_id);
                if($stmt->execute()){
                     $_SESSION['message'] = "Связка добавлена успешно!";
                     $_SESSION['message_type'] = "success";
                } else {
                     $_SESSION['message'] = "Ошибка добавления связки: " . $stmt->error;
                     $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Выбор колледжа и программы обязателен.";
                $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=bundles");
            exit;
        }

        if (isset($_POST['edit_bundle'])) {
            $id = intval($_POST['bundle_id']);
            $establishment_id = intval($_POST['establishment_id']);
            $program_id = intval($_POST['program_id']);
            $education_type = trim($_POST['education_type']);
            $education_base = trim($_POST['education_base']);
            $duration = trim($_POST['duration']);
            $program_address = trim($_POST['program_address']);
            $program_latitude = trim($_POST['program_latitude']);
            $program_longitude = trim($_POST['program_longitude']);
            $cluster_id = !empty($_POST['cluster_id']) ? intval($_POST['cluster_id']) : NULL;

             if ($establishment_id > 0 && $program_id > 0) {
                $stmt = $conn->prepare("UPDATE bundles SET establishment_id=?, program_id=?, education_type=?, education_base=?, duration=?, program_address=?, program_latitude=?, program_longitude=?, cluster_id=? WHERE id=?");
                $stmt->bind_param("iissssssii", $establishment_id, $program_id, $education_type, $education_base, $duration, $program_address, $program_latitude, $program_longitude, $cluster_id, $id);
                 if($stmt->execute()){
                     $_SESSION['message'] = "Связка обновлена успешно!";
                     $_SESSION['message_type'] = "success";
                } else {
                     $_SESSION['message'] = "Ошибка обновления связки: " . $stmt->error;
                     $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = "Выбор колледжа и программы обязателен.";
                $_SESSION['message_type'] = "error";
            }
            header("Location: index.php?tab=bundles");
            exit;
        }

        if (isset($_POST['delete_bundle'])) {
            $id = intval($_POST['bundle_id']);
            $stmt = $conn->prepare("DELETE FROM bundles WHERE id = ?");
            $stmt->bind_param("i", $id);
            if($stmt->execute()){
                $_SESSION['message'] = "Связка удалена успешно!";
                $_SESSION['message_type'] = "success";
            } else {
                 $_SESSION['message'] = "Ошибка удаления связки: " . $stmt->error;
                 $_SESSION['message_type'] = "error";
            }
            $stmt->close();
            header("Location: index.php?tab=bundles");
            exit;
        }
    }
}


?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .admin-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .admin-header h1 { margin: 0; font-size: 1.8em; }
        .admin-nav ul { list-style-type: none; padding: 0; margin: 0; display: flex; flex-wrap: wrap; }
        .admin-nav li { margin-right: 10px; margin-bottom: 5px;}
        .admin-nav a { text-decoration: none; color: #007bff; font-weight: bold; padding: 5px 10px; border-radius: 4px; display: inline-block; }
        .admin-nav a:hover, .admin-nav a.active { background-color: #007bff; color: #fff; }
        .content-section { margin-bottom: 30px; }
        .content-section h2 { border-bottom: 2px solid #007bff; padding-bottom: 5px; margin-bottom: 15px; font-size: 1.5em; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9em; }
        table th, table td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; vertical-align: top;}
        table th { background-color: #f0f0f0; }
        table img.thumbnail { max-width: 50px; max-height: 50px; border-radius: 4px; }
        .action-links form { display: inline-block; margin-right: 5px; }
        .action-links a, .action-links button { display: inline-block; margin-right: 5px; color: #007bff; text-decoration: none; cursor: pointer; padding: 3px 6px; border-radius:3px; font-size:0.9em }
        .action-links button { background: none; border: 1px solid; color: red; }
        .action-links a { border: 1px solid #007bff;}
        .action-links a:hover { background-color:#007bff; color:white;}
        .action-links button:hover { background-color:red; color:white;}


        .form-container { background-color: #f9f9f9; padding: 20px; border-radius: 5px; border: 1px solid #eee; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="file"],
        .form-group textarea,
        .form-group select {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .btn { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .btn-danger { background-color: #dc3545; }
        .btn-primary { background-color: #007bff; }
        .btn:hover { opacity: 0.9; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .login-container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; margin: 50px auto; }
        .login-container h2 { text-align: center; margin-top: 0; margin-bottom: 20px; }
        .login-container .btn { width: 100%; }
        .error-text { color: red; margin-bottom: 10px; }
        #map-placeholder { width: 100%; height: 300px; background-color: #e9e9e9; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; text-align: center; color: #777; margin-top: 10px; border-radius: 4px; }
        #map-placeholder-bundle { width: 100%; height: 300px; background-color: #e9e9e9; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; text-align: center; color: #777; margin-top: 10px; border-radius: 4px; }
        .current-image-admin { max-width: 100px; max-height: 100px; margin-top: 10px; display: block; border:1px solid #ddd; padding:2px; border-radius:4px; }
        .form-group input[type="checkbox"] { width: auto; margin-right: 5px; vertical-align: middle;}
        .form-group label.checkbox-label { font-weight: normal; display:inline; }

        .program-selection-group, .attribute-selection-group {
             border: 1px solid #ccc;
             padding: 10px;
             border-radius: 4px;
             max-height: 150px;
             overflow-y: auto;
             background-color: white;
         }
        .program-selection-group label, .attribute-selection-group label {
             display: block;
             font-weight: normal;
             margin-bottom: 5px;
         }
         .program-selection-group input[type="checkbox"],
         .attribute-selection-group input[type="checkbox"] {
             width: auto;
             margin-right: 8px;
         }
         .admin-nav .user-info { margin-left: auto; padding: 5px 10px; text-align:right; }
         .admin-nav .user-info span { margin-right: 10px; }
    </style>
    <?php if (in_array($current_tab, ['establishments', 'bundles'])): ?>
       <script src="https://api-maps.yandex.ru/2.1/?apikey=45e028ca-92c7-4576-9119-12e906d9c092&lang=ru_RU" type="text/javascript"></script>
    <?php endif; ?>
</head>
<body>
    <?php if ($current_tab == 'login'): ?>
        <div class="login-container">
            <h2>Авторизация</h2>
            <?php
            $login_error_msg = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
            unset($_SESSION['login_error']);
            if (!empty($login_error_msg)): ?>
                <p class="error-text"><?php echo htmlspecialchars($login_error_msg); ?></p>
            <?php endif; ?>
            <form action="index.php?tab=login" method="post">
                <input type="hidden" name="admin_login" value="1">
                <div class="form-group">
                    <label for="username">Логин</label>
                    <input type="text" name="username" id="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" name="password" id="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Войти</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="admin-container">
            <div class="admin-header">
                <h1>Панель Администратора</h1>
                 <div class="user-info"> <span>Привет, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>
                    <a href="index.php?tab=logout" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.9em;">Выход</a>
                </div>
            </div>

            <nav class="admin-nav">
                <ul>
                    <li><a href="?tab=directions" class="<?php if($current_tab == 'directions') echo 'active'; ?>">Направления</a></li>
                    <li><a href="?tab=programs" class="<?php if($current_tab == 'programs') echo 'active'; ?>">Программы</a></li>
                    <li><a href="?tab=establishments" class="<?php if($current_tab == 'establishments') echo 'active'; ?>">Учебные Заведения</a></li>
                    <li><a href="?tab=bundles" class="<?php if($current_tab == 'bundles') echo 'active'; ?>">Связки</a></li>
                    <li><a href="?tab=clusters" class="<?php if($current_tab == 'clusters') echo 'active'; ?>">Кластеры</a></li>
                </ul>
            </nav>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($current_tab == 'directions'): ?>
            <div id="directions" class="content-section">
                <h2>Управление Направлениями</h2>
                <div class="form-container">
                    <h3><?php echo $direction_to_edit ? 'Редактировать Направление' : 'Добавить новое направление'; ?></h3>
                    <form action="index.php?tab=directions" method="post" enctype="multipart/form-data">
                        <?php if ($direction_to_edit): ?>
                            <input type="hidden" name="direction_id" value="<?php echo $direction_to_edit['id']; ?>">
                            <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($direction_to_edit['image_path']); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="direction_name">Название направления:</label>
                            <input type="text" id="direction_name" name="direction_name" value="<?php echo $direction_to_edit ? htmlspecialchars($direction_to_edit['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="direction_code">Код направления (например, 08.00.00):</label>
                            <input type="text" id="direction_code" name="direction_code" value="<?php echo $direction_to_edit ? htmlspecialchars($direction_to_edit['program_code_identifier']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="direction_image">Изображение (для главной страницы):</label>
                            <input type="file" id="direction_image" name="direction_image" accept="image/*">
                             <?php if ($direction_to_edit && !empty($direction_to_edit['image_path'])):
                                $imgPath = UPLOAD_DIR_DIRECTIONS . $direction_to_edit['image_path'];
                                $webPath = '../uploads/directions/' . $direction_to_edit['image_path'];
                                if (file_exists($imgPath)): ?>
                                <img src="<?php echo htmlspecialchars($webPath); ?>" alt="Текущее изображение" class="current-image-admin">
                                <div>
                                    <input type="checkbox" name="delete_current_image" id="delete_direction_image_admin">
                                    <label class="checkbox-label" for="delete_direction_image_admin">Удалить текущее изображение</label>
                                </div>
                             <?php endif; endif; ?>
                        </div>
                        <button type="submit" name="<?php echo $direction_to_edit ? 'edit_direction' : 'add_direction'; ?>" class="btn"><?php echo $direction_to_edit ? 'Сохранить Изменения' : 'Добавить Направление'; ?></button>
                         <?php if ($direction_to_edit): ?>
                            <a href="index.php?tab=directions" class="btn btn-danger" style="background-color:#6c757d;">Отмена</a>
                        <?php endif; ?>
                    </form>
                </div>

                <h3>Список Направлений</h3>
                <table>
                    <thead><tr><th>ID</th><th>Название</th><th>Код</th><th>Изображение</th><th>Действия</th></tr></thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT id, name, program_code_identifier, image_path FROM directions ORDER BY name");
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr><td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['program_code_identifier']) . "</td><td>";
                                if (!empty($row['image_path'])) {
                                    $imgPath = UPLOAD_DIR_DIRECTIONS . $row['image_path'];
                                    $webPath = '../uploads/directions/' . $row['image_path'];
                                    if(file_exists($imgPath)) echo "<img src='" . htmlspecialchars($webPath) . "' alt='Image' class='thumbnail'>"; else echo "Файл не найден";
                                } else { echo "Нет изображения"; }
                                echo "</td><td class='action-links'>
                                        <a href='index.php?tab=directions&edit_id=" . $row['id'] . "'>Редакт.</a>
                                        <form action='index.php?tab=directions' method='post' onsubmit='return confirm(\"Удалить направление? Связанные программы также будут удалены, если они есть и настроено каскадное удаление в БД, ИЛИ удаление будет заблокировано, если есть связанные программы и нет каскадного удаления.\");'>
                                            <input type='hidden' name='direction_id' value='" . $row['id'] . "'>
                                            <button type='submit' name='delete_direction'>Удалить</button>
                                        </form>
                                      </td></tr>";
                            }
                        } else { echo "<tr><td colspan='5'>Направлений не найдено.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>


            <?php if ($current_tab == 'programs'): ?>
            <div id="programs" class="content-section">
                <h2>Управление Программами (Специальностями)</h2>
                <div class="form-container">
                    <h3><?php echo $program_to_edit ? 'Редактировать Программу' : 'Добавить новую программу'; ?></h3>
                    <form action="index.php?tab=programs" method="post" enctype="multipart/form-data">
                        <?php if ($program_to_edit): ?>
                            <input type="hidden" name="program_id" value="<?php echo $program_to_edit['id']; ?>">
                            <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($program_to_edit['image_path']); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="program_name_admin">Название программы:</label>
                            <input type="text" id="program_name_admin" name="program_name" value="<?php echo $program_to_edit ? htmlspecialchars($program_to_edit['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="program_code_admin">Код программы (например, 08.02.01):</label>
                            <input type="text" id="program_code_admin" name="program_code" value="<?php echo $program_to_edit ? htmlspecialchars($program_to_edit['program_code']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="direction_id_admin">Принадлежит направлению:</label>
                            <select id="direction_id_admin" name="direction_id" required>
                                <option value="">-- Выберите направление --</option>
                                <?php
                                $directions_result = $conn->query("SELECT id, name FROM directions ORDER BY name");
                                while($dir_row = $directions_result->fetch_assoc()) {
                                    $selected = ($program_to_edit && $dir_row['id'] == $program_to_edit['direction_id']) ? 'selected' : '';
                                    echo "<option value='" . $dir_row['id'] . "' " . $selected . ">" . htmlspecialchars($dir_row['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="program_keywords_admin">Ключевые слова (для поиска, через запятую):</label>
                            <textarea id="program_keywords_admin" name="program_keywords"><?php echo $program_to_edit ? htmlspecialchars($program_to_edit['keywords']) : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Атрибуты программы:</label>
                            <div class="attribute-selection-group">
                            <?php
                            $program_attributes_current = $program_to_edit ? array_map('trim', explode(', ', $program_to_edit['attributes'])) : [];
                           
                           
                           
                            $attributes_from_db = $conn->query("SELECT title FROM atributes WHERE location='programs'")->fetch_all(MYSQLI_ASSOC);
                            if (empty($attributes_from_db)) {
                                echo "Нет определенных атрибутов.";
                            } else {
                                foreach($attributes_from_db as $attr_db) {
                                    $attr_title = htmlspecialchars($attr_db['title']);
                                    $checked = in_array($attr_db['title'], $program_attributes_current) ? 'checked' : '';
                                    echo "<label class='checkbox-label'><input type='checkbox' name='program_attributes[]' value='{$attr_title}' {$checked}> {$attr_title}</label><br>";
                                }
                            }
                            ?>
                            </div>
                            </div>
                        <div class="form-group">
                            <label for="program_image_admin">Изображение (для карточки программы):</label>
                            <input type="file" id="program_image_admin" name="program_image" accept="image/*">
                             <?php if ($program_to_edit && !empty($program_to_edit['image_path'])): 
                                $imgPath = UPLOAD_DIR_PROGRAMS . $program_to_edit['image_path'];
                                $webPath = '../uploads/programs/' . $program_to_edit['image_path'];
                                if (file_exists($imgPath)): ?>
                                <img src="<?php echo htmlspecialchars($webPath); ?>" alt="Текущее изображение" class="current-image-admin">
                                <div>
                                    <input type="checkbox" name="delete_current_image" id="delete_program_image_admin">
                                    <label class="checkbox-label" for="delete_program_image_admin">Удалить текущее изображение</label>
                                </div>
                             <?php endif; endif; ?>
                        </div>
                        <button type="submit" name="<?php echo $program_to_edit ? 'edit_program' : 'add_program'; ?>" class="btn"><?php echo $program_to_edit ? 'Сохранить Изменения' : 'Добавить Программу'; ?></button>
                         <?php if ($program_to_edit): ?>
                            <a href="index.php?tab=programs" class="btn btn-danger" style="background-color:#6c757d;">Отмена</a>
                        <?php endif; ?>
                    </form>
                </div>

                <h3>Список Программ</h3>
                <table>
                    <thead><tr><th>ID</th><th>Название</th><th>Код</th><th>Направление</th><th>Атрибуты</th><th>Изображение</th><th>Действия</th></tr></thead>
                    <tbody>
                        <?php
                        $prog_sql = "SELECT p.id, p.name, p.program_code, p.attributes, p.image_path, d.name AS direction_name
                                     FROM programs p JOIN directions d ON p.direction_id = d.id ORDER BY d.name, p.name";
                        $prog_result = $conn->query($prog_sql);
                        if ($prog_result->num_rows > 0) {
                            while($row = $prog_result->fetch_assoc()) {
                                echo "<tr><td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['program_code']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['direction_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['attributes']) . "</td><td>";
                                if (!empty($row['image_path'])) {
                                    $imgPath = UPLOAD_DIR_PROGRAMS . $row['image_path'];
                                    $webPath = '../uploads/programs/' . $row['image_path'];
                                    if(file_exists($imgPath)) echo "<img src='" . htmlspecialchars($webPath) . "' alt='Image' class='thumbnail'>"; else echo "Файл не найден";
                                } else { echo "Нет изображения"; }
                                echo "</td><td class='action-links'>
                                        <a href='index.php?tab=programs&edit_id=" . $row['id'] . "'>Редакт.</a>
                                        <form action='index.php?tab=programs' method='post' onsubmit='return confirm(\"Удалить программу? Связанные связки также будут удалены, если настроено каскадное удаление.\");'>
                                            <input type='hidden' name='program_id' value='" . $row['id'] . "'>
                                            <button type='submit' name='delete_program'>Удалить</button>
                                        </form>
                                      </td></tr>";
                            }
                        } else { echo "<tr><td colspan='7'>Программ не найдено.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>


            <?php if ($current_tab == 'establishments'): ?>
            <div id="establishments_admin" class="content-section">
                <h2>Управление Учебными Заведениями</h2>
                 <div class="form-container">
                    <h3><?php echo $establishment_to_edit ? 'Редактировать Учебное Заведение' : 'Добавить новое'; ?></h3>
                    <form action="index.php?tab=establishments" method="post" enctype="multipart/form-data">
                         <?php if ($establishment_to_edit): ?>
                            <input type="hidden" name="establishment_id" value="<?php echo $establishment_to_edit['id']; ?>">
                            <input type="hidden" name="current_logo_path" value="<?php echo htmlspecialchars($establishment_to_edit['logo_path']); ?>">
                         <?php endif; ?>
                        <div class="form-group">
                            <label for="establishment_name_admin">Название заведения:</label>
                            <input type="text" id="establishment_name_admin" name="establishment_name" value="<?php echo $establishment_to_edit ? htmlspecialchars($establishment_to_edit['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="establishment_logo_admin">Логотип:</label>
                            <input type="file" id="establishment_logo_admin" name="establishment_logo" accept="image/*">
                             <?php if ($establishment_to_edit && !empty($establishment_to_edit['logo_path'])): 
                                $imgPath = UPLOAD_DIR_ESTABLISHMENTS . $establishment_to_edit['logo_path'];
                                $webPath = '../uploads/establishments/' . $establishment_to_edit['logo_path'];
                                if (file_exists($imgPath)): ?>
                                <img src="<?php echo htmlspecialchars($webPath); ?>" alt="Текущий логотип" class="current-image-admin">
                                 <div>
                                    <input type="checkbox" name="delete_current_logo" id="delete_establishment_logo_admin">
                                    <label class="checkbox-label" for="delete_establishment_logo_admin">Удалить текущий логотип</label>
                                </div>
                             <?php endif; endif; ?>
                        </div>
                         <div class="form-group">
                            <label for="address_admin">Адрес приемной комиссии:</label>
                            <input type="text" id="address_admin" name="address" value="<?php echo $establishment_to_edit ? htmlspecialchars($establishment_to_edit['address']) : ''; ?>">
                            <div id="map-placeholder">Загрузка карты...</div>
                            <input type="hidden" id="latitude_admin" name="latitude" value="<?php echo $establishment_to_edit ? htmlspecialchars($establishment_to_edit['latitude']) : ''; ?>">
                            <input type="hidden" id="longitude_admin" name="longitude" value="<?php echo $establishment_to_edit ? htmlspecialchars($establishment_to_edit['longitude']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone_admin">Телефон:</label>
                            <input type="text" id="phone_admin" name="phone" value="<?php echo $establishment_to_edit ? htmlspecialchars($establishment_to_edit['phone']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="website_admin">Веб-сайт (полный URL):</label>
                            <input type="text" id="website_admin" name="website" value="<?php echo $establishment_to_edit ? htmlspecialchars($establishment_to_edit['website']) : ''; ?>">
                        </div>
                        <button type="submit" name="<?php echo $establishment_to_edit ? 'edit_establishment' : 'add_establishment'; ?>" class="btn"><?php echo $establishment_to_edit ? 'Сохранить' : 'Добавить Заведение'; ?></button>
                         <?php if ($establishment_to_edit): ?>
                            <a href="index.php?tab=establishments" class="btn btn-danger" style="background-color:#6c757d;">Отмена</a>
                        <?php endif; ?>
                    </form>
                </div>

                <h3>Список Учебных Заведений</h3>
                 <table>
                    <thead><tr><th>ID</th><th>Название</th><th>Адрес приемной</th><th>Телефон</th><th>Сайт</th><th>Лого</th><th>Действия</th></tr></thead>
                    <tbody>
                        <?php
                        $est_sql = "SELECT e.id, e.name, e.address, e.phone, e.website, e.logo_path FROM establishments e ORDER BY e.name";
                        $est_result = $conn->query($est_sql);
                        if ($est_result->num_rows > 0) {
                            while($row = $est_result->fetch_assoc()) {
                                echo "<tr><td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                echo "<td><a href='" . htmlspecialchars($row['website']) . "' target='_blank'>" . htmlspecialchars($row['website']) . "</a></td><td>";
                                if (!empty($row['logo_path'])) {
                                    $imgPath = UPLOAD_DIR_ESTABLISHMENTS . $row['logo_path'];
                                    $webPath = '../uploads/establishments/' . $row['logo_path'];
                                    if(file_exists($imgPath)) echo "<img src='" . htmlspecialchars($webPath) . "' alt='Logo' class='thumbnail'>"; else echo "Файл не найден";
                                } else { echo "Нет лого"; }
                                echo "</td><td class='action-links'>
                                        <a href='index.php?tab=establishments&edit_id=" . $row['id'] . "'>Редакт.</a>
                                        <form action='index.php?tab=establishments' method='post' onsubmit='return confirm(\"Удалить учебное заведение? Связанные связки также будут удалены, если настроено каскадное удаление.\");'>
                                            <input type='hidden' name='establishment_id' value='" . $row['id'] . "'>
                                            <button type='submit' name='delete_establishment'>Удалить</button>
                                        </form>
                                      </td></tr>";
                            }
                        } else { echo "<tr><td colspan='7'>Учебных заведений не найдено.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($current_tab == 'clusters'): ?>
            <div id="clusters_admin" class="content-section">
                <h2>Управление Кластерами Профессионалитета</h2>
                <div class="form-container">
                    <h3><?php echo $cluster_to_edit ? 'Редактировать кластер' : 'Добавить новый кластер'; ?></h3>
                    <form action="index.php?tab=clusters" method="post" enctype="multipart/form-data">
                        <?php if ($cluster_to_edit): ?>
                            <input type="hidden" name="cluster_id" value="<?php echo $cluster_to_edit['id']; ?>">
                            <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($cluster_to_edit['image_path']); ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="cluster_name_admin">Название кластера:</label>
                            <input type="text" id="cluster_name_admin" name="cluster_name" value="<?php echo $cluster_to_edit ? htmlspecialchars($cluster_to_edit['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="cluster_image_admin">Изображение:</label>
                            <input type="file" id="cluster_image_admin" name="cluster_image" accept="image/*">
                             <?php if ($cluster_to_edit && !empty($cluster_to_edit['image_path'])): 
                                $imgPath = UPLOAD_DIR_CLUSTERS . $cluster_to_edit['image_path'];
                                $webPath = '../uploads/clusters/' . $cluster_to_edit['image_path'];
                                if (file_exists($imgPath)): ?>
                                <img src="<?php echo htmlspecialchars($webPath); ?>" alt="Текущее изображение" class="current-image-admin">
                                <div>
                                    <input type="checkbox" name="delete_current_image" id="delete_cluster_image_admin">
                                    <label class="checkbox-label" for="delete_cluster_image_admin">Удалить текущее изображение</label>
                                </div>
                             <?php endif; endif; ?>
                        </div>
                        <button type="submit" name="<?php echo $cluster_to_edit ? 'edit_cluster' : 'add_cluster'; ?>" class="btn"><?php echo $cluster_to_edit ? 'Сохранить' : 'Добавить кластер'; ?></button>
                         <?php if ($cluster_to_edit): ?>
                            <a href="index.php?tab=clusters" class="btn btn-danger" style="background-color:#6c757d;">Отмена</a>
                        <?php endif; ?>
                    </form>
                </div>

                <h3>Список кластеров</h3>
                <table>
                    <thead><tr><th>ID</th><th>Название</th><th>Изображение</th><th>Действия</th></tr></thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT id, name, image_path FROM clusters ORDER BY name");
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr><td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td><td>";
                                if (!empty($row['image_path'])) {
                                     $imgPath = UPLOAD_DIR_CLUSTERS . $row['image_path'];
                                     $webPath = '../uploads/clusters/' . $row['image_path'];
                                    if(file_exists($imgPath)) echo "<img src='" . htmlspecialchars($webPath) . "' alt='Image' class='thumbnail'>"; else echo "Файл не найден";
                                } else { echo "Нет изображения"; }
                                echo "</td><td class='action-links'>
                                        <a href='index.php?tab=clusters&edit_id=" . $row['id'] . "'>Редакт.</a>
                                        <form action='index.php?tab=clusters' method='post' onsubmit='return confirm(\"Удалить кластер? Если он используется в связках, удаление может быть заблокировано или поле cluster_id в связках станет NULL.\");'>
                                            <input type='hidden' name='cluster_id' value='" . $row['id'] . "'>
                                            <button type='submit' name='delete_cluster'>Удалить</button>
                                        </form>
                                      </td></tr>";
                            }
                        } else { echo "<tr><td colspan='4'>Кластеров не найдено.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ($current_tab == 'bundles'): ?>
            <div id="bundles_admin" class="content-section">
                <h2>Управление Связками (Колледж-Программа)</h2>
                <div class="form-container">
                    <h3><?php echo $bundle_to_edit ? 'Редактировать связку' : 'Добавить новую связку'; ?></h3>
                    <form action="index.php?tab=bundles" method="post">
                        <?php if ($bundle_to_edit): ?>
                            <input type="hidden" name="bundle_id" value="<?php echo $bundle_to_edit['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="establishment_id_bundle">Колледж:</label>
                            <select id="establishment_id_bundle" name="establishment_id" required>
                                <option value="">-- Выберите колледж --</option>
                                <?php foreach ($establishments_for_select as $est): ?>
                                <option value="<?php echo $est['id']; ?>" <?php if($bundle_to_edit && $bundle_to_edit['establishment_id'] == $est['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($est['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="program_id_bundle">Программа:</label>
                            <select id="program_id_bundle" name="program_id" required>
                                <option value="">-- Выберите программу --</option>
                                <?php foreach ($programs_for_select as $prog): ?>
                                <option value="<?php echo $prog['id']; ?>" <?php if($bundle_to_edit && $bundle_to_edit['program_id'] == $prog['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($prog['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="education_type_bundle">Образование (например, Бесплатно):</label>
                            <input type="text" id="education_type_bundle" name="education_type" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['education_type']) : 'Бесплатно'; ?>">
                        </div>
                        <div class="form-group">
                            <label for="education_base_bundle">На базе (например, 9 классов):</label>
                            <input type="text" id="education_base_bundle" name="education_base" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['education_base']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="duration_bundle">Срок обучения (например, 3 года 10 месяцев):</label>
                            <input type="text" id="duration_bundle" name="duration" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['duration']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="program_address_bundle">Адрес проведения программы:</label>
                            <input type="text" id="program_address_bundle" name="program_address" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['program_address']) : ''; ?>">
                            <div id="map-placeholder-bundle">Загрузка карты...</div>
                            <input type="hidden" id="program_latitude_bundle" name="program_latitude" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['program_latitude']) : ''; ?>">
                            <input type="hidden" id="program_longitude_bundle" name="program_longitude" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['program_longitude']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="cluster_id_bundle">Профессионалитет (Кластер):</label>
                            <select id="cluster_id_bundle" name="cluster_id">
                                <option value="">--- (Не входит в Профессионалитет) ---</option>
                                <?php foreach ($clusters_for_select as $cluster): ?>
                                <option value="<?php echo $cluster['id']; ?>" <?php if($bundle_to_edit && $bundle_to_edit['cluster_id'] == $cluster['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cluster['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="<?php echo $bundle_to_edit ? 'edit_bundle' : 'add_bundle'; ?>" class="btn"><?php echo $bundle_to_edit ? 'Сохранить' : 'Добавить связку'; ?></button>
                         <?php if ($bundle_to_edit): ?>
                            <a href="index.php?tab=bundles" class="btn btn-danger" style="background-color:#6c757d;">Отмена</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <h3>Список связок</h3>
                <table>
                    <thead><tr><th>ID</th><th>Колледж</th><th>Программа</th><th>Образование</th><th>На базе</th><th>Срок</th><th>Адрес программы</th><th>Кластер</th><th>Действия</th></tr></thead>
                    <tbody>
                    <?php
                    $bundles_sql = "SELECT b.*, e.name as establishment_name, p.name as program_name, c.name as cluster_name 
                                    FROM bundles b 
                                    JOIN establishments e ON b.establishment_id = e.id 
                                    JOIN programs p ON b.program_id = p.id
                                    LEFT JOIN clusters c ON b.cluster_id = c.id
                                    ORDER BY e.name, p.name";
                    $bundles_result = $conn->query($bundles_sql);
                    if ($bundles_result->num_rows > 0) {
                        while($row = $bundles_result->fetch_assoc()) {
                            echo "<tr><td>" . htmlspecialchars($row['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['establishment_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['program_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['education_type']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['education_base']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['duration']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['program_address']) . "</td>";
                            echo "<td>" . ($row['cluster_name'] ? htmlspecialchars($row['cluster_name']) : '---') . "</td>";
                            echo "<td class='action-links'>
                                    <a href='index.php?tab=bundles&edit_id=" . $row['id'] . "'>Редакт.</a>
                                    <form action='index.php?tab=bundles' method='post' onsubmit='return confirm(\"Удалить эту связку?\");'>
                                        <input type='hidden' name='bundle_id' value='" . $row['id'] . "'>
                                        <button type='submit' name='delete_bundle'>Удалить</button>
                                    </form>
                                  </td></tr>";
                        }
                    } else { echo "<tr><td colspan='9'>Связок не найдено.</td></tr>"; }
                    ?>
                    </tbody>
                </table>

            </div>
            <?php endif; ?>


        </div> 
    
        <script type="text/javascript">
            <?php if ($current_tab == 'establishments' || $current_tab == 'bundles'): ?>
            ymaps.ready(initMaps);
            function initMaps() {
                <?php if ($current_tab == 'establishments'): ?>
                setupMap('address_admin', 'latitude_admin', 'longitude_admin', 'map-placeholder', 
                    '<?php echo $establishment_to_edit && $establishment_to_edit['latitude'] ? $establishment_to_edit['latitude'] : '59.9343'; ?>',
                    '<?php echo $establishment_to_edit && $establishment_to_edit['longitude'] ? $establishment_to_edit['longitude'] : '30.3351'; ?>',
                    '<?php echo $establishment_to_edit && ($establishment_to_edit['latitude'] || $establishment_to_edit['longitude']) ? '15' : '10'; ?>',
                    '<?php echo $establishment_to_edit ? htmlspecialchars(addslashes($establishment_to_edit['address'])) : ''; ?>'
                );
                <?php endif; ?>

                <?php if ($current_tab == 'bundles'): ?>
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

    <?php endif; ?>
</body>
</html>
<?php if(isset($conn)) $conn->close(); ?>