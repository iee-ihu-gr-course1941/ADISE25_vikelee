# ADISE25_vikelee
Ξερή Online
Μια διαδικτυακή, multiplayer έκδοση της ξερής. Η εφαρμογή επιτρέπει σε δύο παίκτες να συνδεθούν απομακρυσμένα και να παίξουν σε πραγματικό χρόνο.

Χαρακτηριστικά
Multiplayer: Παιχνίδι 2 παικτών σε πραγματικό χρόνο.

Game Lobby: Σύστημα δημιουργίας δωματίου και σύνδεσης με κωδικό.

Gameplay:

Αυτόματο μοίρασμα φύλλων.

Οπτική αναπαράσταση τραπεζιού και καρτών.

Έλεγχος κανόνων ,μάζεμα με ίδια κάρτα, βαλέδες, υπολογισμός Ξερής.

Αυτόματος υπολογισμός πόντων στο τέλος.

Animations: Εφέ κίνησης για το μάζεμα των φύλλων, το λεγόμενο ghost card effect.

Responsive Design: Προσαρμόζεται σε κινητά και υπολογιστές.

Τεχνολογίες
Frontend: HTML, CSS, JavaScript.

Backend: PHP.

Database: MySQL / MariaDB.

Εγκατάσταση (Installation)
1. Απαιτήσεις
Για να τρέξει η εφαρμογή χρειάζεται το XAMPP.

2. Ρύθμιση Βάσης Δεδομένων
Δημιουργήστε μια βάση δεδομένων με όνομα xeri_db.

Εκτελέστε το παρακάτω SQL script για να δημιουργήσετε τους πίνακες:
```
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
```


3. Ρύθμιση Σύνδεσης (db.php)
Ανοίξτε το αρχείο API/db.php (ή db.php) και επεξεργαστείτε τα στοιχεία σύνδεσης ώστε να ταιριάζουν με το δικό σας σύστημα:
```
$host = 'localhost';
$db   = 'xeri_db';
$user = 'iee2021066';        
$pass = 'Kelemao1!';            
$port = '3307';
```    

4. Εκτέλεση
Τοποθετήστε τα αρχεία στον φάκελο htdocs και ανοίξτε τον browser στη διεύθυνση: http://localhost/xeri/index.html
ή αλλιώς συνδεθείτε απευθείας στο https://users.iee.ihu.gr/~iee2021066/xeri/index.html

Δομή Αρχείων
```
index.html: Η κεντρική σελίδα του παιχνιδιού.

style.css: Τα στυλ και οι κλάσεις για την εμφάνιση.

script.js: Η λογική του παιχνιδιού.

cards/: Φάκελος που περιέχει τις εικόνες των τραπουλόχαρτων.

API/ Backend αρχεία PHP:

db.php: Σύνδεση με τη βάση.

login.php: Εγγραφή/Είσοδος χρήστη.

start_game.php: Δημιουργία νέου τραπεζιού, ανακάτεμα τράπουλας και μοίρασμα.

join_game.php: Είσοδος δεύτερου παίκτη σε υπάρχον τραπέζι.

game_status.php: Επιστρέφει την τρέχουσα κατάσταση του παιχνιδιού.

play_card.php: Εκτέλεση κίνησης, έλεγχος για ξερή και ενημέρωση σκορ.
```

Πώς παίζεται
Παίκτης 1: Μπαίνει, δίνει όνομα, πατάει Είσοδος και μετά Δημιουργία Νέου. Αντιγράφει το Game ID για να το δώσει στον Παίκτη 2.

Παίκτης 2: Μπαίνει, δίνει όνομα, πατάει Είσοδος, βάζει το Game ID στο πεδίο και πατάει Σύνδεση.

Το παιχνίδι ξεκινάει αυτόματα.

Κάντε κλικ σε μια κάρτα για να την ρίξετε.

Το παιχνίδι τελειώνει όταν τελειώσουν όλα τα φύλλα της τράπουλας.

Author: iee2021194, iee2021066
