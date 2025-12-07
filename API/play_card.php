<?php
// play_card.php
header('Content-Type: application/json');
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$game_id = $input['game_id'] ?? null;
$card_id = $input['card_id'] ?? null;

if (!$game_id || !$card_id) {
    echo json_encode(['error' => 'Missing game_id or card_id']);
    exit;
}

// 1. Βρες το φύλλο που παίζεται
$stmt = $pdo->prepare("SELECT * FROM game_cards WHERE id = ?");
$stmt->execute([$card_id]);
$played_card = $stmt->fetch();

// 2. Βρες το πάνω-πάνω φύλλο στο τραπέζι
$stmt = $pdo->prepare("SELECT * FROM game_cards WHERE game_id = ? AND location = 'table' ORDER BY pile_order DESC LIMIT 1");
$stmt->execute([$game_id]);
$top_card = $stmt->fetch();

// 3. Μέτρα πόσα φύλλα έχει το τραπέζι (για έλεγχο ξερής)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE game_id = ? AND location = 'table'");
$stmt->execute([$game_id]);
$table_count = $stmt->fetchColumn();

$action = "drop"; // 'drop' ή 'collect'
$message = "";
$xeri_points = 0;

// ΛΟΓΙΚΗ ΠΑΙΧΝΙΔΙΟΥ
if ($top_card) {
    // Αν ταιριάζει ο αριθμός ή αν ρίξεις Βαλέ
    if ($played_card['rank'] == $top_card['rank'] || $played_card['rank'] == 'J') {
        $action = "collect";
        
        // Έλεγχος για ΞΕΡΗ
        if ($table_count == 1) {
            // Αν ρίξεις Βαλέ για να κάνεις ξερή -> 20 πόντοι
            if ($played_card['rank'] == 'J') {
                $xeri_points = 20;
                $message = "ΞΕΡΗ με Βαλέ! (20 πόντοι)";
            } else {
                // Απλή ξερή -> 10 πόντοι
                $xeri_points = 10;
                $message = "ΞΕΡΗ! (10 πόντοι)";
            }
        } else {
            $message = "Μάζεψες τα φύλλα!";
        }
    } else {
        $message = "Το φύλλο παίχτηκε.";
    }
} else {
    $message = "Το φύλλο παίχτηκε σε άδειο τραπέζι.";
}

// ΕΚΤΕΛΕΣΗ ΑΛΛΑΓΩΝ ΣΤΗ ΒΑΣΗ
if ($action == "collect") {
    // 1. Όλα τα φύλλα του τραπεζιού + το δικό σου πάνε στη στοίβα του P1
    $sql = "UPDATE game_cards SET location = 'p1_pile', pile_order = 0 WHERE game_id = ? AND (location = 'table' OR id = ?)";
    $pdo->prepare($sql)->execute([$game_id, $card_id]);

    // 2. Αν έγινε ξερή, ενημέρωσε το σκορ
    if ($xeri_points > 0) {
        $pdo->prepare("UPDATE games SET p1_score = p1_score + ? WHERE id = ?")->execute([$xeri_points, $game_id]);
    }
} else {
    // Το φύλλο πάει στο τραπέζι πάνω από τα άλλα
    $new_order = ($top_card['pile_order'] ?? 0) + 1;
    $pdo->prepare("UPDATE game_cards SET location = 'table', pile_order = ? WHERE id = ?")->execute([$new_order, $card_id]);
}

// Αλλαγή σειράς (Από 1 σε 2, από 2 σε 1)
$pdo->prepare("UPDATE games SET turn_player = IF(turn_player=1, 2, 1) WHERE id = ?")->execute([$game_id]);

echo json_encode([
    'status' => 'success',
    'action_type' => $action,
    'message' => $message,
    'xeri_points' => $xeri_points
]);
?>