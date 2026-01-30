<?php
// Configuration
$db      = 'xeri_db';
$user    = 'iee2021066';
$pass    = 'Kelemao1!';
$charset = 'utf8mb4';
$socketPath = '/home/student/iee/2021/iee2021066/mysql/run/mysql.sock';

// 1. Pre-connection check: Ensure the socket file actually exists
if (!file_exists($socketPath)) {
    echo json_encode([
        'error' => 'Socket file not found.',
        'hint' => 'Check if your MySQL process is running. Try running "mysql_start" in the terminal.'
    ]);
    exit;
}

// 2. Define the DSN for Unix Socket
// Note: We omit 'host' and 'port' because unix_socket handles the connection locally.
$dsn = "mysql:unix_socket=$socketPath;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 3. Establish the connection
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Optional: Success message (remove for production)
    // echo json_encode(['status' => 'Connected successfully']);

} catch (\PDOException $e) {
    // 4. Detailed error reporting
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Connection failed',
        'message' => $e->getMessage(),
        'code' => (int)$e->getCode()
    ]);
    exit;
}

// Your database logic starts here...