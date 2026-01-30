<?php

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

require 'db.php';

$gid = $_GET['game_id'] ?? null;
if (!$gid) { echo json_encode(['status'=>'error', 'message'=>'No ID']); exit; }

$game = $pdo->query("SELECT * FROM games WHERE id=$gid")->fetch(PDO::FETCH_ASSOC);
if (!$game) { echo json_encode(['status'=>'error', 'message'=>'Game not found']); exit; }

$cards = $pdo->query("SELECT * FROM game_cards WHERE game_id=$gid")->fetchAll(PDO::FETCH_ASSOC);

$p1_hand = []; $p2_hand = []; $table = [];
$p1_pile = 0; $p2_pile = 0;
$p1_xeres = []; $p2_xeres = [];

foreach ($cards as $c) {
    if ($c['location'] === 'p1_hand') $p1_hand[] = $c;
    elseif ($c['location'] === 'p2_hand') $p2_hand[] = $c;
    elseif ($c['location'] === 'table') $table[] = $c;
    elseif ($c['location'] === 'p1_pile') { 
        $p1_pile++;
        if ($c['is_xeri']) $p1_xeres[] = $c;
    }
    elseif ($c['location'] === 'p2_pile') { 
        $p2_pile++;
        if ($c['is_xeri']) $p2_xeres[] = $c;
    }
}


usort($table, function($a, $b) { return $a['pile_order'] - $b['pile_order']; });

$response = [
    'status' => 'success',
    'game_status' => $game['status'],
    'turn' => (int)$game['turn_player'],
    'last_action' => $game['last_action'],
    
   
    'p1_hand' => $p1_hand,
    'p2_hand' => $p2_hand,
    'table_cards' => $table,
    
    'captured' => ['p1' => $p1_pile, 'p2' => $p2_pile],
    'xeres_cards' => ['p1' => $p1_xeres, 'p2' => $p2_xeres],
  
    'scores' => [
        'p1' => (int)$game['p1_score'],
        'p2' => (int)$game['p2_score'],
        'p1_xeres' => (int)$game['p1_xeres'],
        'p2_xeres' => (int)$game['p2_xeres']
    ]
];

echo json_encode($response);
exit;
?>