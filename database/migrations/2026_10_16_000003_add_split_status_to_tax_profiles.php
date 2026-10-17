<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tax_profiles') && ! Schema::hasColumn('tax_profiles', 'is_split_from_large_enterprise')) {
            Schema::table('tax_profiles', function (Blueprint $table): void {
                $table->boolean('is_split_from_large_enterprise')->nullable()->after('eligible_small_enterprise');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tax_profiles') && Schema::hasColumn('tax_profiles', 'is_split_from_large_enterprise')) {
            Schema::table('tax_profiles', function (Blueprint $table): void {
                $table->dropColumn('is_split_from_large_enterprise');
            });
        }
    }
};
