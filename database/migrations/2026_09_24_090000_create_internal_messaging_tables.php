<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('businesses', 'contact_channel')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->string('contact_channel', 24)->default('merkamigo')->after('whatsapp_number');
            });
        }

        DB::table('businesses')
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '')
            ->update(['contact_channel' => 'whatsapp']);

        if (! Schema::hasTable('business_conversations')) {
            Schema::create('business_conversations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_id')->constrained()->cascadeOnDelete();
                $table->foreignId('customer_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('context_key', 100)->default('business');
                $table->string('context_type', 24)->nullable();
                $table->unsignedBigInteger('context_id')->nullable();
                $table->string('context_label')->nullable();
                $table->string('context_url', 2048)->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();

                $table->unique(['business_id', 'customer_user_id', 'context_key'], 'business_conversations_unique_context');
                $table->index(['business_id', 'last_message_at']);
                $table->index(['customer_user_id', 'last_message_at']);
            });
        }

        if (! Schema::hasTable('business_messages')) {
            Schema::create('business_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('business_conversation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['business_conversation_id', 'created_at']);
                $table->index(['sender_user_id', 'read_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_messages');
        Schema::dropIfExists('business_conversations');

        if (Schema::hasColumn('businesses', 'contact_channel')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn('contact_channel');
            });
        }
    }
};
