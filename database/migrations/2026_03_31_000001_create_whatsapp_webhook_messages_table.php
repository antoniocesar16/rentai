<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_webhook_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_instance_id')->nullable()->constrained('whatsapp_instances')->nullOnDelete();
            $table->string('instance_name')->nullable()->index();
            $table->string('event')->nullable()->index();
            $table->string('direction', 20)->index(); // inbound|outbound
            $table->string('sender_number')->nullable()->index();
            $table->string('sender_name')->nullable();
            $table->text('message_text')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_messages');
    }
};
