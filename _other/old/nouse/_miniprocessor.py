import pandas as pd
import numpy as np
import datetime
import os
import shutil
import glob

df = pd.read_csv(
    'bdforecast.csv',
    sep=';',
    header=None,
    encoding='utf-8'
)
df = df[[0, 3, 4, 8]]
df.columns = ['room_number', 'arrival_date', 'departure_date', 'birth_date']
df['arrival_date'] = pd.to_datetime(df['arrival_date'], format='%d.%m.%y')
df['departure_date'] = pd.to_datetime(df['departure_date'], format='%d.%m.%y')
df['birth_date'] = pd.to_datetime(df['birth_date'], format='%d.%m.%y')

df.to_csv('bdforecast-01.csv', index=False, sep=';')