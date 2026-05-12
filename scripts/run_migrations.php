<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/Database.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.' . PHP_EOL);
}

$pdo = Database::pdo();
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$migrationDir = APP_ROOT . '/database/migrations';
$files = glob($migrationDir . '/*.sql') ?: [];
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

$appliedStmt = $pdo->query('SELECT filename FROM schema_migrations ORDER BY filename');
$applied = array_flip(array_map('strval', $appliedStmt->fetchAll(PDO::FETCH_COLUMN)));

$appliedNow = [];
$skipped = [];

foreach ($files as $file) {
    $filename = basename($file);
    if (isset($applied[$filename])) {
        $skipped[] = $filename;
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('Unable to read migration file: ' . $filename);
    }

    $statements = split_sql_statements($sql);
    $pdo->beginTransaction();
    try {
        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if ($trimmed === '') {
                continue;
            }
            $pdo->exec($trimmed);
        }

        $track = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
        $track->execute([$filename]);
        $pdo->commit();
        $appliedNow[] = $filename;
        echo '[applied] ' . $filename . PHP_EOL;
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        echo '[failed] ' . $filename . ' - ' . $throwable->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo PHP_EOL . 'Migration report' . PHP_EOL;
echo 'Applied this run: ' . count($appliedNow) . PHP_EOL;
echo 'Already applied: ' . count($skipped) . PHP_EOL;
echo 'Total discovered: ' . count($files) . PHP_EOL;

if ($appliedNow) {
    echo 'Newly applied:' . PHP_EOL;
    foreach ($appliedNow as $filename) {
        echo '- ' . $filename . PHP_EOL;
    }
}

function split_sql_statements(string $sql): array
{
    $sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql) ?? $sql;
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
                $buffer .= $char;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $i++;
            }
            continue;
        }

        if (!$inSingle && !$inDouble) {
            if ($char === '-' && $next === '-') {
                $third = $i + 2 < $length ? $sql[$i + 2] : '';
                if ($third === ' ' || $third === "\t" || $third === "\r" || $third === "\n" || $third === '') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
            }
            if ($char === '#') {
                $inLineComment = true;
                continue;
            }
            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $i++;
                continue;
            }
        }

        if ($char === "'" && !$inDouble) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inSingle = !$inSingle;
            }
        } elseif ($char === '"' && !$inSingle) {
            $escaped = $i > 0 && $sql[$i - 1] === '\\';
            if (!$escaped) {
                $inDouble = !$inDouble;
            }
        }

        if ($char === ';' && !$inSingle && !$inDouble) {
            $statements[] = $buffer;
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}
