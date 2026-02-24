<?php
$host = 'db';
$dbname = 'gaming_store';
$username = 'user';
$password = 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('<div style="color:#ef4444;padding:2rem;font-family:Inter,system-ui,sans-serif;background:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;"><div style="background:#1e293b;padding:2rem;border-radius:1rem;border:1px solid #334155;max-width:500px;"><h2 style="margin:0 0 1rem;">⚠️ Database Connection Failed</h2><p>' . htmlspecialchars($e->getMessage()) . '</p><p style="color:#94a3b8;margin-top:1rem;">Make sure Docker containers are running and schema.sql has been imported.</p></div></div>');
}
?>
