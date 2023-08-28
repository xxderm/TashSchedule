<?php
require_once 'Database.php';
require_once 'DateCheck.php';

$reg = $_REQUEST["reg"];
$depart = $_REQUEST["depart"];
$cour = $_REQUEST["cour"];

$arrival_date = "";

if (strlen($depart) > 0 & strlen($reg) > 0  && strlen($depart) > 0) {
    $db = Database::getInstance();

    $sql = 'SELECT travel_time 
    FROM region
    WHERE title=:reg_title';
    $travel_time = $db->query($sql, [
        'reg_title' => $reg
    ]);
    $days = $travel_time[0]['travel_time'];

    # Проверка даты отправки
    $date = date('Y-m-d', strtotime($depart));
    if (dateCheck($date, $cour) > 0) {
        echo 'В этот день курьер занят';     
        die();
    }

    # Дата прибытия
    $arrival_date = date('d.m.Y', strtotime($depart . "+{$days} days"));   

    # Проверка даты прибытия
    $check_arriv = date('Y-m-d', strtotime($arrival_date));
    if (dateCheck($check_arriv, $cour) > 0) {
        echo 'В этот день курьер занят';     
        die();
    }
}

echo $arrival_date;