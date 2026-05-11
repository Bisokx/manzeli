<?php
require_once 'includes/db.php';
$hash = password_hash('Admin.123', PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET email = 'admin@manzeli.com', password = ? WHERE role = 'admin'")->execute([$hash]);
echo "Admin updated! Delete this file now.";
?>
