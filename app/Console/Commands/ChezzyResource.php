<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ChezzyResource extends Command
{
    protected $signature = 'chezzy:resource';
    protected $description = 'Generate Filament resource for all models except specified ones';

    public function handle()
    {
        // Tanya jenis resource yang ingin dibuat
        $options = ['default' => 'Default', 'simple' => 'Simple'];

        do {
            $selectedOption = strtolower($this->ask(
                "\033[36mResource jenis apa yang ingin anda buat?\033[0m \033[37m[\033[0m\033[33msimple\033[37m] atau\033[0m",
                'default' // Nilai default jika user hanya tekan Enter
            ));

            if (!isset($options[$selectedOption])) {
                $this->info("\033[31m Opsi tidak tersedia, silakan masukkan ulang.\033[0m");
            }

        } while (!isset($options[$selectedOption])); // Ulangi jika input tidak valid

        // Ambil semua model di dalam folder app/Models
        $modelsPath = app_path('Models');

        if (!File::isDirectory($modelsPath)) {
            $this->error(" Folder 'app/Models' tidak ditemukan.");
            return;
        }

        $modelFiles = File::files($modelsPath);
        if (empty($modelFiles)) {
            $this->warn(" Tidak ada file model di folder 'app/Models'.");
            return;
        }

        $models = collect($modelFiles)
            ->map(fn($file) => pathinfo($file, PATHINFO_FILENAME))
            ->toArray();

        // Model yang selalu dikecualikan
        $alwaysExcludedModels = ['User', 'Role', 'Permission'];
        $modelsToProcess = array_diff($models, $alwaysExcludedModels);

        if (empty($modelsToProcess)) {
            $this->warn(" Saat ini tidak ada model yang tersedia untuk dibuatkan resource.");
            return;
        }

        // Tanya apakah ada model tambahan yang ingin dikecualikan
        $excludedModels = [];
        if ($this->confirm("\033[36mApakah ada model yang ingin dikecualikan\033[0m \033[37m?\033[0m", false)) {
            $excludedModels = $this->askExcludedModels($modelsToProcess);
        }

        $modelsToProcess = array_diff($modelsToProcess, $excludedModels);

        // Tanya apakah model yang dikecualikan ingin dibuat resource simple
        $simpleModels = [];
        if (!empty($excludedModels) && $this->confirm("\033[36mApakah model yang dikecualikan ingin dibuat resource\033[0m [\033[33msimple\033[0m] ?", false)) {
            $simpleModels = $excludedModels;
        }

        // Tampilkan daftar model yang akan dibuat resource
        if (!empty($modelsToProcess)) {
            $this->info("\033[37m Model yang akan dibuat resource\033[0m [\033[33mdefault\033[0m] :");
            foreach ($modelsToProcess as $index => $model) {
                $this->line(" [\033[33m$index\033[0m] \033[33m$model\033[0m");
            }
        }

        $this->newLine();

        if (!empty($simpleModels)) {
            $this->info("\033[37m Model yang akan dibuat resource\033[0m [\033[33mSimple\033[0m] :");
            foreach ($simpleModels as $index => $model) {
                $this->line(" [\033[33m$index\033[0m] \033[33m$model\033[0m");
            }
        }

        // Konfirmasi sebelum eksekusi
        if (!$this->confirm("\033[36mLanjutkan proses pembuatan resource\033[0m ?", true)) {
            $this->error("Proses dibatalkan oleh pengguna.");
            return;
        }

        // Proses pembuatan resource dengan progress bar
        $totalModels = count($modelsToProcess) + count($simpleModels);
        $progress = 0;

        foreach ($modelsToProcess as $model) {
            $this->generateResource($model, 'default');
            $progress++;
            $this->displayProgress($progress, $totalModels, $model);
        }

        foreach ($simpleModels as $model) {
            $this->generateResource($model, 'simple');
            $progress++;
            $this->displayProgress($progress, $totalModels, $model);
        }

        $this->newLine(2);
        $this->info("\033[36m Semua resource telah berhasil dibuat.\033[0m");
    }

    private function askExcludedModels(array $models): array
    {
        $excludedModels = [];

        while (true) {
            $this->info("\033[37m Model yang tersedia :\033[0m");
            foreach ($models as $index => $model) {
                $this->line(" [\033[33m$index\033[0m] \033[33m$model\033[0m");
            }

            $input = $this->ask("\033[36mMasukkan\033[0m [\033[33mnomor\033[0m] \033[36mmodel yang ingin dikecualikan\033[0m [\033[33mpisahkan dengan koma\033[0m]");

            if (empty($input)) {
                break;
            }

            $indexes = explode(',', $input);
            foreach ($indexes as $index) {
                $index = trim($index);
                if (is_numeric($index) && isset($models[$index])) {
                    $excludedModels[] = $models[$index];
                } else {
                    $this->error("Nomor '$index' tidak valid.");
                }
            }

            // Tanya apakah masih ingin mengecualikan model lain
            if (!$this->confirm("\033[36mApakah ada lagi yang ingin dikecualikan\033[0m ?", false)) {
                break;
            }
        }

        return $excludedModels;
    }

    private function generateResource(string $model, string $type)
    {
        $command = $type === 'simple'
            ? "php artisan make:filament-resource $model --simple --generate --view"
            : "php artisan make:filament-resource $model --generate --view";

        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (is_resource($process)) {
            fwrite($pipes[0], "1\n");
            fclose($pipes[0]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);
        }
    }

    private function displayProgress(int $current, int $total, string $model)
    {
        $progressWidth = 30; // Panjang progress bar
        $progress = ($current / $total) * 100;
        $filledBars = round(($progress / 100) * $progressWidth);
        $emptyBars = $progressWidth - $filledBars;
        $progressBar = "[" . str_repeat("=", $filledBars) . ">" . str_repeat("-", $emptyBars) . "]";

        if ($current === 1) {
            // Sembunyikan cursor pertama kali sebelum progress dimulai
            $this->output->write("\033[?25l");
        }

        if ($current === $total) {
            // Model terakhir, tampilkan status "Berhasil" dalam warna cyan
            $progressText = " $current/$total $progressBar " . intval($progress) . "% \033[36mBerhasil\033[0m";
        } else {
            // Model masih dalam proses, tampilkan status "Membuat resource" dalam warna kuning
            $progressText = " $current/$total $progressBar " . intval($progress) . "% \033[33mMembuat resource $model...\033[0m";
        }

        // Hapus baris sebelumnya dan tampilkan progress baru
        $this->output->write("\033[2K\r" . str_pad($progressText, 100));

        if ($current === $total) {
            $this->output->write("\033[?25h");
        }

        usleep(500000); // Delay untuk efek progress bar
    }
}
