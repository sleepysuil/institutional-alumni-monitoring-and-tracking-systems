<?php
header('Content-Type: application/json');
echo json_encode([
    ['program'=>'BSIS','industry'=>'IT','support'=>33.3,'confidence'=>100,'lift'=>1.5],
]);
?>