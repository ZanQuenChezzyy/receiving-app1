<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ChezzyPolicy extends Command
{
    protected $signature = 'chezzy:policy';
    protected $description = 'Generate policy files for selected models';

    /** @var array<string> */
    protected array $excludedModels = ['User', 'Permission', 'Role'];

    public function handle()
    {
        $models = $this->getAvailableModels(); // [['name' => ..., 'fqcn' => ...], ...]

        if (empty($models)) {
            return $this->error('Tidak ada model valid ditemukan di app/Models.');
        }

        // Tampilkan daftar model (tanpa duplikat, sudah diurut & reindex)
        $this->info("\nModel Tersedia:");
        foreach ($models as $i => $m) {
            $this->line(" [\033[33m{$i}\033[0m] {$m['name']}  (\033[36m{$m['fqcn']}\033[0m)");
        }

        // Pilih mode
        $mode = $this->choice(
            "\nPilih mode pembuatan policy",
            ['Semua', 'Pilih Manual'],
            0
        );

        $selected = [];

        if ($mode === 'Semua') {
            $selected = $models; // langsung semua
        } else {
            $indexes = $this->ask("Masukkan nomor model (pisahkan koma, contoh: 0,1,3)");
            $selectedIndexes = array_filter(array_map('trim', explode(',', (string) $indexes)), fn($v) => $v !== '');
            $selected = collect($selectedIndexes)
                ->filter(fn($i) => isset($models[(int) $i]))
                ->map(fn($i) => $models[(int) $i])
                ->values()
                ->all();

            if (empty($selected)) {
                return $this->error('Tidak ada model valid yang dipilih.');
            }
        }

        // Buat policy untuk setiap model terpilih
        foreach ($selected as $m) {
            $this->createPolicy($m['name'], $m['fqcn']);
        }

        $this->info("\nSelesai ✅");
    }

    /**
     * Ambil daftar model unik dari app/Models (rekursif), hanya class yang extends Model,
     * exclude User/Permission/Role, sort & reindex, siap dipakai.
     *
     * @return array<int, array{name: string, fqcn: string}>
     */
    private function getAvailableModels(): array
    {
        $modelsDir = app_path('Models');

        if (!File::exists($modelsDir)) {
            return [];
        }

        $files = File::allFiles($modelsDir);

        $items = collect($files)
            ->filter(fn($f) => $f->getExtension() === 'php')
            ->map(function ($f) use ($modelsDir) {
                $path = $f->getPathname();
                $contents = File::get($path);

                // Pastikan ini benar-benar model sederhana (heuristik)
                if (!Str::contains($contents, 'extends Model')) {
                    return null;
                }

                // Nama file tanpa .php
                $basename = Str::before($f->getFilename(), '.php');

                // FQCN berdasarkan relative path dari app/Models
                $relative = Str::after($path, $modelsDir . DIRECTORY_SEPARATOR);
                $relative = Str::before($relative, '.php');
                $relativeNs = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
                $fqcn = 'App\\Models\\' . $relativeNs;

                return [
                    'name' => $basename,
                    'fqcn' => $fqcn,
                ];
            })
            ->filter() // buang null
            // exclude model tertentu
            ->reject(fn($m) => in_array($m['name'], $this->excludedModels, true))
            // unique berdasarkan FQCN
            ->unique('fqcn')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return $items;
    }

    private function createPolicy(string $modelName, string $modelFqcn): void
    {
        $policyName = "{$modelName}Policy";
        $policyPath = app_path("Policies/{$policyName}.php");

        // Skip jika policy sudah ada
        if (File::exists($policyPath)) {
            $this->line("⏭️  Policy sudah ada, lewati: {$policyName}");
            return;
        }

        $this->info("\n📄 Membuat policy untuk model: {$modelName}...");

        // Jalankan make:policy dengan --model=FQCN agar support subfolder
        $cmd = "php artisan make:policy {$policyName} --model=\"{$modelFqcn}\"";
        $process = Process::fromShellCommandline($cmd);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Gagal membuat policy untuk model {$modelName}.");
            $this->line($process->getErrorOutput());
            return;
        }

        if (!File::exists($policyPath)) {
            $this->error("Policy file tidak ditemukan setelah make: {$policyPath}");
            return;
        }

        $this->overwritePolicyContent($modelName, $modelFqcn, $policyPath);
    }

    private function overwritePolicyContent(string $modelName, string $modelFqcn, string $path): void
    {
        $template = $this->generatePolicyContent($modelName, $modelFqcn);
        File::put($path, $template);
        $this->info("✍️  Policy {$modelName} berhasil diisi dengan template permission.");
    }

    private function generatePolicyContent(string $modelName, string $modelFqcn): string
    {
        $policyName = "{$modelName}Policy";

        // Nama model tanpa spasi (StudlyCase)
        $name = \Illuminate\Support\Str::studly($modelName);

        return <<<PHP
<?php

namespace App\Policies;

use App\Models\User;
use {$modelFqcn};
use Illuminate\Auth\Access\Response;

class {$policyName}
{
    public function viewAny(User \$user): bool
    {
        // ✅ "View Any {Model}"
        return \$user->can('View Any {$name}');
    }

    public function view(User \$user, {$modelName} \$model): bool
    {
        // ✅ "View {Model}"
        return \$user->can('View {$name}');
    }

    public function create(User \$user): bool
    {
        // ✅ "Create {Model}"
        return \$user->can('Create {$name}');
    }

    public function update(User \$user, {$modelName} \$model): bool
    {
        // ✅ "Update {Model}"
        return \$user->can('Update {$name}');
    }

    public function delete(User \$user, {$modelName} \$model): bool
    {
        // ✅ "Delete {Model}"
        return \$user->can('Delete {$name}');
    }

    public function restore(User \$user, {$modelName} \$model): bool
    {
        // ✅ "Restore {Model}"
        return \$user->can('Restore {$name}');
    }

    public function forceDelete(User \$user, {$modelName} \$model): bool
    {
        // ✅ "Force Delete {Model}"
        return \$user->can('Force Delete {$name}');
    }
}
PHP;
    }
}
