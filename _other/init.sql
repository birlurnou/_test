#5B5778 - основной rgba(91, 87, 120, 1);
#9D9AAE - вторичный
#59628B - добавочный
#C0C2C4 - добавочный
#FFFFFF - добавочный

#ffb6c1 rgba(255, 182, 193, 1);
#87cefa rgba(135, 206, 250, 1);
#6a5acd rgba(106, 90, 205, 1);

#ff7f50 rgba(255, 127, 80, 1);

-- boxes
1   room_number                     -- номер комнаты                 -- int
2   adult_count                     -- кол-во взрослых               -- int
3   child_count                     -- кол-во детей                  -- int
4   f_name                          -- полное имя (eng)              -- nvarchar(255)
5   alt_f_name                      -- полное имя (rus)              -- nvarchar(255)
6   title                           -- title                         -- nvarchar(25)
7   birth_date                      -- дата рождения                 -- date
8   vip_code                        -- vip статус (код)              -- nvarchar(25)
9   vip_code_description            -- vip статус (расшифровка)      -- nvarchar(255)
10  arrival_date                    -- дата заезда                   -- date
11  departure_date                  -- дата выезда                   -- date
12  membership_level_tng            -- уровень приорити в tng        -- nvarchar(255)
13  room_type                       -- название комнаты              -- nvarchar(255)
14  room_class                      -- класс комнаты                 -- nvarchar(255)
15  language                        -- язык                          -- nvarchar(25)
16  nationality_code                -- национальность (код)          -- nvarchar(25)
17  nationality_code_description    -- национальность (расшифровка)  -- nvarchar(255)
18  profile_id                      -- номер профайла                -- int
19  reservation_id                  -- номер бронирования            -- int
20  reservation_status              -- статус бронирования           -- nvarchar(255)
21  arrival_time                    -- время заселения               -- nvarchar(25)
22  departure_time                  -- время выселения               -- nvarchar(25)


-- room box
room_number
room_type

language (any)
nationality_code (any)

adult_count
child_count
vip_code (vip_code_description)
membership_level_tng


-- guest box
f_name
alt_f_name
birth_date
arrival_date
arrival_time
departure_date
departure_time
language
nationality_code_description
profile_id

*attended_at



-- structure
guests - гости
records - дневные записи с гостями
comments - комментарии, привязанные к гостям

-- параметры, получаемые из отчёта
1   room_number                     -- номер комнаты                 -- int
2   adult_count                     -- кол-во взрослых               -- int
3   child_count                     -- кол-во детей                  -- int
4   f_name                          -- полное имя (eng)              -- nvarchar(255)
5   alt_f_name                      -- полное имя (rus)              -- nvarchar(255)
6   title                           -- title                         -- nvarchar(25)
7   birth_date                      -- дата рождения                 -- date
8   vip_code                        -- vip статус (код)              -- nvarchar(25)
9   vip_code_description            -- vip статус (расшифровка)      -- nvarchar(255)
10  arrival_date                    -- дата заезда                   -- date
11  departure_date                  -- дата выезда                   -- date
12  membership_level_tng            -- уровень приорити в tng        -- nvarchar(255)
13  room_type                       -- название комнаты              -- nvarchar(255)
14  room_class                      -- класс комнаты                 -- nvarchar(255)
15  language                        -- язык                          -- nvarchar(25)
16  nationality_code                -- национальность (код)          -- nvarchar(25)
17  nationality_code_description    -- национальность (расшифровка)  -- nvarchar(255)
18  profile_id                      -- номер профайла                -- int
19  reservation_id                  -- номер бронирования            -- int
20  reservation_status              -- статус бронирования           -- nvarchar(255)
21  arrival_time                    -- время заселения               -- nvarchar(25)
22  departure_time                  -- время выселения               -- nvarchar(25)

-- [guests] as g
guest_id
+
(
    f_name
    alt_f_name
    birth_date
    profile_id
    *created_at
    *updated_at
)

-- [records] as r
record_id
+
(   
    room_number
    adult_count
    child_count
    title
    vip_code
    vip_code_description
    arrival_date
    departure_date
    membership_level_tng
    room_type
    room_class
    language
    nationality_code
    nationality_code_description
    reservation_id
    reservation_status
    arrival_time
    departure_time

    g.guest_id
    g.f_name
    g.alt_f_name
    g.birth_date
    g.profile_id

    *attended_at
    *created_at
)

-- [comments] as c
comment_id
+
(
    g.guest_id

    *comment
    *created_at
    *created_by
)

-- таблицы
CREATE TABLE guests (
    guest_id SERIAL PRIMARY KEY,
    f_name VARCHAR(255),
    alt_f_name VARCHAR(255),
    birth_date DATE,
    profile_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT NULL
);
CREATE TABLE records (
    record_id SERIAL PRIMARY KEY,
    room_number INT,
    adult_count INT,
    child_count INT,
    title VARCHAR(25),
    vip_code VARCHAR(25),
    vip_code_description VARCHAR(255),
    arrival_date DATE,
    departure_date DATE,
    membership_level_tng VARCHAR(255),
    room_type VARCHAR(255),
    room_class VARCHAR(255),
    language VARCHAR(25),
    nationality_code VARCHAR(25),
    nationality_code_description VARCHAR(255),
    reservation_id INT,
    reservation_status VARCHAR(255),
    arrival_time  VARCHAR(25),
    departure_time VARCHAR(25),
    guest_id INT REFERENCES guests(guest_id) ON DELETE CASCADE,
    attended_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE comments (
    comment_id SERIAL PRIMARY KEY,
    guest_id INT REFERENCES guests(guest_id) ON DELETE CASCADE,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(50) DEFAULT NULL
);
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role VARCHAR(20) NOT NULL,
    username VARCHAR(100) DEFAULT NULL
);

