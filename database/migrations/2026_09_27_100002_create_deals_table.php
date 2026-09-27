<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            // Stored in cents so sums never drift.
            $table->unsignedBigInteger('value_cents')->default(0);
            $table->string('stage')->default('new');
            $table->unsignedInteger('position')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stage', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
