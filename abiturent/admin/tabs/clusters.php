<?php
$edit_cluster_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$cluster_to_edit = null;

if ($edit_cluster_id > 0) {
    $cluster_to_edit = getEditData($conn, 'clusters', $edit_cluster_id, 'id, name, image_path');
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
?>

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