<?php

namespace App\Support;

use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupCollection;
use Spatie\Backup\Tasks\Cleanup\CleanupStrategy;

/**
 * バックアップの掃除ルール：**新しいものから N 個だけ残し、残りは全部消す**。
 *
 * spatie 既定の DefaultStrategy は「直近◯日は全部／その後は日次・週次・月次…」と
 * 期間ごとに間引く方式で、いつ何個残るかが分かりにくい。
 * 社内のDBバックアップは日次1件・サイズも小さいので、個数で管理するほうが単純。
 *
 * 残す個数は config/backup.php の cleanup.keep_latest_count（既定7個）。
 */
class KeepLatestBackupsStrategy extends CleanupStrategy
{
    /** 設定が無い／壊れている場合に残す個数 */
    public const DEFAULT_KEEP = 7;

    public function deleteOldBackups(BackupCollection $backups): void
    {
        // 最低でも1個は残す（0を指定して全滅させない）
        $keep = max(1, (int) config('backup.cleanup.keep_latest_count', self::DEFAULT_KEEP));

        // BackupCollection は新しい順（createFromFiles で日付の降順にソート済み）
        $backups->slice($keep)->each(fn (Backup $backup) => $backup->delete());
    }
}
