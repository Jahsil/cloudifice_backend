<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ReplicateFile implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $username,
        public string $filePath
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $servers = explode(',', env('SECONDARY_SERVERS'));
        // $localPath = '/home'. '/' . $this->username . '/' . $this->filePath;
        
        foreach ($servers as $server) {
            $this->copyToServer($server, $this->filePath);
        }
    }

    protected function copyToServer(string $server, string $localPath): void
    {
        $remoteDir = env('SERVER_STORAGE_PATH') . '/' . $this->username;
        $remotePath = $remoteDir . '/' . basename($this->filePath);

        try {
            // 1. Ensure remote directory exists
            $mkdirProcess = new Process([
                'ssh',
                '-i', env('SERVER_SSH_KEY_PATH'),
                env('SERVER_USER') . '@' . $server,
                'mkdir -p ' . escapeshellarg($remoteDir)
            ]);
            $mkdirProcess->setTimeout(30);
            $mkdirProcess->run();

            if (!$mkdirProcess->isSuccessful()) {
                Log::error("Failed to create directory on {$server}: " . $mkdirProcess->getErrorOutput());
                return;
            }

            // 2. Copy the file
            $scpProcess = new Process([
                'scp',
                '-i', env('SERVER_SSH_KEY_PATH'),
                $localPath,
                env('SERVER_USER') . '@' . $server . ':' . $remotePath
            ]);
            $scpProcess->setTimeout(300); // 5-minute timeout
            $scpProcess->run();

            if (!$scpProcess->isSuccessful()) {
                Log::error("Replication to {$server} failed: " . $scpProcess->getErrorOutput());
            }
        } catch (\Exception $e) {
            Log::error("Replication error to {$server}: " . $e->getMessage());
        }
    }
}
