<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('cmr_notifications',function(Blueprint$table){$table->id();$table->foreignId('cmr_document_id')->constrained('cmr_documents')->cascadeOnDelete();$table->string('type',50);$table->string('channel',20)->default('sms');$table->string('recipient',30);$table->string('status',20)->default('pending')->index();$table->unsignedSmallInteger('attempts')->default(0);$table->text('message');$table->string('download_url',2048)->nullable();$table->json('metadata')->nullable();$table->text('last_error')->nullable();$table->dateTime('sent_at')->nullable();$table->timestamps();$table->unique(['cmr_document_id','type','recipient'],'cmr_notify_doc_type_recipient_uq');}); }
    public function down(): void { Schema::dropIfExists('cmr_notifications'); }
};
