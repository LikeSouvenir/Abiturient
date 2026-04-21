<?php
$edit_establishment_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$establishment_to_edit = null;
$all_contacts = [];

if ($edit_establishment_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, website, logo_path FROM establishments WHERE id = ?");
    $stmt->bind_param("i", $edit_establishment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $establishment_to_edit = $result->fetch_assoc();
    $stmt->close();
    
    if ($establishment_to_edit) {
        $addr_result = $conn->query("SELECT id, address, latitude, longitude, admissions_committee FROM addresses WHERE establishment_id = $edit_establishment_id ORDER BY id");
        while($addr = $addr_result->fetch_assoc()) {
            $phones_for_addr = [];
            $phone_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = $edit_establishment_id AND admissions_committee = " . $addr['admissions_committee']);
            while($phone = $phone_result->fetch_assoc()) {
                $phones_for_addr[] = $phone['phone'];
            }
            
            $all_contacts[] = [
                'address_id' => $addr['id'],
                'address' => $addr['address'],
                'latitude' => $addr['latitude'],
                'longitude' => $addr['longitude'],
                'is_admission' => $addr['admissions_committee'] == 1,
                'phones' => $phones_for_addr
            ];
        }
        
        $phone_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = $edit_establishment_id AND admissions_committee = 0");
        $general_phones = [];
        while($phone = $phone_result->fetch_assoc()) {
            // Проверяем, не привязан ли уже этот телефон к какому-то адресу
            $already_used = false;
            foreach($all_contacts as $contact) {
                if (in_array($phone['phone'], $contact['phones'])) {
                    $already_used = true;
                    break;
                }
            }
            if (!$already_used) {
                $general_phones[] = $phone['phone'];
            }
        }
        
        if (!empty($general_phones)) {
            $all_contacts[] = [
                'address_id' => null,
                'address' => '',
                'latitude' => null,
                'longitude' => null,
                'is_admission' => false,
                'phones' => $general_phones
            ];
        }
    }
}

if (empty($all_contacts)) {
    $all_contacts[] = [
        'address_id' => null,
        'address' => '',
        'latitude' => null,
        'longitude' => null,
        'is_admission' => false,
        'phones' => ['']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_establishment']) || isset($_POST['edit_establishment'])) {
        $is_edit = isset($_POST['edit_establishment']);
        
        $name = trim($_POST['establishment_name']);
        $website = trim($_POST['website']);
        
        $contacts_data = [];
        if (isset($_POST['addresses']) && is_array($_POST['addresses'])) {
            foreach($_POST['addresses'] as $index => $address) {
                $address = trim($address);
                $is_admission = isset($_POST['is_admission'][$index]) ? 1 : 0;
                $lat = isset($_POST['address_latitude'][$index]) && $_POST['address_latitude'][$index] !== '' ? floatval($_POST['address_latitude'][$index]) : null;
                $lon = isset($_POST['address_longitude'][$index]) && $_POST['address_longitude'][$index] !== '' ? floatval($_POST['address_longitude'][$index]) : null;
                
                $phones = [];
                if (isset($_POST['phones'][$index])) {
                    $phone_str = trim($_POST['phones'][$index]);
                    if (!empty($phone_str)) {
                        $phones = preg_split('/[,;]\s*/', $phone_str);
                        $phones = array_filter(array_map('trim', $phones));
                    }
                }
                
                if ($is_admission) {
                    if (empty($address)) {
                        $_SESSION['message'] = "Для приёмной комиссии адрес обязателен!";
                        $_SESSION['message_type'] = "error";
                        header("Location: index.php?tab=establishments" . ($is_edit ? "&edit_id=$edit_establishment_id" : ""));
                        exit;
                    }
                    if (empty($phones)) {
                        $_SESSION['message'] = "Для приёмной комиссии необходимо указать хотя бы один телефон!";
                        $_SESSION['message_type'] = "error";
                        header("Location: index.php?tab=establishments" . ($is_edit ? "&edit_id=$edit_establishment_id" : ""));
                        exit;
                    }
                    if ($lat === null || $lon === null) {
                        $_SESSION['message'] = "Для адреса приёмной комиссии необходимо указать координаты!";
                        $_SESSION['message_type'] = "error";
                        header("Location: index.php?tab=establishments" . ($is_edit ? "&edit_id=$edit_establishment_id" : ""));
                        exit;
                    }
                }
                
                if (!empty($address) || !empty($phones)) {
                    $contacts_data[] = [
                        'address' => $address,
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'is_admission' => $is_admission,
                        'phones' => $phones
                    ];
                }
            }
        }
        
        if (!empty($name)) {
            if ($is_edit) {
                $id = intval($_POST['establishment_id']);
                $current_logo_filename = trim($_POST['current_logo_path'] ?? '');
                $logo_filename_to_save = $current_logo_filename;
                
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
                
                $stmt = $conn->prepare("UPDATE establishments SET name = ?, website = ?, logo_path = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $website, $logo_filename_to_save, $id);
                
                if ($stmt->execute()) {
                    $conn->query("DELETE FROM phones WHERE establishment_id = $id");
                    $conn->query("DELETE FROM addresses WHERE establishment_id = $id");
                    
                    foreach($contacts_data as $contact) {
                        if (!empty($contact['address'])) {
                            $ins_addr = $conn->prepare("INSERT INTO addresses (establishment_id, address, latitude, longitude, admissions_committee) VALUES (?, ?, ?, ?, ?)");
                            $ins_addr->bind_param("isddi", $id, $contact['address'], $contact['latitude'], $contact['longitude'], $contact['is_admission']);
                            $ins_addr->execute();
                            $ins_addr->close();
                            
                            if (!empty($contact['phones'])) {
                                $ins_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone, admissions_committee) VALUES (?, ?, ?)");
                                foreach($contact['phones'] as $phone) {
                                    $ins_phone->bind_param("isi", $id, $phone, $contact['is_admission']);
                                    $ins_phone->execute();
                                }
                                $ins_phone->close();
                            }
                        } else {
                            if (!empty($contact['phones'])) {
                                $ins_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone, admissions_committee) VALUES (?, ?, ?)");
                                foreach($contact['phones'] as $phone) {
                                    $ins_phone->bind_param("isi", $id, $phone, $contact['is_admission']);
                                    $ins_phone->execute();
                                }
                                $ins_phone->close();
                            }
                        }
                    }
                    
                    $_SESSION['message'] = "Учебное заведение обновлено успешно!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Ошибка обновления заведения: " . $stmt->error;
                    $_SESSION['message_type'] = "error";
                }
                $stmt->close();
            } else {
                $logo_filename = null;
                $uploaded_filename = handle_upload('establishment_logo', UPLOAD_DIR_ESTABLISHMENTS);
                if ($uploaded_filename) {
                    $logo_filename = $uploaded_filename;
                }
                
                foreach($contacts_data as $contact) {
                    if ($contact['is_admission']) {
                        if (empty($contact['address'])) {
                            $_SESSION['message'] = "Для приёмной комиссии адрес обязателен!";
                            $_SESSION['message_type'] = "error";
                            header("Location: index.php?tab=establishments");
                            exit;
                        }
                        if (empty($contact['phones'])) {
                            $_SESSION['message'] = "Для приёмной комиссии необходимо указать телефон!";
                            $_SESSION['message_type'] = "error";
                            header("Location: index.php?tab=establishments");
                            exit;
                        }
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO establishments (name, website, logo_path) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $website, $logo_filename);
                
                if ($stmt->execute()) {
                    $new_id = $stmt->insert_id;
                    
                    foreach($contacts_data as $contact) {
                        if (!empty($contact['address'])) {
                            $ins_addr = $conn->prepare("INSERT INTO addresses (establishment_id, address, latitude, longitude, admissions_committee) VALUES (?, ?, ?, ?, ?)");
                            $ins_addr->bind_param("isddi", $new_id, $contact['address'], $contact['latitude'], $contact['longitude'], $contact['is_admission']);
                            $ins_addr->execute();
                            $ins_addr->close();
                            
                            if (!empty($contact['phones'])) {
                                $ins_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone, admissions_committee) VALUES (?, ?, ?)");
                                foreach($contact['phones'] as $phone) {
                                    $ins_phone->bind_param("isi", $new_id, $phone, $contact['is_admission']);
                                    $ins_phone->execute();
                                }
                                $ins_phone->close();
                            }
                        } else {
                            if (!empty($contact['phones'])) {
                                $ins_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone, admissions_committee) VALUES (?, ?, ?)");
                                foreach($contact['phones'] as $phone) {
                                    $ins_phone->bind_param("isi", $new_id, $phone, $contact['is_admission']);
                                    $ins_phone->execute();
                                }
                                $ins_phone->close();
                            }
                        }
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
        
        $check_stmt = $conn->prepare("SELECT id FROM bundles WHERE establishment_id = ? LIMIT 1");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $_SESSION['message'] = "Удаление невозможно! Существуют связки для этого учебного заведения.";
            $_SESSION['message_type'] = "error";
        } else {
            $logo_stmt = $conn->prepare("SELECT logo_path FROM establishments WHERE id = ?");
            $logo_stmt->bind_param("i", $id);
            $logo_stmt->execute();
            $logo_data = $logo_stmt->get_result()->fetch_assoc();
            
            $conn->query("DELETE FROM phones WHERE establishment_id = $id");
            $conn->query("DELETE FROM addresses WHERE establishment_id = $id");
            
            if ($logo_data && !empty($logo_data['logo_path'])) {
                delete_file_from_system($logo_data['logo_path'], UPLOAD_DIR_ESTABLISHMENTS);
            }
            $logo_stmt->close();
            
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

<div id="establishments_admin" class="content-section">
    <h2>Управление Учебными Заведениями</h2>
    
    <style>
        .contact-block {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .contact-block .admission-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .contact-block .admission-header input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .contact-block .admission-header label {
            font-weight: bold;
            color: #d9534f;
            cursor: pointer;
        }
        .address-row {
            margin-bottom: 15px;
        }
        .phone-row {
            margin-bottom: 10px;
        }
        .coordinates-row {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .coordinates-row input {
            flex: 1;
        }
        .required-asterisk {
            color: red;
            margin-left: 3px;
        }
        .current-image-admin {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
            display: block;
        }
        .action-links {
            white-space: nowrap;
        }
        .action-links a, .action-links button {
            margin: 0 5px;
            text-decoration: none;
            font-size: 18px;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .admin-table th, .admin-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .admin-table th {
            background-color: #f2f2f2;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="url"],
        .form-group input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-danger {
            background-color: #dc3545;
        }
    </style>
    
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
            
            <div class="form-group">
                <label style="font-size: 1.1em; font-weight: bold;">📌 Контакты</label>
                <div id="contacts-container">
                    <?php foreach($all_contacts as $index => $contact): ?>
                    <div class="contact-block" data-index="<?php echo $index; ?>">
                        <div class="admission-header">
                            <input type="checkbox" 
                                   name="is_admission[]" 
                                   id="is_admission_<?php echo $index; ?>"
                                   value="1"
                                   <?php echo $contact['is_admission'] ? 'checked' : ''; ?>
                                   onchange="toggleAdmissionRequired(this)">
                            <label for="is_admission_<?php echo $index; ?>">
                                🏫 Приёмная комиссия
                                <span style="font-weight: normal; color: #666; font-size: 12px;">(адрес, телефон и координаты обязательны)</span>
                            </label>
                            <button type="button" class="remove-contact-btn" style="margin-left: auto; background: none; border: none; color: #dc3545; font-size: 18px; cursor: pointer;">✕</button>
                        </div>
                        
                        <div class="address-row">
                            <label>
                                Адрес
                                <span class="required-asterisk admission-required-addr" style="<?php echo $contact['is_admission'] ? '' : 'display: none;'; ?>">*</span>
                            </label>
                            <input type="text" 
                                   name="addresses[]" 
                                   value="<?php echo htmlspecialchars($contact['address']); ?>" 
                                   placeholder="Введите адрес" 
                                   style="width: 100%;"
                                   class="address-input"
                                   <?php echo $contact['is_admission'] ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="phone-row">
                            <label>
                                Телефон(ы)
                                <span class="required-asterisk admission-required-phone" style="<?php echo $contact['is_admission'] ? '' : 'display: none;'; ?>">*</span>
                                <small style="color: #666;">(можно несколько через запятую)</small>
                            </label>
                            <input type="text" 
                                   name="phones[<?php echo $index; ?>]" 
                                   value="<?php echo htmlspecialchars(implode(', ', $contact['phones'])); ?>" 
                                   placeholder="+7 (812) 123-45-67, +7 (921) 123-45-67" 
                                   style="width: 100%;"
                                   class="phone-input"
                                   <?php echo $contact['is_admission'] ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="coordinates-row">
                            <div style="flex: 1;">
                                <label>
                                    Широта
                                    <span class="required-asterisk admission-required-coord" style="<?php echo $contact['is_admission'] ? '' : 'display: none;'; ?>">*</span>
                                </label>
                                <input type="text" 
                                       name="address_latitude[]" 
                                       value="<?php echo htmlspecialchars($contact['latitude'] ?? ''); ?>" 
                                       placeholder="59.934280" 
                                       style="width: 100%;"
                                       class="coord-lat"
                                       <?php echo $contact['is_admission'] ? 'required' : ''; ?>>
                            </div>
                            <div style="flex: 1;">
                                <label>
                                    Долгота
                                    <span class="required-asterisk admission-required-coord" style="<?php echo $contact['is_admission'] ? '' : 'display: none;'; ?>">*</span>
                                </label>
                                <input type="text" 
                                       name="address_longitude[]" 
                                       value="<?php echo htmlspecialchars($contact['longitude'] ?? ''); ?>" 
                                       placeholder="30.335100" 
                                       style="width: 100%;"
                                       class="coord-lon"
                                       <?php echo $contact['is_admission'] ? 'required' : ''; ?>>
                            </div>
                            <div style="display: flex; align-items: flex-end;">
                                <button type="button" class="geocode-btn btn btn-primary" style="font-size: 12px; white-space: nowrap;">
                                    🗺️ Определить координаты
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" id="add-contact-btn" class="btn btn-primary" style="margin-top: 15px;">
                    ➕ Добавить адрес
                </button>
            </div>
            
            <button type="submit" name="<?php echo $establishment_to_edit ? 'edit_establishment' : 'add_establishment'; ?>" class="btn">
                <?php echo $establishment_to_edit ? 'Сохранить' : 'Добавить Заведение'; ?>
            </button>
            <?php if ($establishment_to_edit): ?>
                <a href="index.php?tab=establishments" class="btn btn-danger" style="background-color:#6c757d;">Отмена</a>
            <?php endif; ?>
        </form>
    </div>

    <h3>Список Учебных Заведений</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Контакты</th>
                <th>Сайт</th>
                <th>Лого</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $est_result = $conn->query("SELECT id, name, website, logo_path FROM establishments ORDER BY name");
            
            if ($est_result && $est_result->num_rows > 0) {
                while($row = $est_result->fetch_assoc()) {
                    $contacts_display = [];
                    
                    // Получаем адреса для заведения
                    $addr_result = $conn->query("SELECT a.id, a.address, a.admissions_committee, a.latitude, a.longitude 
                                                 FROM addresses a 
                                                 WHERE a.establishment_id = " . $row['id'] . " 
                                                 ORDER BY a.admissions_committee DESC, a.id");
                    
                    if ($addr_result && $addr_result->num_rows > 0) {
                        while($addr = $addr_result->fetch_assoc()) {
                            $phones = [];
                            // Получаем телефоны для этого адреса
                            $phone_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = " . $row['id'] . " AND admissions_committee = " . $addr['admissions_committee']);
                            if ($phone_result) {
                                while($phone = $phone_result->fetch_assoc()) {
                                    $phones[] = $phone['phone'];
                                }
                            }
                            
                            $icon = $addr['admissions_committee'] ? '🏫' : '📍';
                            $contact_text = $icon . ' ' . htmlspecialchars($addr['address']);
                            
                            if (!empty($phones)) {
                                $contact_text .= '<br>📞 ' . htmlspecialchars(implode(', ', $phones));
                            }
                            
                            if ($addr['admissions_committee'] && !empty($addr['latitude']) && !empty($addr['longitude'])) {
                                $contact_text .= '<br><span style="font-size:11px;color:#666;">🗺️ ' . htmlspecialchars($addr['latitude']) . ', ' . htmlspecialchars($addr['longitude']) . '</span>';
                            }
                            
                            $contacts_display[] = $contact_text;
                        }
                    }
                    
                    // Получаем общие телефоны (которые не привязаны ни к какому адресу)
                    // Просто получаем все телефоны с admissions_committee = 0
                    $gen_phone_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = " . $row['id'] . " AND admissions_committee = 0");
                    $gen_phones = [];
                    if ($gen_phone_result) {
                        while($gp = $gen_phone_result->fetch_assoc()) {
                            $gen_phones[] = $gp['phone'];
                        }
                    }
                    if (!empty($gen_phones)) {
                        $contacts_display[] = '📞 ' . htmlspecialchars(implode(', ', $gen_phones));
                    }
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . (!empty($contacts_display) ? implode('<hr style="margin:5px 0;">', $contacts_display) : '—') . "</td>";
                    echo "<td>";
                    if (!empty($row['website'])) {
                        echo "<a href='" . htmlspecialchars($row['website']) . "' target='_blank'>🌐</a>";
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
                            <a href='index.php?tab=establishments&edit_id=" . $row['id'] . "'>✏️</a>
                            <form action='index.php?tab=establishments' method='post' onsubmit='return confirm(\"Удалить учебное заведение?\");' style='display:inline;'>
                                <input type='hidden' name='establishment_id' value='" . $row['id'] . "'>
                                <button type='submit' name='delete_establishment' style='background:none; border:none; color:#dc3545; cursor:pointer; font-size:18px;'>🗑️</button>
                            </form>
                            </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align: center;'>📭 Учебных заведений не найдено.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
(function() {
    let contactIndex = <?php echo count($all_contacts); ?>;
    
    // Функция переключения обязательности
    window.toggleAdmissionRequired = function(checkbox) {
        const block = checkbox.closest('.contact-block');
        if (!block) return;
        
        const addrInput = block.querySelector('.address-input');
        const phoneInput = block.querySelector('.phone-input');
        const latInput = block.querySelector('.coord-lat');
        const lonInput = block.querySelector('.coord-lon');
        
        const addrAsterisk = block.querySelector('.admission-required-addr');
        const phoneAsterisk = block.querySelector('.admission-required-phone');
        const coordAsterisks = block.querySelectorAll('.admission-required-coord');
        
        if (checkbox.checked) {
            if (addrInput) addrInput.required = true;
            if (phoneInput) phoneInput.required = true;
            if (latInput) latInput.required = true;
            if (lonInput) lonInput.required = true;
            
            if (addrAsterisk) addrAsterisk.style.display = 'inline';
            if (phoneAsterisk) phoneAsterisk.style.display = 'inline';
            coordAsterisks.forEach(el => el.style.display = 'inline');
        } else {
            if (addrInput) addrInput.required = false;
            if (phoneInput) phoneInput.required = false;
            if (latInput) latInput.required = false;
            if (lonInput) lonInput.required = false;
            
            if (addrAsterisk) addrAsterisk.style.display = 'none';
            if (phoneAsterisk) phoneAsterisk.style.display = 'none';
            coordAsterisks.forEach(el => el.style.display = 'none');
        }
    };
    
    // Функция геокодирования
    window.geocodeAddress = function(button) {
        const block = button.closest('.contact-block');
        if (!block) return;
        
        const addressInput = block.querySelector('.address-input');
        const latInput = block.querySelector('.coord-lat');
        const lonInput = block.querySelector('.coord-lon');
        
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
    };
    
    // Добавление нового блока
    function addContactBlock() {
        const container = document.getElementById('contacts-container');
        if (!container) return;
        
        const div = document.createElement('div');
        div.className = 'contact-block';
        div.setAttribute('data-index', contactIndex);
        
        div.innerHTML = `
            <div class="admission-header">
                <input type="checkbox" 
                       name="is_admission[]" 
                       id="is_admission_${contactIndex}"
                       value="1"
                       onchange="toggleAdmissionRequired(this)">
                <label for="is_admission_${contactIndex}">
                    🏫 Приёмная комиссия
                    <span style="font-weight: normal; color: #666; font-size: 12px;">(адрес, телефон и координаты обязательны)</span>
                </label>
                <button type="button" class="remove-contact-btn" style="margin-left: auto; background: none; border: none; color: #dc3545; font-size: 18px; cursor: pointer;">✕</button>
            </div>
            
            <div class="address-row">
                <label>
                    Адрес
                    <span class="required-asterisk admission-required-addr" style="display: none;">*</span>
                </label>
                <input type="text" name="addresses[]" placeholder="Введите адрес" style="width: 100%;" class="address-input">
            </div>
            
            <div class="phone-row">
                <label>
                    Телефон(ы)
                    <span class="required-asterisk admission-required-phone" style="display: none;">*</span>
                    <small style="color: #666;">(можно несколько через запятую)</small>
                </label>
                <input type="text" name="phones[${contactIndex}]" placeholder="+7 (812) 123-45-67, +7 (921) 123-45-67" style="width: 100%;" class="phone-input">
            </div>
            
            <div class="coordinates-row">
                <div style="flex: 1;">
                    <label>
                        Широта
                        <span class="required-asterisk admission-required-coord" style="display: none;">*</span>
                    </label>
                    <input type="text" name="address_latitude[]" placeholder="59.934280" style="width: 100%;" class="coord-lat">
                </div>
                <div style="flex: 1;">
                    <label>
                        Долгота
                        <span class="required-asterisk admission-required-coord" style="display: none;">*</span>
                    </label>
                    <input type="text" name="address_longitude[]" placeholder="30.335100" style="width: 100%;" class="coord-lon">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="button" class="geocode-btn btn btn-primary" style="font-size: 12px; white-space: nowrap;" onclick="geocodeAddress(this)">
                        🗺️ Определить координаты
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(div);
        contactIndex++;
    }
    
    // Удаление блока
    function removeContactBlock(button) {
        if (document.querySelectorAll('.contact-block').length > 1) {
            button.closest('.contact-block').remove();
        } else {
            alert('Должен быть хотя бы один контакт');
        }
    }
    
    // Навешиваем обработчики событий
    document.addEventListener('DOMContentLoaded', function() {
        // Кнопка добавления
        const addBtn = document.getElementById('add-contact-btn');
        if (addBtn) {
            addBtn.addEventListener('click', addContactBlock);
        }
        
        // Делегирование для кнопок удаления и геокодирования
        document.addEventListener('click', function(e) {
            // Удаление
            if (e.target.classList.contains('remove-contact-btn')) {
                removeContactBlock(e.target);
            }
            
            // Геокодирование (для динамически добавленных кнопок)
            if (e.target.classList.contains('geocode-btn')) {
                geocodeAddress(e.target);
            }
        });
        
        // Инициализация существующих чекбоксов
        const existingCheckboxes = document.querySelectorAll('input[type="checkbox"][name="is_admission[]"]');
        existingCheckboxes.forEach(cb => {
            cb.onchange = function() { toggleAdmissionRequired(this); };
        });
    });
})();
</script>