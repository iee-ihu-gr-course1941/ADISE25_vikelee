<?php
header('Content-Type: application/json');
require 'db.php';

// Λήψη δεδομένων JSON
$input = json_decode(file_get_contents('php://input'), true);
$game_id = $input['game_id'] ?? null;
$card_id = $input['card_id'] ?? null;

if (!$game_id || !$card_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing game_id or card_id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Έλεγχος Σειράς
    $stmt = $pdo->prepare("SELECT turn_player, status FROM games WHERE id=?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();

    if ($game['status'] !== 'active') {
        echo json_encode(['error' => 'Game ended']); exit;
    }

    // 2. Πληροφορίες Κάρτας που παίζεται
    $stmt = $pdo->prepare("SELECT * FROM game_cards WHERE id=? AND game_id=?");
    $stmt->execute([$card_id, $game_id]);
    $played_card = $stmt->fetch();

    // Verification: Είναι όντως στο χέρι του παίκτη που έχει σειρά;
    $expected_loc = 'p' . $game['turn_player'] . '_hand';
    if (!$played_card || $played_card['location'] !== $expected_loc) {
        echo json_encode(['error' => 'Not your card or wrong turn']); exit;
    }

    // 3. Πληροφορίες Τραπεζιού (Πάνω φύλλο)
    $stmt = $pdo->prepare("SELECT * FROM game_cards WHERE game_id=? AND location='table' ORDER BY pile_order DESC LIMIT 1");
    $stmt->execute([$game_id]);
    $top_card = $stmt->fetch();

    // Μέτρημα φύλλων στο τραπέζι (για Ξερή)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE game_id=? AND location='table'");
    $stmt->execute([$game_id]);
    $table_count = $stmt->fetchColumn();

    // --- LOGIC ---
    $action = "drop";
    $xeri_points = 0;
    $message = "Card played";

    if ($top_card) {
        // Κανόνας: Ίδιο Rank ή Βαλές
        if ($played_card['rank'] == $top_card['rank'] || $played_card['rank'] == 'J') {
            $action = "collect";
            
            // Check Ξερή (μόνο αν δεν είναι Βαλές πάνω σε Βαλέ, συνήθως)
            // Απλοποιημένος κανόνας: Αν υπάρχει μόνο 1 φύλλο κάτω -> Ξερή
            if ($table_count == 1) {
                $xeri_points = ($played_card['rank'] == 'J') ? 20 : 10;
                $message = "XERI! ($xeri_points points)";
            } else {
                $message = "Collected!";
            }
        }
    }

    // --- EXECUTE ACTION ---
    if ($action == "collect") {
        $pile = 'p' . $game['turn_player'] . '_pile';
        
        // Μαζεύουμε το τραπέζι ΚΑΙ το φύλλο που ρίξαμε
        $sql = "UPDATE game_cards SET location=?, pile_order=0 WHERE game_id=? AND (location='table' OR id=?)";
        $pdo->prepare($sql)->execute([$pile, $game_id, $card_id]);

        // Ενημέρωση Last Collector
        $pdo->prepare("UPDATE games SET last_collector=? WHERE id=?")->execute([$game['turn_player'], $game_id]);

        // Πόντοι Ξερής
        if ($xeri_points > 0) {
            $score_col = 'p' . $game['turn_player'] . '_score';
            $pdo->prepare("UPDATE games SET $score_col = $score_col + ? WHERE id=?")->execute([$xeri_points, $game_id]);
        }
    } else {
        // Drop: Το φύλλο πάει στο τραπέζι
        $new_order = ($top_card['pile_order'] ?? 0) + 1;
        $pdo->prepare("UPDATE game_cards SET location='table', pile_order=? WHERE id=?")->execute([$new_order, $card_id]);
    }

    // 4. Αλλαγή Σειράς
    $next_player = ($game['turn_player'] == 1) ? 2 : 1;
    $pdo->prepare("UPDATE games SET turn_player=? WHERE id=?")->execute([$next_player, $game_id]);

    // 5. ΕΛΕΓΧΟΣ ΓΙΑ ΝΕΟ ΜΟΙΡΑΣΜΑ (Redeal)
    // Ελέγχουμε αν άδειασαν τα χέρια ΚΑΙ των δύο
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE game_id=? AND location IN ('p1_hand', 'p2_hand')");
    $stmt->execute([$game_id]);
    $hands_total = $stmt->fetchColumn();

    if ($hands_total == 0) {
        // Ελέγχουμε αν έχει φύλλα η τράπουλα
        $stmt = $pdo->prepare("SELECT id FROM game_cards WHERE game_id=? AND location='deck' LIMIT 12");
        $stmt->execute([$game_id]);
        $deck_cards = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($deck_cards) > 0) {
            // Μοίρασμα
            foreach ($deck_cards as $idx => $cid) {
                $dest = ($idx < 6) ? 'p1_hand' : 'p2_hand';
                $pdo->prepare("UPDATE game_cards SET location=? WHERE id=?")->execute([$dest, $cid]);
            }
            $message .= " (Redealed)";
        } else {
            // ΤΕΛΟΣ ΠΑΙΧΝΙΔΙΟΥ
            // Ό,τι έμεινε στο τραπέζι πάει στον last_collector
            $stmt = $pdo->prepare("SELECT last_collector FROM games WHERE id=?");
            $stmt->execute([$game_id]);
            $last_col = $stmt->fetchColumn();
            
            $final_pile = 'p' . $last_col . '_pile';
            $pdo->prepare("UPDATE game_cards SET location=? WHERE game_id=? AND location='table'")->execute([$final_pile, $game_id]);
            
            $pdo->prepare("UPDATE games SET status='ended' WHERE id=?")->execute([$game_id]);
            $message .= " (Game Over)";
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'action' => $action, 'message' => $message]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>