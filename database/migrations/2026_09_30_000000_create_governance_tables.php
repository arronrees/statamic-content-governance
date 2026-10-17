<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('governable_type');
            $table->string('governable_id');
            $table->string('site');
            $table->string('collection');
            $table->string('origin_id')->nullable()->index();
            $table->string('owner_id')->nullable()->index();
            $table->unsignedInteger('review_interval_days')->nullable();
            $table->dateTime('last_reviewed_at')->nullable();
            $table->string('last_reviewed_by')->nullable();
            $table->dateTime('next_review_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->string('status');
            $table->json('flags');
            $table->dateTime('next_action_at')->nullable()->index();

            $table->unique(['governable_type', 'governable_id', 'site']);
            $table->index(['status', 'site']);
        });

        Schema::create('governance_audit_events', function (Blueprint $table) {
            $table->id('sequence');
            $table->uuid('id')->unique();
            $table->uuid('record_id')->index();
            $table->string('governable_id')->index();
            $table->string('site');
            $table->string('type');
            $table->string('actor_id')->nullable();
            $table->json('payload');
            $table->dateTime('created_at', precision: 6)->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_audit_events');
        Schema::dropIfExists('governance_records');
    }
};
