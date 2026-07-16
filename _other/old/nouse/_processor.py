import pandas as pd
import numpy as np
import datetime
import os
import shutil
import glob

# создание директорий
# for folder in ['archive', 'cleaned', 'completed', 'logs']:
#     os.makedirs(folder, exist_ok=True)

# функция логирования
start_log_time = datetime.datetime.now().strftime('%Y%m%d_%H%M%S')

def _logging(log_text):
    print(log_text)
    # try:
    #     with open(f'logs/log_{start_log_time}.txt', 'a', encoding='utf-8') as f:
    #         f.write(f'[{str(datetime.datetime.now())[:-7]}] {log_text}\n')
    # except:
    #     pass

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
    _logging(f'Ошибка при структуризации исходного csv: {e}')
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
    _logging(f'Ошибка при очищении исходного csv: {e}')
    exit()

# перемещаем source_filename в папку archive

# try:
#     shutil.move(source_filename, f'archive/{source_filename}')
#     _logging(f'Файл {source_filename} перемещен в archive')
# except Exception as e:
#     _logging(f'Ошибка: {e}')
#     exit()

# обработка сырых данных
try:
    df = pd.read_csv(clean_filename, sep=';')
    _logging(f'Файл {clean_filename} прочитан, df создан')
except Exception as e:
    _logging(f'Ошибка при создании необработанного dataframe: {e}')
    exit()

# перемещаем clean_filename в папку cleaned

# try:
#     shutil.move(clean_filename, f'cleaned/{clean_filename}')
#     _logging(f'Файл {clean_filename} перемещен в cleaned')
# except Exception as e:
#     _logging(f'Ошибка: {e}')
#     exit()

df.columns = df.columns.str.lower()

try:
    df = df.rename(columns={
        'room': 'room_number',
        'persons': 'guest_count',
        'adults': 'adult_count',
        'children': 'child_count',
        'trunc_arrival': 'arrival_date',
        'trunc_departure': 'departure_date',
        'room_category': 'room_category_numeric',
        'room_category_label': 'room_category'
    })

    result = df[['room_number', 'display_name', 'adult_count', 'child_count', 'guest_name_id', 'arrival_date',
                 'departure_date', 'room_category']].copy() # , 'room_class'

    # очистка пустых значений (гостей без номеров)
    result['room_number'] = result['room_number'].fillna(0)
    # фильтруем строки, которые не имеют room_number
    result = result.loc[result['room_number'] != 0]

    # добавляем столбик со статусом
    # status_map = {
    #     'ROOM': 'Standard',
    #     'DELUX': 'VIP',
    #     'STES': 'VIP',
    #     'CLUB': 'Standard',
    #     'PRES': 'VIP'
    # }
    # result['status'] = result['room_class'].map(status_map)

    # изменяем тип данных room_number
    result['room_number'] = result['room_number'].astype('int')

    # форматируем дату заселения
    result['arrival_date'] = pd.to_datetime(result['arrival_date'], format='%d-%b-%y')

    # форматируем дату выезда
    result['departure_date'] = pd.to_datetime(result['departure_date'], format='%d-%b-%y')

    # количество заездов (сегодня)
    today = pd.Timestamp.now().normalize()
    arrival_today = len(result.loc[result['arrival_date'] == today])

    # фильтруем дату заселения, оставляем тех, у кого будет завтрак (убираем тех, кто заселён сегодня и в будущем)
    today = pd.Timestamp.now().normalize()
    print(today)
    result = result.loc[result['arrival_date'] < today]
    result = result.loc[result['departure_date'] >= today]

    # сортируем данные
    result = result.sort_values(['room_number'], ascending=[True])
    # result = result.sort_values(['arrival_date', 'ROOM_NUMBER'], ascending=[True, True])
    # result = result.sort_values(['departure_date'], ascending=[True])

    complete_filename = f'completed_{datetime.datetime.now().strftime('%Y%m%d_%H%M%S')}.csv'
    result.to_csv(complete_filename, index=False, sep=';')
    _logging(f'Файл {complete_filename} успешно создан')

    counts = result['room_number'].value_counts()
    rooms_unique = len(counts)
    # more_than_1 = len(counts[counts > 1])
    _logging(f'Количество номеров (на заселение): {arrival_today}')
    _logging(f'Количество номеров (на завтрак): {rooms_unique}')
    _logging(f'Количество взрослых (на завтрак): {result['adult_count'].sum()}')
    _logging(f'Количество детей (на завтрак): {result['child_count'].sum()}')
    _logging(f'Размер: {[i for i in result.shape]}')
    _logging(f'{result.dtypes}')

