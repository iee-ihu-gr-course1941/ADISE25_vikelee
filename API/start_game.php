<?php
// start_game.php
header('Content-Type: application/json');
require 'db.php';

try {
    $pdo->beginTransaction();

    // 1. Δημιουργία Παιχνιδιού
    $pdo->query("INSERT INTO games (status, turn_player) VALUES ('active', 1)");
    $game_id = $pdo->lastInsertId();

    // 2. Δημιουργία Τράπουλας
    $suits = ['ΣΠΑΘΙ', 'ΚΑΡΟ', 'ΚΟΥΠΕΣ', 'ΜΠΑΣΤΟΥΝΙ']; 
    $ranks = ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K'];
    $deck = [];

    foreach ($suits as $suit) {
        foreach ($ranks as $index => $rank) {
            $value = $index + 1;
            
            // --- ΥΠΟΛΟΓΙΣΜΟΣ ΠΟΝΤΩΝ ---
            $points = 0;
            
            // Κανόνας 1: Φιγούρες & 10αρια & Άσσοι = 1 πόντος
            if (in_array($rank, ['A', 'K', 'Q', 'J', '10'])) {
                $points = 1;
            }

            // Κανόνας 2: Το 10 Καρό (D) παίρνει +1 πόντο (σύνολο 2)
            if ($rank == '10' && $suit == 'ΚΑΡΟ') {
                $points = 2;
            }

            // Κανόνας 3: Το 2 Σπαθί (C) παίρνει +1 πόντο (σύνολο 1)
            if ($rank == '2' && $suit == 'ΣΠΑΘΙ') {
                $points = 1;
            }

            $deck[] = ['suit'=>$suit, 'rank'=>$rank, 'val'=>$value, 'pts'=>$points];
        }
    }

    // 3. Ανακάτεμα
    shuffle($deck);

    // 4. Μοίρασμα (6 στον P1, 6 στον P2, 4 κάτω)
    $sql = "INSERT INTO game_cards (game_id, suit, rank, card_value, points, location, pile_order) VALUES (?,?,?,?,?,?,?)";
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

        $stmt->execute([$game_id, $c['suit'], $c['rank'], $c['val'], $c['pts'], $loc, $order]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'game_id' => $game_id, 'message' => 'New game started']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
?>