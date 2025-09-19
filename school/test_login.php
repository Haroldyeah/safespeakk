<?php
$host = 'localhost';
$dbname = 'capstone_system'; // replace with your DB name
$user = 'root';              // default for WAMP
$pass = '';                  // default is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to DB<br>";
} catch (PDOException $e) {
    die("❌ DB Connection failed: " . $e->getMessage());
}

$usernameInput = 'admin';
$passwordInput = 'i_love_cebu_philippines123'; // test password

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute([$usernameInput, $usernameInput]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ User found: {$user['username']}<br>";
    echo "Stored hash: {$user['password']}<br>";
    echo "Input password: {$passwordInput}<br>";

    if (password_verify($passwordInput, $user['password'])) {
        echo "<strong style='color:green'>✅ Password is correct</strong>";
    } else {
        echo "<strong style='color:red'>❌ Password does NOT match</strong>";
    }
} else {
    echo "❌ User not found.";
}
