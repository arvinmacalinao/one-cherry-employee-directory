<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('id')
                ->constrained()->nullOnDelete();
            $table->string('external_id')->nullable()->comment('Reserved for future Active Directory / Google Workspace SSO');
            $table->string('auth_provider')->default('local')->comment('local | azure_ad | google_workspace');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
            $table->dropColumn(['external_id', 'auth_provider', 'is_active', 'last_login_at']);
        });
    }
};
