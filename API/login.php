<?php
ob_start();
header('Content-Type: application/json');
require 'db.php';

$response = [];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['username'])) throw new Exception("No username");
    $username = trim($input['username']);

    $stmt = $pdo->prepare("SELECT token FROM players WHERE username = ?");
    $stmt->execute([$username]);
    $exists = $stmt->fetch();

    if ($exists) {
        $token = $exists['token'];
    } else {
        $token = bin2hex(random_bytes(16));
        $pdo->prepare("INSERT INTO players (username, token) VALUES (?, ?)")->execute([$username, $token]);
    }
    $response = ['status'=>'success', 'username'=>$username, 'token'=>$token];

} catch (Exception $e) {
    $response = ['status'=>'error', 'error'=>$e->getMessage()];
}

if(ob_get_length()) ob_clean();
echo json_encode($response);
exit;