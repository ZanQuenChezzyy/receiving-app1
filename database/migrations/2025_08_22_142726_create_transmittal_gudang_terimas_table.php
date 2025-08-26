<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transmittal_gudang_terimas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transmittal_gudang_kirim_id');
            $table->date('tanggal_terima');
            $table->unsignedBigInteger('diterima_oleh'); // user gudang
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('transmittal_gudang_kirim_id', 'fk_tgt_tgk')
                ->references('id')
                ->on('transmittal_gudang_kirims')
                ->onDelete('cascade');

            $table->foreign('diterima_oleh', 'fk_tgt_user_terima')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transmittal_gudang_terimas');
    }
};
