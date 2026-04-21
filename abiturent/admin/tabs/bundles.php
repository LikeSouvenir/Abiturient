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

// Предзагрузка всех адресов для всех колледжей
$all_establishments_addresses = [];
foreach ($establishments_for_select as $est) {
    $all_establishments_addresses[$est['id']] = getEstablishmentAddresses($conn, $est['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_bundle'])) {
        $establishment_id = intval($_POST['establishment_id']);
        $program_id = intval($_POST['program_id']);
        $education_type = trim($_POST['education_type']);
        $education_base = trim($_POST['education_base']);
        $duration_years = intval($_POST['duration_years']);
        $duration_months = intval($_POST['duration_months']);
        
        // Получаем данные адреса из выбранного radio button
        $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
        $program_address = '';
        $program_latitude = '';
        $program_longitude = '';
        
        if ($address_id > 0) {
            $addr_stmt = $conn->prepare("SELECT address, latitude, longitude FROM addresses WHERE id = ?");
            $addr_stmt->bind_param("i", $address_id);
            $addr_stmt->execute();
            $addr_result = $addr_stmt->get_result();
            if ($addr = $addr_result->fetch_assoc()) {
                $program_address = $addr['address'];
                $program_latitude = $addr['latitude'];
                $program_longitude = $addr['longitude'];
            }
            $addr_stmt->close();
        }
        
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
        
        // Получаем данные адреса из выбранного radio button
        $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
        $program_address = '';
        $program_latitude = '';
        $program_longitude = '';
        
        if ($address_id > 0) {
            $addr_stmt = $conn->prepare("SELECT address, latitude, longitude FROM addresses WHERE id = ?");
            $addr_stmt->bind_param("i", $address_id);
            $addr_stmt->execute();
            $addr_result = $addr_stmt->get_result();
            if ($addr = $addr_result->fetch_assoc()) {
                $program_address = $addr['address'];
                $program_latitude = $addr['latitude'];
                $program_longitude = $addr['longitude'];
            }
            $addr_stmt->close();
        }
        
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
$selected_address_id = 0;
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
    
    // Находим ID адреса по текущему адресу
    if (!empty($bundle_to_edit['program_address'])) {
        $addr_stmt = $conn->prepare("SELECT id FROM addresses WHERE address = ? AND establishment_id = ? LIMIT 1");
        $addr_stmt->bind_param("si", $bundle_to_edit['program_address'], $bundle_to_edit['establishment_id']);
        $addr_stmt->execute();
        $addr_result = $addr_stmt->get_result();
        if ($addr = $addr_result->fetch_assoc()) {
            $selected_address_id = $addr['id'];
        }
        $addr_stmt->close();
    }
}
?>

