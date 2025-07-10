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
        Schema::create('delivery_order_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_terbit_id')->constrained('purchase_order_terbits')->cascadeOnDelete();
            $table->string('nomor_do', 15);
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->date('received_date');
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('tahapan', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_receipts');
    }
};
