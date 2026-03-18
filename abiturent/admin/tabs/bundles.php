<?php
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
$programs_for_select = $conn->query("SELECT id, name, attributes FROM programs ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$clusters_for_select = $conn->query("SELECT id, name FROM clusters ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Получаем адреса для выбранного заведения (для AJAX)
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_addresses' && isset($_GET['establishment_id'])) {
    header('Content-Type: application/json');
    $est_id = intval($_GET['establishment_id']);
    $addresses = getEstablishmentAddresses($conn, $est_id);
    echo json_encode($addresses);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_bundle'])) {
        $establishment_id = intval($_POST['establishment_id']);
        $program_id = intval($_POST['program_id']);
        $education_type = trim($_POST['education_type']);
        $education_base = trim($_POST['education_base']);
        $duration_years = intval($_POST['duration_years']);
        $duration_months = intval($_POST['duration_months']);
        $program_address = trim($_POST['program_address']);
        $program_latitude = trim($_POST['program_latitude']);
        $program_longitude = trim($_POST['program_longitude']);
        $cluster_id = !empty($_POST['cluster_id']) ? intval($_POST['cluster_id']) : NULL;
        
        // Получаем значение чекбокса для 2 ОГЭ
        $oge_2 = isset($_POST['oge_2']) ? 1 : 0;
        
        // Формируем строку длительности
        $duration = '';
        if ($duration_years > 0) {
            $duration .= $duration_years . ' г. ';
        }
        if ($duration_months > 0) {
            $duration .= $duration_months . ' мес.';
        }
        $duration = trim($duration);

        if ($establishment_id > 0 && $program_id > 0) {
            $stmt = $conn->prepare("INSERT INTO bundles (establishment_id, program_id, education_type, education_base, duration, program_address, program_latitude, program_longitude, cluster_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssssi", $establishment_id, $program_id, $education_type, $education_base, $duration, $program_address, $program_latitude, $program_longitude, $cluster_id);
            if($stmt->execute()){
                
                // Обновляем атрибуты программы для 2 ОГЭ
                $attributes = $oge_2 ? '2 ОГЭ' : NULL;
                $update_program = $conn->prepare("UPDATE programs SET attributes = ? WHERE id = ?");
                $update_program->bind_param("si", $attributes, $program_id);
                $update_program->execute();
                $update_program->close();
                
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
        $duration_years = intval($_POST['duration_years']);
        $duration_months = intval($_POST['duration_months']);
        $program_address = trim($_POST['program_address']);
        $program_latitude = trim($_POST['program_latitude']);
        $program_longitude = trim($_POST['program_longitude']);
        $cluster_id = !empty($_POST['cluster_id']) ? intval($_POST['cluster_id']) : NULL;
        
        // Получаем значение чекбокса для 2 ОГЭ
        $oge_2 = isset($_POST['oge_2']) ? 1 : 0;
        
        // Формируем строку длительности
        $duration = '';
        if ($duration_years > 0) {
            $duration .= $duration_years . ' г. ';
        }
        if ($duration_months > 0) {
            $duration .= $duration_months . ' мес.';
        }
        $duration = trim($duration);

        if ($establishment_id > 0 && $program_id > 0) {
            $stmt = $conn->prepare("UPDATE bundles SET establishment_id=?, program_id=?, education_type=?, education_base=?, duration=?, program_address=?, program_latitude=?, program_longitude=?, cluster_id=? WHERE id=?");
            $stmt->bind_param("iissssssii", $establishment_id, $program_id, $education_type, $education_base, $duration, $program_address, $program_latitude, $program_longitude, $cluster_id, $id);
            if($stmt->execute()){
                
                // Обновляем атрибуты программы для 2 ОГЭ
                $attributes = $oge_2 ? '2 ОГЭ' : NULL;
                $update_program = $conn->prepare("UPDATE programs SET attributes = ? WHERE id = ?");
                $update_program->bind_param("si", $attributes, $program_id);
                $update_program->execute();
                $update_program->close();
                
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

// Функция для разбора длительности
function parseDuration($duration) {
    $years = 0;
    $months = 0;
    
    if (preg_match('/(\d+)\s*г/', $duration, $matches)) {
        $years = intval($matches[1]);
    }
    if (preg_match('/(\d+)\s*мес/', $duration, $matches)) {
        $months = intval($matches[1]);
    }
    
    return ['years' => $years, 'months' => $months];
}

$duration_parts = ['years' => 0, 'months' => 0];
if ($bundle_to_edit && !empty($bundle_to_edit['duration'])) {
    $duration_parts = parseDuration($bundle_to_edit['duration']);
}

// Получаем текущее значение 2 ОГЭ для программы
$oge_2_checked = false;
if ($bundle_to_edit) {
    // Получаем атрибуты программы
    $prog_stmt = $conn->prepare("SELECT attributes FROM programs WHERE id = ?");
    $prog_stmt->bind_param("i", $bundle_to_edit['program_id']);
    $prog_stmt->execute();
    $prog_result = $prog_stmt->get_result();
    if ($prog_row = $prog_result->fetch_assoc()) {
        $oge_2_checked = ($prog_row['attributes'] == '2 ОГЭ');
    }
    $prog_stmt->close();
}
?>

<div id="bundles_admin" class="content-section">
    <h2>Управление Связками (Колледж-Программа)</h2>
    <div class="form-container">
        <h3><?php echo $bundle_to_edit ? 'Редактировать связку' : 'Добавить новую связку'; ?></h3>
        <form action="index.php?tab=bundles" method="post" id="bundleForm">
            <?php if ($bundle_to_edit): ?>
                <input type="hidden" name="bundle_id" value="<?php echo $bundle_to_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="establishment_id_bundle">Колледж:</label>
                <select id="establishment_id_bundle" name="establishment_id" required onchange="loadAddresses(this.value)">
                    <option value="">-- Выберите колледж --</option>
                    <?php foreach ($establishments_for_select as $est): ?>
                    <option value="<?php echo $est['id']; ?>" 
                        <?php if($bundle_to_edit && $bundle_to_edit['establishment_id'] == $est['id']) echo 'selected'; ?>
                        data-addresses='<?php 
                            $addrs = getEstablishmentAddresses($conn, $est['id']);
                            echo htmlspecialchars(json_encode($addrs), ENT_QUOTES, 'UTF-8');
                        ?>'>
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
                    <option value="<?php echo $prog['id']; ?>" 
                        <?php if($bundle_to_edit && $bundle_to_edit['program_id'] == $prog['id']) echo 'selected'; ?>
                        data-attributes="<?php echo htmlspecialchars($prog['attributes'] ?? ''); ?>">
                        <?php echo htmlspecialchars($prog['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="oge_2" name="oge_2" <?php echo $oge_2_checked ? 'checked' : ''; ?>>
                    2 ОГЭ
                </label>
                <small style="color: #666; display: block; margin-top: 5px;">Отметьте, если для поступления нужно сдать 2 ОГЭ</small>
            </div>
            
            <div class="form-group">
                <label for="education_type_bundle">Образование (например, За счет бюджетных средств):</label>
                <input type="text" id="education_type_bundle" name="education_type" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['education_type']) : 'За счет бюджетных средств'; ?>">
            </div>
            
            <div class="form-group">
                <label for="education_base_bundle">На базе:</label>
                <select id="education_base_bundle" name="education_base" required>
                    <option value="">-- Выберите базу --</option>
                    <option value="9 классов" <?php echo ($bundle_to_edit && $bundle_to_edit['education_base'] == '9 классов') ? 'selected' : ''; ?>>9 классов</option>
                    <option value="11 классов" <?php echo ($bundle_to_edit && $bundle_to_edit['education_base'] == '11 классов') ? 'selected' : ''; ?>>11 классов</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Срок обучения:</label>
                <div style="display: flex; gap: 20px; align-items: center;">
                    <div style="flex: 1;">
                        <label for="duration_years">Лет:</label>
                        <input type="range" id="duration_years" name="duration_years" min="0" max="5" value="<?php echo $duration_parts['years']; ?>" oninput="updateDurationDisplay()">
                        <span id="years_display"><?php echo $duration_parts['years']; ?></span>
                    </div>
                    <div style="flex: 1;">
                        <label for="duration_months">Месяцев:</label>
                        <input type="range" id="duration_months" name="duration_months" min="0" max="11" value="<?php echo $duration_parts['months']; ?>" oninput="updateDurationDisplay()">
                        <span id="months_display"><?php echo $duration_parts['months']; ?></span>
                    </div>
                </div>
                <div style="margin-top: 10px; font-weight: bold;">
                    Итого: <span id="total_duration"><?php 
                        echo $duration_parts['years'] > 0 ? $duration_parts['years'] . ' г. ' : '';
                        echo $duration_parts['months'] > 0 ? $duration_parts['months'] . ' мес.' : '';
                        if ($duration_parts['years'] == 0 && $duration_parts['months'] == 0) echo '0';
                    ?></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="program_address_bundle">Адрес проведения программы:</label>
                <input type="text" id="program_address_bundle" name="program_address" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['program_address']) : ''; ?>" list="addresses-list">
                <datalist id="addresses-list"></datalist>
                <div id="map-placeholder-bundle" style="height: 200px; background: #f0f0f0; margin-top: 5px;"></div>
                <input type="hidden" id="program_latitude_bundle" name="program_latitude" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['program_latitude']) : ''; ?>">
                <input type="hidden" id="program_longitude_bundle" name="program_longitude" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['program_longitude']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="cluster_id_bundle">Профессионалитет (Кластер):</label>
                <select id="cluster_id_bundle" name="cluster_id">
                    <option value="">--- Не входит в Профессионалитет ---</option>
                    <?php foreach ($clusters_for_select as $cluster): ?>
                    <option value="<?php echo $cluster['id']; ?>" <?php if($bundle_to_edit && $bundle_to_edit['cluster_id'] == $cluster['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cluster['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="<?php echo $bundle_to_edit ? 'edit_bundle' : 'add_bundle'; ?>" class="btn">
                <?php echo $bundle_to_edit ? 'Сохранить' : 'Добавить связку'; ?>
            </button>
            <?php if ($bundle_to_edit): ?>
                <a href="index.php?tab=bundles" class="btn btn-danger" style="background-color:#6c757d;">Отмена</a>
            <?php endif; ?>
        </form>
    </div>
    
    <h3>Список связок</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Колледж</th>
                <th>Программа</th>
                <th>2 ОГЭ</th>
                <th>Образование</th>
                <th>На базе</th>
                <th>Срок</th>
                <th>Адрес программы</th>
                <th>Профессионалитет</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $bundles_sql = "SELECT b.*, e.name as establishment_name, p.name as program_name, p.attributes, c.name as cluster_name 
                        FROM bundles b 
                        JOIN establishments e ON b.establishment_id = e.id 
                        JOIN programs p ON b.program_id = p.id
                        LEFT JOIN clusters c ON b.cluster_id = c.id
                        ORDER BY e.name, p.name";
        $bundles_result = $conn->query($bundles_sql);
        if ($bundles_result->num_rows > 0) {
            while($row = $bundles_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['establishment_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['program_name']) . "</td>";
                echo "<td>" . ($row['attributes'] == '2 ОГЭ' ? 'Да' : 'Нет') . "</td>";
                echo "<td>" . htmlspecialchars($row['education_type']) . "</td>";
                echo "<td>" . htmlspecialchars($row['education_base']) . "</td>";
                echo "<td>" . htmlspecialchars($row['duration']) . "</td>";
                echo "<td>" . htmlspecialchars($row['program_address']) . "</td>";
                echo "<td>" . ($row['cluster_name'] ? 'Да (' . htmlspecialchars($row['cluster_name']) . ')' : 'Нет') . "</td>";
                echo "<td class='action-links'>
                        <a href='index.php?tab=bundles&edit_id=" . $row['id'] . "'>Редакт.</a>
                        <form action='index.php?tab=bundles' method='post' onsubmit='return confirm(\"Удалить эту связку?\");'>
                            <input type='hidden' name='bundle_id' value='" . $row['id'] . "'>
                            <button type='submit' name='delete_bundle'>Удалить</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
        } else { 
            echo "<tr><td colspan='10'>Связок не найдено.</td></tr>"; 
        }
        ?>
        </tbody>
    </table>
</div>

<script>
function loadAddresses(establishmentId) {
    const select = document.getElementById('establishment_id_bundle');
    const selectedOption = select.options[select.selectedIndex];
    const addressesList = document.getElementById('addresses-list');
    
    if (selectedOption && selectedOption.dataset.addresses) {
        try {
            const addresses = JSON.parse(selectedOption.dataset.addresses);
            addressesList.innerHTML = '';
            addresses.forEach(address => {
                const option = document.createElement('option');
                option.value = address;
                addressesList.appendChild(option);
            });
        } catch (e) {
            console.error('Error parsing addresses:', e);
        }
    }
}

function updateDurationDisplay() {
    const years = document.getElementById('duration_years').value;
    const months = document.getElementById('duration_months').value;
    
    document.getElementById('years_display').textContent = years;
    document.getElementById('months_display').textContent = months;
    
    let total = '';
    if (years > 0) total += years + ' г. ';
    if (months > 0) total += months + ' мес.';
    if (years == 0 && months == 0) total = '0';
    
    document.getElementById('total_duration').textContent = total;
}

// Функция для обновления чекбокса 2 ОГЭ при выборе программы
document.getElementById('program_id_bundle').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const attributes = selectedOption.dataset.attributes;
    const ogeCheckbox = document.getElementById('oge_2');
    
    if (attributes === '2 ОГЭ') {
        ogeCheckbox.checked = true;
    } else {
        ogeCheckbox.checked = false;
    }
});

// Загружаем адреса при загрузке страницы, если выбран колледж
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('establishment_id_bundle');
    if (select.value) {
        loadAddresses(select.value);
    }
    
    // Инициализируем отображение длительности
    updateDurationDisplay();
});
</script>