import pandas as pd
import psycopg2
from psycopg2 import sql
from psycopg2.extras import execute_values
import datetime
import os
import glob
import re
import configparser


# создание директорий

for folder in ['logs']:
    os.makedirs(folder, exist_ok=True)


# функция логирования

start_log_time = datetime.datetime.now().strftime('%Y%m%d_%H%M%S')
def _logging(log_text):
    try:
        with open(f'logs/log_importer_{start_log_time}.txt', 'a', encoding='utf-8') as f:
            f.write(f'[{str(datetime.datetime.now())[:-7]}] {log_text}\n')
    except:
        pass


# подключение конфига

config_file = 'config'
config = configparser.ConfigParser()
config.read(config_file, encoding='utf-8')

def get_config(setting_parent, setting_child):
    if config.has_section(f'{setting_parent}'):
        if config.has_option(f'{setting_parent}', f'{setting_child}'):
            return config.get(f'{setting_parent}', f'{setting_child}')
    _logging(f'Ошибка: параметр ["{setting_parent}"]["{setting_child}"] не найден')


# получение данных из конфига

host = get_config('import', 'host') \
    if get_config('import', 'host') else exit()

port = get_config('import', 'port') \
    if get_config('import', 'port') else exit()

dbname = get_config('import', 'dbname') \
    if get_config('import', 'dbname') else exit()

user = get_config('import', 'user') \
    if get_config('import', 'user') else exit()

password = get_config('import', 'password') # \
    # if get_config('import', 'password') else exit()


# параметры подключения к БД

DB_CONFIG = {
    'host': f'{host}',
    'port': f'{port}',
    'dbname': f'{dbname}',
    'user': f'{user}',
    'password': f'{password}',
}


def get_db_connection():
    """создание подключения к базе данных"""
    return psycopg2.connect(**DB_CONFIG)


def read_csv_file(file_path):
    """чтение CSV файла"""
    try:
        # используем точку с запятой как разделитель
        df = pd.read_csv(file_path, sep=';')

        # замена NaN на None для корректной вставки в БД
        df = df.where(pd.notnull(df), None)

        # выводим названия колонок для отладки
        _logging(f'Колонки в DataFrame: {df.columns.tolist()}')
        _logging(f'Загружено {len(df)} строк из CSV')

        return df
    except Exception as e:
        _logging(f'Ошибка при чтении CSV файла: {e}')
        return None


def prepare_guest_data(df):
    """подготовка данных для таблицы guests"""
    guests = []

    guest_columns = ['f_name', 'alt_f_name', 'birth_date', 'profile_id']

    missing_columns = [col for col in guest_columns if col not in df.columns]
    if missing_columns:
        _logging(f'Ошибка: следующие колонки отсутствуют в DataFrame: {missing_columns}')
        _logging(f'Доступные колонки: {df.columns.tolist()}')
        return []

    guest_df = df[guest_columns].copy()

    # преобразуем birth_date в дату, если это строка
    if 'birth_date' in guest_df.columns and guest_df['birth_date'].dtype == 'object':
        guest_df['birth_date'] = pd.to_datetime(guest_df['birth_date'], errors='coerce')

    # заменяем NaT на None
    guest_df['birth_date'] = guest_df['birth_date'].where(pd.notnull(guest_df['birth_date']), None)

    unique_guests = guest_df.drop_duplicates()

    _logging(f'Найдено {len(unique_guests)} уникальных гостей')

    for _, row in unique_guests.iterrows():
        birth_date = row['birth_date']
        if isinstance(birth_date, pd.Timestamp):
            birth_date = birth_date.date()
        elif pd.isna(birth_date):
            birth_date = None

        guest = (
            row['f_name'],
            row['alt_f_name'],
            birth_date,
            int(row['profile_id']) if pd.notnull(row['profile_id']) else None
        )
        guests.append(guest)

    return guests


