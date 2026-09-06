<?php

declare(strict_types=1);

final class Migration_runner
{
	public const BASELINE_SCHEMA = 'legacy-41-20260906';
	public const HISTORY_TABLE = 'namua_schema_migrations';

	private PDO $database;
	private string $migrationDirectory;
	private string $baselineSchemaPath;

	public function __construct(PDO $database, string $migrationDirectory, string $baselineSchemaPath)
	{
		$this->database = $database;
		$this->migrationDirectory = rtrim($migrationDirectory, DIRECTORY_SEPARATOR);
		$this->baselineSchemaPath = $baselineSchemaPath;
		$this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->database->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
	}

	public function status(): array
	{
		$migrations = $this->loadMigrations();
		$applied = $this->appliedMigrations($migrations);
		$current = self::BASELINE_SCHEMA;
		foreach ($applied as $row) {
			$current = (string) $row['to_schema'];
		}
		$pending = array_slice($migrations, count($applied));
		$target = $migrations === array()
			? self::BASELINE_SCHEMA
			: (string) $migrations[array_key_last($migrations)]['to_schema'];

		return array(
			'current_schema' => $current,
			'target_schema' => $target,
			'applied' => array_map(static fn (array $row): string => (string) $row['version'], $applied),
			'pending' => array_map(static fn (array $migration): string => (string) $migration['version'], $pending),
		);
	}

	public function currentSchema(): string
	{
		return (string) $this->status()['current_schema'];
	}

	public function migrateUp(): array
	{
		return $this->withLock(function (): array {
			$migrations = $this->loadMigrations();
			$applied = $this->appliedMigrations($migrations);
			$pending = array_slice($migrations, count($applied));
			if ($pending === array()) return $this->status();

			$this->ensureHistoryTable();
			$batch = (int) $this->database->query(
				'SELECT COALESCE(MAX(batch), 0) + 1 FROM `'.self::HISTORY_TABLE.'`'
			)->fetchColumn();
			$current = $applied === array()
				? self::BASELINE_SCHEMA
				: (string) $applied[array_key_last($applied)]['to_schema'];

			foreach ($pending as $migration) {
				if (!hash_equals($current, (string) $migration['from_schema'])) {
					throw new RuntimeException('Migration chain does not match the current schema.');
				}
				($migration['up'])($this->database);
				if (!(bool) ($migration['verify_up'])($this->database)) {
					throw new RuntimeException('Post-up verification failed for migration '.$migration['version'].'. Restore the verified backup before retrying.');
				}
				$insert = $this->database->prepare(
					'INSERT INTO `'.self::HISTORY_TABLE.'` '
					.'(version, name, from_schema, to_schema, checksum_sha256, batch, applied_at) '
					.'VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())'
				);
				$insert->execute(array(
					$migration['version'], $migration['name'], $migration['from_schema'],
					$migration['to_schema'], $migration['checksum_sha256'], $batch,
				));
				$current = (string) $migration['to_schema'];
			}

			return $this->status();
		});
	}

	public function migrateDown(int $steps = 1): array
	{
		if ($steps < 1 || $steps > 20) throw new InvalidArgumentException('Rollback steps must be between 1 and 20.');

		return $this->withLock(function () use ($steps): array {
			$migrations = $this->loadMigrations();
			$applied = $this->appliedMigrations($migrations);
			if (count($applied) < $steps) throw new RuntimeException('Rollback exceeds the applied migration count.');

			for ($index = 0; $index < $steps; $index++) {
				$row = $applied[array_key_last($applied)];
				$migration = $migrations[count($applied) - 1];
				if (!hash_equals((string) $row['checksum_sha256'], (string) $migration['checksum_sha256'])) {
					throw new RuntimeException('Applied migration checksum no longer matches its source file.');
				}
				($migration['down'])($this->database);
				if (!(bool) ($migration['verify_down'])($this->database)) {
					throw new RuntimeException('Post-down verification failed for migration '.$migration['version'].'. Restore the verified backup before retrying.');
				}
				$delete = $this->database->prepare('DELETE FROM `'.self::HISTORY_TABLE.'` WHERE version = ?');
				$delete->execute(array($migration['version']));
				array_pop($applied);
			}

			if ($applied === array()) {
				$this->database->exec('DROP TABLE `'.self::HISTORY_TABLE.'`');
			}

			return $this->status();
		});
	}

