<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This command can only run from CLI.\n");
	exit(1);
}

$baseUrl = rtrim(getenv('APP_URL') ?: 'https://efin.namuaprojects.com', '/');
$failures = 0;
$checks = 0;

$check = static function (bool $condition, string $message) use (&$failures, &$checks): void {
	$checks++;
	fwrite($condition ? STDOUT : STDERR, ($condition ? '[PASS] ' : '[FAIL] ').$message.PHP_EOL);
	if (!$condition) $failures++;
};

$request = static function (string $method, string $path, array $data = array(), ?string $cookieJar = null): array {
	global $baseUrl;
	$handle = curl_init($baseUrl.$path);
	$options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => true,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_TIMEOUT => 15,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_CUSTOMREQUEST => $method,
	);
	if ($cookieJar !== null) {
		$options[CURLOPT_COOKIEJAR] = $cookieJar;
		$options[CURLOPT_COOKIEFILE] = $cookieJar;
	}
	if ($method === 'POST') $options[CURLOPT_POSTFIELDS] = http_build_query($data);
	curl_setopt_array($handle, $options);
	$response = (string) curl_exec($handle);
	$status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
	$headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
	$error = curl_error($handle);
	curl_close($handle);
	return array($status, substr($response, 0, $headerSize), substr($response, $headerSize), $error);
};

list($status, $loginHeaders, $body, $error) = $request('GET', '/auth/login');
$check($error === '' && $status === 200, 'Login page returns 200');
$check(strpos($body, 'data-password-toggle="loginPassword"') !== false, 'Login has accessible password visibility control');
$check(stripos($loginHeaders, 'strict-transport-security:') !== false && stripos($loginHeaders, 'x-frame-options: DENY') !== false, 'HTTPS response includes transport and framing protection');
$check(stripos($loginHeaders, 'cache-control: no-store') !== false, 'Authentication response disables caching');
$check(preg_match('/^X-Request-ID:\s*[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\s*$/im', $loginHeaders) === 1, 'Response exposes a valid correlation request ID');
$check(preg_match('/^Set-Cookie:.*Secure;.*HttpOnly;.*SameSite=Lax/im', $loginHeaders) === 1, 'Security cookies use Secure, HttpOnly, and SameSite=Lax');

list($status) = $request('GET', '/dashboard');
$check(in_array($status, array(302, 303, 307), true), 'Anonymous dashboard request redirects to login');

list($status, $healthHeaders, $body) = $request('GET', '/health');
$health = json_decode($body, true);
$check($status === 200 && ($health['product'] ?? '') === 'NAMUA_PENATAUSAHAAN', 'Health endpoint identifies the product');
$check(preg_match('/^X-Request-ID:\s*[a-f0-9-]{36}\s*$/im', $healthHeaders) === 1, 'Health endpoint returns a correlation request ID');

list($status) = $request('POST', '/auth/login', array('identitas' => 'csrf-without-token', 'password' => 'not-a-password'));
$check($status === 403, 'POST without CSRF token is rejected');

$cookieJar = tempnam(sys_get_temp_dir(), 'penatus-smoke-cookie-');
list($status, , $body) = $request('GET', '/auth/login', array(), $cookieJar);
preg_match('/<input type="hidden" name="([^"]+)"\s+value="([^"]+)"/m', $body, $token);
$check($status === 200 && isset($token[1], $token[2]), 'Login issues a CSRF token');
if (isset($token[1], $token[2])) {
	list($status, $headers) = $request('POST', '/auth/login', array(
		'identitas' => '__smoke_'.bin2hex(random_bytes(6)),
		'password' => 'invalid-smoke-password',
		$token[1] => $token[2],
	), $cookieJar);
	$check(in_array($status, array(302, 303), true) && stripos($headers, 'location: '.$baseUrl.'/auth/login') !== false, 'POST with valid CSRF reaches authentication safely');
}
@unlink($cookieJar);

list($status) = $request('GET', '/auth/logout');
$check($status === 405, 'Logout rejects GET');

foreach (array('/assets/vendor/bootstrap/bootstrap.min.css', '/assets/css/app.css', '/assets/img/namua-projects.svg', '/favicon.ico') as $path) {
	list($status) = $request('GET', $path);
	$check($status === 200, 'Static asset loads: '.$path);
}

foreach (array('/penatus.sql', '/db_backup/penatus.sql', '/application/config/database.php', '/database/schema/20260906_baseline.sql', '/database/README.md', '/deploy/nginx-ci3.conf', '/docs/ROADMAP.md', '/tools/http_smoke.php', '/app-manifest.json', '/composer.json', '/INSTALL.md', '/setup', '/welcome') as $path) {
	list($status) = $request('GET', $path);
	$check($status === 404, 'Sensitive path is hidden: '.$path);
}

fwrite(STDOUT, "Checks: {$checks}; failures: {$failures}.\n");
exit($failures === 0 ? 0 : 1);
