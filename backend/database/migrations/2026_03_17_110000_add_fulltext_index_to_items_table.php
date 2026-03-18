<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            // MySQL/InnoDB full-text index for faster "contains" search on name.
            $table->fullText('name');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            $table->dropFullText(['name']);
        });
    }
};

