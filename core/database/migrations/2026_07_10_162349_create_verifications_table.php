<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('claim');                       // l'affirmation vérifiée
            $table->string('rating');                    // true|false|misleading|unproven
            $table->text('summary');                     // verdict court
            $table->longText('body')->nullable();        // article complet
            $table->string('category')->nullable();      // santé, gouvernance, image...
            $table->foreignId('personality_id')->nullable()->constrained('personalities')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');  // draft|published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
