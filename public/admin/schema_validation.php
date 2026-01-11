<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/lib/db.php';

$pdo = db();
$schemaName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

$phaseTables = ['playlists', 'playlist_clips', 'annotations'];
$availableTables = fetchTables($pdo, $schemaName);

$tableExists = array_combine(
    $phaseTables,
    array_map(static fn ($table) => in_array($table, $availableTables, true), $phaseTables)
) ?: [];
$tableMetadata = [];
foreach ($phaseTables as $table) {
    if ($tableExists[$table]) {
        $tableMetadata[$table] = [
            'columns' => fetchColumns($pdo, $schemaName, $table),
            'indexes' => fetchIndexes($pdo, $schemaName, $table),
            'foreign_keys' => fetchForeignKeys($pdo, $schemaName, $table),
        ];
    } else {
        $tableMetadata[$table] = [
            'columns' => [],
            'indexes' => [],
            'foreign_keys' => [],
        ];
    }
}

$overallFail = in_array(false, $tableExists, true);

$playlistColumns = $tableMetadata['playlists']['columns'];
$playlistFKs = $tableMetadata['playlists']['foreign_keys'];

$matchColumn = findColumnByNames($playlistColumns, ['match_id']);
$matchFK = findForeignKey($playlistFKs, 'matches');
$matchPresent = $matchColumn !== null || $matchFK !== null;

$titleColumn = findColumnByNames($playlistColumns, ['title', 'name']);
$notesColumn = findColumnByNames($playlistColumns, ['notes', 'description', 'details']);
$createdColumn = findColumnByNames($playlistColumns, ['created_at', 'created']);
$createdByColumn = findColumnByNames($playlistColumns, ['created_by']);
$deletedAtColumn = findColumnByNames($playlistColumns, ['deleted_at']);

$playlistStructureRows = [
    [
        'label' => 'Match reference (column or FK)',
        'column' => $matchColumn['column_name'] ?? ($matchFK !== null ? 'matches (FK)' : 'N/A'),
        'type' => $matchColumn['column_type'] ?? 'N/A',
        'present' => $matchPresent,
        'notes' => $matchFK !== null ? 'Foreign key to matches' : '',
        'required' => true,
    ],
    [
        'label' => 'Title / Name',
        'column' => $titleColumn['column_name'] ?? 'N/A',
        'type' => $titleColumn['column_type'] ?? 'N/A',
        'present' => $titleColumn !== null,
        'notes' => 'Required text column',
        'required' => true,
    ],
    [
        'label' => 'Notes / Description',
        'column' => $notesColumn['column_name'] ?? 'N/A',
        'type' => $notesColumn['column_type'] ?? 'N/A',
        'present' => $notesColumn !== null,
        'notes' => 'Developer annotations expected',
        'required' => true,
    ],
    [
        'label' => 'Created timestamp',
        'column' => $createdColumn['column_name'] ?? 'N/A',
        'type' => $createdColumn['column_type'] ?? 'N/A',
        'present' => $createdColumn !== null,
        'notes' => 'Expected datetime/timestamp',
        'required' => true,
    ],
    [
        'label' => 'Created by (optional)',
        'column' => $createdByColumn['column_name'] ?? 'N/A',
        'type' => $createdByColumn['column_type'] ?? 'N/A',
        'present' => $createdByColumn !== null,
        'notes' => 'Optional user reference',
        'required' => false,
    ],
    [
        'label' => 'Deleted at (optional)',
        'column' => $deletedAtColumn['column_name'] ?? 'N/A',
        'type' => $deletedAtColumn['column_type'] ?? 'N/A',
        'present' => $deletedAtColumn !== null,
        'notes' => 'Soft-delete tracking',
        'required' => false,
    ],
];

if (!$matchPresent || $titleColumn === null || $notesColumn === null || $createdColumn === null) {
    $overallFail = true;
}

$playlistClipsColumns = $tableMetadata['playlist_clips']['columns'];
$playlistClipsIndexes = $tableMetadata['playlist_clips']['indexes'];
$playlistClipsFKs = $tableMetadata['playlist_clips']['foreign_keys'];

