import psycopg2
from psycopg2 import sql, extras
from contextlib import contextmanager
from typing import Optional, List, Dict, Any
from datetime import date, datetime


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
        """Контекстный менеджер для работы с курсором"""
        conn = psycopg2.connect(**self.conn_params)
        try:
            yield conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)
            conn.commit()
        except Exception as e:
            conn.rollback()
            raise e
        finally:
            conn.close()

    def execute_query(self, query: str, params: tuple = None) -> List[Dict]:
        """Выполнить запрос и вернуть результат"""
        with self.get_cursor() as cursor:
            cursor.execute(query, params)
            return cursor.fetchall() if cursor.description else []

    def execute_many(self, query: str, params_list: List[tuple]):
        """Массовая вставка"""
        with self.get_cursor() as cursor:
            extras.execute_values(cursor, query, params_list)

    def insert_guest(self, guest_data: dict) -> int:
        """Пример вставки с возвратом ID"""
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