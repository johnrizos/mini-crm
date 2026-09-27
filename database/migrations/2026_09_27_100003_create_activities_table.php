<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->text('body');
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index(['contact_id', 'happened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