except Exception as e:
    _logging(f'Ошибка при финальной обработке: {e}')
    exit()

# перемещаем complete_filename в папку completed

# try:
#     shutil.move(complete_filename, f'completed/{complete_filename}')
#     _logging(f'Файл {complete_filename} перемещен в completed')
# except Exception as e:
#     _logging(f'Ошибка: {e}')
#     exit()

# вставляем пустую строку в конце работы

# try:
#     with open(f'logs/log_{start_log_time}.txt', 'a', encoding='utf-8') as f:
#         f.write(f'\n')
# except:
#     pass


# 'PRODUCT_ID1',                    дроп
# 'PRODUCT_DESC',                   дроп
# 'STAY_DATE1',                     дроп
# 'CONFIRMATION_NO',                дроп (номер подтверждения гостя)
# 'ADULTS',                         количество взрослых
# 'CHILDREN',                       количество детей
# 'GUEST_NAME',                     дроп (имя гостя)
# 'RESV_STATUS',                    1
# 'REPORT_ID1',                     дроп
# 'INSERT_DATE',                    дроп
# 'NUMBER1',                        дроп
# 'STAY_DATE_CHAR1',                дата в формате char
# 'TOTAL_PKGS1',                    дроп
# 'ARRIVAL_DAY_YN',                 дроп
# 'STAY_DAY1',                      дроп
# 'PKG_QTY',                        дроп
# 'CALCULATION_RULE',               дроп
# 'QUANTITY',                       дроп
# 'PERSONS',                        количество гостей
# 'NO_OF_ROOMS',                    дроп
# 'TRUNC_ARRIVAL',                  дата заселения
# 'TRUNC_DEPARTURE',                дата выезда
# 'RESV_NAME_ID',                   дроп
# 'GUEST_NAME_ID',                  уникальный идентификатор имени гостя
# 'ROOM',                           номер комнаты
# 'ROOM_CATEGORY',                  категория
# 'ROOM_CATEGORY_LABEL',            название категории
# 'BOOKED_ROOM_CATEGORY',           категория
# 'BOOKED_ROOM_CATEGORY_LABEL',     название категории
# 'ROOM_CLASS',                     класс номера
# 'GUEST_FIRST_NAME',               первое имя гостя
# 'DISPLAY_NAME',                   полное имя гостя
# 'COMPUTED_RESV_STATUS',           статус
# 'RES_STATUS',                     статус бронирования
# 'RATE_CODE',                      код бронирования
# 'PRODUCTS',                       услуги
# 'POS_NEXT_DAY_YN',                дроп
# 'GUARANTEE_CODE',                 дроп
# 'RESERVE_INVENTORY_YN',           дроп
# 'PKG_FORCAST_GROUP',              дроп
# 'GROUP_SELL_SEQ'                  дроп


# RESV_STATUS: ['CHECKED IN' 'CHECKED OUT' 'RESERVED']
# ROOM_CATEGORY: [10102 10101 10106 10107 10105 10104 10108 10109]
# ROOM_CATEGORY_LABEL: ['TWIN' 'KING' 'DLXK' 'RGSK' 'CLBT' 'CLBK' 'DIPL' 'PRES']
# ROOM_CLASS: ['ROOM' 'DELUX' 'STES' 'CLUB' 'PRES']
# COMPUTED_RESV_STATUS: ['DUE OUT' 'CHECKED IN' 'CHECKED OUT' 'RESERVED']
# RES_STATUS: ['DUOT' 'CKIN' 'CKOT' 'CO-G' 'DP-R' 'TA-G' 'CC-G' 'DB' 'NON-' 'DF-G']
# POS_NEXT_DAY_YN: ?
# GUARANTEE_CODE: ['CHECKED IN' 'CO-GTD' 'DP-REC' 'TA-GTD' 'CC-GTD' 'DB' 'NON-GTD' 'DF-GRP']
# RESERVE_INVENTORY_YN: ['Y' 'N']