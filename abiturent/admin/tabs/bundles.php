<?php
$edit_bundle_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$bundle_to_edit = null;

if ($edit_bundle_id > 0) {
    $bundle_to_edit = getEditData($conn, 'bundles', $edit_bundle_id);
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
?>

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
                <label for="education_type_bundle">Образование (например, За счет бюджетных средств):</label>
                <input type="text" id="education_type_bundle" name="education_type" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['education_type']) : 'За счет бюджетных средств'; ?>">
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