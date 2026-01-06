<?php
ob_start();
header('Content-Type: application/json');
require 'db.php';
$response = [];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['game_id'])) throw new Exception("Παρακαλώ εισάγετε Game ID");
    $gid = $input['game_id'];
    
    // 1. Ψάχνουμε το παιχνίδι
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id=?");
    $stmt->execute([$gid]);
    $game = $stmt->fetch();

    // ΕΛΕΓΧΟΣ: Υπάρχει το παιχνίδι;
    if (!$game) {
        throw new Exception("Λάθος ID: Το παιχνίδι δεν βρέθηκε!");
    }

    // ΕΛΕΓΧΟΣ: Είναι σε αναμονή;
    if ($game['status'] !== 'waiting') {
        throw new Exception("Δεν μπορείς να μπεις (Το παιχνίδι τρέχει ή έχει τελειώσει)");
    }

    // ΕΛΕΓΧΟΣ: Μήπως είναι γεμάτο;
    if ($game['p2_token']) {
        throw new Exception("Το παιχνίδι είναι γεμάτο!");
    }

    // Όλα καλά -> Βάζουμε τον P2
    $p2_token = bin2hex(random_bytes(16));
    $pdo->prepare("UPDATE games SET p2_token=?, status='active' WHERE id=?")->execute([$p2_token, $gid]);
    
    $response = ['status'=>'success', 'game_id'=>$gid, 'token'=>$p2_token];

} catch (Exception $e) {
    // Εδώ επιστρέφουμε το λάθος στο JS
    $response = ['status'=>'error', 'error'=>$e->getMessage()];
}

if(ob_get_length()) ob_clean();
echo json_encode($response);
exit;
?>