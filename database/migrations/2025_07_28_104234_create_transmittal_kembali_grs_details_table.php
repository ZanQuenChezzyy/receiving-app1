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
        Schema::create('transmittal_kembali_grs_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transmittal_kembali_grs_id');
            $table->foreignId('transmittal_kirim_grs_id');
            $table->foreignId('do_receipt_detail_id');

            $table->string('code', 50);
            $table->string('code_105', 15);
            $table->unsignedMediumInteger('total_item');
            $table->timestamps();

            // Manual constraint name supaya tidak terlalu panjang
            $table->foreign('transmittal_kembali_grs_id', 'fk_kembali_grs')
                ->references('id')
                ->on('transmittal_kembali_grs')
                ->onDelete('cascade');

            $table->foreign('transmittal_kirim_grs_id', 'fk_kirim_grs')
                ->references('id')
                ->on('transmittal_kirim_grs')
                ->onDelete('cascade');

            $table->foreign('do_receipt_detail_id', 'fk_do_receipt_detail')
                ->references('id')
                ->on('delivery_order_receipt_details')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transmittal_kembali_grs_details');
    }
};
