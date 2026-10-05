<?php
declare(strict_types=1);

function backup_dir(): string
{
    $d = data_dir() . '/backups';
    if (!is_dir($d)) {
        @mkdir($d, 0775, true);
    }
    return $d;
}

/** Consistent snapshot of the SQLite database (VACUUM INTO); keeps the newest $keep files. */
function backup_db(int $keep = 10): ?string
{
    try {
        $f = backup_dir() . '/uzman-' . date('Ymd-His') . '.sqlite';
        db()->exec('VACUUM INTO ' . db()->quote($f));
        $all = glob(backup_dir() . '/uzman-*.sqlite') ?: [];
        rsort($all);
        foreach (array_slice($all, $keep) as $old) {
            @unlink($old);
        }
        set_setting('backup_last', date('Y-m-d H:i:s'));
        return $f;
    } catch (Throwable $e) {
        error_log('backup failed: ' . $e->getMessage());
        return null;
    }
}

function backup_list(): array
{
    $all = glob(backup_dir() . '/uzman-*.sqlite') ?: [];
    rsort($all);
    return array_map(function ($f) { return ['name' => basename($f), 'size' => filesize($f), 'time' => date('Y-m-d H:i:s', (int)filemtime($f))]; }, $all);
}
