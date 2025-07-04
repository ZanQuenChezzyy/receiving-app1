<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ChezzyModel extends Command
{
    protected $signature = 'chezzy:model';
    protected $description = 'Membuat satu atau beberapa model dengan migration dan fillable default';

    public function handle()
    {
        $models = [];

        while (true) {
            $modelName = $this->ask("\033[36mMasukkan nama model yang ingin Anda buat \033[37m[\033[0m\033[33mkosongkan untuk selesai\033[0m\033[37m]\033[0m");

            if (empty($modelName)) {
                break; // Keluar dari loop jika input kosong
            }

            if (in_array($modelName, $models)) {
                $this->info("\033[31m Model '$modelName' sudah ditambahkan sebelumnya, Masukkan model lagi.\033[0m");
            } else {
                $models[] = $modelName;
            }
        }

        if (empty($models)) {
            $this->info("\033[31m Tidak ada model yang ditambahkan. Proses dibatalkan.\033[0m");
            return;
        }

        $this->info(" \033[37mModel yang akan Dibuat :\033[0m");
        foreach ($models as $index => $model) {
            $this->line(" [\033[33m" . ($index + 1) . "\033[0m] \033[33m$model\033[0m");
        }

        if (!$this->confirm("\033[36mApakah Anda ingin melanjutkan pembuatan model ini\033[0m \033[37m?", true)) {
            $this->warn("\033[31m Proses dibatalkan.\033[0m");
            return;
        }

        // Menampilkan progress bar
        $totalModels = count($models);
        $progressWidth = 30;
        $index = 0;

        $this->output->write("\033[?25l"); // Sembunyikan cursor untuk tampilan progress

        foreach ($models as $model) {
            $index++;

            // Hitung progress
            $progressBar = str_repeat('=', intval(($index / $totalModels) * $progressWidth)) . ">" . str_repeat('-', $progressWidth - intval(($index / $totalModels) * $progressWidth));
            if ($index === $totalModels) {
                $progressText = " $index/$totalModels [$progressBar]  " . intval(($index / $totalModels) * 100) . "% \033[36mBerhasil\033[0m";
            } else {
                $progressText = " $index/$totalModels [$progressBar]  " . intval(($index / $totalModels) * 100) . "% \033[33mMembuat model $model\033[0m";
            }

            $this->output->write("\033[2K\r" . str_pad($progressText, 100));

            $command = "php artisan make:model $model -m";
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $this->updateModelFile($model);
            }

            usleep(500000); // Delay untuk efek progress bar
        }

        $this->output->write("\033[?25h");

        $this->newLine(2);
        $this->info("\033[36m Model\033[0m \033[37m[\033[0m\033[36m" . implode(', ', $models) . "\033[0m\033[37m]\033[0m \033[36mberhasil dibuat.\033[0m");
    }

    private function updateModelFile(string $model)
    {
        $modelFilePath = app_path("Models/{$model}.php");

        if (file_exists($modelFilePath)) {
            $template = file_get_contents(app_path('Console/Commands/Template/ModelTemplate.stub'));
            $modelContent = str_replace('{model}', $model, $template);
            file_put_contents($modelFilePath, $modelContent);
        }
    }
}
