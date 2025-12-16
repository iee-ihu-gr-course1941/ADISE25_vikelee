<?php
$host = 'localhost';
$db   = 'xeri_db';
$user = 'iee2021066';
$pass = 'Kelemao1!';
$port = '3307'; // <--- Εδώ ορίσαμε τη θύρα
$charset = 'utf8mb4';

// kaksdaklsdkasldk

// Προσθέσαμε το port=$port μέσα στο string σύνδεσης
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
    exit;
}
?>
