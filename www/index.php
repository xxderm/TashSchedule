<?php

require_once 'EscapeOutput.php';
require_once 'Database.php';
require_once 'DateCheck.php';

$db = Database::getInstance();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Календарь</title>    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>    
</head>
<body>    
    <script>
        selectedRegionTitle = "";
        selectedDate = "";
        selectedCour = 0;
        function showArrival() {
            if (selectedRegionTitle.length == 0 || selectedDate.length == 0)
                return;            
            var xmlhttp = new XMLHttpRequest();
            selectedCour = document.getElementById("courier_select").value;  
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById("date_arrival").innerHTML = this.responseText;
                }
            };
            xmlhttp.open("GET", "CalcArrivalDate.php?cour=" + selectedCour + "&reg=" + selectedRegionTitle + "&depart=" + selectedDate, true);
            xmlhttp.send();
        }        
        function regionChange(title) {
            selectedRegionTitle = title;
            showArrival();
        }
        function departureChange(date) {
            selectedDate = date;
            selectedRegionTitle = document.getElementById("region_select").value;
            showArrival();
        }
        function courChange(cour) {
            selectedCour = cour;
            showArrival();
        }
    </script>
    <br/>
    <?php 
        $selectedDate = '';
        $schedule = [];
        if ( !empty($_GET['calendar']) ) {
            $selectedDate = $_GET['calendar'];
            $sql = 'SELECT schedule.id, schedule.departure, schedule.arrival, courier.name, courier.surname, courier.patronymic, region.title 
                    FROM schedule 
                    INNER JOIN region
                    ON region.id = schedule.region_id
                    INNER JOIN courier
                    ON courier.id = schedule.courier_id
                    WHERE departure=:sel_date';
            $schedule = $db->query($sql, ['sel_date'=>$selectedDate]);
        } 
        else {
            $sql = 'SELECT schedule.id, schedule.departure, schedule.arrival, courier.name, courier.surname, courier.patronymic, region.title
                    FROM schedule 
                    INNER JOIN region
                    ON region.id = schedule.region_id
                    INNER JOIN courier
                    ON courier.id = schedule.courier_id
                    ORDER BY departure';
            $schedule = $db->query($sql);
        }
    ?>
    <!--
        Выбор даты
     -->
    <form action="index.php" method="GET">
        <p style="padding-left: 15px; margin:15px;">Выберите дату:  
            <input style="margin-top:15px;" type="date" name="calendar" class="form-control" value = "<?php echo EscapeOutput::write($selectedDate); ?>"> 
            <br/>
            <input type="submit" value="Показать" class="btn btn-primary">
        </p>
    </form>
    <form action="index.php" method="GET">
        <button name="newSchedule" value="true" class="btn btn-primary" role="button" style="margin: 15px;">Добавить</button>
        <a class="btn btn-primary" href="/" role="button" style="margin: 15px;">Сброс</a>
    </form>
    <!--
        Форма внесения данных в расписание
     -->
    <?php  if (isset($_GET['newSchedule'])): ?>
    <form style="margin:15px;" action="index.php" method="POST">    
        <div class="form-group">
            <label for="region_select">Выберите регион:</label>
            <select class="form-control" name="selected_reg" id="region_select" onload="regionChange(this.value)" onchange="regionChange(this.value)">
                <?php 
                    $sql = 'SELECT id, title FROM region';
                    $regions = $db->query($sql);
                    foreach ($regions as $region) 
                        echo "<option>{$region['title']}</option>";
                ?>
            </select>
            <label style="margin-top:15px;" for="courier_select">Выберите курьера:</label>
            <select class="form-control" name="selected_cour" id="courier_select" onchange="courChange(this.value)">
                <?php 
                    $sql = 'SELECT * FROM courier';
                    $couriers = $db->query($sql);
                    foreach ($couriers as $courier) 
                        echo '<option value="' . $courier['id'] . '">' . "{$courier['surname']} {$courier['name']} {$courier['patronymic']}</option>";
                ?>
            </select>
            <label style="margin-top:15px;" for="departure">Выберите дату:</label>
            <input type="date" name="date_select" class="form-control" id="departure" onchange="departureChange(this.value)">   
            <p style="margin-top:15px;">Дата прибытия: <span id="date_arrival"></span></p>
            <input type="submit" value="Записать" class="btn btn-primary">
            <br/>         
        </div>     
    </form>
    <?php endif; ?>
    <!--
        Обработка запроса на добавление
     -->
    <?php
    if ( isset($_POST['selected_reg']) && isset($_POST['selected_cour']) && !empty($_POST['date_select']) ) {
        $db = Database::getInstance();
        $dep_date = $_POST['date_select'];
        $cour_id = $_POST['selected_cour'];
        $reg_title = $_POST['selected_reg'];

        $sql = 'SELECT travel_time,id
                FROM region
                WHERE title=:reg_title';
        $travel_time = $db->query($sql, ['reg_title'=>$reg_title]);
        $days = $travel_time[0]['travel_time'];
        $region_id = $travel_time[0]['id'];
        $arrival_date = date('Y-m-d', strtotime($dep_date . "+{$days} days"));   
        if (dateCheck($dep_date, $cour_id) > 0 || dateCheck($arrival_date, $cour_id) > 0) 
            echo 'В этот день курьер занят';     
        else {
            $sql = '
                INSERT INTO schedule(region_id, courier_id, departure, arrival)
                VALUES (:reg_id, :cour_id, :dep, :arriv)';                  
            $db->query($sql, [
                'reg_id'=>$region_id,
                'cour_id'=>$cour_id,
                'dep'=>$dep_date,
                'arriv'=>$arrival_date
            ]);
            echo 'Маршрут записан';
            unset($_POST['selected_reg']);
            unset($_POST['selected_cour']);
            unset($_POST['date_select']);
        }
    }
    ?>
     <!--
        Вывод расписания на выбранную дату
     -->
    <?php if (!empty($schedule)): ?>
    <table class="table">
    <thead>
        <tr>
        <th scope="col">Номер</th>
        <th scope="col">Регион</th>
        <th scope="col">Курьер</th>
        <th scope="col">Отправка</th>
        <th scope="col">Прибытие</th>
        </tr>
    </thead>
    <tbody>
        <?php        
            foreach($schedule as $row) {
                $id = $row['id'];
                $region_title = $row['title'];
                $cour_name_full = $row['surname'] . ' ' . $row['name'] . ' ' . $row['patronymic'];
                $departure = $row['departure'];
                $arrival = $row['arrival'];
                echo (
                    '<tr><th scope="row">' . $id . '</th>' .
                    "<td>{$region_title}</td>" .
                    "<td>{$cour_name_full}</td>" .
                    "<td>{$departure}</td>" .
                    "<td>{$arrival}</td></tr>"
                );
            }
        ?>   
    </tbody>
    </table>
    <?php endif; ?>
</body>
</html>