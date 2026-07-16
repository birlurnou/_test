import base64
import os
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.backends import default_backend

ENCRYPTION_KEY = 'VapB88C3P0HfflpWvougljGKDXJhW0bcqR2+ad+gRFw='


def encrypt_password(password):
    key = base64.b64decode(ENCRYPTION_KEY)
    iv = os.urandom(16)

    # Паддинг PKCS7 - ВСЕГДА добавляем паддинг
    password_bytes = password.encode('utf-8')
    padding_len = 16 - (len(password_bytes) % 16)
    if padding_len == 0:
        padding_len = 16  # Важно! Для PKCS7 всегда добавляем паддинг

    padded_data = password_bytes + bytes([padding_len] * padding_len)

    # Шифрование
    cipher = Cipher(algorithms.AES(key), modes.CBC(iv), backend=default_backend())
    encryptor = cipher.encryptor()
    encrypted = encryptor.update(padded_data) + encryptor.finalize()

    # Формат: base64(iv::encrypted)
    return base64.b64encode(iv + b'::' + encrypted).decode('utf-8')


def decrypt_password(encrypted_data):
    try:
        data = base64.b64decode(encrypted_data)
    except:
        return encrypted_data  # Если не base64 - старый формат

    if b'::' not in data:
        return encrypted_data  # старый незашифрованный формат

    iv, encrypted = data.split(b'::', 1)

    # Проверка длины IV
    if len(iv) != 16:
        raise ValueError(f'Invalid IV length: {len(iv)}')

    key = base64.b64decode(ENCRYPTION_KEY)

    cipher = Cipher(algorithms.AES(key), modes.CBC(iv), backend=default_backend())
    decryptor = cipher.decryptor()
    decrypted_padded = decryptor.update(encrypted) + decryptor.finalize()

    # Убираем паддинг PKCS7
    padding_len = decrypted_padded[-1]

    # Проверка валидности паддинга
    if padding_len < 1 or padding_len > 16:
        raise ValueError(f'Invalid padding length: {padding_len}')

    # Проверяем, что все байты паддинга одинаковые
    if not all(b == padding_len for b in decrypted_padded[-padding_len:]):
        raise ValueError('Invalid PKCS7 padding')

    return decrypted_padded[:-padding_len].decode('utf-8')


# Проверка совместимости с PHP
def test_compatibility():
    # Тест 1: Пустой пароль
    empty_test = ''
    encrypted_empty = encrypt_password(empty_test)
    decrypted_empty = decrypt_password(encrypted_empty)
    print(f'Пустой пароль: {decrypted_empty == empty_test}')

    # Тест 2: Пароль длиной кратной 16
    test_16 = '1234567890123456'  # 16 символов
    encrypted_16 = encrypt_password(test_16)
    decrypted_16 = decrypt_password(encrypted_16)
    print(f'Пароль 16 символов: {decrypted_16 == test_16}')

    # Тест 3: Длинный пароль
    test_long = 'super_secret_password_123!@#'
    encrypted_long = encrypt_password(test_long)
    decrypted_long = decrypt_password(encrypted_long)
    print(f'Длинный пароль: {decrypted_long == test_long}')


if __name__ == '__main__':
    # Основной тест
    test = 'Иванов Иван Иванович'
    encrypted = encrypt_password(test)
    print(f'Зашифровано: {encrypted}')
    decrypted = decrypt_password(encrypted)
    print(f'Расшифровано: {decrypted}')

    # Проверка совместимости
    test_compatibility()