<div id="bundles_admin" class="content-section">
    <h2>Управление Связками (Колледж-Программа)</h2>
    
    <style>
        .addresses-list {
            margin-top: 10px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
            max-height: 300px;
            overflow-y: auto;
        }
        .address-option {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        .address-option:hover {
            background: #f0f0f0;
            border-color: #007bff;
        }
        .address-option input[type="radio"] {
            margin-right: 10px;
            cursor: pointer;
        }
        .address-option label {
            cursor: pointer;
            width: 100%;
        }
        .address-option.selected {
            background: #e3f2fd;
            border-color: #007bff;
        }
        .address-option .address-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .address-option .address-coords {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }
        .admission-badge {
            display: inline-block;
            background: #d9534f;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 8px;
        }
        .no-addresses {
            padding: 20px;
            text-align: center;
            color: #999;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .btn {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group select,
        .form-group input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .admin-table th,
        .admin-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .admin-table th {
            background-color: #f2f2f2;
        }
        .action-links {
            white-space: nowrap;
        }
        .action-links a,
        .action-links button {
            margin: 0 5px;
            text-decoration: none;
        }
    </style>
    
    <div class="form-container">
        <h3><?php echo $bundle_to_edit ? 'Редактировать связку' : 'Добавить новую связку'; ?></h3>
        <form action="index.php?tab=bundles" method="post" id="bundleForm">
            <?php if ($bundle_to_edit): ?>
                <input type="hidden" name="bundle_id" value="<?php echo $bundle_to_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="establishment_id_bundle">Колледж:</label>
                <select id="establishment_id_bundle" name="establishment_id" required>
                    <option value="">-- Выберите колледж --</option>
                    <?php foreach ($establishments_for_select as $est): ?>
                    <option value="<?php echo $est['id']; ?>" 
                        <?php if($bundle_to_edit && $bundle_to_edit['establishment_id'] == $est['id']) echo 'selected'; ?>
                        data-addresses='<?php echo htmlspecialchars(json_encode($all_establishments_addresses[$est['id']]), ENT_QUOTES, 'UTF-8'); ?>'>
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
                        <label for="duration_years">Лет (0-12):</label>
                        <input type="range" id="duration_years" name="duration_years" min="0" max="12" step="1" value="<?php echo $duration_parts['years']; ?>" oninput="updateDurationDisplay()">
                        <span id="years_display"><?php echo $duration_parts['years']; ?></span>
                    </div>
                    <div style="flex: 1;">
                        <label for="duration_months">Месяцев (0-11):</label>
                        <input type="range" id="duration_months" name="duration_months" min="0" max="11" step="1" value="<?php echo $duration_parts['months']; ?>" oninput="updateDurationDisplay()">
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
                <label>Адрес проведения программы:</label>
                <div id="addresses-container">
                    <!-- Адреса будут загружены через JavaScript -->
                    <div class="no-addresses">📭 Выберите колледж, чтобы увидеть доступные адреса</div>
                </div>
                <small style="color: #666; display: block; margin-top: 5px;">Выберите адрес из списка выше</small>
                <input type="hidden" id="program_address_hidden" name="program_address" value="<?php echo $bundle_to_edit ? htmlspecialchars($bundle_to_edit['program_address']) : ''; ?>">
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
                <a href="index.php?tab=bundles" class="btn btn-danger" style="background-color:#6c757d; text-decoration: none;">Отмена</a>
            <?php endif; ?>
        </form>
    </div>
    
    <h3>Список связок</h3>
    <table class="admin-table">
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
                        <a href='index.php?tab=bundles&edit_id=" . $row['id'] . "'>✏️ Редакт.</a>
                        <form action='index.php?tab=bundles' method='post' onsubmit='return confirm(\"Удалить эту связку?\");' style='display:inline;'>
                            <input type='hidden' name='bundle_id' value='" . $row['id'] . "'>
                            <button type='submit' name='delete_bundle' style='background:none; border:none; color:#dc3545; cursor:pointer;'>🗑️ Удалить</button>
                        </form>
                        </td>";
                echo "</tr>";
            }
        } else { 
            echo "<tr><td colspan='10' style='text-align: center;'>📭 Связок не найдено.</td></tr>"; 
        }
        ?>
        </tbody>
    </table>
</div>

<script>
// Функция для загрузки адресов при выборе колледжа (без AJAX, из data-атрибута)
function loadAddresses() {
    const select = document.getElementById('establishment_id_bundle');
    const selectedOption = select.options[select.selectedIndex];
    const container = document.getElementById('addresses-container');
    const selectedAddressId = <?php echo $selected_address_id; ?>;
    
    if (!selectedOption || !selectedOption.value) {
        container.innerHTML = '<div class="no-addresses">📭 Выберите колледж, чтобы увидеть доступные адреса</div>';
        // Очищаем скрытые поля
        document.getElementById('program_address_hidden').value = '';
        document.getElementById('program_latitude_bundle').value = '';
        document.getElementById('program_longitude_bundle').value = '';
        return;
    }
    
    // Получаем адреса из data-атрибута
    let addresses = [];
    try {
        if (selectedOption.dataset.addresses) {
            addresses = JSON.parse(selectedOption.dataset.addresses);
        }
    } catch (e) {
        console.error('Ошибка парсинга адресов:', e);
        addresses = [];
    }
    
    if (addresses && addresses.length > 0) {
        let html = '';
        addresses.forEach(address => {
            const isChecked = (selectedAddressId == address.id);
            html += `
                <div class="address-option">
                    <label style="display: flex; align-items: flex-start; cursor: pointer;">
                        <input type="radio" 
                               name="address_id" 
                               value="${address.id}"
                               data-address="${escapeHtml(address.address)}"
                               data-lat="${escapeHtml(address.latitude || '')}"
                               data-lon="${escapeHtml(address.longitude || '')}"
                               ${isChecked ? 'checked' : ''}>
                        <div style="flex: 1; margin-left: 10px;">
                            <div class="address-title">
                                ${escapeHtml(address.address)}
                                ${address.admissions_committee ? '<span class="admission-badge">Приёмная комиссия</span>' : ''}
                            </div>
                            ${address.latitude && address.longitude ? 
                                `<div class="address-coords">📍 Координаты: ${escapeHtml(address.latitude)}, ${escapeHtml(address.longitude)}</div>` : 
                                '<div class="address-coords">📍 Координаты не указаны</div>'}
                        </div>
                    </label>
                </div>
            `;
        });
        container.innerHTML = html;
        
        // Привязываем обработчики и обновляем скрытые поля
        attachRadioHandlers();
    } else {
        container.innerHTML = '<div class="no-addresses">📭 У выбранного колледжа нет адресов</div>';
        document.getElementById('program_address_hidden').value = '';
        document.getElementById('program_latitude_bundle').value = '';
        document.getElementById('program_longitude_bundle').value = '';
    }
}

