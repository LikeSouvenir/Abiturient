<?php
$edit_establishment_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$establishment_to_edit = null;

if ($edit_establishment_id > 0) {
    $establishment_to_edit = getEditData($conn, 'establishments', $edit_establishment_id, 'id, name, address, latitude, longitude, phone, website, logo_path');
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
?>

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