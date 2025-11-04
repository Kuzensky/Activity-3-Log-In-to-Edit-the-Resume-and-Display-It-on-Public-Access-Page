<?php
define('DB_SERVER', 'localhost');        
define('DB_PORT', '5432');                
define('DB_USERNAME', 'postgres');      
define('DB_PASSWORD', 'Syaako44');        
define('DB_NAME', 'nayre_login_db');      

try {
    
    $pdo = new PDO("pgsql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>
