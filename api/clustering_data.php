<?php
header('Content-Type: application/json');
echo json_encode([
    ['cluster'=>'Recent','points'=>[['x'=>2022,'y'=>150000],['x'=>2023,'y'=>160000]]],
    ['cluster'=>'Experienced','points'=>[['x'=>2020,'y'=>380000],['x'=>2021,'y'=>420000]]],
]);
?>