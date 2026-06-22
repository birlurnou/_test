import pandas as pd
import numpy as np
import datetime
import os
import shutil
import glob

# создание директорий

for folder in ['archive', 'cleaned', 'completed', 'logs']:
    os.makedirs(folder, exist_ok=True)

# функция логирования

start_log_time = datetime.datetime.now().strftime('%Y%m%d_%H%M%S')

def _logging(log_text):
    try:
        with open(f'logs/log_{start_log_time}.txt', 'a', encoding='utf-8') as f:
            f.write(f'[{str(datetime.datetime.now())[:-7]}] {log_text}\n')
    except:
        pass

# структуризация сырых данных

start_idx = None
end_idx = None

files = glob.glob('pkgforecast*.csv')
if files:
    source_filename = files[0]
    _logging(f'Файл {source_filename} найден')
else:
    _logging(f'Файл не найден, завершение работы')
    _logging('\n')
    exit()

try:
    with open(source_filename, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    for i, line in enumerate(lines):
        if line.strip().startswith('PRODUCT_ID1;'):
            start_idx = i
        if line.strip().startswith('STAY_DATE2;'):
            end_idx = i
            break

    if start_idx and end_idx:
        _logging(f'Файл {source_filename} прочитан, индексы извлечены')

except Exception as e:
    _logging(f'Ошибка: {e}')
    exit()

clean_filename = ''

try:
    if start_idx is not None and end_idx is not None:
        lines = lines[start_idx:end_idx]
        clean_filename = f'cleaned_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.csv'
        with open(clean_filename, 'w', encoding='utf-8') as f:
            f.writelines(lines)
    _logging(f'Файл {clean_filename} создан, переход к обработке')

except Exception as e:
    _logging(f'Ошибка: {e}')
    exit()

# перемещаем source_filename в папку archive

try:
    shutil.move(source_filename, f'archive/{source_filename}')
    _logging(f'Файл {source_filename} перемещен в archive')
except Exception as e:
    _logging(f'Ошибка: {e}')
    exit()

# обработка сырых данных

try:
    df = pd.read_csv(clean_filename, sep=';')
    _logging(f'Файл {clean_filename} прочитан, df создан')
except Exception as e:
    _logging(f'Ошибка: {e}')
    exit()

# перемещаем clean_filename в папку cleaned

try:
    shutil.move(clean_filename, f'cleaned/{clean_filename}')
    _logging(f'Файл {clean_filename} перемещен в cleaned')
except Exception as e:
    _logging(f'Ошибка: {e}')
    exit()

try:
    df = df.rename(columns={
        'ROOM': 'ROOM_NUMBER',
        'PERSONS': 'GUEST_COUNT',
        'ADULTS': 'ADULT_COUNT',
        'CHILDREN': 'CHILD_COUNT',
        'TRUNC_ARRIVAL': 'DATE_ARRIVAL',
        'TRUNC_DEPARTURE': 'DATE_DEPARTURE',
        'ROOM_CATEGORY': 'ROOM_CATEGORY_NUMERIC',
        'ROOM_CATEGORY_LABEL': 'ROOM_CATEGORY'
    })

    result = df[['ROOM_NUMBER', 'GUEST_COUNT', 'ADULT_COUNT', 'CHILD_COUNT', 'GUEST_NAME_ID', 'DATE_ARRIVAL',
                 'DATE_DEPARTURE', 'ROOM_CATEGORY', 'ROOM_CLASS']].copy()

    # очистка пустых значений (гостей без номеров)
    result['ROOM_NUMBER'] = result['ROOM_NUMBER'].fillna(0)
    # фильтруем строки, которые не имеют ROOM_NUMBER
    result = result.loc[result['ROOM_NUMBER'] != 0]

    # добавляем столбик со статусом
    status_map = {
        'ROOM': 'Standard',
        'DELUX': 'VIP',
        'STES': 'VIP',
        'CLUB': 'Standard',
        'PRES': 'VIP'
    }
    result['STATUS'] = result['ROOM_CLASS'].map(status_map)

    # изменяем тип данных ROOM_NUMBER
    result['ROOM_NUMBER'] = result['ROOM_NUMBER'].astype('int')

    # форматируем дату заселения
    result['DATE_ARRIVAL'] = pd.to_datetime(result['DATE_ARRIVAL'], format='%d-%b-%y')

    # форматируем дату выезда
    result['DATE_DEPARTURE'] = pd.to_datetime(result['DATE_DEPARTURE'], format='%d-%b-%y')

    # количество заездов (сегодня)
    today = pd.Timestamp.now().normalize()
    arrival_today = len(result.loc[result['DATE_ARRIVAL'] == today])

    # фильтруем дату заселения, оставляем тех, у кого будет завтрак (убираем тех, кто заселён сегодня и в будущем)
    today = pd.Timestamp.now().normalize()
    result = result.loc[result['DATE_ARRIVAL'] < today]

    # сортируем данные
    result = result.sort_values(['ROOM_NUMBER'], ascending=[True])

    complete_filename = f'completed_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.csv'
    result.to_csv(complete_filename, index=False, sep=';')
    _logging(f'Файл {complete_filename} успешно создан')

    counts = result['ROOM_NUMBER'].value_counts()
    rooms_unique = len(counts)
    _logging(f'Количество номеров (на заселение): {arrival_today}')
    _logging(f'Количество номеров (на завтрак): {rooms_unique}')
    _logging(f'Количество взрослых (на завтрак): {result['ADULT_COUNT'].sum()}')
    _logging(f'Количество детей (на завтрак): {result['CHILD_COUNT'].sum()}')
    _logging(f'Размер: {[i for i in result.shape]}\n{result.dtypes}')

except Exception as e:
    _logging(f'Ошибка: {e}')
    exit()

# перемещаем complete_filename в папку completed

try:
    shutil.move(complete_filename, f'completed/{complete_filename}')
    _logging(f'Файл {complete_filename} перемещен в completed')
except Exception as e:
    _logging(f'Ошибка: {e}')
    exit()
