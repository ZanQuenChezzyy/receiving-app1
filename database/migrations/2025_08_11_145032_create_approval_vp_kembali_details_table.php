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
        Schema::create('approval_vp_kembali_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_vp_kirim_id')->constrained('approval_vp_kirims')->cascadeOnDelete();
            $table->foreignId('approval_vp_kembali_id')->constrained('approval_vp_kembalis')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('status', 8); // 105, 124, 105 & 124
            $table->unsignedMediumInteger('total_item');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_vp_kembali_details');
    }
};