$playlistIdColumn = findColumnByNames($playlistClipsColumns, ['playlist_id']);
$clipIdColumn = findColumnByNames($playlistClipsColumns, ['clip_id']);
$orderColumn = findColumnByNames($playlistClipsColumns, ['sort_order', 'position', 'order_in_playlist']);
$playlistClipsPlaylistFK = findForeignKey($playlistClipsFKs, 'playlists');
$playlistClipsClipFK = findForeignKey($playlistClipsFKs, 'clips');

$uniqueConstraint = hasUniqueConstraint($playlistClipsIndexes, ['playlist_id', 'clip_id']);
$playlistConstraintFail = $playlistIdColumn === null || $clipIdColumn === null || $orderColumn === null || !$uniqueConstraint;
if ($playlistConstraintFail) {
    $overallFail = true;
}

$playlistClipConstraintRows = [
    [
        'label' => 'Playlist reference',
        'column' => $playlistIdColumn['column_name'] ?? 'N/A',
        'type' => $playlistIdColumn['column_type'] ?? 'N/A',
        'present' => $playlistIdColumn !== null,
        'notes' => $playlistClipsPlaylistFK ? 'FK to playlists' : '',
    ],
    [
        'label' => 'Clip reference',
        'column' => $clipIdColumn['column_name'] ?? 'N/A',
        'type' => $clipIdColumn['column_type'] ?? 'N/A',
        'present' => $clipIdColumn !== null,
        'notes' => $playlistClipsClipFK ? 'FK to clips' : '',
    ],
    [
        'label' => 'Ordering column',
        'column' => $orderColumn['column_name'] ?? 'N/A',
        'type' => $orderColumn['column_type'] ?? 'N/A',
        'present' => $orderColumn !== null,
        'notes' => 'sort_order or equivalent',
    ],
    [
        'label' => 'Unique constraint (playlist_id + clip_id)',
        'column' => $uniqueConstraint ? 'playlist_id + clip_id' : 'N/A',
        'type' => 'UNIQUE',
        'present' => $uniqueConstraint,
        'notes' => $uniqueConstraint ? 'Prevents duplicates' : 'Missing unique index',
    ],
];

