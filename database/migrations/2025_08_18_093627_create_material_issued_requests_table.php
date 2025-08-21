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
        Schema::create('material_issued_requests', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('mir_no', 50)->unique();
            $table->string('department', 100);
            $table->string('used_for', 100);
            $table->string('requested_by', 45);
            $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cost_center', 50)->nullable();
            $table->string('jor_no', 50)->nullable();
            $table->string('equipment_no', 50)->nullable();
            $table->string('reservation_no', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('purchase_order_terbit_id')->constrained('purchase_order_terbits')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_issued_requests');
    }
};
