<?php
// includes/address_helper.php

/**
 * Сокращает "Санкт-Петербург" до "СПБ" в адресе
 * 
 * @param string $address Полный адрес
 * @return string Адрес с сокращениями
 */
function shortenAddress($address) {
    if (empty($address)) return $address;
    
    // Массив замен для различных вариантов написания Санкт-Петербурга
    $patterns = [
        '/Санкт-Петербург/ui' => 'СПБ',
        '/Санкт Петербург/ui' => 'СПБ',
        '/СанктПетербург/ui' => 'СПБ',
        '/г\.?\s*Санкт-Петербург/ui' => 'г. СПБ',
        '/город\s*Санкт-Петербург/ui' => 'г. СПБ',
        '/^Санкт-Петербург/ui' => 'СПБ',
        '/,\s*Санкт-Петербург/ui' => ', СПБ',
        '/\s+Санкт-Петербург\s+/ui' => ' СПБ ',
        '/\s+Санкт-Петербург$/ui' => ' СПБ',
    ];
    
    $shortened = preg_replace(array_keys($patterns), array_values($patterns), $address);
    
    // Дополнительные сокращения
    $shortened = preg_replace('/Ленинградская\s+область/ui', 'Лен. обл.', $shortened);
    $shortened = preg_replace('/\s+/', ' ', $shortened);
    $shortened = trim($shortened);
    
    return $shortened;
}

/**
 * Проверяет, есть ли в адресе Санкт-Петербург
 * 
 * @param string $address Адрес для проверки
 * @return bool
 */
function hasSaintPetersburg($address) {
    return preg_match('/Санкт-Петербург/ui', $address) === 1;
}
?>