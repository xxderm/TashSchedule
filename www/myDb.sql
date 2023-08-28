DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`local`@`%` PROCEDURE `fill_schedule` ()   BEGIN
	DECLARE begin_date DATE;
    DECLARE end_date DATE;
    DECLARE curr_date DATE;   
    DECLARE reg_travel_time INT;
    
	DECLARE curr_reg_id INT;
    DECLARE reg_count INT;
    # Счетчик регионов
    DECLARE i INT;
    # Счетчик курьеров
    DECLARE j INT;
    DECLARE mod_of_cour INT;
    
    DECLARE slots_count INT;
    DECLARE curr_cour INT;
    DECLARE cour_count INT;
    DECLARE curr_slot INT;
    
    # В табл. будут хранится слоты для курьеров
	CREATE TEMPORARY TABLE region_slots (
        cour_id INT,
		region_id INT,
        departure DATE,
        arrival DATE
    );
    # В табл. будут хранится метки занятости курьеров
    CREATE TEMPORARY TABLE cour_slots (
        cour_id INT,
        arrival DATE NULL
    );
    # Помещаю всех курьеров
    INSERT INTO cour_slots (cour_id, arrival)
    SELECT courier.id, NULL
    FROM courier;

    SET begin_date = CURRENT_DATE();
    SET curr_date = begin_date;
    SET end_date = DATE_ADD(begin_date, INTERVAL 3 MONTH);
    
    SELECT COUNT(*) INTO reg_count FROM region;
    SELECT COUNT(*) INTO cour_count FROM courier;
    SET i = 0;
    SET j = 0;
    
    WHILE i < reg_count DO
    	# id тек. региона
    	SELECT region.id 
        INTO curr_reg_id
        FROM region
        ORDER BY region.id
        LIMIT 1 OFFSET i;
        
        SELECT region.travel_time
        INTO reg_travel_time
        FROM region
        WHERE region.id = curr_reg_id;
        
        # Добавляю слоты для региона
        WHILE curr_date <= end_date DO
        	INSERT INTO region_slots (cour_id, region_id, departure, arrival)
            VALUES (NULL, curr_reg_id, curr_date, DATE_ADD(curr_date, INTERVAL reg_travel_time DAY));                
            SET curr_date = DATE_ADD(curr_date, INTERVAL reg_travel_time DAY);            
        END WHILE;
        SET curr_date = begin_date;
        SET i = i + 1;
    END WHILE;
   	
    # Кол-во слотов
    SELECT COUNT(*) INTO slots_count FROM region_slots;    
    # Фиксирую курьеров под слоты    
    SET i = 0;
    SET curr_date = begin_date;
    # Распределяю при помощи мод. Round Robin
    WHILE curr_date <= end_date DO
    	SET i = 0;
        WHILE i < reg_count DO
            # id тек. региона
            SELECT region.id, region.travel_time
            INTO curr_reg_id, reg_travel_time
            FROM region
            ORDER BY region.id
            LIMIT 1 OFFSET i;
            
            # Тек. кур. из таблицы слотов, у которого не задано время, или текущая дата после прибытия курьера
			SELECT cour_slots.cour_id
				INTO curr_cour
                FROM cour_slots
                WHERE cour_slots.arrival IS NULL OR curr_date > cour_slots.arrival
                LIMIT 1; 
            # Обновил тек. слот на дату прибытия
            UPDATE cour_slots
            SET cour_slots.arrival = DATE_ADD(curr_date, INTERVAL reg_travel_time DAY)
            WHERE cour_slots.cour_id = curr_cour;
            
            
            UPDATE region_slots
            SET region_slots.cour_id = curr_cour
            WHERE region_slots.departure = curr_date AND region_slots.region_id = curr_reg_id;
            SET j = j + 1;
            SET i = i + 1;
        END WHILE;
        SET curr_date = DATE_ADD(curr_date, INTERVAL 1 DAY);     
    END WHILE;
    
    SELECT * FROM region_slots;
    
    INSERT INTO schedule (schedule.courier_id, schedule.region_id, schedule.departure, schedule.arrival)
    SELECT region_slots.cour_id, region_slots.region_id, region_slots.departure, region_slots.arrival
    FROM region_slots;
    
	DROP TEMPORARY TABLE region_slots;
    DROP TEMPORARY TABLE cour_slots;
   
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `courier`
--

CREATE TABLE `courier` (
  `id` int NOT NULL,
  `name` varchar(200) NOT NULL,
  `surname` varchar(200) NOT NULL,
  `patronymic` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `courier`
--

INSERT INTO `courier` (`id`, `name`, `surname`, `patronymic`) VALUES
(1, 'Артём', 'Семенов', 'Владимирович'),
(2, 'Матвей', 'Павлов', 'Максимович'),
(3, 'Злата', 'Зубкова', 'Артёмовна'),
(4, 'Максим', 'Кириллов', 'Михайлович'),
(5, 'Давид', 'Дьяконов', 'Егорович'),
(6, 'Михаил', 'Петров', 'Ильич'),
(7, 'Дарья', 'Семенова', 'Макаровна'),
(8, 'Алиса', 'Захарова', 'Матвеевна'),
(9, 'Иван', 'Шмелев', 'Семёнович'),
(10, 'Леонид', 'Галкин', 'Святославович'),
(11, 'Роберт', 'Васильев', 'Романович'),
(12, 'Сафия', 'Сахарова', 'Александровна'),
(13, 'Камилла', 'Михеева', 'Михайловна'),
(14, 'Давид', 'Столяров', 'Матвеевич');

-- --------------------------------------------------------

--
-- Структура таблицы `region`
--

CREATE TABLE `region` (
  `id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `travel_time` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `region`
--

INSERT INTO `region` (`id`, `title`, `travel_time`) VALUES
(1, 'Санкт-Петербург', 1),
(2, 'Уфа', 1),
(3, 'Нижний Новгород', 2),
(4, 'Владимир', 1),
(5, 'Кострома', 2),
(6, 'Екатеринбург', 3),
(7, 'Ковров', 1),
(8, 'Воронеж', 2),
(9, 'Самара', 3),
(10, 'Астрахань', 3);

-- --------------------------------------------------------

--
-- Структура таблицы `schedule`
--

CREATE TABLE `schedule` (
  `id` int NOT NULL,
  `region_id` int NOT NULL,
  `courier_id` int NOT NULL,
  `departure` date NOT NULL,
  `arrival` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `courier`
--
ALTER TABLE `courier`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `region`
--
ALTER TABLE `region`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `region_id` (`region_id`),
  ADD KEY `courier_id` (`courier_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `courier`
--
ALTER TABLE `courier`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT для таблицы `region`
--
ALTER TABLE `region`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=607;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `ct1` FOREIGN KEY (`courier_id`) REFERENCES `courier` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `ct2` FOREIGN KEY (`region_id`) REFERENCES `region` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
