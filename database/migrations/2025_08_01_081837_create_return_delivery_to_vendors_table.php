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
            $table->unsignedBigInteger('delivery_order_receipt_id');
            $table->date('tanggal_terbit');
            $table->string('code', 50);
            $table->string('code_124', 20);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('delivery_order_receipt_id', 'fk_rdtv_details_do')
                ->references('id')
                ->on('delivery_order_receipts')
                ->onDelete('cascade');
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
