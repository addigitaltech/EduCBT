<?php

namespace App\Domain\System\Support;

use App\Domain\System\Models\SystemIdentity;
use App\Support\Profile\Profile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Plain-language checks of the local installation. Status is always text plus
 * a label, never colour alone.
 */
final class HealthChecker
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const PROBLEM = 'problem';

    /**
     * @return list<array{key: string, label: string, status: string, message: string}>
     */
    public function run(): array
    {
        return [
            $this->database(),
            $this->profile(),
            $this->storage(),
            $this->disk(),
            $this->queue(),
            $this->scheduler(),
            $this->php(),
        ];
    }

    /** @param list<array{status: string}> $checks */
    public function overall(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array(self::PROBLEM, $statuses, true)) {
            return self::PROBLEM;
        }

        return in_array(self::WARNING, $statuses, true) ? self::WARNING : self::OK;
    }

    public function databaseIsUp(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function database(): array
    {
        return $this->databaseIsUp()
            ? $this->result('database', 'Database', self::OK, 'The database is working.')
            : $this->result('database', 'Database', self::PROBLEM, 'The database cannot be reached. Ask your technical support to check it.');
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function profile(): array
    {
        try {
            $identity = SystemIdentity::current();
        } catch (Throwable) {
            $identity = null;
        }

        if ($identity === null) {
            return $this->result('profile', 'Installation', self::PROBLEM, 'This installation has not been set up yet.');
        }

        return $identity->matches(Profile::current())
            ? $this->result('profile', 'Installation', self::OK, 'Running as: '.Profile::current()->label().'.')
            : $this->result('profile', 'Installation', self::PROBLEM, 'This installation was set up as a different profile. Do not use it until this is fixed.');
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function storage(): array
    {
        $writable = is_writable(storage_path('app')) && is_writable(storage_path('logs'));

        return $writable
            ? $this->result('storage', 'File storage', self::OK, 'The server can save files.')
            : $this->result('storage', 'File storage', self::PROBLEM, 'The server cannot save files. Check folder permissions.');
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function disk(): array
    {
        $free = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());

        if ($free === false || $total === false || $total <= 0) {
            return $this->result('disk', 'Disk space', self::WARNING, 'Disk space could not be checked.');
        }

        $percent = (int) round($free / $total * 100);

        if ($percent < 5) {
            return $this->result('disk', 'Disk space', self::PROBLEM, "Only {$percent}% of disk space is left. Free some space now.");
        }

        if ($percent < 15) {
            return $this->result('disk', 'Disk space', self::WARNING, "{$percent}% of disk space is left. Plan to free some space.");
        }

        return $this->result('disk', 'Disk space', self::OK, "{$percent}% of disk space is free.");
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function queue(): array
    {
        try {
            if (! Schema::hasTable('jobs')) {
                return $this->result('queue', 'Background jobs', self::WARNING, 'The background job table is missing.');
            }

            $pending = DB::table('jobs')->count();
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
            $oldest = DB::table('jobs')->min('created_at');
        } catch (Throwable) {
            return $this->result('queue', 'Background jobs', self::WARNING, 'Background jobs could not be checked.');
        }

        if ($failed > 0) {
            return $this->result('queue', 'Background jobs', self::WARNING, "{$failed} background job(s) failed. Ask technical support to review them.");
        }

        if ($pending > 0 && $oldest !== null && (time() - (int) $oldest) > 600) {
            return $this->result('queue', 'Background jobs', self::WARNING, 'Some background jobs have been waiting for more than 10 minutes. The job runner may have stopped.');
        }

        return $this->result('queue', 'Background jobs', self::OK, $pending === 0 ? 'No jobs are waiting.' : "{$pending} job(s) are waiting and being processed.");
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function scheduler(): array
    {
        $beat = Cache::get('scheduler.heartbeat');

        if ($beat === null) {
            return $this->result('scheduler', 'Scheduled tasks', self::WARNING, 'Scheduled tasks have not run yet. Check that the one-minute scheduler is set up (see the installation guide).');
        }

        return (time() - (int) $beat) > 180
            ? $this->result('scheduler', 'Scheduled tasks', self::WARNING, 'Scheduled tasks have not run for more than 3 minutes. Check the scheduler or cron job.')
            : $this->result('scheduler', 'Scheduled tasks', self::OK, 'Scheduled tasks are running.');
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function php(): array
    {
        $issues = [];

        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            $issues[] = 'PHP '.PHP_VERSION.' is older than the recommended 8.3.';
        }

        if (! extension_loaded('sodium')) {
            $issues[] = 'The PHP "sodium" extension is missing; it is needed for signed licences and updates in a later phase.';
        }

        return $issues === []
            ? $this->result('php', 'Server software', self::OK, 'PHP '.PHP_VERSION.' with required extensions.')
            : $this->result('php', 'Server software', self::WARNING, implode(' ', $issues));
    }

    /** @return array{key: string, label: string, status: string, message: string} */
    private function result(string $key, string $label, string $status, string $message): array
    {
        return compact('key', 'label', 'status', 'message');
    }
}
