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
        Schema::create('return_delivery_to_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id')->constrained('delivery_order_receipts')->cascadeOnDelete()->name('fk_rdtv_details_do');
            $table->date('tanggal_terbit');
            $table->string('code', 50);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_delivery_to_vendors');
    }
};
