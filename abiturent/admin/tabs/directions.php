<?php
$edit_direction_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$direction_to_edit = null;

if ($edit_direction_id > 0) {
    $direction_to_edit = getEditData($conn, 'directions', $edit_direction_id, 'id, name, program_code_identifier, image_path');
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
?>

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