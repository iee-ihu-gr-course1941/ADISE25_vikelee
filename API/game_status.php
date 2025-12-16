<?php
header('Content-Type: application/json');
require 'db.php';

$game_id = $_GET['game_id'] ?? null;
if (!$game_id) { echo json_encode([]); exit; }

// Δεδομένα Παιχνιδιού
$stmt = $pdo->prepare("SELECT * FROM games WHERE id=?");
$stmt->execute([$game_id]);
$game = $stmt->fetch();

// Φύλλα P1
$stmt = $pdo->prepare("SELECT id, suit, rank FROM game_cards WHERE game_id=? AND location='p1_hand'");
$stmt->execute([$game_id]);
$p1_hand = $stmt->fetchAll();

// Φύλλα P2 (Αν θες να τεστάρεις μόνος σου, αλλιώς κρύψ' τα)
$stmt = $pdo->prepare("SELECT id, suit, rank FROM game_cards WHERE game_id=? AND location='p2_hand'");
$stmt->execute([$game_id]);
$p2_hand = $stmt->fetchAll();

// Τραπέζι (μόνο το πάνω φύλλο ή όλα)
$stmt = $pdo->prepare("SELECT id, suit, rank FROM game_cards WHERE game_id=? AND location='table' ORDER BY pile_order ASC");
$stmt->execute([$game_id]);
$table = $stmt->fetchAll();

echo json_encode([
    'game' => $game,
    'table' => $table,
    'p1_hand' => $p1_hand,
    'p2_hand' => $p2_hand
], JSON_PRETTY_PRINT);
?>