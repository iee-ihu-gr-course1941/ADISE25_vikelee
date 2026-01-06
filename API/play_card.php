<?php
ob_start();
header('Content-Type: application/json');
require 'db.php';
$response = [];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['game_id']) || empty($input['card_id'])) throw new Exception("Error");
    
    $gid = $input['game_id'];
    $cid = $input['card_id'];

    $pdo->beginTransaction();

    $game = $pdo->query("SELECT * FROM games WHERE id=$gid FOR UPDATE")->fetch();
    if (!$game || $game['status'] !== 'active') throw new Exception("Game inactive");

    $turn = $game['turn_player'];
    $hand_col = ($turn == 1) ? 'p1_hand' : 'p2_hand';
    $pile_col = ($turn == 1) ? 'p1_pile' : 'p2_pile';
    $score_col = ($turn == 1) ? 'p1_score' : 'p2_score';
    $xeri_col = ($turn == 1) ? 'p1_xeres' : 'p2_xeres';

    // Βρίσκουμε την κάρτα που παίζεται
    $stmt = $pdo->prepare("SELECT * FROM game_cards WHERE id=? AND game_id=? AND location=?");
    $stmt->execute([$cid, $gid, $hand_col]);
    $card = $stmt->fetch();
    if (!$card) throw new Exception("Card not found");

    // Βρίσκουμε τι υπάρχει στο τραπέζι
    $table_cards = $pdo->query("SELECT * FROM game_cards WHERE game_id=$gid AND location='table' ORDER BY pile_order ASC")->fetchAll();
    $top_card = end($table_cards);

    $collected = false;
    $is_xeri = false;
    $points = 0;
    
    // --- ΛΟΓΙΚΗ ΜΑΖΕΜΑΤΟΣ ---
    if ($top_card) {
        if ($card['rank'] === $top_card['rank'] || $card['rank'] === 'J') {
            $collected = true;
            if (count($table_cards) === 1 && $card['rank'] === $top_card['rank']) {
                $is_xeri = true;
            }
        }
    }

    // --- ΕΚΤΕΛΕΣΗ ΚΙΝΗΣΗΣ ---
    if ($collected) {
        $ids_to_collect = [$cid];
        foreach ($table_cards as $tc) {
            $ids_to_collect[] = $tc['id'];
            $points += $tc['points'];
        }
        $points += $card['points'];
        
        if ($is_xeri) {
            $points += 10;
            if ($card['rank'] === 'J') $points += 10;
            $pdo->prepare("UPDATE game_cards SET is_xeri=1 WHERE id=?")->execute([$cid]);
            $pdo->query("UPDATE games SET $xeri_col = $xeri_col + 1 WHERE id=$gid");
        }

        $in_str = str_repeat('?,', count($ids_to_collect) - 1) . '?';
        $sql = "UPDATE game_cards SET location=?, pile_order=0 WHERE id IN ($in_str)";
        $pdo->prepare($sql)->execute(array_merge([$pile_col], $ids_to_collect));

        $pdo->prepare("UPDATE games SET $score_col = $score_col + ? WHERE id=?")->execute([$points, $gid]);

        $code = $is_xeri ? "XERI" : "CAPTURE";
        $action_msg = $code . ":" . $card['rank'] . $card['suit']; 
        $pdo->prepare("UPDATE games SET last_action=? WHERE id=?")->execute([$action_msg, $gid]);

    } else {
        $new_order = count($table_cards) + 1;
        $pdo->prepare("UPDATE game_cards SET location='table', pile_order=? WHERE id=?")->execute([$new_order, $cid]);
        
        $action_msg = "PLAY:" . $card['rank'] . $card['suit'];
        $pdo->prepare("UPDATE games SET last_action=? WHERE id=?")->execute([$action_msg, $gid]);
    }

    // --- ΑΛΛΑΓΗ ΣΕΙΡΑΣ ---
    $next_turn = ($turn == 1) ? 2 : 1;
    $pdo->prepare("UPDATE games SET turn_player=? WHERE id=?")->execute([$next_turn, $gid]);

    // --- ΝΕΟ: ΕΛΕΓΧΟΣ ΓΙΑ ΞΑΝΑ-ΜΟΙΡΑΣΜΑ (RE-DEAL) ---
    // Μετράμε πόσα φύλλα έχουν μείνει στα χέρια των παικτών
    $cards_in_hands = $pdo->query("SELECT count(*) FROM game_cards WHERE game_id=$gid AND location IN ('p1_hand', 'p2_hand')")->fetchColumn();

    // Αν τα χέρια άδειασαν (είναι 0), πρέπει να μοιράσουμε από την τράπουλα (αν έχει φύλλα)
    if ($cards_in_hands == 0) {
        // Παίρνουμε τα επόμενα 12 φύλλα από την τράπουλα
        $deck_cards = $pdo->query("SELECT id FROM game_cards WHERE game_id=$gid AND location='deck' ORDER BY pile_order ASC LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);

        if ($deck_cards) {
            foreach ($deck_cards as $index => $card_id) {
                // Τα πρώτα 6 στον P1, τα επόμενα 6 στον P2
                $new_loc = ($index < 6) ? 'p1_hand' : 'p2_hand';
                $pdo->prepare("UPDATE game_cards SET location=? WHERE id=?")->execute([$new_loc, $card_id]);
            }
        }
    }

    // --- ΕΛΕΓΧΟΣ ΤΕΛΟΥΣ ΠΑΙΧΝΙΔΙΟΥ ---
    // Το παιχνίδι τελειώνει όταν δεν υπάρχουν φύλλα ούτε στην τράπουλα, ούτε στα χέρια
    $remaining = $pdo->query("SELECT count(*) FROM game_cards WHERE location IN ('deck','p1_hand','p2_hand') AND game_id=$gid")->fetchColumn();
    
    if ($remaining == 0) {
        // ΤΕΛΕΥΤΑΙΟ ΜΑΖΕΜΑ: Όποιος έκανε την τελευταία μπάζα, παίρνει ό,τι έμεινε κάτω
        // (Αυτή είναι μια λεπτομέρεια της Ξερής, αν θες την ενεργοποιούμε. Προς το παρόν απλά τελειώνει).
        $pdo->query("UPDATE games SET status='ended' WHERE id=$gid");
    }

    $pdo->commit();
    $response = ['status'=>'success'];

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $response = ['status'=>'error', 'error'=>$e->getMessage()];
}

if(ob_get_length()) ob_clean();
echo json_encode($response);
exit;
?>