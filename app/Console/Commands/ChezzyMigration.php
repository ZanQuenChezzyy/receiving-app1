<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ChezzyMigration extends Command
{
    protected $signature = 'chezzy:migration';
    protected $description = 'Menjalankan migrasi database dan seeder';

    public function handle()
    {
        $this->info("Menjalankan migrasi database...");
        $migrate = $this->callSilent('migrate', ['--force' => true]);

        if ($migrate === 0) {
            $this->info("✔️  Migrasi database berhasil.");
        } else {
            $this->error("❌  Migrasi gagal.");
            return;
        }

        $this->info("Menjalankan seeder...");
        $seeder = $this->callSilent('db:seed', ['--class' => 'UserRolePermissionSeeder']);

        if ($seeder === 0) {
            $this->info("✔️  Seeder berhasil dijalankan.");
        } else {
            $this->error("❌  Seeder gagal.");
        }

        $this->info("✅ Proses migrasi & seeder selesai!");
    }
}
