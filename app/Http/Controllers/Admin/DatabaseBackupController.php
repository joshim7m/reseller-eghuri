<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    private string $disk = 'local';

    public function index(): Response
    {
        $backups = collect(Storage::disk($this->disk)->files('backups'))
            ->map(fn (string $path) => [
                'name' => basename($path),
                'size' => Storage::disk($this->disk)->size($path),
                'created_at' => Storage::disk($this->disk)->lastModified($path),
            ])
            ->sortByDesc('created_at')
            ->values();

        return Inertia::render('Admin/Settings/DatabaseBackup', [
            'backups' => $backups,
        ]);
    }

    public function store(): RedirectResponse
    {
        $filename = 'reseller_'.now()->format('Y-m-d_His').'.sql';
        $disk = Storage::disk($this->disk);
        $disk->makeDirectory('backups');

        $path = $disk->path('backups/'.$filename);

        $connection = config('database.connections.'.config('database.default'));

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s 2>/dev/null > %s',
            escapeshellarg($connection['host']),
            escapeshellarg($connection['port']),
            escapeshellarg($connection['username']),
            escapeshellarg($connection['database']),
            escapeshellarg($path),
        );

        if (! empty($connection['password'])) {
            $command = sprintf(
                'mysqldump -h %s -P %s -u %s -p%s %s 2>/dev/null > %s',
                escapeshellarg($connection['host']),
                escapeshellarg($connection['port']),
                escapeshellarg($connection['username']),
                escapeshellarg($connection['password']),
                escapeshellarg($connection['database']),
                escapeshellarg($path),
            );
        }

        $result = Process::forever()->run($command);

        if ($result->failed() || ! $disk->exists('backups/'.$filename)) {
            return back()->withErrors(['backup' => 'Backup failed. Please check server logs.']);
        }

        return back()->with('success', 'Backup "'.$filename.'" created successfully.');
    }

    public function download(string $filename): StreamedResponse
    {
        abort_unless(Storage::disk($this->disk)->exists('backups/'.$filename), 404);

        return Storage::disk($this->disk)
            ->download('backups/'.$filename, $filename, ['Content-Type' => 'application/sql']);
    }

    public function destroy(string $filename): RedirectResponse
    {
        abort_unless(Storage::disk($this->disk)->exists('backups/'.$filename), 404);

        Storage::disk($this->disk)->delete('backups/'.$filename);

        return back()->with('success', 'Backup "'.$filename.'" deleted.');
    }
}
