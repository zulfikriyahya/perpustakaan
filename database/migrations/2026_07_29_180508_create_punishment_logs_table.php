<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('punishment_logs', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignId('user_id')->constrained();
            $table->foreignUuid('punishment_id')->constrained();
            $table->dateTime('tanggal_diterapkan');
            $table->dateTime('tanggal_berakhir')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('punishment_logs');
    }
};
