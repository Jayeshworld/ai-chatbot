<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_requests', function (Blueprint $table) {
            $table->string('name')->nullable()->change();        // allow pre-OTP partial record
            $table->string('otp', 6)->nullable()->after('message');
            $table->timestamp('otp_expires_at')->nullable()->after('otp');
            $table->unsignedTinyInteger('otp_attempts')->default(0)->after('otp_expires_at');
            $table->timestamp('email_verified_at')->nullable()->after('otp_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('registration_requests', function (Blueprint $table) {
            $table->dropColumn(['otp', 'otp_expires_at', 'otp_attempts', 'email_verified_at']);
            $table->string('name')->nullable(false)->change();
        });
    }
};
