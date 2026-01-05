<?php
ob_start();
header('Content-Type: application/json');
require 'db.php';
$response = [];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['game_id'])) throw new Exception("No Game ID");
    $gid = $input['game_id'];
    
    $game = $pdo->query("SELECT * FROM games WHERE id=$gid")->fetch();
    if (!$game) throw new Exception("Game not found");
    if ($game['p2_token']) throw new Exception("Game full");

    $p2_token = bin2hex(random_bytes(16));
    $pdo->prepare("UPDATE games SET p2_token=? WHERE id=?")->execute([$p2_token, $gid]);
    $response = ['status'=>'success', 'game_id'=>$gid, 'token'=>$p2_token];
} catch (Exception $e) {
    $response = ['status'=>'error', 'error'=>$e->getMessage()];
}

if(ob_get_length()) ob_clean();
echo json_encode($response);
exit;