	private function appliedMigrations(array $migrations): array
	{
		$this->assertBaselineTablesExist();
		if (!$this->tableExists(self::HISTORY_TABLE)) return array();

		$rows = $this->database->query(
			'SELECT version, name, from_schema, to_schema, checksum_sha256, batch, applied_at '
			.'FROM `'.self::HISTORY_TABLE.'` ORDER BY version ASC'
		)->fetchAll(PDO::FETCH_ASSOC);
		if (count($rows) > count($migrations)) throw new RuntimeException('Database contains migrations unknown to this release.');

		$current = self::BASELINE_SCHEMA;
		foreach ($rows as $position => $row) {
			$migration = $migrations[$position] ?? null;
			if ($migration === null
				|| !hash_equals((string) $migration['version'], (string) $row['version'])
				|| !hash_equals((string) $migration['name'], (string) $row['name'])
				|| !hash_equals((string) $migration['from_schema'], (string) $row['from_schema'])
				|| !hash_equals((string) $migration['to_schema'], (string) $row['to_schema'])
				|| !hash_equals((string) $migration['checksum_sha256'], (string) $row['checksum_sha256'])
				|| !hash_equals($current, (string) $row['from_schema'])) {
				throw new RuntimeException('Migration history integrity validation failed.');
			}
			if (!(bool) ($migration['verify_up'])($this->database)) {
				throw new RuntimeException('Applied migration verification failed for '.$row['version'].'.');
			}
			$current = (string) $row['to_schema'];
		}

		return $rows;
	}

	private function loadMigrations(): array
	{
		$files = glob($this->migrationDirectory.'/[0-9]*_*.php') ?: array();
		sort($files, SORT_STRING);
		$migrations = array();
		$expectedFrom = self::BASELINE_SCHEMA;

		foreach ($files as $file) {
			$basename = basename($file);
			if (preg_match('/^(\d{14})_([a-z0-9_]+)\.php$/D', $basename, $matches) !== 1) {
				throw new RuntimeException('Invalid migration filename: '.$basename);
			}
			$migration = require $file;
			if (!is_array($migration)) throw new RuntimeException('Migration must return an array: '.$basename);
			foreach (array('version', 'name', 'from_schema', 'to_schema', 'up', 'down', 'verify_up', 'verify_down') as $key) {
				if (!array_key_exists($key, $migration)) throw new RuntimeException('Migration field is missing: '.$basename.' / '.$key);
			}
			if (!hash_equals($matches[1], (string) $migration['version'])
				|| !hash_equals($matches[2], (string) $migration['name'])
				|| !hash_equals($expectedFrom, (string) $migration['from_schema'])
				|| preg_match('/^[A-Za-z0-9._-]{1,80}$/D', (string) $migration['to_schema']) !== 1
				|| !is_callable($migration['up']) || !is_callable($migration['down'])
				|| !is_callable($migration['verify_up']) || !is_callable($migration['verify_down'])) {
				throw new RuntimeException('Migration definition is invalid: '.$basename);
			}
			$migration['checksum_sha256'] = hash_file('sha256', $file);
			$migrations[] = $migration;
			$expectedFrom = (string) $migration['to_schema'];
		}

		return $migrations;
	}

	private function assertBaselineTablesExist(): void
	{
		if (!is_file($this->baselineSchemaPath) || !is_readable($this->baselineSchemaPath)) {
			throw new RuntimeException('Baseline schema is unavailable.');
		}
		$sql = (string) file_get_contents($this->baselineSchemaPath);
		preg_match_all('/^CREATE TABLE `([A-Za-z0-9_]+)`/m', $sql, $matches);
		$expected = array_values(array_unique($matches[1] ?? array()));
		if (count($expected) !== 41) throw new RuntimeException('Baseline schema definition must contain exactly 41 tables.');

		$statement = $this->database->query(
			"SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'"
		);
		$actual = $statement->fetchAll(PDO::FETCH_COLUMN);
		$missing = array_diff($expected, $actual);
		if ($missing !== array()) throw new RuntimeException('Database does not match the 41-table baseline; missing: '.implode(', ', $missing));
	}

	private function ensureHistoryTable(): void
	{
		$this->database->exec(
			'CREATE TABLE IF NOT EXISTS `'.self::HISTORY_TABLE.'` ('
			.'`version` CHAR(14) NOT NULL, '
			.'`name` VARCHAR(120) NOT NULL, '
			.'`from_schema` VARCHAR(80) NOT NULL, '
			.'`to_schema` VARCHAR(80) NOT NULL, '
			.'`checksum_sha256` CHAR(64) NOT NULL, '
			.'`batch` INT UNSIGNED NOT NULL, '
			.'`applied_at` DATETIME NOT NULL, '
			.'PRIMARY KEY (`version`), KEY `idx_namua_schema_migrations_batch` (`batch`)'
			.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
		);
	}

	private function tableExists(string $table): bool
	{
		$statement = $this->database->prepare(
			'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? AND table_type = ?'
		);
		$statement->execute(array($table, 'BASE TABLE'));
		return (int) $statement->fetchColumn() === 1;
	}

	private function withLock(callable $operation): array
	{
		$name = 'namua_penatus_migration_'.substr(hash('sha256', (string) $this->database->query('SELECT DATABASE()')->fetchColumn()), 0, 24);
		$lock = $this->database->prepare('SELECT GET_LOCK(?, 10)');
		$lock->execute(array($name));
		if ((int) $lock->fetchColumn() !== 1) throw new RuntimeException('Unable to acquire the database migration lock.');
		try {
			return $operation();
		} finally {
			$release = $this->database->prepare('SELECT RELEASE_LOCK(?)');
			$release->execute(array($name));
		}
	}
}
