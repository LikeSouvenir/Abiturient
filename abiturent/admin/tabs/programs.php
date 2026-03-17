<?php
$edit_program_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$program_to_edit = null;

if ($edit_program_id > 0) {
    $program_to_edit = getEditData($conn, 'programs', $edit_program_id, 'id, name, program_code, direction_id, keywords, attributes, image_path');
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
?>

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