def insert_guests(guests):
    """вставка гостей в таблицу guests с проверкой на существование"""
    if not guests:
        _logging('Нет гостей для вставки')
        return {}

    conn = get_db_connection()
    cursor = conn.cursor()

    inserted_count = 0
    guest_map = {}

    try:
        for guest in guests:
            f_name, alt_f_name, birth_date, profile_id = guest

            # приоритет 1: birth_date + profile_id
            if birth_date is not None and profile_id is not None:
                check_query = """
                    SELECT guest_id FROM guests 
                    WHERE birth_date = %s AND profile_id = %s
                """
                cursor.execute(check_query, (birth_date, profile_id))
                existing_guest = cursor.fetchone()

                if existing_guest:
                    guest_id = existing_guest[0]
                    guest_key = (f_name, alt_f_name, birth_date)
                    guest_map[guest_key] = guest_id
                    continue

            # приоритет 2: birth_date + (f_name ИЛИ alt_f_name)
            if birth_date is not None:
                check_query = """
                    SELECT guest_id FROM guests 
                    WHERE birth_date = %s AND (f_name = %s OR alt_f_name = %s)
                """
                cursor.execute(check_query, (birth_date, f_name, alt_f_name))
                existing_guest = cursor.fetchone()

                if existing_guest:
                    guest_id = existing_guest[0]
                    guest_key = (f_name, alt_f_name, birth_date)
                    guest_map[guest_key] = guest_id
                    continue

            # приоритет 3: profile_id (для гостей без birth_date)
            if profile_id is not None:
                check_query = """
                    SELECT guest_id FROM guests 
                    WHERE profile_id = %s
                """
                cursor.execute(check_query, (profile_id,))
                existing_guest = cursor.fetchone()

                if existing_guest:
                    guest_id = existing_guest[0]
                    guest_key = (f_name, alt_f_name, birth_date)
                    guest_map[guest_key] = guest_id
                    continue

            # если гость не найден - вставляем нового
            insert_query = """
                INSERT INTO guests (f_name, alt_f_name, birth_date, profile_id)
                VALUES (%s, %s, %s, %s)
                RETURNING guest_id
            """
            cursor.execute(insert_query, (f_name, alt_f_name, birth_date, profile_id))
            guest_id = cursor.fetchone()[0]
            inserted_count += 1

            guest_key = (f_name, alt_f_name, birth_date)
            guest_map[guest_key] = guest_id

        conn.commit()
        _logging(f'Вставлено {inserted_count} новых гостей')

    except Exception as e:
        conn.rollback()
        _logging(f'Ошибка при вставке гостей: {e}')
        raise
    finally:
        cursor.close()
        conn.close()

    return guest_map


def get_guest_id_mapping(df):
    """получение mapping гостей из БД"""
    conn = get_db_connection()
    cursor = conn.cursor()

    required_cols = ['f_name', 'alt_f_name', 'birth_date', 'profile_id']
    missing_cols = [col for col in required_cols if col not in df.columns]
    if missing_cols:
        _logging(f'Ошибка: колонки {missing_cols} отсутствуют в DataFrame')
        cursor.close()
        conn.close()
        return {}

    unique_guests = df[required_cols].drop_duplicates()
    guest_map = {}

    for _, row in unique_guests.iterrows():
        f_name = row['f_name']
        alt_f_name = row['alt_f_name']
        birth_date = row['birth_date']
        profile_id = row['profile_id']

        # приводим birth_date к единому формату
        if isinstance(birth_date, pd.Timestamp):
            birth_date = birth_date.date()
        elif isinstance(birth_date, str) and birth_date:
            try:
                birth_date = pd.to_datetime(birth_date).date()
            except:
                birth_date = None
        elif pd.isna(birth_date):
            birth_date = None

        # приоритет 1: birth_date + profile_id
        if birth_date is not None and profile_id is not None:
            query = """
                SELECT guest_id FROM guests 
                WHERE birth_date = %s AND profile_id = %s
            """
            cursor.execute(query, (birth_date, profile_id))
            result = cursor.fetchone()

            if result:
                guest_key = (f_name, alt_f_name, birth_date)
                guest_map[guest_key] = result[0]
                continue

        # приоритет 2: birth_date + (f_name ИЛИ alt_f_name)
        if birth_date is not None:
            query = """
                SELECT guest_id FROM guests 
                WHERE birth_date = %s AND (f_name = %s OR alt_f_name = %s)
            """
            cursor.execute(query, (birth_date, f_name, alt_f_name))
            result = cursor.fetchone()

            if result:
                guest_key = (f_name, alt_f_name, birth_date)
                guest_map[guest_key] = result[0]
                continue

        # приоритет 3: profile_id (для гостей без birth_date)
        if profile_id is not None:
            query = """
                SELECT guest_id FROM guests 
                WHERE profile_id = %s
            """
            cursor.execute(query, (profile_id,))
            result = cursor.fetchone()

            if result:
                guest_key = (f_name, alt_f_name, birth_date)
                guest_map[guest_key] = result[0]

    cursor.close()
    conn.close()

    _logging(f'Найдено {len(guest_map)} гостей в БД')
    return guest_map


