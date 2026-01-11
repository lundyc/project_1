<?php
declare(strict_types=1);

require __DIR__ . '/../../app/lib/db.php';

$pdo = db();
$schemaName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
$timestamp = (new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get())))->format('Y-m-d H:i:s');

$requiredTables = ['playlists', 'playlist_clips', 'annotations'];
$availableTables = fetchTables($pdo, $schemaName);

$tableDetails = [];
foreach ($requiredTables as $table) {
    $tableDetails[$table] = [
        'exists' => in_array($table, $availableTables, true),
        'columns' => fetchColumns($pdo, $schemaName, $table),
        'indexes' => fetchIndexes($pdo, $schemaName, $table),
        'foreign_keys' => fetchForeignKeys($pdo, $schemaName, $table),
    ];
}

$overallFail = false;
foreach ($tableDetails as $name => $details) {
    if (!$details['exists']) {
        $overallFail = true;
    }
}

$playlistColumnChecks = [
    [
        'name' => 'match_id',
        'label' => 'Match reference',
        'expected' => 'BIGINT UNSIGNED',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsBigIntUnsigned($column),
        'notes' => 'Playlists should store match references for related media.',
    ],
    [
        'name' => 'title',
        'label' => 'Title',
        'expected' => 'VARCHAR(160)',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsVarcharLength($column, 160),
        'notes' => 'Human-readable title limited to 160 characters.',
    ],
    [
        'name' => 'notes',
        'label' => 'Notes',
        'expected' => 'TEXT',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsText($column),
        'notes' => 'Developer annotations are stored as TEXT.',
    ],
    [
        'name' => 'created_at',
        'label' => 'Created timestamp',
        'expected' => 'DATETIME/TIMESTAMP',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsDateOrTimestamp($column),
        'notes' => 'Tracks when playlists were created.',
    ],
    [
        'name' => 'deleted_at',
        'label' => 'Deleted at (soft delete)',
        'expected' => 'DATETIME (optional)',
        'required' => false,
        'validate' => static fn (?array $column) => columnIsDatetime($column),
        'notes' => 'Optional soft-delete timestamp.',
    ],
];

$playlistColumns = $tableDetails['playlists']['columns'];
$playlistStructureRows = [];
foreach ($playlistColumnChecks as $check) {
    $column = findColumn($playlistColumns, [$check['name']]);
    $present = $column !== null;
    $valid = $check['validate']($column);
    $statusType = $present && $valid ? 'pass' : ($check['required'] ? 'fail' : 'warning');
    $statusText = $present && $valid ? '✅ PASS' : ($check['required'] ? '❌ FAIL' : '⚠️ WARNING');
    $notes = $present ? ($valid ? $check['notes'] : sprintf('Type mismatch (%s).', $column['column_type'])) : ($check['required'] ? 'Column missing.' : 'Optional column not present.');
    $playlistStructureRows[] = [
        'label' => $check['label'],
        'column' => $column['column_name'] ?? 'N/A',
        'type' => $column['column_type'] ?? 'N/A',
        'expected' => $check['expected'],
        'statusText' => $statusText,
        'statusType' => $statusType,
        'notes' => $notes,
    ];

    if ($check['required'] && !($present && $valid)) {
        $overallFail = true;
    }
}

$playlistClipsInfo = $tableDetails['playlist_clips'];
$playlistClipsColumns = $playlistClipsInfo['columns'];
$playlistClipsIndexes = $playlistClipsInfo['indexes'];
$playlistClipsFKs = $playlistClipsInfo['foreign_keys'];

$playlistIdColumn = findColumn($playlistClipsColumns, ['playlist_id']);
$clipIdColumn = findColumn($playlistClipsColumns, ['clip_id']);
$orderColumn = findColumn($playlistClipsColumns, ['sort_order', 'position', 'order_in_playlist']);
$playlistIdFK = findForeignKey($playlistClipsFKs, 'playlist_id', 'playlists');
$clipIdFK = findForeignKey($playlistClipsFKs, 'clip_id', 'clips');
$uniqueConstraint = hasUniqueIndex($playlistClipsIndexes, ['playlist_id', 'clip_id']);

