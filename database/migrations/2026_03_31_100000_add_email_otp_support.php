<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'use_email_otp')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('use_email_otp')->default(false)->after('use_totp');
            });
        }

        if (!Schema::hasTable('email_otp_tokens')) {
            Schema::create('email_otp_tokens', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->string('token_hash');
                $table->timestamp('expires_at');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otp_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('use_email_otp');
        });
    }
};
