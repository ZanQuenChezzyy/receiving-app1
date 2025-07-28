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
            $table->foreignId('transmittal_kembali_id')->constrained('transmittal_kembali_grs')->cascadeOnDelete();
            $table->foreignId('transmittal_kirim_id')->constrained('transmittal_kirim_grs')->cascadeOnDelete();
            $table->foreignId('do_receipt_detail_id')->constrained('delivery_order_receipt_details')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('code_105', 15);
            $table->unsignedMediumInteger('total_item');
            $table->timestamps();
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
