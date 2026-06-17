<?php

define('ENCRYPTION_KEY', 'VapB88C3P0HfflpWvougljGKDXJhW0bcqR2+ad+gRFw=');
define('ENCRYPTION_METHOD', 'aes-256-cbc');

function encryptPassword($password) {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($password, ENCRYPTION_METHOD, base64_decode(ENCRYPTION_KEY), 0, $iv);
    return base64_encode($iv . '::' . $encrypted);
}

function decryptPassword($encryptedData) {
    $data = base64_decode($encryptedData);
    if (strpos($data, '::') === false) return $encryptedData; // старый незашифрованный формат
    list($iv, $encrypted) = explode('::', $data, 2);
    return openssl_decrypt($encrypted, ENCRYPTION_METHOD, base64_decode(ENCRYPTION_KEY), 0, $iv);
}

?>