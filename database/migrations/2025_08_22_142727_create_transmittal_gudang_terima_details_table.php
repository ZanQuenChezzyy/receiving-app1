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
        Schema::create('transmittal_gudang_terima_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transmittal_gudang_terima_id');
            $table->unsignedBigInteger('transmittal_gudang_kirim_detail_id');
            $table->integer('qty_diterima');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('transmittal_gudang_terima_id', 'fk_tgtd_tgt')
                ->references('id')
                ->on('transmittal_gudang_terimas')
                ->onDelete('cascade');

            $table->foreign('transmittal_gudang_kirim_detail_id', 'fk_tgtd_tgkd')
                ->references('id')
                ->on('transmittal_gudang_kirim_details')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transmittal_gudang_terima_details');
    }
};
