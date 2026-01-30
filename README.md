# ADISE25_vikelee
🂡 Ξερή Online (Xeri Online)
Μια διαδικτυακή, multiplayer έκδοση του κλασικού ελληνικού παιχνιδιού τράπουλας "Ξερή". Η εφαρμογή επιτρέπει σε δύο παίκτες να συνδεθούν απομακρυσμένα και να παίξουν σε πραγματικό χρόνο.

📋 Χαρακτηριστικά
Multiplayer: Παιχνίδι 2 παικτών σε πραγματικό χρόνο.

Game Lobby: Σύστημα δημιουργίας δωματίου (Create Game) και σύνδεσης με κωδικό (Join Game).

Gameplay:

Αυτόματο μοίρασμα φύλλων.

Οπτική αναπαράσταση τραπεζιού και καρτών.

Έλεγχος κανόνων (μάζεμα με ίδια κάρτα, βαλέδες, υπολογισμός "Ξερής").

Αυτόματος υπολογισμός πόντων στο τέλος.

Animations: Εφέ κίνησης για το μάζεμα των φύλλων ("Ghost Card" effect).

Responsive Design: Προσαρμόζεται σε κινητά και υπολογιστές.

🛠️ Τεχνολογίες
Frontend: HTML5, CSS3, JavaScript (Vanilla JS - Fetch API).

Backend: PHP (Native).

Database: MySQL / MariaDB.

⚙️ Εγκατάσταση (Installation)
1. Απαιτήσεις
Για να τρέξει η εφαρμογή χρειάζεστε έναν Web Server με υποστήριξη PHP και MySQL (π.χ. XAMPP, WAMP, ή LAMP stack).

2. Ρύθμιση Βάσης Δεδομένων
Δημιουργήστε μια βάση δεδομένων με όνομα xeri_db.

Εκτελέστε το παρακάτω SQL script για να δημιουργήσετε τους πίνακες:

SQL
CREATE TABLE games (
    id INT(11) NOT NULL AUTO_INCREMENT,
    status ENUM('waiting','active','ended') NULL DEFAULT 'waiting',
    turn_player INT(11) NULL DEFAULT '1',
    p1_token VARCHAR(64) NULL DEFAULT NULL,
    p2_token VARCHAR(64) NULL DEFAULT NULL,
    p1_score INT(11) NULL DEFAULT '0',
    p2_score INT(11) NULL DEFAULT '0',
    p1_xeres INT(11) NULL DEFAULT '0',
    p2_xeres INT(11) NULL DEFAULT '0',
    last_collector INT(11) NULL DEFAULT '0',
    last_action VARCHAR(255) NULL DEFAULT '',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE game_cards (
    id INT(11) NOT NULL AUTO_INCREMENT,
    game_id INT(11) NOT NULL,
    suit VARCHAR(10) NULL DEFAULT NULL,
    rank VARCHAR(10) NULL DEFAULT NULL,
    points INT(11) NULL DEFAULT '0',
    location VARCHAR(20) NULL DEFAULT NULL,
    pile_order INT(11) NULL DEFAULT '0',
    is_xeri TINYINT(1) NULL DEFAULT '0',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE players (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    token VARCHAR(64) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

3. Ρύθμιση Σύνδεσης (db.php)
Ανοίξτε το αρχείο API/db.php (ή db.php) και επεξεργαστείτε τα στοιχεία σύνδεσης ώστε να ταιριάζουν με το δικό σας σύστημα:

$host = 'localhost';
$db   = 'xeri_db';
$user = 'iee2021066';        // Το username της βάσης σας
$pass = 'Kelemao1!';            // Το password της βάσης σας
$port = '3307';        // Συνήθως 3306 (ή 3309 ανάλογα το setup)

4. Εκτέλεση
Τοποθετήστε τα αρχεία στον φάκελο htdocs (για XAMPP) ή www και ανοίξτε τον browser στη διεύθυνση: http://localhost/xeri/index.html

📂 Δομή Αρχείων
index.html: Η κεντρική σελίδα του παιχνιδιού.

style.css: Τα στυλ και οι κλάσεις για την εμφάνιση (UI).

script.js: Η λογική του παιχνιδιού (χειρισμός DOM, κλήσεις API, polling).

cards/: Φάκελος που περιέχει τις εικόνες των τραπουλόχαρτων (π.χ. king_of_hearts.png).

API/ (Backend αρχεία PHP):

db.php: Σύνδεση με τη βάση.

login.php: Εγγραφή/Είσοδος χρήστη (δημιουργία session token).

start_game.php: Δημιουργία νέου τραπεζιού, ανακάτεμα τράπουλας και μοίρασμα.

join_game.php: Είσοδος δεύτερου παίκτη σε υπάρχον τραπέζι.

game_status.php: Επιστρέφει την τρέχουσα κατάσταση του παιχνιδιού (χέρια, τραπέζι, σκορ).

play_card.php: Εκτέλεση κίνησης, έλεγχος για μπάζα/ξερή και ενημέρωση σκορ.

🎮 Πώς παίζεται
Παίκτης 1: Μπαίνει, δίνει όνομα, πατάει "Είσοδος" και μετά "Δημιουργία Νέου". Αντιγράφει το Game ID.

Παίκτης 2: Μπαίνει, δίνει όνομα, πατάει "Είσοδος", βάζει το Game ID στο πεδίο και πατάει "Σύνδεση".

Το παιχνίδι ξεκινάει αυτόματα.

Κάντε κλικ σε μια κάρτα για να την ρίξετε.

Το παιχνίδι τελειώνει όταν τελειώσουν όλα τα φύλλα της τράπουλας.

Author: iee2021194, iee2021066