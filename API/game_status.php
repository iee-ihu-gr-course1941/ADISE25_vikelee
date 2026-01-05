<?php
ob_start();
header('Content-Type: application/json');
require 'db.php';
$response = [];

try {
    $gid = $_GET['game_id'] ?? null;
    if (!$gid) throw new Exception("No ID");

    $game = $pdo->query("SELECT * FROM games WHERE id=$gid")->fetch();
    if (!$game) throw new Exception("Game not found");

    $cards = $pdo->query("SELECT * FROM game_cards WHERE game_id=$gid ORDER BY pile_order ASC")->fetchAll();

    $p1_hand = []; 
    $p2_hand_count = 0;
    $table_cards = [];
    
    // Αρχικοί πόντοι από τα bonus Ξερής (που αποθηκεύονται στο games table)
    $p1_score = $game['p1_score'];
    $p2_score = $game['p2_score'];
    
    $p1_captured = 0;
    $p2_captured = 0;
    $p1_xeri_cards = [];
    $p2_xeri_cards = [];

    foreach ($cards as $c) {
        if ($c['location'] === 'p1_hand') $p1_hand[] = $c;
        elseif ($c['location'] === 'p2_hand') $p2_hand_count++;
        elseif ($c['location'] === 'table') $table_cards[] = $c;
        
        // P1 PILE
        elseif ($c['location'] === 'p1_pile') {
            $p1_score += $c['points']; // Προσθέτουμε πόντους κάρτας
            $p1_captured++;
            if ($c['is_xeri']) $p1_xeri_cards[] = $c;
        }
        // P2 PILE
        elseif ($c['location'] === 'p2_pile') {
            $p2_score += $c['points'];
            $p2_captured++;
            if ($c['is_xeri']) $p2_xeri_cards[] = $c;
        }
    }

    $response = [
        'status' => 'success',
        'turn' => $game['turn_player'],
        'table_cards' => $table_cards,
        'p1_hand' => $p1_hand,
        'p2_hand_count' => $p2_hand_count,
        'scores' => [
            'p1' => $p1_score, 'p2' => $p2_score,
            'p1_xeres' => $game['p1_xeres'], 'p2_xeres' => $game['p2_xeres']
        ],
        'captured' => ['p1' => $p1_captured, 'p2' => $p2_captured],
        'xeres_cards' => ['p1' => $p1_xeri_cards, 'p2' => $p2_xeri_cards]
    ];
} catch (Exception $e) {
    $response = ['status'=>'error', 'error'=>$e->getMessage()];
}

if(ob_get_length()) ob_clean();
echo json_encode($response);
exit;