$playlistClipConstraintRows = [
    [
        'label' => 'Playlist reference',
        'column' => $playlistIdColumn['column_name'] ?? 'N/A',
        'type' => $playlistIdColumn['column_type'] ?? 'N/A',
        'status' => $playlistClipsInfo['exists'] && $playlistIdColumn && $playlistIdFK,
        'notes' => $playlistClipsInfo['exists'] ? ($playlistIdFK ? 'FK to playlists.id' : 'Missing FK to playlists.id') : 'Table missing.',
    ],
    [
        'label' => 'Clip reference',
        'column' => $clipIdColumn['column_name'] ?? 'N/A',
        'type' => $clipIdColumn['column_type'] ?? 'N/A',
        'status' => $playlistClipsInfo['exists'] && $clipIdColumn && $clipIdFK,
        'notes' => $playlistClipsInfo['exists'] ? ($clipIdFK ? 'FK to clips.id' : 'Missing FK to clips.id') : 'Table missing.',
    ],
    [
        'label' => 'Ordering column (sort_order or equivalent)',
        'column' => $orderColumn['column_name'] ?? 'N/A',
        'type' => $orderColumn['column_type'] ?? 'N/A',
        'status' => $playlistClipsInfo['exists'] && $orderColumn !== null,
        'notes' => $playlistClipsInfo['exists'] ? 'Controls clip ordering inside playlists.' : 'Table missing.',
    ],
    [
        'label' => 'Unique constraint (playlist_id + clip_id)',
        'column' => $uniqueConstraint ? 'playlist_id + clip_id' : 'N/A',
        'type' => 'UNIQUE INDEX',
        'status' => $playlistClipsInfo['exists'] && $uniqueConstraint,
        'notes' => $playlistClipsInfo['exists'] ? ($uniqueConstraint ? 'Prevents duplicates inside playlists.' : 'Missing unique index on playlist_id + clip_id.') : 'Table missing.',
    ],
];

foreach ($playlistClipConstraintRows as $row) {
    if (!$row['status']) {
        $overallFail = true;
    }
}

$annotationChecks = [
    [
        'name' => 'target_type',
        'label' => 'target_type',
        'expected' => "ENUM('match_video','clip')",
        'required' => true,
        'validate' => static fn (?array $column) => columnIsEnumWithValues($column, ['match_video', 'clip']),
        'notes' => 'Discriminates against clips vs full match video.',
    ],
    [
        'name' => 'target_id',
        'label' => 'target_id',
        'expected' => 'BIGINT UNSIGNED',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsBigIntUnsigned($column),
        'notes' => 'Relates annotations to a row of the target table.',
    ],
    [
        'name' => 'match_id',
        'label' => 'match_id',
        'expected' => 'BIGINT UNSIGNED',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsBigIntUnsigned($column),
        'notes' => 'Matches link annotations back to source video.',
    ],
    [
        'name' => 'timestamp_second',
        'label' => 'timestamp_second',
        'expected' => 'INT UNSIGNED',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsIntUnsigned($column),
        'notes' => 'Numeric offset within match or clip.',
    ],
    [
        'name' => 'show_from_second',
        'label' => 'show_from_second',
        'expected' => 'INT UNSIGNED',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsIntUnsigned($column),
        'notes' => 'Earliest second the drawing is visible.',
    ],
    [
        'name' => 'show_to_second',
        'label' => 'show_to_second',
        'expected' => 'INT UNSIGNED',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsIntUnsigned($column),
        'notes' => 'Latest second the drawing is visible.',
    ],
    [
        'name' => 'show_before_seconds',
        'label' => 'show_before_seconds',
        'expected' => 'INT UNSIGNED',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsIntUnsigned($column),
        'notes' => 'Seconds before timestamp when drawing appears.',
    ],
    [
        'name' => 'show_after_seconds',
        'label' => 'show_after_seconds',
        'expected' => 'INT UNSIGNED',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsIntUnsigned($column),
        'notes' => 'Seconds after timestamp when drawing stays visible.',
    ],
    [
        'name' => 'drawing_data',
        'label' => 'drawing_data',
        'expected' => 'LONGTEXT',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsLongtext($column),
        'notes' => 'JSON payload describing annotations.',
    ],
    [
        'name' => 'created_at',
        'label' => 'created_at',
        'expected' => 'DATETIME',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsDatetime($column),
        'notes' => 'Stamp when annotation was created.',
    ],
    [
        'name' => 'updated_at',
        'label' => 'updated_at',
        'expected' => 'DATETIME',
        'required' => true,
        'validate' => static fn (?array $column) => columnIsDatetime($column),
        'notes' => 'Stamp when annotation was last updated.',
    ],
];

