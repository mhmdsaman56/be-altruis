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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('content_type', ['post', 'answer', 'comment','article']);
            $table->longText('body');
            $table->foreignId('parent_id')->nullable()->constrained('contents')->onDelete('cascade');
            $table->string('slug')->unique()->nullable();
            $table->timestamps();
        });
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('contents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['like', 'dislike', 'agree', 'disagree', 'helpful', 'unhelpful', 'upvote', 'downvote']);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
        Schema::dropIfExists('reactions');
    }
};
