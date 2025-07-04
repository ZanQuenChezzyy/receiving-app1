<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class Chezzy extends Command
{
    protected $signature = 'chezzy';
    protected $description = 'Setup LaravelChezzy Starter Kits';

    public function handle()
    {
        $this->line("\033[31m     __                                __________                        ");
        $this->line("    / /   ____ __________ __   _____  / / ____/ /_  ___  ________  __  __");
        $this->line("   / /   / __ `/ ___/ __ `/ | / / _ \/ / /   / __ \/ _ \/_  /_  / / / / /");
        $this->line("  / /___/ /_/ / /  / /_/ /| |/ /  __/ / /___/ / / /  __/ / /_/ /_/ /_/ / ");
        $this->line(" /_____/\__,_/_/   \__,_/ |___/\___/_/\____/_/ /_/\___/ /___/___/\__, /  ");
        $this->line("                                                                /____/   \033[0m");
        $this->line("\033[37m By \033[31mAndereyan Muhammat\033[0m");
        $this->newLine();

        do {
            $appName = trim($this->ask("\033[36mMasukkan nama aplikasi yang ingin dibuat\033[0m"));

            if (empty($appName)) {
                $this->warn("\033[31m Nama aplikasi tidak boleh kosong. Silakan masukkan kembali.\033[0m");
                continue;
            }

            $dbDatabase = strtolower(str_replace(' ', '_', $appName));

            $confirmed = $this->confirm("\033[36mApakah Anda yakin dengan nama aplikasi \033[37m[\033[0m\033[33m$appName\033[33m\033[37m]\033[0m ?\033[0m\033[33m", true);

            if (!$confirmed) {
                $this->warn("\033[31m Silakan masukkan nama aplikasi lagi.\033[0m");
            }

        } while (empty($appName) || !$confirmed);

        $this->updateEnv($appName, $dbDatabase);

        if (!$this->confirm("\033[33mApakah Anda ingin melanjutkan setup\033[0m ?\033[33m", true)) {
            $this->warn('Proses setup dibatalkan.');
            return;
        }

        $tasks = [
            'Menyalin .env & generate key' => fn() => $this->setupEnv(),
            'Menginstall NPM dependencies & build' => fn() => $this->runProcess(['npm', 'install', '&&', 'npm', 'run', 'build']),
        ];

        $messages = [
            'Menyalin .env & generate key' => ['<fg=cyan>Berhasil Menyalin .env & generate key.</>', '<fg=yellow>.env & Key sudah ada, tidak perlu diubah.</>'],
            'Menginstall NPM dependencies & build' => ['<fg=cyan>Berhasil Menginstall NPM dependencies & build.</>', '<fg=yellow>Dependencies npm sudah ada, tidak perlu diulang.</>'],
        ];

        $totalTasks = count($tasks);
        $progressWidth = 30;
        $index = 0;

        $this->output->write("\033[?25l");

        $results = [];

        foreach ($tasks as $task => $callback) {
            $index++;

            $progressBar = str_repeat('=', intval(($index / $totalTasks) * $progressWidth)) . ">" . str_repeat('-', $progressWidth - intval(($index / $totalTasks) * $progressWidth));
            $progressText = " $index/$totalTasks [$progressBar]  " . intval(($index / $totalTasks) * 100) . "% <fg=yellow>$task!</>";

            $this->output->write("\033[2K\r" . str_pad($progressText, 100));

            $status = $callback();

            $message = $messages[$task][$status ? 0 : 1];

            $results[] = (!$status ? "<fg=yellow>(Warning)</> " : "<fg=cyan>(success)</> ") . "$index/$totalTasks $message";

            $progressText = " $index/$totalTasks [$progressBar]  " . intval(($index / $totalTasks) * 100) . "%";

            $this->output->write("\033[2K\r" . str_pad($progressText, 100));
        }

        $this->output->write("\033[?25h");
        $this->newLine(2);
        $this->info(" \033[37mHasil Proses Setup\033[0m :");
        foreach ($results as $result) {
            $this->line(" $result");
        }

        $this->newLine();
        $this->info(" \033[36mKonfigurasi Selesai, \033[31m$appName\033[0m \033[36msiap digunakan!\033[0m");
    }

    private function setupEnv(): bool
    {
        if (!File::exists(base_path('.env'))) {
            File::copy(base_path('.env.example'), base_path('.env'));
            $this->callSilent('key:generate');
            $this->callSilent('storage:link');
            return true;
        }
        return false;
    }

    private function updateEnv(string $appName, string $dbDatabase): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        // Tentukan file mana yang akan diubah
        $filePath = File::exists($envPath) ? $envPath : $envExamplePath;

        if (!File::exists($filePath)) {
            $this->error("File $filePath tidak ditemukan.");
            return;
        }

        $envContent = File::get($filePath);

        $envContent = preg_replace('/^APP_NAME=.*/m', "APP_NAME=\"$appName\"", $envContent);
        $envContent = preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE=$dbDatabase", $envContent);

        File::put($filePath, $envContent);
    }

    private function runProcess(array $command): bool
    {
        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        return $process->isSuccessful();
    }
}
