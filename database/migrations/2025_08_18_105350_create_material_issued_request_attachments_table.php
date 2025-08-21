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
        Schema::create('material_issued_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_issued_request_id');
            $table->string('file_path'); // simpan path file
            $table->string('file_name')->nullable(); // nama asli file
            $table->timestamps();

            $table->foreign('material_issued_request_id', 'fk_mir_attachment')
                ->references('id')
                ->on('material_issued_requests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_issued_request_attachments');
    }
};
