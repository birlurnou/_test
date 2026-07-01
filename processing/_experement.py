import hashlib
import binascii

# Данные
data = [
    (8651220, "ad12ac10"),
    (9885532, "af141ea4"),
    (35140953, "e43373eb"),
]

print("🔍 ПОИСК ЗАКОНОМЕРНОСТИ")
print("=" * 70)

for name_id, xor_key in data:
    print(f"\n📅 name_id: {name_id}, XOR: {xor_key}")
    print("-" * 50)

    # 1. name_id в HEX (4 байта)
    hex_id = hex(name_id)[2:].zfill(8)
    print(f"  name_id в HEX: {hex_id}")
    print(f"  XOR ключ:      {xor_key}")
    print(f"  Совпадают?     {'✅' if hex_id == xor_key else '❌'}")

    # 2. Инверсия HEX
    inv_hex = hex(name_id ^ 0xFFFFFFFF)[2:].zfill(8)
    print(f"  Инверсия HEX:  {inv_hex}")
    print(f"  Совпадают?     {'✅' if inv_hex == xor_key else '❌'}")

    # 3. MD5 от name_id (первые 8 символов)
    md5 = hashlib.md5(str(name_id).encode()).hexdigest()[:8]
    print(f"  MD5(name_id):  {md5}")
    print(f"  Совпадают?     {'✅' if md5 == xor_key else '❌'}")

    # 4. SHA1 от name_id (первые 8 символов)
    sha1 = hashlib.sha1(str(name_id).encode()).hexdigest()[:8]
    print(f"  SHA1(name_id): {sha1}")
    print(f"  Совпадают?     {'✅' if sha1 == xor_key else '❌'}")

    # 5. MD5 от name_id + соль 'OPERA'
    md5_salt = hashlib.md5(f"OPERA{name_id}".encode()).hexdigest()[:8]
    print(f"  MD5(OPERA+id): {md5_salt}")
    print(f"  Совпадают?     {'✅' if md5_salt == xor_key else '❌'}")

    # 6. MD5 от name_id + соль 'MICROS'
    md5_salt2 = hashlib.md5(f"MICROS{name_id}".encode()).hexdigest()[:8]
    print(f"  MD5(MICROS+id):{md5_salt2}")
    print(f"  Совпадают?     {'✅' if md5_salt2 == xor_key else '❌'}")

    # 7. XOR с константой
    const = 0x12345678
    xor_const = hex(name_id ^ const)[2:].zfill(8)
    print(f"  name_id ^ 0x12345678: {xor_const}")
    print(f"  Совпадают?           {'✅' if xor_const == xor_key else '❌'}")

    # 8. MD5 от имени + фамилии
    # У нас нет имени и фамилии в этом скрипте, но мы можем попробовать
    # с теми, что есть в БД