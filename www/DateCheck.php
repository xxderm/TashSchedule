<?php
require_once 'Database.php';

function dateCheck($date_str, $cour) {
    $db = Database::getInstance();
    $date = date('Y-m-d', strtotime($date_str));
    $sql = 'SELECT * 
            FROM schedule
            WHERE schedule.courier_id=:cour_id AND (:sel_date >= schedule.departure AND :sel_date <= schedule.arrival)';
    $date_res = $db->query($sql, [
        'cour_id'=> $cour,
        'sel_date' => $date,
    ]);    
    return count($date_res);
}