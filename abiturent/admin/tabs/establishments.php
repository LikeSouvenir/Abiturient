<?php
$edit_establishment_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$establishment_to_edit = null;
$establishment_phones = [];
$admission_phones = [];
$establishment_addresses = [];
$admission_addresses = [];

if ($edit_establishment_id > 0) {
    // Получаем данные заведения
    $stmt = $conn->prepare("SELECT id, name, website, logo_path FROM establishments WHERE id = ?");
    $stmt->bind_param("i", $edit_establishment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $establishment_to_edit = $result->fetch_assoc();
    $stmt->close();
    
    if ($establishment_to_edit) {
        // Получаем обычные телефоны (не приёмная комиссия)
        $phones_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = $edit_establishment_id AND (admissions_committee = 0 OR admissions_committee IS NULL)");
        while($phone = $phones_result->fetch_assoc()) {
            $establishment_phones[] = $phone['phone'];
        }
        
        // Получаем телефоны приёмной комиссии
        $admission_phones_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = $edit_establishment_id AND admissions_committee = 1");
        while($phone = $admission_phones_result->fetch_assoc()) {
            $admission_phones[] = $phone['phone'];
        }
        
        // Получаем обычные адреса (не приёмная комиссия) - без координат или с NULL
        $addr_result = $conn->query("SELECT address, latitude, longitude FROM addresses WHERE establishment_id = $edit_establishment_id AND (admissions_committee = 0 OR admissions_committee IS NULL)");
        while($addr = $addr_result->fetch_assoc()) {
            $establishment_addresses[] = $addr;
        }
        
        // Получаем адреса приёмной комиссии с координатами
        $adm_result = $conn->query("SELECT address, latitude, longitude FROM addresses WHERE establishment_id = $edit_establishment_id AND admissions_committee = 1");
        while($adm = $adm_result->fetch_assoc()) {
            $admission_addresses[] = $adm;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_establishment']) || isset($_POST['edit_establishment'])) {
        $is_edit = isset($_POST['edit_establishment']);
        
        $name = trim($_POST['establishment_name']);
        $website = trim($_POST['website']);
        
        // Сбор обычных телефонов
        $phones = [];
        if (isset($_POST['phones']) && is_array($_POST['phones'])) {
            foreach($_POST['phones'] as $phone) {
                $phone = trim($phone);
                if (!empty($phone)) {
                    $phones[] = $phone;
                }
            }
        }
        
        // Сбор телефонов приёмной комиссии
        $admission_phones_data = [];
        if (isset($_POST['admission_phones']) && is_array($_POST['admission_phones'])) {
            foreach($_POST['admission_phones'] as $phone) {
                $phone = trim($phone);
                if (!empty($phone)) {
                    $admission_phones_data[] = $phone;
                }
            }
        }
        
        // Сбор обычных адресов (с возможными координатами)
        $addresses = [];
        if (isset($_POST['addresses']) && is_array($_POST['addresses'])) {
            foreach($_POST['addresses'] as $index => $address) {
                $address = trim($address);
                if (!empty($address)) {
                    // Для обычных адресов тоже могут быть координаты
                    $lat = isset($_POST['address_latitude'][$index]) && $_POST['address_latitude'][$index] !== '' ? floatval($_POST['address_latitude'][$index]) : null;
                    $lon = isset($_POST['address_longitude'][$index]) && $_POST['address_longitude'][$index] !== '' ? floatval($_POST['address_longitude'][$index]) : null;
                    
                    $addresses[] = [
                        'address' => $address,
                        'admissions_committee' => 0,
                        'latitude' => $lat,
                        'longitude' => $lon
                    ];
                }
            }
        }
        
        // Сбор адресов приёмной комиссии (с координатами)
        $admission_addresses_data = [];
        if (isset($_POST['admission_addresses']) && is_array($_POST['admission_addresses'])) {
            foreach($_POST['admission_addresses'] as $index => $address) {
                $address = trim($address);
                if (!empty($address)) {
                    $lat = isset($_POST['admission_latitude'][$index]) && $_POST['admission_latitude'][$index] !== '' ? floatval($_POST['admission_latitude'][$index]) : null;
                    $lon = isset($_POST['admission_longitude'][$index]) && $_POST['admission_longitude'][$index] !== '' ? floatval($_POST['admission_longitude'][$index]) : null;
                    
                    $admission_addresses_data[] = [
                        'address' => $address,
                        'admissions_committee' => 1,
                        'latitude' => $lat,
                        'longitude' => $lon
                    ];
                }
            }
        }
        
        // Объединяем все адреса
        $all_addresses = array_merge($addresses, $admission_addresses_data);
        
        if (!empty($name)) {
            if ($is_edit) {
                // Обновление заведения
                $id = intval($_POST['establishment_id']);
                $current_logo_filename = trim($_POST['current_logo_path'] ?? '');
                $logo_filename_to_save = $current_logo_filename;
                
                // Обработка логотипа
                $new_uploaded_filename = handle_upload('establishment_logo', UPLOAD_DIR_ESTABLISHMENTS);
                if ($new_uploaded_filename) {
                    if (!empty($current_logo_filename)) {
                        delete_file_from_system($current_logo_filename, UPLOAD_DIR_ESTABLISHMENTS);
                    }
                    $logo_filename_to_save = $new_uploaded_filename;
                } elseif (isset($_POST['delete_current_logo'])) {
                    if (!empty($current_logo_filename)) {
                        delete_file_from_system($current_logo_filename, UPLOAD_DIR_ESTABLISHMENTS);
                    }
                    $logo_filename_to_save = null;
                }
                
                // Обновление establishments (без координат, они теперь в addresses)
                $stmt = $conn->prepare("UPDATE establishments SET name = ?, website = ?, logo_path = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $website, $logo_filename_to_save, $id);
                
                if ($stmt->execute()) {
                    // Сохраняем обычные телефоны
                    $del_phones = $conn->prepare("DELETE FROM phones WHERE establishment_id = ? AND (admissions_committee = 0 OR admissions_committee IS NULL)");
                    $del_phones->bind_param("i", $id);
                    $del_phones->execute();
                    $del_phones->close();
                    
                    if (!empty($phones)) {
                        $ins_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone, admissions_committee) VALUES (?, ?, 0)");
                        foreach($phones as $phone) {
                            $ins_phone->bind_param("is", $id, $phone);
                            $ins_phone->execute();
                        }
                        $ins_phone->close();
                    }
                    
                    // Сохраняем телефоны приёмной комиссии
                    $del_admission_phones = $conn->prepare("DELETE FROM phones WHERE establishment_id = ? AND admissions_committee = 1");
                    $del_admission_phones->bind_param("i", $id);
                    $del_admission_phones->execute();
                    $del_admission_phones->close();
                    
                    if (!empty($admission_phones_data)) {
                        $ins_admission_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone, admissions_committee) VALUES (?, ?, 1)");
                        foreach($admission_phones_data as $phone) {
                            $ins_admission_phone->bind_param("is", $id, $phone);
                            $ins_admission_phone->execute();
                        }
                        $ins_admission_phone->close();
                    }
                    
                    // Сохраняем адреса (все адреса с координатами в таблице addresses)
                    $del_addrs = $conn->prepare("DELETE FROM addresses WHERE establishment_id = ?");
                    $del_addrs->bind_param("i", $id);
                    $del_addrs->execute();
                    $del_addrs->close();
                    
                    if (!empty($all_addresses)) {
                        $ins_addr = $conn->prepare("INSERT INTO addresses (establishment_id, address, admissions_committee, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
                        foreach($all_addresses as $addr_data) {
                            $ins_addr->bind_param("isidd", $id, $addr_data['address'], $addr_data['admissions_committee'], $addr_data['latitude'], $addr_data['longitude']);
                            $ins_addr->execute();
                        }
                        $ins_addr->close();
                    }
                    
                    $_SESSION['message'] = "Учебное заведение обновлено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка обновления заведения: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                // Добавление нового заведения
                $logo_filename = null;
                $uploaded_filename = handle_upload('establishment_logo', UPLOAD_DIR_ESTABLISHMENTS);
                if ($uploaded_filename) {
                    $logo_filename = $uploaded_filename;
                }
                
                // Вставка в establishments (без координат)
                $stmt = $conn->prepare("INSERT INTO establishments (name, website, logo_path) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $website, $logo_filename);
                
                if ($stmt->execute()) {
                    $new_id = $stmt->insert_id;
                    
                    // Сохраняем обычные телефоны
                    if (!empty($phones)) {
                        $ins_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone, admissions_committee) VALUES (?, ?, 0)");
                        foreach($phones as $phone) {
                            $ins_phone->bind_param("is", $new_id, $phone);
                            $ins_phone->execute();
                        }
                        $ins_phone->close();
                    }
                    
                    // Сохраняем телефоны приёмной комиссии
                    if (!empty($admission_phones_data)) {
                        $ins_admission_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone, admissions_committee) VALUES (?, ?, 1)");
                        foreach($admission_phones_data as $phone) {
                            $ins_admission_phone->bind_param("is", $new_id, $phone);
                            $ins_admission_phone->execute();
                        }
                        $ins_admission_phone->close();
                    }
                    
                    // Сохраняем адреса (все адреса с координатами)
                    if (!empty($all_addresses)) {
                        $ins_addr = $conn->prepare("INSERT INTO addresses (establishment_id, address, admissions_committee, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
                        foreach($all_addresses as $addr_data) {
                            $ins_addr->bind_param("isidd", $new_id, $addr_data['address'], $addr_data['admissions_committee'], $addr_data['latitude'], $addr_data['longitude']);
                            $ins_addr->execute();
                        }
                        $ins_addr->close();
                    }
                    
                    $_SESSION['message'] = "Учебное заведение добавлено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка добавления заведения: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            }
        } else {
            $_SESSION['message'] = "Название заведения обязательно.";
            $_SESSION['message_type'] = "error";
        }
        
        header("Location: index.php?tab=establishments");
        exit;
    }

    if (isset($_POST['delete_establishment'])) {
        $id = intval($_POST['establishment_id']);
        
        // Проверяем наличие связок
        $check_stmt = $conn->prepare("SELECT id FROM bundles WHERE establishment_id = ? LIMIT 1");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $_SESSION['message'] = "Удаление невозможно! Существуют связки для этого учебного заведения.";
            $_SESSION['message_type'] = "error";
        } else {
            // Получаем путь к логотипу
            $logo_stmt = $conn->prepare("SELECT logo_path FROM establishments WHERE id = ?");
            $logo_stmt->bind_param("i", $id);
            $logo_stmt->execute();
            $logo_data = $logo_stmt->get_result()->fetch_assoc();
            
            // Удаляем телефоны
            $del_phones = $conn->prepare("DELETE FROM phones WHERE establishment_id = ?");
            $del_phones->bind_param("i", $id);
            $del_phones->execute();
            $del_phones->close();
            
            // Удаляем адреса
            $del_addrs = $conn->prepare("DELETE FROM addresses WHERE establishment_id = ?");
            $del_addrs->bind_param("i", $id);
            $del_addrs->execute();
            $del_addrs->close();
            
            // Удаляем логотип
            if ($logo_data && !empty($logo_data['logo_path'])) {
                delete_file_from_system($logo_data['logo_path'], UPLOAD_DIR_ESTABLISHMENTS);
            }
            $logo_stmt->close();
            
            // Удаляем заведение
            $del_est = $conn->prepare("DELETE FROM establishments WHERE id = ?");
            $del_est->bind_param("i", $id);
            if ($del_est->execute()) {
                $_SESSION['message'] = "Учебное заведение удалено успешно!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Ошибка удаления: " . $del_est->error;
                $_SESSION['message_type'] = "error";
            }
            $del_est->close();
        }
        $check_stmt->close();
        
        header("Location: index.php?tab=establishments");
        exit;
    }
}
?>

<!-- HTML форма -->
<div id="establishments_admin" class="content-section">
    <h2>Управление Учебными Заведениями</h2>
    
    <div class="form-container">
        <h3><?php echo $establishment_to_edit ? 'Редактировать Учебное Заведение' : 'Добавить новое'; ?></h3>
        <form action="index.php?tab=establishments" method="post" enctype="multipart/form-data" id="establishmentForm">
            <?php if ($establishment_to_edit): ?>
                <input type="hidden" name="establishment_id" value="<?php echo $establishment_to_edit['id']; ?>">
                <input type="hidden" name="current_logo_path" value="<?php echo htmlspecialchars($establishment_to_edit['logo_path'] ?? ''); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="establishment_name_admin">Название заведения:</label>
                <input type="text" id="establishment_name_admin" name="establishment_name" 
                       value="<?php echo $establishment_to_edit ? htmlspecialchars($establishment_to_edit['name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="establishment_logo_admin">Логотип:</label>
                <input type="file" id="establishment_logo_admin" name="establishment_logo" accept="image/*">
                <?php if ($establishment_to_edit && !empty($establishment_to_edit['logo_path'])): 
                    $webPath = '../uploads/establishments/' . $establishment_to_edit['logo_path'];
                    if (file_exists('../uploads/establishments/' . $establishment_to_edit['logo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($webPath); ?>" alt="Текущий логотип" class="current-image-admin">
                    <div>
                        <input type="checkbox" name="delete_current_logo" id="delete_establishment_logo_admin">
                        <label class="checkbox-label" for="delete_establishment_logo_admin">Удалить текущий логотип</label>
                    </div>
                <?php endif; endif; ?>
            </div>
            
            <div class="form-group">
                <label for="website_admin">Веб-сайт:</label>
                <input type="url" id="website_admin" name="website" 
                       value="<?php echo $establishment_to_edit ? htmlspecialchars($establishment_to_edit['website'] ?? '') : ''; ?>" 
                       placeholder="https://example.com">
            </div>
            
            <!-- Обычные телефоны -->
            <div class="form-group">
                <label>Телефоны (основные):</label>
                <div id="phones-container">
                    <?php if (!empty($establishment_phones)): ?>
                        <?php foreach($establishment_phones as $phone): ?>
                        <div class="phone-item" style="margin-bottom: 10px;">
                            <input type="text" name="phones[]" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Номер телефона" style="width: 90%;">
                            <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="phone-item" style="margin-bottom: 10px;">
                            <input type="text" name="phones[]" placeholder="Номер телефона" style="width: 90%;">
                            <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addPhone()" class="btn btn-primary" style="margin-top: 5px;">+ Добавить телефон</button>
            </div>
            
            <!-- Обычные адреса (могут иметь координаты) -->
            <div class="form-group">
                <label>Адреса (основные):</label>
                <div id="addresses-container">
                    <?php if (!empty($establishment_addresses)): ?>
                        <?php foreach($establishment_addresses as $index => $addr): ?>
                        <div class="address-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #eee;">
                            <div style="margin-bottom: 10px;">
                                <label>Адрес:</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="addresses[]" value="<?php echo htmlspecialchars($addr['address']); ?>" placeholder="Адрес" style="flex: 1;">
                                    <button type="button" onclick="this.closest('.address-item').remove()" style="width: 30px;">✕</button>
                                </div>
                            </div>
                            <div>
                                <label>Координаты (опционально):</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="address_latitude[]" value="<?php echo htmlspecialchars($addr['latitude'] ?? ''); ?>" placeholder="Широта" style="flex: 1;">
                                    <input type="text" name="address_longitude[]" value="<?php echo htmlspecialchars($addr['longitude'] ?? ''); ?>" placeholder="Долгота" style="flex: 1;">
                                    <button type="button" onclick="geocodeAddress(this, 'addresses')" class="btn btn-primary" style="font-size: 12px;">Определить</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="address-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #eee;">
                            <div style="margin-bottom: 10px;">
                                <label>Адрес:</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="addresses[]" placeholder="Адрес" style="flex: 1;">
                                    <button type="button" onclick="this.closest('.address-item').remove()" style="width: 30px;">✕</button>
                                </div>
                            </div>
                            <div>
                                <label>Координаты (опционально):</label>
                                <div style="display: flex; gap: 10px;">
                                    <input type="text" name="address_latitude[]" placeholder="Широта" style="flex: 1;">
                                    <input type="text" name="address_longitude[]" placeholder="Долгота" style="flex: 1;">
                                    <button type="button" onclick="geocodeAddress(this, 'addresses')" class="btn btn-primary" style="font-size: 12px;">Определить</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addAddress()" class="btn btn-primary" style="margin-top: 5px;">+ Добавить адрес</button>
                <small style="color: #666; display: block; margin-top: 5px;">Для обычных адресов координаты можно указать опционально</small>
            </div>
            
            <!-- Секция Приёмной комиссии -->
            <div class="form-group">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <label style="margin: 0; font-weight: bold;">Приёмная комиссия:</label>
                    <button type="button" onclick="toggleAdmissionSection()" class="btn btn-primary" style="font-size: 12px; padding: 5px 10px;">
                        Показать/скрыть
                    </button>
                </div>
                <div id="admission-section" style="display: none; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    
                    <!-- Телефоны приёмной комиссии -->
                    <div style="margin-bottom: 20px;">
                        <label>Телефоны приёмной комиссии:</label>
                        <div id="admission-phones-container">
                            <?php if (!empty($admission_phones)): ?>
                                <?php foreach($admission_phones as $phone): ?>
                                <div class="admission-phone-item" style="margin-bottom: 10px;">
                                    <input type="text" name="admission_phones[]" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Телефон приёмной комиссии" style="width: 90%;">
                                    <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="admission-phone-item" style="margin-bottom: 10px;">
                                    <input type="text" name="admission_phones[]" placeholder="Телефон приёмной комиссии" style="width: 90%;">
                                    <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" onclick="addAdmissionPhone()" class="btn btn-primary" style="margin-top: 5px;">+ Добавить телефон приёмной комиссии</button>
                    </div>
                    
                    <!-- Адреса приёмной комиссии с координатами -->
                    <div>
                        <label>Адреса приёмной комиссии (с координатами):</label>
                        <div id="admission-addresses-container">
                            <?php if (!empty($admission_addresses)): ?>
                                <?php foreach($admission_addresses as $index => $addr): ?>
                                <div class="admission-address-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #eee;">
                                    <div style="margin-bottom: 10px;">
                                        <label>Адрес:</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" name="admission_addresses[]" value="<?php echo htmlspecialchars($addr['address']); ?>" placeholder="Адрес" style="flex: 1;">
                                            <button type="button" onclick="this.closest('.admission-address-item').remove()" style="width: 30px;">✕</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label>Координаты:</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" name="admission_latitude[]" value="<?php echo htmlspecialchars($addr['latitude'] ?? ''); ?>" placeholder="Широта" style="flex: 1;">
                                            <input type="text" name="admission_longitude[]" value="<?php echo htmlspecialchars($addr['longitude'] ?? ''); ?>" placeholder="Долгота" style="flex: 1;">
                                            <button type="button" onclick="geocodeAdmissionAddress(this)" class="btn btn-primary" style="font-size: 12px;">Определить</button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="admission-address-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #eee;">
                                    <div style="margin-bottom: 10px;">
                                        <label>Адрес:</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" name="admission_addresses[]" placeholder="Адрес" style="flex: 1;">
                                            <button type="button" onclick="this.closest('.admission-address-item').remove()" style="width: 30px;">✕</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label>Координаты:</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" name="admission_latitude[]" placeholder="Широта" style="flex: 1;">
                                            <input type="text" name="admission_longitude[]" placeholder="Долгота" style="flex: 1;">
                                            <button type="button" onclick="geocodeAdmissionAddress(this)" class="btn btn-primary" style="font-size: 12px;">Определить</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" onclick="addAdmissionAddress()" class="btn btn-primary" style="margin-top: 10px;">+ Добавить адрес приёмной комиссии</button>
                    </div>
                </div>
            </div>
            
            <button type="submit" name="<?php echo $establishment_to_edit ? 'edit_establishment' : 'add_establishment'; ?>" class="btn">
                <?php echo $establishment_to_edit ? 'Сохранить' : 'Добавить Заведение'; ?>
            </button>
            <?php if ($establishment_to_edit): ?>
                <a href="index.php?tab=establishments" class="btn btn-danger" style="background-color:#6c757d;">Отмена</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Список заведений -->
    <h3>Список Учебных Заведений</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Телефоны</th>
                <th>Адреса</th>
                <th>Приёмная комиссия</th>
                <th>Сайт</th>
                <th>Лого</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $est_result = $conn->query("SELECT id, name, website, logo_path FROM establishments ORDER BY name");
            
            if ($est_result->num_rows > 0) {
                while($row = $est_result->fetch_assoc()) {
                    // Получаем обычные телефоны
                    $phones = [];
                    $p_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = " . $row['id'] . " AND (admissions_committee = 0 OR admissions_committee IS NULL)");
                    while($p = $p_result->fetch_assoc()) {
                        $phones[] = $p['phone'];
                    }
                    
                    // Получаем телефоны приёмной комиссии
                    $admission_phones_list = [];
                    $ap_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = " . $row['id'] . " AND admissions_committee = 1");
                    while($ap = $ap_result->fetch_assoc()) {
                        $admission_phones_list[] = $ap['phone'];
                    }
                    
                    // Получаем обычные адреса с координатами
                    $addresses = [];
                    $a_result = $conn->query("SELECT address, latitude, longitude FROM addresses WHERE establishment_id = " . $row['id'] . " AND (admissions_committee = 0 OR admissions_committee IS NULL)");
                    while($a = $a_result->fetch_assoc()) {
                        $addresses[] = $a;
                    }
                    
                    // Получаем адреса приёмной комиссии с координатами
                    $admission_addrs = [];
                    $adm_result = $conn->query("SELECT address, latitude, longitude FROM addresses WHERE establishment_id = " . $row['id'] . " AND admissions_committee = 1");
                    while($adm = $adm_result->fetch_assoc()) {
                        $admission_addrs[] = $adm;
                    }
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . (!empty($phones) ? htmlspecialchars(implode('<br>', $phones)) : '—') . "</td>";
                    
                    // Выводим обычные адреса с координатами
                    echo "<td>";
                    if (!empty($addresses)) {
                        foreach($addresses as $addr) {
                            echo htmlspecialchars($addr['address']);
                            if (!empty($addr['latitude']) && !empty($addr['longitude'])) {
                                echo "<br><span style='font-size:11px;color:#666;'>🗺️ " . $addr['latitude'] . ", " . $addr['longitude'] . "</span>";
                            }
                            echo "<hr style='margin: 5px 0;'>";
                        }
                    } else {
                        echo "—";
                    }
                    echo "</td>";
                    
                    // Выводим информацию о приёмной комиссии
                    echo "<td>";
                    if (!empty($admission_phones_list) || !empty($admission_addrs)) {
                        if (!empty($admission_phones_list)) {
                            echo "<strong>📞 Телефоны:</strong><br>" . htmlspecialchars(implode('<br>', $admission_phones_list)) . "<br>";
                        }
                        if (!empty($admission_addrs)) {
                            foreach($admission_addrs as $adm) {
                                echo "<strong>📍 Адрес:</strong> " . htmlspecialchars($adm['address']) . "<br>";
                                if (!empty($adm['latitude']) && !empty($adm['longitude'])) {
                                    echo "<span style='font-size:11px;color:#666;'>🗺️ " . $adm['latitude'] . ", " . $adm['longitude'] . "</span><br>";
                                }
                                echo "<hr style='margin: 5px 0;'>";
                            }
                        }
                    } else {
                        echo "—";
                    }
                    echo "</td>";
                    
                    echo "<td>";
                    if (!empty($row['website'])) {
                        echo "<a href='" . htmlspecialchars($row['website']) . "' target='_blank'>" . htmlspecialchars($row['website']) . "</a>";
                    } else {
                        echo "—";
                    }
                    echo "</td>";
                    
                    echo "<td>";
                    if (!empty($row['logo_path'])) {
                        $webPath = '../uploads/establishments/' . $row['logo_path'];
                        if(file_exists('../uploads/establishments/' . $row['logo_path'])) {
                            echo "<img src='" . htmlspecialchars($webPath) . "' alt='Logo' style='max-width:50px; max-height:50px;'>";
                        } else {
                            echo "❌";
                        }
                    } else {
                        echo "—";
                    }
                    echo "</td>";
                    
                    echo "<td class='action-links'>
                            <a href='index.php?tab=establishments&edit_id=" . $row['id'] . "'>✏️ Редакт.</a>
                            <form action='index.php?tab=establishments' method='post' onsubmit='return confirm(\"Удалить учебное заведение?\");' style='display:inline;'>
                                <input type='hidden' name='establishment_id' value='" . $row['id'] . "'>
                                <button type='submit' name='delete_establishment' style='background:none; border:none; color:#dc3545; cursor:pointer;'>🗑️ Удалить</button>
                            </form>
                           </div></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8' style='text-align: center;'>📭 Учебных заведений не найдено.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
function addPhone() {
    const container = document.getElementById('phones-container');
    const div = document.createElement('div');
    div.className = 'phone-item';
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <input type="text" name="phones[]" placeholder="Номер телефона" style="width: 90%;">
        <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
    `;
    container.appendChild(div);
}

function addAddress() {
    const container = document.getElementById('addresses-container');
    const div = document.createElement('div');
    div.className = 'address-item';
    div.style.marginBottom = '15px';
    div.style.padding = '10px';
    div.style.border = '1px solid #eee';
    div.innerHTML = `
        <div style="margin-bottom: 10px;">
            <label>Адрес:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="addresses[]" placeholder="Адрес" style="flex: 1;">
                <button type="button" onclick="this.closest('.address-item').remove()" style="width: 30px;">✕</button>
            </div>
        </div>
        <div>
            <label>Координаты (опционально):</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="address_latitude[]" placeholder="Широта" style="flex: 1;">
                <input type="text" name="address_longitude[]" placeholder="Долгота" style="flex: 1;">
                <button type="button" onclick="geocodeAddress(this, 'addresses')" class="btn btn-primary" style="font-size: 12px;">Определить</button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function addAdmissionPhone() {
    const container = document.getElementById('admission-phones-container');
    const div = document.createElement('div');
    div.className = 'admission-phone-item';
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <input type="text" name="admission_phones[]" placeholder="Телефон приёмной комиссии" style="width: 90%;">
        <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
    `;
    container.appendChild(div);
}

function addAdmissionAddress() {
    const container = document.getElementById('admission-addresses-container');
    const div = document.createElement('div');
    div.className = 'admission-address-item';
    div.style.marginBottom = '15px';
    div.style.padding = '10px';
    div.style.border = '1px solid #eee';
    div.innerHTML = `
        <div style="margin-bottom: 10px;">
            <label>Адрес:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="admission_addresses[]" placeholder="Адрес" style="flex: 1;">
                <button type="button" onclick="this.closest('.admission-address-item').remove()" style="width: 30px;">✕</button>
            </div>
        </div>
        <div>
            <label>Координаты:</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="admission_latitude[]" placeholder="Широта" style="flex: 1;">
                <input type="text" name="admission_longitude[]" placeholder="Долгота" style="flex: 1;">
                <button type="button" onclick="geocodeAdmissionAddress(this)" class="btn btn-primary" style="font-size: 12px;">Определить</button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function toggleAdmissionSection() {
    const section = document.getElementById('admission-section');
    if (section.style.display === 'none') {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}

function geocodeAddress(button, type) {
    const item = button.closest('.address-item, .admission-address-item');
    const addressInput = item.querySelector('input[name="' + type + '[]"]');
    const latInput = item.querySelector('input[name="address_latitude[]"]') || item.querySelector('input[name="admission_latitude[]"]');
    const lonInput = item.querySelector('input[name="address_longitude[]"]') || item.querySelector('input[name="admission_longitude[]"]');
    
    const address = addressInput.value.trim();
    if (!address) {
        alert('Введите адрес');
        return;
    }
    
    latInput.value = '';
    lonInput.value = '';
    
    if (typeof ymaps !== 'undefined') {
        ymaps.geocode(address, { results: 1 }).then(function (res) {
            const firstGeoObject = res.geoObjects.get(0);
            if (firstGeoObject) {
                const coords = firstGeoObject.geometry.getCoordinates();
                latInput.value = coords[0].toFixed(6);
                lonInput.value = coords[1].toFixed(6);
                alert('Координаты определены: ' + latInput.value + ', ' + lonInput.value);
            } else {
                alert('Координаты не найдены');
            }
        }).catch(function(err) {
            console.error('Geocoding error:', err);
            alert('Ошибка определения координат');
        });
    } else {
        alert('API карт не загружен');
    }
}

function geocodeAdmissionAddress(button) {
    geocodeAddress(button, 'admission_addresses');
}

if (typeof ymaps === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://api-maps.yandex.ru/2.1/?apikey=ВАШ_API_КЛЮЧ&lang=ru_RU';
    script.onload = function() {
        ymaps.ready(function() {
            console.log('Яндекс.Карты загружены');
        });
    };
    document.head.appendChild(script);
}

document.addEventListener('DOMContentLoaded', function() {
    const hasAdmissionPhones = document.querySelectorAll('#admission-phones-container .admission-phone-item input').length > 0;
    const hasAdmissionAddresses = document.querySelectorAll('#admission-addresses-container .admission-address-item input[name="admission_addresses[]"]').length > 0;
    
    if (hasAdmissionPhones || hasAdmissionAddresses) {
        document.getElementById('admission-section').style.display = 'block';
    }
});
</script>