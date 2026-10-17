<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'customer_email')) {
                $table->string('customer_email')->nullable();
            }
            if (! Schema::hasColumn('orders', 'guest_access_token_hash')) {
                $table->char('guest_access_token_hash', 64)->nullable()->unique();
            }
            if (! Schema::hasColumn('orders', 'guest_access_token_expires_at')) {
                $table->timestamp('guest_access_token_expires_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'guest_access_token_expires_at')) {
                $table->dropColumn('guest_access_token_expires_at');
            }
            if (Schema::hasColumn('orders', 'guest_access_token_hash')) {
                $table->dropUnique(['guest_access_token_hash']);
                $table->dropColumn('guest_access_token_hash');
            }
            if (Schema::hasColumn('orders', 'customer_email')) {
                $table->dropColumn('customer_email');
            }
        });
    }
};
