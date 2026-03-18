<?php
$edit_establishment_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
$establishment_to_edit = null;
$establishment_phones = [];
$establishment_addresses = [];

if ($edit_establishment_id > 0) {
    // Получаем данные заведения (теперь включая координаты)
    $stmt = $conn->prepare("SELECT id, name, website, logo_path, latitude, longitude FROM establishments WHERE id = ?");
    $stmt->bind_param("i", $edit_establishment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $establishment_to_edit = $result->fetch_assoc();
    $stmt->close();
    
    if ($establishment_to_edit) {
        // Получаем телефоны
        $phones_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = $edit_establishment_id");
        while($phone = $phones_result->fetch_assoc()) {
            $establishment_phones[] = $phone['phone'];
        }
        
        // Получаем адреса (только адреса, без координат)
        $addr_result = $conn->query("SELECT address FROM addresses WHERE establishment_id = $edit_establishment_id");
        while($addr = $addr_result->fetch_assoc()) {
            $establishment_addresses[] = $addr['address'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_establishment']) || isset($_POST['edit_establishment'])) {
        $is_edit = isset($_POST['edit_establishment']);
        
        $name = trim($_POST['establishment_name']);
        $website = trim($_POST['website']);
        
        // Получаем координаты из полей (теперь они для establishments)
        $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
        $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
        
        // Сбор телефонов
        $phones = [];
        if (isset($_POST['phones']) && is_array($_POST['phones'])) {
            foreach($_POST['phones'] as $phone) {
                $phone = trim($phone);
                if (!empty($phone)) {
                    $phones[] = $phone;
                }
            }
        }
        
        // Сбор адресов (только адреса, без координат)
        $addresses = [];
        if (isset($_POST['addresses']) && is_array($_POST['addresses'])) {
            foreach($_POST['addresses'] as $address) {
                $address = trim($address);
                if (!empty($address)) {
                    $addresses[] = $address;
                }
            }
        }
        
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
                
                // Обновление establishments с координатами
                $stmt = $conn->prepare("UPDATE establishments SET name = ?, website = ?, logo_path = ?, latitude = ?, longitude = ? WHERE id = ?");
                $stmt->bind_param("sssddi", $name, $website, $logo_filename_to_save, $latitude, $longitude, $id);
                
                if ($stmt->execute()) {
                    // Сохраняем телефоны
                    $del_phones = $conn->prepare("DELETE FROM phones WHERE establishment_id = ?");
                    $del_phones->bind_param("i", $id);
                    $del_phones->execute();
                    $del_phones->close();
                    
                    if (!empty($phones)) {
                        $ins_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone) VALUES (?, ?)");
                        foreach($phones as $phone) {
                            $ins_phone->bind_param("is", $id, $phone);
                            $ins_phone->execute();
                        }
                        $ins_phone->close();
                    }
                    
                    // Сохраняем адреса (только адреса)
                    $del_addrs = $conn->prepare("DELETE FROM addresses WHERE establishment_id = ?");
                    $del_addrs->bind_param("i", $id);
                    $del_addrs->execute();
                    $del_addrs->close();
                    
                    if (!empty($addresses)) {
                        $ins_addr = $conn->prepare("INSERT INTO addresses (establishment_id, address) VALUES (?, ?)");
                        foreach($addresses as $address) {
                            $ins_addr->bind_param("is", $id, $address);
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
                
                // Вставляем с координатами
                $stmt = $conn->prepare("INSERT INTO establishments (name, website, logo_path, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdd", $name, $website, $logo_filename, $latitude, $longitude);
                
                if ($stmt->execute()) {
                    $new_id = $stmt->insert_id;
                    
                    // Сохраняем телефоны
                    if (!empty($phones)) {
                        $ins_phone = $conn->prepare("INSERT INTO phones (establishment_id, phone) VALUES (?, ?)");
                        foreach($phones as $phone) {
                            $ins_phone->bind_param("is", $new_id, $phone);
                            $ins_phone->execute();
                        }
                        $ins_phone->close();
                    }
                    
                    // Сохраняем адреса (только адреса)
                    if (!empty($addresses)) {
                        $ins_addr = $conn->prepare("INSERT INTO addresses (establishment_id, address) VALUES (?, ?)");
                        foreach($addresses as $address) {
                            $ins_addr->bind_param("is", $new_id, $address);
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

<div id="establishments_admin" class="content-section">
    <h2>Управление Учебными Заведениями</h2>
    
    <!-- Форма добавления/редактирования -->
    <div class="form-container">
        <h3><?php echo $establishment_to_edit ? 'Редактировать Учебное Заведение' : 'Добавить новое'; ?></h3>
        <form action="index.php?tab=establishments" method="post" enctype="multipart/form-data" id="establishmentForm">
            <?php if ($establishment_to_edit): ?>
                <input type="hidden" name="establishment_id" value="<?php echo $establishment_to_edit['id']; ?>">
                <input type="hidden" name="current_logo_path" value="<?php echo htmlspecialchars($establishment_to_edit['logo_path'] ?? ''); ?>">
            <?php endif; ?>
            
            <!-- Основная информация -->
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
            
            <!-- Координаты (теперь в establishments) -->
            <div class="form-group">
                <label>Координаты:</label>
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label for="latitude">Широта:</label>
                        <input type="text" id="latitude" name="latitude" 
                               value="<?php echo $establishment_to_edit && isset($establishment_to_edit['latitude']) ? htmlspecialchars($establishment_to_edit['latitude']) : ''; ?>" 
                               placeholder="55.7558" class="address-lat">
                    </div>
                    <div style="flex: 1;">
                        <label for="longitude">Долгота:</label>
                        <input type="text" id="longitude" name="longitude" 
                               value="<?php echo $establishment_to_edit && isset($establishment_to_edit['longitude']) ? htmlspecialchars($establishment_to_edit['longitude']) : ''; ?>" 
                               placeholder="37.6176" class="address-lon">
                    </div>
                </div>
            </div>
            
            <!-- Телефоны -->
            <div class="form-group">
                <label>Телефоны:</label>
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
            
            <!-- Адреса (только адреса, без координат) -->
            <div class="form-group">
                <label>Адреса:</label>
                <div id="addresses-container">
                    <?php if (!empty($establishment_addresses)): ?>
                        <?php foreach($establishment_addresses as $address): ?>
                        <div class="address-item" style="margin-bottom: 10px;">
                            <input type="text" name="addresses[]" value="<?php echo htmlspecialchars($address); ?>" placeholder="Адрес" style="width: 90%;">
                            <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="address-item" style="margin-bottom: 10px;">
                            <input type="text" name="addresses[]" placeholder="Адрес" style="width: 90%;">
                            <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="addAddress()" class="btn btn-primary" style="margin-top: 5px;">+ Добавить адрес</button>
            </div>
            
            <!-- Кнопка для определения координат по первому адресу -->
            <div class="form-group">
                <button type="button" onclick="geocodeFirstAddress()" class="btn btn-primary">Определить координаты по первому адресу</button>
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
                <th>Сайт</th>
                <th>Координаты</th>
                <th>Лого</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $est_result = $conn->query("SELECT id, name, website, logo_path, latitude, longitude FROM establishments ORDER BY name");
            
            if ($est_result->num_rows > 0) {
                while($row = $est_result->fetch_assoc()) {
                    // Получаем телефоны
                    $phones = [];
                    $p_result = $conn->query("SELECT phone FROM phones WHERE establishment_id = " . $row['id']);
                    while($p = $p_result->fetch_assoc()) {
                        $phones[] = $p['phone'];
                    }
                    
                    // Получаем адреса
                    $addresses = [];
                    $a_result = $conn->query("SELECT address FROM addresses WHERE establishment_id = " . $row['id']);
                    while($a = $a_result->fetch_assoc()) {
                        $addresses[] = $a['address'];
                    }
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                    echo "<td>" . htmlspecialchars(implode('<br>', $phones)) . "</td>";
                    echo "<td>" . htmlspecialchars(implode('<br>', $addresses)) . "</td>";
                    echo "<td>";
                    if (!empty($row['website'])) {
                        echo "<a href='" . htmlspecialchars($row['website']) . "' target='_blank'>" . htmlspecialchars($row['website']) . "</a>";
                    }
                    echo "</td>";
                    echo "<td>";
                    if (!empty($row['latitude']) && !empty($row['longitude'])) {
                        echo "Ш: " . $row['latitude'] . "<br>Д: " . $row['longitude'];
                    } else {
                        echo "Не указаны";
                    }
                    echo "</td>";
                    echo "<td>";
                    if (!empty($row['logo_path'])) {
                        $webPath = '../uploads/establishments/' . $row['logo_path'];
                        if(file_exists('../uploads/establishments/' . $row['logo_path'])) {
                            echo "<img src='" . htmlspecialchars($webPath) . "' alt='Logo' class='thumbnail'>";
                        } else {
                            echo "Файл не найден";
                        }
                    } else {
                        echo "Нет лого";
                    }
                    echo "</td>";
                    echo "<td class='action-links'>
                            <a href='index.php?tab=establishments&edit_id=" . $row['id'] . "'>Редакт.</a>
                            <form action='index.php?tab=establishments' method='post' onsubmit='return confirm(\"Удалить учебное заведение? Все связанные телефоны, адреса и связки будут удалены.\");' style='display:inline;'>
                                <input type='hidden' name='establishment_id' value='" . $row['id'] . "'>
                                <button type='submit' name='delete_establishment'>Удалить</button>
                            </form>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8' style='text-align: center;'>Учебных заведений не найдено.</td></tr>";
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
    div.style.marginBottom = '10px';
    div.innerHTML = `
        <input type="text" name="addresses[]" placeholder="Адрес" style="width: 90%;">
        <button type="button" onclick="this.parentElement.remove()" style="width: 8%;">✕</button>
    `;
    container.appendChild(div);
}

function geocodeFirstAddress() {
    const addressInputs = document.querySelectorAll('#addresses-container input[name="addresses[]"]');
    if (addressInputs.length === 0) {
        alert('Добавьте хотя бы один адрес');
        return;
    }
    
    const firstAddress = addressInputs[0].value.trim();
    if (!firstAddress) {
        alert('Введите первый адрес');
        return;
    }
    
    const latInput = document.getElementById('latitude');
    const lonInput = document.getElementById('longitude');
    
    latInput.value = '';
    lonInput.value = '';
    
    // Используем Яндекс.Карты для геокодирования
    if (typeof ymaps !== 'undefined') {
        ymaps.geocode(firstAddress, { results: 1 }).then(function (res) {
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

// Загрузка API Яндекс.Карт если ещё не загружен
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
</script>