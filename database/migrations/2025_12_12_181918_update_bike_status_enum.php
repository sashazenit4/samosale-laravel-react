<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Для MySQL используем MODIFY COLUMN
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bikes MODIFY status ENUM('renting', 'free', 'stolen', 'disassembly', 'repair', 'reserved') DEFAULT 'free'");
        }
        // Для PostgreSQL используем CHECK constraint
        elseif (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE bikes DROP CONSTRAINT bikes_status_check");
            DB::statement("ALTER TABLE bikes ADD CONSTRAINT bikes_status_check CHECK (status IN ('renting', 'free', 'stolen', 'disassembly', 'repair', 'reserved'))");
        }
        // Для SQLite - создаем новую таблицу
        elseif (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('bikes', function (Blueprint $table) {
                $table->dropColumn('status');
                $table->enum('status', ['renting', 'free', 'stolen', 'disassembly', 'repair', 'reserved'])
                    ->default('free')
                    ->after('frame_number');
            });
        }
        // Универсальный способ для других БД
        else {
            Schema::table('bikes', function (Blueprint $table) {
                $table->enum('status', ['renting', 'free', 'stolen', 'disassembly', 'repair', 'reserved'])
                    ->default('free')
                    ->change();
            });
        }
    }

    public function down(): void
    {
        // Возвращаем обратно к оригинальным значениям
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bikes MODIFY status ENUM('renting', 'free', 'stolen') DEFAULT 'free'");
        }
        elseif (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE bikes DROP CONSTRAINT bikes_status_check");
            DB::statement("ALTER TABLE bikes ADD CONSTRAINT bikes_status_check CHECK (status IN ('renting', 'free', 'stolen'))");
        }
        elseif (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('bikes', function (Blueprint $table) {
                $table->dropColumn('status');
                $table->enum('status', ['renting', 'free', 'stolen'])
                    ->default('free')
                    ->after('frame_number');
            });
        }
        else {
            Schema::table('bikes', function (Blueprint $table) {
                $table->enum('status', ['renting', 'free', 'stolen'])
                    ->default('free')
                    ->change();
            });
        }
    }
};
