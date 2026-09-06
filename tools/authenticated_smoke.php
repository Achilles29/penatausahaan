<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

$runtimePath = getenv('PENATUS_RUNTIME_CONFIG') ?: '/var/lib/penatausahaan-config/runtime.json';
$runtime = is_file($runtimePath) ? json_decode((string) file_get_contents($runtimePath), true) : array();
if (!is_array($runtime) || empty($runtime['database'])) throw new RuntimeException('Runtime configuration is unavailable.');
$db = $runtime['database'];
$pdo = new PDO(
	'mysql:host='.(string) $db['hostname'].';dbname='.(string) $db['database'].';charset=utf8mb4',
	(string) $db['username'],
	(string) $db['password'],
	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false)
);
$baseUrl = rtrim((string) ($runtime['application']['url'] ?? ''), '/');
$failures = 0;
$checks = 0;
$check = static function (bool $condition, string $message) use (&$failures, &$checks): void {
	$checks++;
	fwrite($condition ? STDOUT : STDERR, ($condition ? '[PASS] ' : '[FAIL] ').$message.PHP_EOL);
	if (!$condition) $failures++;
};
$request = static function (string $method, string $path, array $data, string $cookieJar) use ($baseUrl): array {
	$handle = curl_init($baseUrl.$path);
	$options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_CUSTOMREQUEST => $method,
		CURLOPT_COOKIEJAR => $cookieJar,
		CURLOPT_COOKIEFILE => $cookieJar,
	);
	if ($method === 'POST') $options[CURLOPT_POSTFIELDS] = http_build_query($data);
	curl_setopt_array($handle, $options);
	$response = (string) curl_exec($handle);
	$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
	$headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
	$error = curl_error($handle);
	curl_close($handle);
	return array($status, substr($response, 0, $headerSize), substr($response, $headerSize), $error);
};

$username = '__smoke_'.bin2hex(random_bytes(8));
$password = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
$newPassword = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
$cookieJar = tempnam(sys_get_temp_dir(), 'penatus-auth-smoke-');
$userId = 0;

try {
	$scope = $pdo->query(
		'SELECT own.id AS opd_id, other_pegawai.id AS other_pegawai_id '
		.'FROM master_opd own JOIN pegawai other_pegawai ON other_pegawai.opd_id <> own.id '
		.'WHERE own.is_active = 1 AND other_pegawai.is_active = 1 LIMIT 1'
	)->fetch(PDO::FETCH_ASSOC);
	if (!$scope) throw new RuntimeException('Two OPD scopes with employee data are required for the authenticated smoke test.');

	$insert = $pdo->prepare(
		"INSERT INTO users (username, password, nama, role, opd_id, is_active, created_at) "
		."VALUES (?, ?, 'Automated Smoke Test', 'admin_opd', ?, 1, NOW())"
	);
	$insert->execute(array($username, password_hash($password, PASSWORD_DEFAULT), (int) $scope['opd_id']));
	$userId = (int) $pdo->lastInsertId();

	[$status, , $loginBody, $error] = $request('GET', '/auth/login', array(), $cookieJar);
	preg_match('/<input type="hidden" name="([^"]+)"\s+value="([^"]+)"/m', $loginBody, $csrf);
	$check($error === '' && $status === 200 && isset($csrf[1], $csrf[2]), 'Authenticated smoke obtains login CSRF token');
	if (!isset($csrf[1], $csrf[2])) throw new RuntimeException('Login CSRF token is unavailable.');

	[$status, $headers] = $request('POST', '/auth/login', array(
		'identitas' => $username,
		'password' => $password,
		$csrf[1] => $csrf[2],
	), $cookieJar);
	$check(in_array($status, array(302, 303), true) && stripos($headers, 'location: '.$baseUrl.'/dashboard') !== false, 'Temporary test account can authenticate');

	[$status, , $dashboardBody] = $request('GET', '/dashboard', array(), $cookieJar);
	$check($status === 200 && strpos($dashboardBody, 'Namua Penatausahaan') !== false, 'Authenticated dashboard returns 200');

	[$status, , $accountBody] = $request('GET', '/account/password', array(), $cookieJar);
	preg_match('/<meta name="csrf-name" content="([^"]+)">/', $accountBody, $csrfName);
	preg_match('/<meta name="csrf-hash" content="([^"]+)">/', $accountBody, $csrfToken);
	$check($status === 200 && substr_count($accountBody, 'data-password-toggle=') >= 3, 'Password page exposes visibility controls');
	if (!isset($csrfName[1], $csrfToken[1])) throw new RuntimeException('Authenticated CSRF metadata is unavailable.');

	[$status, , $scopeBody] = $request('POST', '/gaji/hitung', array(
		'pegawai_id' => (int) $scope['other_pegawai_id'],
		'bulan' => 1,
		'tahun' => 2026,
		'is_ke' => 0,
		$csrfName[1] => $csrfToken[1],
	), $cookieJar);
	$scopeJson = json_decode($scopeBody, true);
	$check($status === 200 && is_array($scopeJson) && ($scopeJson['ok'] ?? 1) === 0, 'Cross-OPD payroll lookup is rejected');

	[$status] = $request('GET', '/rekap/detail/'.(int) $scope['other_pegawai_id'].'/2026/1/1', array(), $cookieJar);
	$check($status === 404, 'Cross-OPD payroll detail returns 404');

	[$status, $headers] = $request('POST', '/account/password', array(
		'current_password' => $password,
		'new_password' => $newPassword,
		'confirm_password' => $newPassword,
		$csrfName[1] => $csrfToken[1],
	), $cookieJar);
	$check(in_array($status, array(302, 303), true) && stripos($headers, 'location: '.$baseUrl.'/dashboard') !== false, 'Self-service password change succeeds');
	[$status] = $request('GET', '/dashboard', array(), $cookieJar);
	$check($status === 200, 'Current session remains valid after self-service password change');

	$pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute(array(password_hash($password, PASSWORD_DEFAULT), $userId));
	[$status, $headers] = $request('GET', '/dashboard', array(), $cookieJar);
	preg_match('/^Location:\s*([^\r\n]+)/mi', $headers, $location);
	$revoked = in_array($status, array(302, 303, 307), true) && isset($location[1]) && parse_url(trim($location[1]), PHP_URL_PATH) === '/auth/login';
	if (!$revoked) fwrite(STDERR, '[INFO] Revocation response HTTP '.$status.'; location='.(isset($location[1]) ? parse_url(trim($location[1]), PHP_URL_PATH) : 'none').PHP_EOL);
	$check($revoked, 'Administrative password reset revokes the previous session');
} finally {
	if ($userId > 0) $pdo->prepare('DELETE FROM users WHERE id = ?')->execute(array($userId));
	@unlink($cookieJar);
}

fwrite(STDOUT, "Checks: {$checks}; failures: {$failures}; temporary account removed.\n");
exit($failures === 0 ? 0 : 1);
