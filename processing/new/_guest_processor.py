import pandas as pd
import numpy as np
import datetime
import os
import shutil
import glob
import re
import traceback

# создание директорий
# source_folder = r'D:\MICROS\OPERA\export\OPERA\yekhr'
source_folder = r'\\yekhrpmsnod01\d$\MICROS\OPERA\export\OPERA\yekhr'
# source_folder = r'C:\xampp\htdocs\_test\processing\new'

# for folder in ['archive', 'archive/completed', 'logs']:
for folder in ['archive', 'logs']:
    os.makedirs(folder, exist_ok=True)

# функция логирования
start_log_time = datetime.datetime.now().strftime('%Y%m%d_%H%M%S')

def _logging(log_text):
    try:
        with open(f'logs/log_processor_{start_log_time}.txt', 'a', encoding='utf-8') as f:
            f.write(f'[{str(datetime.datetime.now())[:-7]}] {log_text}\n')
    except:
        pass

def _error(e):
    _logging(f'Ошибка: {str(e)}\nТип: {type(e).__name__}\nTraceback:\n{traceback.format_exc()}')

# структуризация сырых данных

start_idx = None
end_idx = None
search_pattern = os.path.join(source_folder, 'guest_profile_extract_for_web_*.csv')
files = glob.glob(search_pattern)
if files:
    source_filename = files[0]
    _logging(f'Найден исходный файл по пути {source_filename}')
else:
    _logging(f'Исходный файл не найден в директории {source_folder}, завершение работы')
    exit()

