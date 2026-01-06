<?php
ob_start();
require 'db.php';
$response = [];

try {
    $pdo->beginTransaction();

    $p1_token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("INSERT INTO games (status, turn_player, p1_token) VALUES ('waiting', 1, ?)");
    $stmt->execute([$p1_token]);
    $game_id = $pdo->lastInsertId();

    $suits = ['C', 'D', 'H', 'S'];
    $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    $deck = [];
    foreach ($suits as $s) {
        foreach ($ranks as $r) {
            $pts = 0;
            if (in_array($r, ['A','K','Q','J','10'])) $pts = 1;
            if ($r=='10' && $s=='D') $pts=2;
            if ($r=='2' && $s=='C') $pts=1;
            $deck[] = ['s'=>$s, 'r'=>$r, 'pts'=>$pts];
        }
    }
    shuffle($deck);

    $sql = "INSERT INTO game_cards (game_id, suit, rank, points, location, pile_order) VALUES (?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    foreach ($deck as $i => $c) {
        $loc = 'deck'; $order = 0;
        if ($i < 6) $loc = 'p1_hand';
        elseif ($i < 12) $loc = 'p2_hand';
        elseif ($i < 16) { $loc = 'table'; $order = $i-11; }
        $stmt->execute([$game_id, $c['s'], $c['r'], $c['pts'], $loc, $order]);
    }

    $pdo->commit();
    $response = ['status'=>'success', 'game_id'=>$game_id, 'token'=>$p1_token];
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response = ['status'=>'error', 'error'=>$e->getMessage()];
}

if(ob_get_length()) ob_clean();
header('Content-Type: application/json');
echo json_encode($response);
exit;