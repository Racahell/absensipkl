<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departments') && ! Schema::hasColumn('departments', 'is_deleted')) {
            Schema::table('departments', function (Blueprint $table): void {
                $table->boolean('is_deleted')->default(false)->after('name');
            });
        }

        if (Schema::hasTable('school_classes') && ! Schema::hasColumn('school_classes', 'is_deleted')) {
            Schema::table('school_classes', function (Blueprint $table): void {
                $table->boolean('is_deleted')->default(false)->after('department_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'is_deleted')) {
            Schema::table('departments', function (Blueprint $table): void {
                $table->dropColumn('is_deleted');
            });
        }

        if (Schema::hasTable('school_classes') && Schema::hasColumn('school_classes', 'is_deleted')) {
            Schema::table('school_classes', function (Blueprint $table): void {
                $table->dropColumn('is_deleted');
            });
        }
    }
};
