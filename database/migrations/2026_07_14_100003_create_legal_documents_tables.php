<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['terms', 'privacy']);
            $table->string('version_number');
            $table->longText('content_text');
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['document_type', 'is_active']);
        });

        Schema::create('user_legal_agreements', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->unsignedBigInteger('legal_document_id');
            $table->timestamp('accepted_at')->useCurrent();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('user')->onDelete('cascade');
            $table->foreign('legal_document_id')->references('id')->on('legal_documents')->onDelete('cascade');
            $table->unique(['user_id', 'legal_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_legal_agreements');
        Schema::dropIfExists('legal_documents');
    }
};