// Функция для экранирования HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Функция для обработки выбора адреса
function attachRadioHandlers() {
    const radioButtons = document.querySelectorAll('input[name="address_id"]');
    radioButtons.forEach(radio => {
        // Удаляем старые обработчики, чтобы не было дублирования
        radio.removeEventListener('change', radio._handler);
        
        // Создаем новый обработчик
        const handler = function() {
            if (this.checked) {
                const address = this.dataset.address;
                const lat = this.dataset.lat;
                const lon = this.dataset.lon;
                
                document.getElementById('program_address_hidden').value = address || '';
                document.getElementById('program_latitude_bundle').value = lat || '';
                document.getElementById('program_longitude_bundle').value = lon || '';
                
                console.log('Выбран адрес:', address, lat, lon);
            }
        };
        
        // Сохраняем обработчик и добавляем
        radio._handler = handler;
        radio.addEventListener('change', handler);
        
        // Если radio уже выбран, обновляем скрытые поля
        if (radio.checked) {
            const address = radio.dataset.address;
            const lat = radio.dataset.lat;
            const lon = radio.dataset.lon;
            
            document.getElementById('program_address_hidden').value = address || '';
            document.getElementById('program_latitude_bundle').value = lat || '';
            document.getElementById('program_longitude_bundle').value = lon || '';
        }
    });
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

function setDurationByBase() {
    const educationBase = document.getElementById('education_base_bundle').value;
    const yearsInput = document.getElementById('duration_years');
    const monthsInput = document.getElementById('duration_months');
    
    if (educationBase === '9 классов') {
        yearsInput.value = 3;
        monthsInput.value = 10;
    } else if (educationBase === '11 классов') {
        yearsInput.value = 2;
        monthsInput.value = 10;
    }
    
    updateDurationDisplay();
}

// Обработчик изменения программы
const programSelect = document.getElementById('program_id_bundle');
if (programSelect) {
    programSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const attributes = selectedOption.dataset.attributes;
        const ogeCheckbox = document.getElementById('oge_2');
        
        if (attributes === '2 ОГЭ') {
            ogeCheckbox.checked = true;
        } else {
            ogeCheckbox.checked = false;
        }
    });
}

// Обработчик изменения колледжа
const establishmentSelect = document.getElementById('establishment_id_bundle');
if (establishmentSelect) {
    establishmentSelect.addEventListener('change', function() {
        loadAddresses();
    });
}

// Обработчик изменения базы обучения
const educationBaseSelect = document.getElementById('education_base_bundle');
if (educationBaseSelect) {
    educationBaseSelect.addEventListener('change', function() {
        setDurationByBase();
    });
}

// Загружаем адреса при загрузке страницы, если выбран колледж
document.addEventListener('DOMContentLoaded', function() {
    if (establishmentSelect && establishmentSelect.value) {
        loadAddresses();
    }
    setDurationByBase();
});
</script>