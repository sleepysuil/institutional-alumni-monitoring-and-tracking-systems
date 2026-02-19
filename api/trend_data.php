<?php
header('Content-Type: application/json');
echo json_encode([
    ['year'=>2020,'rate'=>100],
    ['year'=>2021,'rate'=>100],
    ['year'=>2022,'rate'=>100],
    ['year'=>2023,'rate'=>50],
    ['year'=>2024,'rate'=>0],
]);
?>