$annotationColumns = $tableDetails['annotations']['columns'];
$annotationRows = [];
foreach ($annotationChecks as $check) {
    $column = findColumn($annotationColumns, [$check['name']]);
    $present = $column !== null;
    $valid = $check['validate']($column);
    $statusType = $present && $valid ? 'pass' : 'fail';
    $statusText = $present && $valid ? '✅ PASS' : '❌ FAIL';
    $notes = $present ? ($valid ? $check['notes'] : sprintf('Type mismatch (%s).', $column['column_type'])) : 'Column missing.';
    $annotationRows[] = [
        'column' => $column['column_name'] ?? 'N/A',
        'type' => $column['column_type'] ?? 'N/A',
        'statusText' => $statusText,
        'statusType' => $statusType,
        'notes' => $notes,
    ];
    if ($check['required'] && !($present && $valid)) {
        $overallFail = true;
    }
}

$indexRequirements = [
    ['table' => 'playlists', 'columns' => ['match_id'], 'label' => 'playlists.match_id'],
    ['table' => 'playlist_clips', 'columns' => ['playlist_id'], 'label' => 'playlist_clips.playlist_id'],
    ['table' => 'playlist_clips', 'columns' => ['sort_order'], 'label' => 'playlist_clips.sort_order'],
    ['table' => 'annotations', 'columns' => ['match_id'], 'label' => 'annotations.match_id'],
    ['table' => 'annotations', 'columns' => ['target_type', 'target_id'], 'label' => 'annotations.target_type + target_id'],
    ['table' => 'annotations', 'columns' => ['timestamp_second'], 'label' => 'annotations.timestamp_second'],
];

$indexChecks = [];
foreach ($indexRequirements as $requirement) {
    $table = $requirement['table'];
    $exists = hasIndex($tableDetails[$table]['indexes'], $requirement['columns']);
    $indexChecks[] = [
        'table' => $table,
        'columns' => implode(', ', $requirement['columns']),
        'label' => $requirement['label'],
        'present' => $exists,
    ];
}

$foreignKeySummary = [];
foreach (['playlists', 'playlist_clips', 'annotations'] as $table) {
    $foreignKeySummary[$table] = $tableDetails[$table]['foreign_keys'];
}