try:
    with open(source_filename, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    pattern = r'^.{4};'
    start = ''
    last = ''
    for i, line in enumerate(lines):
        if len(line) < 5:
            continue
        if re.match(pattern, line.strip()[0:5]):
            if start_idx is None:
                # start = line.split(';')[0:5]
                start_idx = i
            # last = line.split(';')[0:5]
            end_idx = i

    if start_idx and end_idx:
        _logging(f'Прочитан исходный файл {source_filename}, нужные индексы извлечены:')
        _logging(f'Стартовый индекс: {start_idx}')
        _logging(f'Конечный индекс: {end_idx}')
        _logging(f'Всего строк: {abs(start_idx - end_idx) + 1}')

except Exception as e:
    _logging(f'Ошибка при структуризации исходного csv: {e}')
    _error(e)
    exit()

clean_filename = ''

try:
    if start_idx is not None and end_idx is not None:
        lines = lines[start_idx:end_idx+1]
        clean_filename = f'cleaned_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.csv'
        with open(clean_filename, 'w', encoding='utf-8') as f:
            f.writelines(lines)
    _logging(f'Создан файл с чистыми данными {clean_filename}, переход к обработке')

except Exception as e:
    _logging(f'Ошибка при очищении исходного файла: {e}')
    _error(e)
    exit()

# перемещаем source_filename в папку archive

try:
    shutil.move(source_filename, f'archive/{source_filename.split('\\')[-1]}_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}')
    _logging(f'Файл {source_filename} перемещен в archive')
except Exception as e:
    _logging(f'Ошибка при перемещении source_filename: {e}')
    _error(e)
    exit()

# обработка сырых данных

try:
    df = pd.read_csv(clean_filename, sep=';', header=None, encoding='utf-8')
    _logging(f'Прочитан обработанный файл {clean_filename}, создан DataFrame')
except Exception as e:
    _logging(f'Ошибка при создании необработанного DataFrame: {e}')
    _error(e)
    exit()

def remove_clean_filename():
    try:
        os.remove(clean_filename)
        _logging(f'Удалён файл с чистыми данными {clean_filename}')
    except:
        _logging(f'Ошибка при удалении файл с чистыми данными: {e}')
        _error(e)
        exit()

try:
    df.columns = ['room_number',
                  'adult_count',
                  'child_count',
                  'f_name',
                  'alt_f_name',
                  'title',
                  'birth_date',
                  'vip_code',
                  'vip_code_description',
                  'arrival_date',
                  'departure_date',
                  'membership_level_tng',
                  'room_type',
                  'room_class',
                  'language',
                  'nationality_code',
                  'nationality_code_description',
                  'profile_id',
                  'reservation_id',
                  'reservation_status',
                  'arrival_time',
                  'departure_time'
                  ]
    result = df

    def clean_name(name):
        if pd.isna(name):
            return None
        items = str(name).replace(',', '').split()
        new_name = []
        for item in items:
            if '-' in item:
                _parts = []
                _items = item.split('-')
                for _item in _items:
                    _parts.append(_item.capitalize())
                new_name.append('-'.join(_parts))
            else:
                new_name.append(item.capitalize())
        return ' '.join(new_name)

    # на всякий случай заполняем пустые значения нулями
    result['room_number'] = result['room_number'].fillna(0)
    # фильтруем строки, в которых room_number == 0
    result = result.loc[result['room_number'] != 0]
    # на всякий случай изменяем тип данных room_number на int (уже должен быть int при добавлении в df)
    result['room_number'] = result['room_number'].apply(lambda x: int(x) if not pd.isna(x) else None)

    # форматируем имя гостя
    result['f_name'] = result['f_name'].apply(clean_name)

    # форматируем альт имя гостя
    result['alt_f_name'] = result['alt_f_name'].apply(clean_name)

    # форматируем title
    result['title'] = result['title'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'title: {df['title'].unique()}')

    # форматируем дату рождения
    result['birth_date'] = pd.to_datetime(result['birth_date'], format='%d.%m.%Y')

    # форматируем vip код
    result['vip_code'] = result['vip_code'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'vip_code: {df['vip_code'].unique()}')

    # форматируем расшифровку vip кода
    result['vip_code_description'] = result['vip_code_description'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'vip_code_description: {df['vip_code_description'].unique()}')

    # форматируем дату заселения
    result['arrival_date'] = pd.to_datetime(result['arrival_date'], format='%d-%b-%y')
    # print(f'arrival_date: {df['arrival_date'].unique()}')

    # форматируем дату выезда
    result['departure_date'] = pd.to_datetime(result['departure_date'], format='%d-%b-%y')
    # print(f'departure_date: {df['departure_date'].unique()}')

    # форматируем membership_level_tng
    result['membership_level_tng'] = result['membership_level_tng'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'membership_level_tng: {df['membership_level_tng'].unique()}')

    # форматируем room_type
    result['room_type'] = result['room_type'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'room_type: {df['room_type'].unique()}')

    # форматируем room_class
    result['room_class'] = result['room_class'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'room_class: {df['room_class'].unique()}')

    # форматируем language
    result['language'] = result['language'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'language: {df['language'].unique()}')

    # форматируем nationality_code
    result['nationality_code'] = result['nationality_code'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'nationality_code: {df['nationality_code'].unique()}')

    # форматируем nationality_code_description
    result['nationality_code_description'] = result['nationality_code_description'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'nationality_code_description: {df['nationality_code_description'].unique()}')

    # форматируем reservation_status
    result['reservation_status'] = result['reservation_status'].apply(lambda x: str(x).lower() if not pd.isna(x) else None)
    # print(f'reservation_status: {df['reservation_status'].unique()}')

    # форматируем arrival_time
    result['arrival_time'] = result['arrival_time'].apply(lambda x: str(x).replace('*', '').strip() if not pd.isna(x) else None)
    # print(f'arrival_time: {df['arrival_time'].unique()}')

    # форматируем departure_time
    result['departure_time'] = result['departure_time'].apply(lambda x: str(x).replace('*', '') if not pd.isna(x) else None)
    # print(f'departure_time: {df['departure_time'].unique()}')

    def _():
        pass

    # фильтруем даты заселения и выезда
    today = pd.Timestamp.now().normalize()
    condition = (
        # (result['arrival_date'] == today) &     # заезжают сегодня
        # (result['arrival_time'] < '05:00') &    # до 5 утра
        # (result['arrival_time'] > '00:00') |    # после полуночи

        # (result['arrival_time'].isna()) |       # время не указано

        # (result['arrival_date'] < today) |      # уже заехали ранее

        # (result['reservation_status'] in ['checked in', 'due out', 'due in', 'walkin', 'no show'])

        # (result['departure_date'] == today) &     # выезжают сегодня
        # (result['departure_time'] > '00:00') &    # после полуночи
        # (result['departure_time'] < '04:00') |    # до 4 утра

        # (result['departure_date'] > today)        # выезжают позже
    )
    # result = result.loc[condition]

    # сортируем данные order by room_number asc
    result = result.sort_values(['room_number'], ascending=[True])
    # result = result.sort_values(['arrival_date'], ascending=[False])

    # print(result['reservation_status'].unique())

    # формируем и сохраняем итоговый csv
    complete_filename = f'completed_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.csv'
    result.to_csv(complete_filename, index=False, sep=';')
    remove_clean_filename()
    _logging(f'Создан итоговый файл: {complete_filename}')

    counts = result['room_number'].value_counts()
    rooms_unique = len(counts)
    _logging(f'Количество номеров (на завтрак): {rooms_unique}')
    _logging(f'Количество гостей (на завтрак): {len(result['profile_id'].value_counts())}')
    _logging(f'Количество взрослых (на завтрак): {result['adult_count'].sum()}')
    _logging(f'Количество детей (на завтрак): {result['child_count'].sum()}')
    _logging(f'Размер: {[i for i in result.shape]}')
    _logging(f'Типы данных:\n{result.dtypes}')

except Exception as e:
    _logging(f'Ошибка при финальной обработке: {e}')
    _error(e)
    exit()