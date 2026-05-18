<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class AdminMaintenanceController extends Controller
{
    private const STATUS_FILE = 'app/maintenance/uat-seeder-status.json';

    private const LOG_FILE = 'logs/uat-seeder-web.log';

    public function runUatSeeder(Request $request): RedirectResponse
    {
        $redirectTo = route('admin.dashboard');
        $confirmation = trim((string) $request->input('seed_confirmation', ''));

        if ($confirmation !== 'RUN UAT SEEDER') {
            throw $this->formValidationException(
                ['seed_confirmation' => 'Type "RUN UAT SEEDER" to start the demo data seeder.'],
                'uatSeeder',
                $redirectTo,
            );
        }

        $status = $this->statusPayload();

        if (($status['status'] ?? null) === 'running' && $this->processIsRunning((int) ($status['pid'] ?? 0))) {
            return $this->redirectWithMessage(
                $redirectTo,
                'info',
                'The UAT seeder is already running. Wait a few minutes, then refresh the dashboard.',
            );
        }

        $this->ensureMaintenanceDirectory();
        File::put($this->logPath(), '[' . now()->toDateTimeString() . "] Starting UAT seeder...\n");

        $pid = $this->startSeederProcess();

        $this->writeStatus([
            'status' => 'running',
            'pid' => $pid,
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'message' => 'UAT seeder is running in the background.',
        ]);

        Cache::flush();

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'UAT seeder started. It may take a few minutes before all students and clearances appear.',
        );
    }

    public function uatSeederStatus(): JsonResponse
    {
        $status = $this->statusPayload();

        if (($status['status'] ?? null) === 'running' && ! $this->processIsRunning((int) ($status['pid'] ?? 0))) {
            $log = $this->logTail();
            $failed = str_contains($log, 'Exception')
                || str_contains($log, 'ERROR')
                || str_contains($log, 'failed');

            $status = array_merge($status, [
                'status' => $failed ? 'failed' : 'finished',
                'finished_at' => now()->toIso8601String(),
                'message' => $failed
                    ? 'Seeder stopped with an error. Check the log output below.'
                    : 'Seeder finished. Refresh dashboard counts if they still look old.',
            ]);

            $this->writeStatus($status);
            Cache::flush();
        }

        return response()->json(array_merge($status, [
            'log_tail' => $this->logTail(),
        ]));
    }

    private function startSeederProcess(): ?int
    {
        $artisan = base_path('artisan');
        $logPath = $this->logPath();
        $class = 'Database\\Seeders\\UatDatabaseSeeder';

        if (PHP_OS_FAMILY === 'Windows') {
            $command = sprintf(
                'start /B "" %s %s db:seed --class=%s --force >> %s 2>&1',
                escapeshellarg(PHP_BINARY),
                escapeshellarg($artisan),
                escapeshellarg($class),
                escapeshellarg($logPath),
            );

            pclose(popen($command, 'r'));

            return null;
        }

        $command = sprintf(
            'cd %s && %s %s db:seed --class=%s --force >> %s 2>&1 & echo $!',
            escapeshellarg(base_path()),
            escapeshellarg(PHP_BINARY),
            escapeshellarg($artisan),
            escapeshellarg($class),
            escapeshellarg($logPath),
        );

        $output = [];
        exec('sh -c ' . escapeshellarg($command), $output);

        return isset($output[0]) ? (int) $output[0] : null;
    }

    private function processIsRunning(int $pid): bool
    {
        if ($pid <= 0 || ! function_exists('posix_kill')) {
            return false;
        }

        return posix_kill($pid, 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function statusPayload(): array
    {
        $path = storage_path(self::STATUS_FILE);

        if (! File::exists($path)) {
            return [
                'status' => 'idle',
                'pid' => null,
                'started_at' => null,
                'finished_at' => null,
                'message' => 'Seeder has not been started from the web dashboard.',
            ];
        }

        $payload = json_decode((string) File::get($path), true);

        return is_array($payload) ? $payload : [
            'status' => 'unknown',
            'pid' => null,
            'started_at' => null,
            'finished_at' => null,
            'message' => 'Seeder status could not be read.',
        ];
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function writeStatus(array $status): void
    {
        $this->ensureMaintenanceDirectory();

        File::put(storage_path(self::STATUS_FILE), json_encode($status, JSON_PRETTY_PRINT));
    }

    private function logTail(): string
    {
        $path = $this->logPath();

        if (! File::exists($path)) {
            return '';
        }

        $contents = (string) File::get($path);

        return strlen($contents) > 6000 ? substr($contents, -6000) : $contents;
    }

    private function logPath(): string
    {
        return storage_path(self::LOG_FILE);
    }

    private function ensureMaintenanceDirectory(): void
    {
        File::ensureDirectoryExists(storage_path('app/maintenance'));
        File::ensureDirectoryExists(storage_path('logs'));
    }
}