function fetchTables(PDO $pdo, string $schema): array
{
    if ($schema === '') {
        return [];
    }
    $stmt = $pdo->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = :schema');
    $stmt->execute(['schema' => $schema]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function fetchColumns(PDO $pdo, string $schema, string $table): array
{
    $stmt = $pdo->prepare('SELECT column_name, column_type, data_type FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table ORDER BY ordinal_position');
    $stmt->execute(['schema' => $schema, 'table' => $table]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchIndexes(PDO $pdo, string $schema, string $table): array
{
    $stmt = $pdo->prepare('SELECT index_name, non_unique, column_name, seq_in_index FROM information_schema.statistics WHERE table_schema = :schema AND table_name = :table ORDER BY index_name, seq_in_index');
    $stmt->execute(['schema' => $schema, 'table' => $table]);

    $indexes = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $name = $row['index_name'];
        if (!isset($indexes[$name])) {
            $indexes[$name] = [
                'non_unique' => (int) $row['non_unique'],
                'columns' => [],
            ];
        }
        $indexes[$name]['columns'][] = $row['column_name'];
    }

    return array_values($indexes);
}

function fetchForeignKeys(PDO $pdo, string $schema, string $table): array
{
    $stmt = $pdo->prepare('SELECT constraint_name, column_name, referenced_table_name, referenced_column_name FROM information_schema.key_column_usage WHERE table_schema = :schema AND table_name = :table AND referenced_table_name IS NOT NULL ORDER BY constraint_name, ordinal_position');
    $stmt->execute(['schema' => $schema, 'table' => $table]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function findColumn(array $columns, array $names): ?array
{
    $lowerNames = array_map('strtolower', $names);
    foreach ($columns as $column) {
        if (in_array(strtolower($column['column_name']), $lowerNames, true)) {
            return $column;
        }
    }

    return null;
}

function hasUniqueIndex(array $indexes, array $columns): bool
{
    $target = array_map('strtolower', $columns);
    foreach ($indexes as $index) {
        if ($index['non_unique'] !== 0) {
            continue;
        }
        $idxColumns = array_map('strtolower', $index['columns']);
        if (count($idxColumns) < count($target)) {
            continue;
        }
        foreach (range(0, count($idxColumns) - count($target)) as $offset) {
            if (array_slice($idxColumns, $offset, count($target)) === $target) {
                return true;
            }
        }
    }

    return false;
}

function hasIndex(array $indexes, array $columns): bool
{
    $target = array_map('strtolower', $columns);
    foreach ($indexes as $index) {
        $idxColumns = array_map('strtolower', $index['columns']);
        if (count($idxColumns) < count($target)) {
            continue;
        }
        foreach (range(0, count($idxColumns) - count($target)) as $offset) {
            if (array_slice($idxColumns, $offset, count($target)) === $target) {
                return true;
            }
        }
    }

    return false;
}

function findForeignKey(array $foreignKeys, string $columnName, string $referencedTable, string $referencedColumn = 'id'): ?array
{
    foreach ($foreignKeys as $foreignKey) {
        if (strtolower($foreignKey['column_name']) === strtolower($columnName)
            && strtolower($foreignKey['referenced_table_name']) === strtolower($referencedTable)
            && strtolower($foreignKey['referenced_column_name']) === strtolower($referencedColumn)
        ) {
            return $foreignKey;
        }
    }

    return null;
}

function columnIsBigIntUnsigned(?array $column): bool
{
    if ($column === null) {
        return false;
    }
    $dataType = strtolower($column['data_type'] ?? '');
    $columnType = strtolower($column['column_type'] ?? '');

    return $dataType === 'bigint' && str_contains($columnType, 'unsigned');
}

function columnIsVarcharLength(?array $column, int $length): bool
{
    if ($column === null) {
        return false;
    }
    $dataType = strtolower($column['data_type'] ?? '');
    $columnType = strtolower($column['column_type'] ?? '');

    return $dataType === 'varchar' && str_contains($columnType, sprintf('varchar(%d)', $length));
}

function columnIsText(?array $column): bool
{
    if ($column === null) {
        return false;
    }

    return strtolower($column['data_type'] ?? '') === 'text';
}

function columnIsDateOrTimestamp(?array $column): bool
{
    if ($column === null) {
        return false;
    }

    $type = strtolower($column['data_type'] ?? '');
    return in_array($type, ['datetime', 'timestamp'], true);
}

function columnIsDatetime(?array $column): bool
{
    if ($column === null) {
        return false;
    }

    return strtolower($column['data_type'] ?? '') === 'datetime';
}

function columnIsEnumWithValues(?array $column, array $values): bool
{
    if ($column === null) {
        return false;
    }
    $dataType = strtolower($column['data_type'] ?? '');
    $columnType = strtolower($column['column_type'] ?? '');
    if ($dataType !== 'enum') {
        return false;
    }
    foreach ($values as $value) {
        if (!str_contains($columnType, sprintf("'%s'", strtolower($value)))) {
            return false;
        }
    }

    return true;
}

function columnIsLongtext(?array $column): bool
{
    if ($column === null) {
        return false;
    }

    return strtolower($column['data_type'] ?? '') === 'longtext';
}

function columnIsIntUnsigned(?array $column): bool
{
    if ($column === null) {
        return false;
    }

    $dataType = strtolower($column['data_type'] ?? '');
    $columnType = strtolower($column['column_type'] ?? '');
    return str_contains($dataType, 'int') && str_contains($columnType, 'unsigned');
}

function renderStatusLabel(string $text, string $type): string
{
    $class = match ($type) {
        'pass' => 'status-pass',
        'fail' => 'status-fail',
        'warning' => 'status-warning',
        default => 'status-muted',
    };

    return sprintf('<span class="status-pill %s">%s</span>', $class, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Phase 1 – Schema Validation Dashboard</title>
    <style>
        body {
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 1.5rem;
            background: #f4f6f9;
            color: #1c1f26;
        }
        .page {
            max-width: 1100px;
            margin: 0 auto;
        }
        header {
            margin-bottom: 1.5rem;
        }
        h1 {
            margin-bottom: 0.25rem;
            font-size: 1.85rem;
        }
        h2 {
            margin-top: 1.75rem;
            margin-bottom: 0.5rem;
            font-size: 1.35rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
        }
        th,
        td {
            padding: 0.5rem 0.75rem;
            border: 1px solid #dfe2ea;
            vertical-align: top;
        }
        th {
            background: #f8f9fb;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.78rem;
            letter-spacing: 0.04em;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            border-radius: 999px;
            padding: 0.15rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-pass {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-fail {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .status-muted {
            background: #e2e3e5;
            color: #41464b;
            border: 1px solid #d3d6db;
        }
        .status-line {
            margin-top: 0.5rem;
            font-size: 1.05rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
        }
        .meta {
            margin-top: 0.35rem;
            font-size: 0.9rem;
            color: #555b69;
            display: flex;
            gap: 1.25rem;
            flex-wrap: wrap;
        }
        section {
            background: #ffffff;
            border: 1px solid #e1e4ef;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem 1.25rem;
        }
        .notes {
            font-size: 0.85rem;
            color: #555b69;
        }
        ul {
            padding-left: 1.25rem;
            margin-top: 0;
        }
        .fk-list {
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>
<div class="page">
    <header>
        <h1>Phase 1 – Schema Validation Dashboard</h1>
        <p class="notes">Read-only report driven by <code>INFORMATION_SCHEMA</code>. No changes are performed.</p>
        <div class="meta">
            <span>Database: <?= htmlspecialchars($schemaName ?: 'unknown', ENT_QUOTES, 'UTF-8') ?></span>
            <span>Timestamp (server): <?= htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="status-line">
            <strong>Phase 1 Schema Validation Status:</strong>
            <?= renderStatusLabel($overallFail ? '❌ FAIL' : '✅ PASS', $overallFail ? 'fail' : 'pass') ?>
        </div>
    </header>

    <section>
        <h2>Table existence (critical)</h2>
        <table>
            <thead>
            <tr>
                <th>Table</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($tableDetails as $table => $info): ?>
                <tr>
                    <td><?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $info['exists']
                        ? renderStatusLabel('✅ PASS', 'pass')
                        : renderStatusLabel('❌ FAIL', 'fail') ?></td>
                    <td class="notes">
                        <?= $info['exists']
                            ? 'Table exists within the current schema.'
                            : 'Missing table; Phase 1 validation cannot succeed until created.' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>playlists table structure</h2>
        <table>
            <thead>
            <tr>
                <th>Requirement</th>
                <th>Column</th>
                <th>Type</th>
                <th>Expected</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($playlistStructureRows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['column'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['expected'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= renderStatusLabel($row['statusText'], $row['statusType']) ?></td>
                    <td class="notes"><?= htmlspecialchars($row['notes'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>playlist_clips constraints (critical)</h2>
        <table>
            <thead>
            <tr>
                <th>Requirement</th>
                <th>Column</th>
                <th>Type</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($playlistClipConstraintRows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['column'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= renderStatusLabel($row['status'] ? '✅ PASS' : '❌ FAIL', $row['status'] ? 'pass' : 'fail') ?></td>
                    <td class="notes"><?= htmlspecialchars($row['notes'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>annotations target model</h2>
        <table>
            <thead>
            <tr>
                <th>Column</th>
                <th>Type</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($annotationRows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['column'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= renderStatusLabel($row['statusText'], $row['statusType']) ?></td>
                    <td class="notes"><?= htmlspecialchars($row['notes'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Index validation (performance warning)</h2>
        <table>
            <thead>
            <tr>
                <th>Index</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($indexChecks as $check): ?>
                <tr>
                    <td><?= htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $check['present']
                        ? renderStatusLabel('✅ PASS', 'pass')
                        : renderStatusLabel('⚠️ WARNING', 'warning') ?></td>
                    <td class="notes">
                        <?= $check['present']
                            ? 'Index exists for ' . htmlspecialchars($check['columns'], ENT_QUOTES, 'UTF-8') . '.'
                            : 'Index on ' . htmlspecialchars($check['columns'], ENT_QUOTES, 'UTF-8') . ' is missing; may impact performance.' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Foreign keys (informational)</h2>
        <?php foreach ($foreignKeySummary as $table => $fks): ?>
            <div class="fk-list">
                <strong><?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if (empty($fks)): ?>
                    <p class="notes">No foreign keys detected.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($fks as $fk): ?>
                            <li>
                                <?= htmlspecialchars($fk['constraint_name'], ENT_QUOTES, 'UTF-8') ?>
                                — <?= htmlspecialchars($fk['column_name'], ENT_QUOTES, 'UTF-8') ?> →
                                <?= htmlspecialchars($fk['referenced_table_name'], ENT_QUOTES, 'UTF-8') ?>
                                (<?= htmlspecialchars($fk['referenced_column_name'], ENT_QUOTES, 'UTF-8') ?>)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>
</body>
</html>
