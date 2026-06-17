import random

data = [
    500, 502, 504, 506, 508, 509, 510, 512, 514, 516, 518, 520, 522, 524, 526, 528, 530, 532, 534,
    600, 602, 604, 606, 608, 609, 610, 612, 614, 616, 618, 620, 622, 624, 626, 628, 630, 632, 634,
    700, 702, 704, 706, 708, 709, 710, 712, 714, 716, 717, 718, 720, 722, 724, 725, 726, 728, 730, 732, 734,
    800, 802, 804, 806, 808, 809, 810, 812, 814, 816, 817, 818, 820, 822, 824, 825, 826, 828, 830, 832, 834,
    900, 902, 904, 906, 908, 909, 910, 912, 914, 916, 917, 918, 920, 922, 924, 925, 926, 928, 930, 932, 934,
    1000, 1002, 1004, 1006, 1008, 1009, 1010, 1012, 1014, 1016, 1017, 1018, 1020, 1022, 1024, 1025, 1026, 1028, 1030,
    1032, 1034,
    1100, 1102, 1104, 1106, 1108, 1109, 1110, 1112, 1114, 1116, 1117, 1118, 1120, 1122, 1124, 1125, 1126, 1128, 1130,
    1132, 1134,
    1200, 1202, 1204, 1206, 1208, 1209, 1210, 1212, 1214, 1216, 1217, 1218, 1220, 1222, 1224, 1225, 1226, 1228, 1230,
    1232, 1234,
    1300, 1302, 1304, 1306, 1308, 1309, 1310, 1312, 1314, 1316, 1317, 1318, 1320, 1322, 1324, 1325, 1326, 1328, 1330,
    1332, 1334,
    1400, 1402, 1404, 1406, 1408, 1409, 1410, 1412, 1414, 1416, 1417, 1418, 1420, 1422, 1424, 1425, 1426, 1428, 1430,
    1432, 1434,
    1500, 1502, 1504, 1506, 1508, 1509, 1510, 1512, 1514, 1516, 1517, 1518, 1520, 1522, 1524, 1525, 1526, 1528, 1530,
    1532, 1534,
    1600, 1602, 1604, 1606, 1608, 1609, 1610, 1612, 1614, 1616, 1617, 1618, 1620, 1622, 1624, 1625, 1626, 1628, 1630,
    1632, 1634,
    1700, 1702, 1704, 1706, 1708, 1709, 1710, 1712, 1714, 1716, 1717, 1718, 1720, 1722, 1724, 1725, 1726, 1728, 1730,
    1732, 1734,
    1800, 1802, 1804, 1806, 1807, 1808, 1810, 1811, 1812, 1814, 1816, 1818,
    1902, 1904, 1906, 1908, 1910, 1911, 1912, 1914, 1916, 1918, 1919, 1920
]
type = ['standard', 'club', 'deluxe', 'luxe']

string = ''
f_value = True

for item in range(len(data)):
    if len(str(data[item])) == 3:
        if f_value:
            string += f'    {data[item-1]},'
            f_value = False
        if str(data[item])[0] == str(data[item-1])[0]:
            string += f' {data[item]},'
        else:
            string += '\n'
            f_value = True
    else:
        if f_value:
            string += f'    {data[item-1]},'
            f_value = False
        if str(data[item])[0:2] == str(data[item-1])[0:2]:
            string += f' {data[item]},'
        else:
            string += '\n'
            f_value = True

# print(string[:-1])

string = 'INSERT INTO rooms (room_number, room_type) VALUES\n'
count = 0

for i in data:
    string += f"('{i if len(str(i)) > 3 else '0' + str(i)}', '{random.choice(type)}'),\n"
    count += 1

# print(count)
# print(string[:-2])

from datetime import datetime, timedelta
import names

start_date = datetime(1990, 1, 1).date()
end_date = datetime(2022, 1, 1).date()
guest_type_data = ['adult', 'child']
gender_data = ('male', 'female')

string = 'INSERT INTO guests (room_id, name, birth_date, arrival_date, departure_date, guest_type, gender) VALUES\n'
for i in data:
    room_id = random.randint(1, count)
    gender = random.choice(gender_data)
    name = names.get_full_name(gender=gender)
    birth_date = start_date + timedelta(days=random.randint(0, (end_date - start_date).days))
    current_date = datetime.today().date()
    arrival_date = current_date - timedelta(days=random.randint(0, 4))
    departure_date = arrival_date + timedelta(days=random.randint(1, 4))
    if int(str(current_date - birth_date).split(' ')[0]) > 5000:
        guest_type = guest_type_data[0]
    else:
        guest_type = guest_type_data[1]


    string += f"('{room_id}', '{name}', '{birth_date}', '{arrival_date}', '{departure_date}', '{guest_type}', '{gender}'),\n"

print(string[:-2])