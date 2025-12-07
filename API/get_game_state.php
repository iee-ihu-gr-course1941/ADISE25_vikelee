<?php
// get_game_state.php
header('Content-Type: application/json');
require 'db.php';

$game_id = $_GET['game_id'] ?? null;

// Τα φύλλα μου (Παίκτης 1)
$stmt = $pdo->prepare("SELECT id, suit, rank FROM game_cards WHERE game_id = ? AND location = 'p1_hand'");
$stmt->execute([$game_id]);
$my_hand = $stmt->fetchAll();

// Φύλλα στο τραπέζι (όλα για να φαίνονται)
$stmt = $pdo->prepare("SELECT id, suit, rank FROM game_cards WHERE game_id = ? AND location = 'table' ORDER BY pile_order ASC");
$stmt->execute([$game_id]);
$table = $stmt->fetchAll();

// Πληροφορίες Παιχνιδιού (Σκορ, Σειρά)
$stmt = $pdo->prepare("SELECT turn_player, p1_score, p2_score FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$info = $stmt->fetch();

echo json_encode([
    'turn' => $info['turn_player'],
    'my_hand' => $my_hand,
    'table_cards' => $table,
    'scores' => ['p1_xeri' => $info['p1_score']]
]);
?>