<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('form');     // bot | form
            $table->text('content');                      // l'affirmation / question à vérifier
            $table->string('contact')->nullable();        // email/tel facultatif du signaleur
            $table->string('status')->default('new');     // new | reviewing | published | dismissed
            $table->foreignId('verification_id')->nullable()->constrained('verifications')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