-- индексы
CREATE INDEX idx_records_guest_id ON records(guest_id);
CREATE INDEX idx_records_arrival_date ON records(arrival_date);
CREATE INDEX idx_records_departure_date ON records(departure_date);
CREATE INDEX idx_records_room_number ON records(room_number);
CREATE INDEX idx_comments_guest_id ON comments(guest_id);
CREATE INDEX idx_users_login ON users(login);

-- удаление
DROP TABLE IF EXISTS guests CASCADE;
DROP TABLE IF EXISTS records CASCADE;
DROP TABLE IF EXISTS comments CASCADE;
DROP TABLE IF EXISTS users CASCADE;

TRUNCATE TABLE guests RESTART IDENTITY CASCADE;
TRUNCATE TABLE records RESTART IDENTITY CASCADE;


-- удаляем все данные
DELETE FROM guests;
DELETE FROM records;
DELETE FROM comments;
DELETE FROM users;

-- нещашифрованная дата рождения
rep_gen.dob_str(b.encrypted_birth_date)

-- records
with count_coms as (
    select c.guest_id, count(c.comment_id) as count from comments c
    group by 1
)
select
    row_number() over (order by r.room_number)  as record,
    r.room_number as room,
    g.profile_id,
    r.reservation_id as res_id,
    g.f_name as name,
    case
        when g.birth_date is null then 'pass'
        when extract(month from g.birth_date) = extract(month from localtimestamp)
         and extract(day from g.birth_date) = extract(day from localtimestamp) then 'yes'
        else ''
    end as bday,
    -- r.title,
    case
        when r.title in ('mr')
            then 'male'
        when r.title in ('mrs', 'ms')
            then 'female'
    end as gender,
    UPPER(r.nationality_code) || ' (' || COALESCE(r.nationality_code_description, '') || ') (' || COALESCE(r.language, '') || ')' as nationality,
    (
        select
            coalesce(sum(r2.adult_count + r2.child_count), 0) || ' (' ||
            coalesce(sum(r2.child_count), 0) || ')'
        from records r2
        where date_trunc('day', r2.created_at) = date_trunc('day', localtimestamp)
        and r2.room_number = r.room_number
        and case
                when r.reservation_status in ('checked in', 'due out', 'walk in')
                    then r2.reservation_status in ('checked in', 'due out', 'walk in')
                when r.reservation_status in ('due in', 'no show')
                    then r2.reservation_status in ('due in', 'no show')
            end
    ) as guest_count,
    r.reservation_status as res_status,
    r.arrival_date || '  ' || coalesce(r.arrival_time, '--:--') as arrival,
    r.departure_date || '  ' || coalesce(r.departure_time, '--:--') as departure,
    r.vip_code || ' (' || r.vip_code_description || ')' as vip_status,
    -- r.membership_level_tng as tng,
    r.room_type || ' (' || COALESCE(r.room_class, '') || ')' as type,
    cc.count::int as coms,
    r.attended_at as check_time
from records r
inner join guests g on r.guest_id = g.guest_id
left join count_coms cc on r.guest_id = cc.guest_id
where date_trunc('day', r.created_at) = date_trunc('day', localtimestamp)
order by r.record_id;

-- comments
select
    g.f_name as name,
    r.room_number as room,
    c.comment,
    c.created_at
from comments c
inner join guests g on c.guest_id = g.guest_id
inner join records r on c.guest_id = r.guest_id
where date_trunc('day', r.created_at) = date_trunc('day', localtimestamp)
order by room asc, created_at desc;

-- results

select
    (select count(*) from records r 
     where r.attended_at is not null
       and date_trunc('day', r.created_at) = date_trunc('day', LOCALTIMESTAMP)) as "Attended Count",
    
    (select count(*) from records r 
     where date_trunc('day', r.created_at) = date_trunc('day', LOCALTIMESTAMP)) as "Guest Count",
    
    (select count(*) from records 
     where reservation_status = 'checked in' 
       and date_trunc('day', created_at) = date_trunc('day', LOCALTIMESTAMP)) as "Checked In",
    
    (select count(*) from records 
     where reservation_status = 'due out' 
       and date_trunc('day', created_at) = date_trunc('day', LOCALTIMESTAMP)) as "Due Out",
    
    (select count(*) from records 
     where reservation_status = 'walk in' 
       and date_trunc('day', created_at) = date_trunc('day', LOCALTIMESTAMP)) as "Walk In",
    
    (select count(*) from records 
     where reservation_status = 'due in' 
       and date_trunc('day', created_at) = date_trunc('day', LOCALTIMESTAMP)) as "Due In",
    
    (select count(*) from records 
     where reservation_status = 'no show' 
       and date_trunc('day', created_at) = date_trunc('day', LOCALTIMESTAMP)) as "No Show",
    
    (select count(*) from records r 
     inner join guests g on r.guest_id = g.guest_id
     where date_trunc('day', r.created_at) = date_trunc('day', LOCALTIMESTAMP)
       and extract(month from g.birth_date) = extract(month from localtimestamp) 
       and extract(day from g.birth_date) = extract(day from localtimestamp)) as birthday_count,
    
    (select count(*) from records r 
     inner join comments c on r.guest_id = c.guest_id
     where date_trunc('day', r.created_at) = date_trunc('day', LOCALTIMESTAMP)) as comment_count;