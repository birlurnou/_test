import psycopg2
from psycopg2 import sql, extras
from contextlib import contextmanager
from typing import Optional, List, Dict, Any
from datetime import date, datetime
import pandas as pd


class Database:
    def __init__(self, host='localhost', port='5432', dbname='hotel_breakfast',
                 user='postgres', password=''):
        self.conn_params = {
            'host': host,
            'port': port,
            'dbname': dbname,
            'user': user,
            'password': password
        }

    @contextmanager
    def get_cursor(self):
        conn = psycopg2.connect(**self.conn_params)
        try:
            yield conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
            conn.commit()
        except Exception as e:
            conn.rollback()
            raise e
        finally:
            conn.close()

    def insert_records(self, csv_file: str):

        query = """
            INSERT INTO guests (room_id, name, birth_date, arrival_date, 
                               departure_date, guest_type, gender)
            VALUES (%(room_id)s, %(name)s, %(birth_date)s, %(arrival_date)s,
                    %(departure_date)s, %(guest_type)s, %(gender)s)
            RETURNING guest_id
        """
        with self.get_cursor() as cursor:
            cursor.execute(query, guest_data)
            return cursor.fetchone()['guest_id']

    def import_birthdays(self, csv_file: str):

        df = pd.read_csv(csv_file, sep=';', encoding='utf-8')

        data = []
        for _, row in df.iterrows():
            data.append((
                row['room_number'],
                row['arrival_date'],
                row['departure_date'],
                row['birth_date']
            ))

        query = """
            INSERT INTO birthdays (room_number, arrival_date, departure_date, birth_date)
            VALUES %s
        """
        with self.get_cursor() as cursor:
            extras.execute_values(cursor, query, data)

def create_data(csv_file: str):

    df = pd.read_csv(csv_file, sep=';', encoding='utf-8')

    data = []
    for _, row in df.iterrows():
        data.append((
            row['room_number'],
            row['arrival_date'],
            row['departure_date'],
            row['birth_date']
        ))
    return data

if __name__ == '__main__':

    db = Database(password='')

    db.import_birthdays('bdforecast-01.csv')