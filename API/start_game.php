<?php
header('Content-Type: application/json');
require 'db.php';

try {
    $pdo->beginTransaction();

    // 1. Δημιουργία νέου παιχνιδιού
    $pdo->query("INSERT INTO games (status, turn_player) VALUES ('active', 1)");
    $game_id = $pdo->lastInsertId();

    // 2. Δημιουργία Τράπουλας
    $suits = ['C', 'D', 'H', 'S']; // Clubs, Diamonds, Hearts, Spades
    $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    
    $deck = [];
    foreach ($suits as $suit) {
        foreach ($ranks as $rank) {
            // Υπολογισμός Πόντων
            $points = 0;
            // Κανόνας: Φιγούρες, 10, Άσσοι = 1 πόντος
            if (in_array($rank, ['A', 'K', 'Q', 'J', '10'])) {
                $points = 1;
            }
            // Κανόνας: 10 Καρό = +1 πόντος (σύνολο 2)
            if ($rank == '10' && $suit == 'D') $points = 2;
            // Κανόνας: 2 Σπαθί = +1 πόντος (σύνολο 1)
            if ($rank == '2' && $suit == 'C') $points = 1;

            $deck[] = ['s'=>$suit, 'r'=>$rank, 'pts'=>$points];
        }
    }

    shuffle($deck);

    // 3. Εισαγωγή και Μοίρασμα (6 στον P1, 6 στον P2, 4 Κάτω)
    $sql = "INSERT INTO game_cards (game_id, suit, rank, points, location, pile_order) VALUES (?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);

    foreach ($deck as $i => $c) {
        $loc = 'deck';
        $order = 0;

        if ($i < 6) $loc = 'p1_hand';
        elseif ($i < 12) $loc = 'p2_hand';
        elseif ($i < 16) {
            $loc = 'table';
            $order = $i - 11; // 1,2,3,4
        }
        
        $stmt->execute([$game_id, $c['s'], $c['r'], $c['pts'], $loc, $order]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'game_id' => $game_id, 'message' => 'Game initialized']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
?>