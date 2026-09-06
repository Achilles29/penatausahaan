<?php

declare(strict_types=1);

return array(
	'version' => '20260906170000',
	'name' => 'create_installation_metadata',
	'from_schema' => 'legacy-41-20260906',
	'to_schema' => '20260906170000',
	'up' => static function (PDO $database): void {
		$database->exec(
			'CREATE TABLE IF NOT EXISTS `namua_installation_meta` ('
			.'`meta_key` VARCHAR(100) NOT NULL, '
			.'`meta_value` TEXT NOT NULL, '
			.'`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, '
			.'PRIMARY KEY (`meta_key`)'
			.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
		);
		$statement = $database->prepare(
			'INSERT INTO `namua_installation_meta` (`meta_key`, `meta_value`, `updated_at`) '
			.'VALUES (?, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE `meta_value` = VALUES(`meta_value`), `updated_at` = UTC_TIMESTAMP()'
		);
		$statement->execute(array('product_code', 'NAMUA_PENATAUSAHAAN'));
		$statement->execute(array('schema_version', '20260906170000'));
	},
	'down' => static function (PDO $database): void {
		$database->exec('DROP TABLE IF EXISTS `namua_installation_meta`');
	},
	'verify_up' => static function (PDO $database): bool {
		$statement = $database->prepare(
			"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'namua_installation_meta'"
		);
		$statement->execute();
		if ((int) $statement->fetchColumn() !== 1) return false;
		$value = $database->query(
			"SELECT meta_value FROM namua_installation_meta WHERE meta_key = 'schema_version' LIMIT 1"
		)->fetchColumn();
		return is_string($value) && hash_equals('20260906170000', $value);
	},
	'verify_down' => static function (PDO $database): bool {
		$statement = $database->prepare(
			"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'namua_installation_meta'"
		);
		$statement->execute();
		return (int) $statement->fetchColumn() === 0;
	},
);
