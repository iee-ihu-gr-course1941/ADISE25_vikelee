<?php

$db      = 'xeri_db';
$user    = 'iee2021066';
$pass    = 'Kelemao1!';
$charset = 'utf8mb4';
$socketPath = '/home/student/iee/2021/iee2021066/mysql/run/mysql.sock';


if (!file_exists($socketPath)) {
    echo json_encode([
        'error' => 'Socket file not found.',
        'hint' => 'Check if your MySQL process is running. Try running "mysql_start" in the terminal.'
    ]);
    exit;
}


$dsn = "mysql:unix_socket=$socketPath;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
   
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    

} catch (\PDOException $e) {
    
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Connection failed',
        'message' => $e->getMessage(),
        'code' => (int)$e->getCode()
    ]);
    exit;
}