$annotationColumns = $tableMetadata['annotations']['columns'];
$annotationChecks = [
    ['name' => 'target_type', 'notes' => ''],
    ['name' => 'target_id', 'notes' => ''],
    ['name' => 'match_id', 'notes' => ''],
    ['name' => 'timestamp_second', 'notes' => 'Timestamp numeric'],
    ['name' => 'show_from_second', 'notes' => 'Visibility window start'],
    ['name' => 'show_to_second', 'notes' => 'Visibility window end'],
    ['name' => 'show_before_seconds', 'notes' => 'Visibility lead time'],
    ['name' => 'show_after_seconds', 'notes' => 'Visibility trailing window'],
    ['name' => 'tool_type', 'notes' => ''],
    ['name' => 'drawing_data', 'notes' => 'JSON expected'],
    ['name' => 'created_at', 'notes' => 'Timestamp'],
    ['name' => 'updated_at', 'notes' => 'Timestamp'],
];
$annotationRows = [];
foreach ($annotationChecks as $check) {
    $column = findColumnByNames($annotationColumns, [$check['name']]);
    $present = $column !== null;
    $notes = $check['notes'];
    if ($present && $check['name'] === 'drawing_data' && strtolower($column['data_type'] ?? '') === 'json') {
        $notes = 'JSON OK';
    }
    if ($present && $check['name'] === 'timestamp_second' && str_contains($column['data_type'] ?? '', 'int')) {
        $notes = 'Timestamp numeric';
    }
    $annotationRows[] = [
        'column' => $column['column_name'] ?? 'N/A',
        'type' => $column['column_type'] ?? 'N/A',
        'present' => $present,
        'notes' => $notes,
    ];
    if (
        !$present &&
        in_array(
            $check['name'],
            ['target_type', 'target_id', 'match_id', 'timestamp_second', 'show_from_second', 'show_to_second', 'show_before_seconds', 'show_after_seconds', 'tool_type'],
            true
        )
    ) {
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
    $available = hasIndex($tableMetadata[$requirement['table']]['indexes'], $requirement['columns']);
    $indexChecks[] = [
        'label' => $requirement['label'],
        'present' => $available,
    ];
}

$foreignKeys = [
    'playlists' => $tableMetadata['playlists']['foreign_keys'],
    'playlist_clips' => $tableMetadata['playlist_clips']['foreign_keys'],
    'annotations' => $tableMetadata['annotations']['foreign_keys'],
];

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

function findColumnByNames(array $columns, array $names): ?array
{
    $target = array_map('strtolower', $names);
    foreach ($columns as $column) {
        if (in_array(strtolower($column['column_name']), $target, true)) {
            return $column;
        }
    }

    return null;
}

function findForeignKey(array $foreignKeys, string $referencedTable): ?array
{
    foreach ($foreignKeys as $fk) {
        if (strtolower($fk['referenced_table_name']) === strtolower($referencedTable)) {
            return $fk;
        }
    }

    return null;
}

function hasUniqueConstraint(array $indexes, array $columns): bool
{
    $target = array_map('strtolower', $columns);
    foreach ($indexes as $index) {
        if ($index['non_unique'] !== 0) {
            continue;
        }

        $idxCols = array_map('strtolower', $index['columns']);
        if (count($idxCols) < count($target)) {
            continue;
        }

        foreach (range(0, count($idxCols) - count($target)) as $offset) {
            if (array_slice($idxCols, $offset, count($target)) === $target) {
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
        $idxCols = array_map('strtolower', $index['columns']);
        if (count($idxCols) < count($target)) {
            continue;
        }
        foreach (range(0, count($idxCols) - count($target)) as $offset) {
            if (array_slice($idxCols, $offset, count($target)) === $target) {
                return true;
            }
        }
    }

    return false;
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
    <title>Phase 1 Schema Validation</title>
    <style>
        body {
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 1.5rem;
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
            align-items: center;
            gap: 0.5rem;
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
        <h1>Phase 1 Schema Validation Dashboard</h1>
        <p class="notes">Read-only report driven by <code>INFORMATION_SCHEMA</code>. No changes are performed.</p>
        <div class="status-line">
            <strong>Phase 1 Schema Validation Status:</strong>
            <?= renderStatusLabel($overallFail ? '❌ FAIL' : '✅ PASS', $overallFail ? 'fail' : 'pass') ?>
            <small class="notes">Database: <?= htmlspecialchars($schemaName ?: 'unknown', ENT_QUOTES, 'UTF-8') ?></small>
        </div>
    </header>

    <section>
        <h2>Table existence</h2>
        <table>
            <thead>
            <tr>
                <th>Table</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($phaseTables as $table): ?>
                <tr>
                    <td><?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?= $tableExists[$table]
                            ? renderStatusLabel('✅ Exists', 'pass')
                            : renderStatusLabel('❌ Missing', 'fail') ?>
                    </td>
                    <td class="notes">
                        <?= $tableExists[$table]
                            ? 'Table available in the schema.'
                            : 'Table not found; schema validation will fail until created.' ?>
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
                <th>Status</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($playlistStructureRows as $row): ?>
                <?php
                $statusType = $row['present'] ? 'pass' : ($row['required'] ? 'fail' : 'warning');
                $statusText = $row['present'] ? 'YES' : ($row['required'] ? 'NO' : 'OPTIONAL');
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['column'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= renderStatusLabel($statusText, $statusType) ?></td>
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
                    <td>
                        <?= renderStatusLabel($row['present'] ? '✅ PASS' : '❌ FAIL', $row['present'] ? 'pass' : 'fail') ?>
                    </td>
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
                <th>Column name</th>
                <th>Data type</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($annotationRows as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['column'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?= renderStatusLabel($row['present'] ? '✅ Present' : '❌ Missing', $row['present'] ? 'pass' : 'fail') ?>
                    </td>
                    <td class="notes"><?= htmlspecialchars($row['notes'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Index validation (performance checks)</h2>
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
                    <td>
                        <?= $check['present']
                            ? renderStatusLabel('✅ Exists', 'pass')
                            : renderStatusLabel('⚠️ Missing', 'warning') ?>
                    </td>
                    <td class="notes">
                        <?= $check['present']
                            ? 'Index available for faster scans.'
                            : 'Warning: index missing; query performance may degrade.' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2>Foreign keys (informational)</h2>
        <?php foreach ($foreignKeys as $table => $fks): ?>
            <div class="fk-list">
                <strong><?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?></strong>
                <?php if (!empty($fks)): ?>
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
                <?php else: ?>
                    <p class="notes">No foreign keys detected.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>
</body>
</html>
