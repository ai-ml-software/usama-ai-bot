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
        // check if the table exists first
        if (!Schema::hasTable('withdrawal_viewers')) {
            Schema::create('withdrawal_viewers', function (Blueprint $table) {
                $table->id();
                $table->string('amount');
                $table->string('hash');
                $table->string('wallet');
                $table->string('timestamp');
                $table->longText('explorer');
                $table->string('code');
                $table->string('next_time')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_viewers');
    }
};