def prepare_records_data(df, guest_map):
    """подготовка данных для таблицы records"""
    records = []

    record_columns = [
        'room_number', 'adult_count', 'child_count', 'title', 'vip_code',
        'vip_code_description', 'arrival_date', 'departure_date',
        'membership_level_tng', 'room_type', 'room_class', 'language',
        'nationality_code', 'nationality_code_description', 'reservation_id',
        'reservation_status', 'arrival_time', 'departure_time'
    ]

    missing_cols = [col for col in record_columns if col not in df.columns]
    if missing_cols:
        _logging(f'Предупреждение: следующие колонки отсутствуют в DataFrame: {missing_cols}')

    guest_id_cols = ['f_name', 'alt_f_name', 'birth_date']

    for idx, row in df.iterrows():
        # приводим birth_date к единому формату (date объект)
        birth_date = row['birth_date']
        if isinstance(birth_date, pd.Timestamp):
            birth_date = birth_date.date()
        elif isinstance(birth_date, str) and birth_date:
            try:
                birth_date = pd.to_datetime(birth_date).date()
            except:
                birth_date = None
        elif pd.isna(birth_date):
            birth_date = None

        guest_key = (row['f_name'], row['alt_f_name'], birth_date)
        guest_id = guest_map.get(guest_key)

        if guest_id is None:
            _logging(f'Предупреждение: не найден guest_id для гостя {guest_key} (строка {idx})')
            continue

        record_data = []
        for col in record_columns:
            value = row[col]
            if pd.notnull(value):
                if col in ['room_number', 'adult_count', 'child_count', 'reservation_id']:
                    try:
                        value = int(value)
                    except (ValueError, TypeError):
                        value = None
                elif col in ['arrival_date', 'departure_date']:
                    if isinstance(value, str):
                        try:
                            value = pd.to_datetime(value).date()
                        except:
                            value = None
                    elif isinstance(value, pd.Timestamp):
                        value = value.date()
            else:
                value = None
            record_data.append(value)

        record_data.append(guest_id)
        records.append(tuple(record_data))

    return records


def insert_records(records):
    """вставка записей в таблицу records"""
    if not records:
        _logging('Нет данных для вставки в records')
        return

    conn = get_db_connection()
    cursor = conn.cursor()

    insert_query = """
        INSERT INTO records (
            room_number, adult_count, child_count, title, vip_code,
            vip_code_description, arrival_date, departure_date,
            membership_level_tng, room_type, room_class, language,
            nationality_code, nationality_code_description, reservation_id,
            reservation_status, arrival_time, departure_time, guest_id
        ) VALUES %s
    """

    try:
        execute_values(cursor, insert_query, records)
        conn.commit()
        _logging(f'Вставлено {len(records)} записей в records')

    except Exception as e:
        conn.rollback()
        _logging(f'Ошибка при вставке записей: {e}')
        raise
    finally:
        cursor.close()
        conn.close()


def main():
    """основная функция"""
    
    files = glob.glob('completed_*.csv')
    if files:
        csv_file = files[0]
    else:
        _logging(f'Файл с паттерном completed_*.csv не найден')
        exit()

    df = read_csv_file(csv_file)

    if df is None:
        return

    # подготовка данных для гостей
    guests = prepare_guest_data(df)
    _logging(f'Подготовлено {len(guests)} уникальных гостей')

    # вставка гостей
    guest_map = insert_guests(guests)

    # если гости не были вставлены, получаем их из БД
    if not guest_map:
        _logging('Получаем mapping гостей из БД...')
        guest_map = get_guest_id_mapping(df)

    if not guest_map:
        _logging('Ошибка: не удалось получить guest_id для гостей')
        return

    # подготовка данных для записей
    records = prepare_records_data(df, guest_map)
    _logging(f'Подготовлено {len(records)} записей для вставки')

    # вставка записей
    if records:
        insert_records(records)

    # удаление файла
    try:
        os.remove(csv_file)
        _logging(f'Файл {csv_file} удалён')
    except Exception as e:
        _logging(f'Ошибка при удалении файла completed: {e}')
        exit()


if __name__ == '__main__':
    main()