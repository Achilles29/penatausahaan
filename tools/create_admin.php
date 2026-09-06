<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$runtime = is_file($runtimePath) ? json_decode((string) file_get_contents($runtimePath), true) : array();
if (!is_array($runtime) || empty($runtime['database'])) throw new RuntimeException('Runtime configuration is unavailable.');

$username = trim((string) (getenv('PENATUS_ADMIN_USERNAME') ?: ''));
$name = trim((string) (getenv('PENATUS_ADMIN_NAME') ?: ''));
$password = (string) (getenv('PENATUS_ADMIN_PASSWORD') ?: '');
if (preg_match('/^[A-Za-z0-9._-]{4,50}$/D', $username) !== 1) throw new RuntimeException('Admin username must be 4–50 safe characters.');
if (mb_strlen($name) < 3 || mb_strlen($name) > 150) throw new RuntimeException('Admin display name must be 3–150 characters.');
if (strlen($password) < 12 || strlen($password) > 200) throw new RuntimeException('Admin password must be 12–200 characters.');

$db = $runtime['database'];
$pdo = new PDO(
	'mysql:host='.(string) $db['hostname'].';dbname='.(string) $db['database'].';charset=utf8mb4',
	(string) $db['username'],
	(string) $db['password'],
	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
);
$activeAdmins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND is_active = 1")->fetchColumn();
if ($activeAdmins > 0) throw new RuntimeException('An active superadmin already exists; use the authenticated user-management screen.');

$statement = $pdo->prepare(
	"INSERT INTO users (username, password, nama, role, is_active, created_at) "
	."VALUES (?, ?, ?, 'superadmin', 1, NOW())"
);
$statement->execute(array($username, password_hash($password, PASSWORD_DEFAULT), $name));
fwrite(STDOUT, 'Initial superadmin created without storing or displaying its password.'.PHP_EOL);
