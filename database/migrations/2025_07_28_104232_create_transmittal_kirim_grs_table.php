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
        Schema::create('transmittal_kirim_grs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50);
            $table->string('code_105', 15);
            $table->foreignId('delivery_order_receipt_id')->constrained('delivery_order_receipts')->cascadeOnDelete();
            $table->date('tanggal_kirim');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transmittal_kirim_grs');
    }
};
