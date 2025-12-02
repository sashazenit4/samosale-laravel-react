<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCorporateToPaymentTypeEnum extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Для PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE {$this->getTable()} ALTER COLUMN payment_type TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE {$this->getTable()} ALTER COLUMN payment_type SET DEFAULT 'cash'");
            DB::statement("ALTER TABLE {$this->getTable()} ADD CONSTRAINT payment_type_check CHECK (payment_type IN ('cash', 'cashless', 'mixed', 'corporate'))");
        }
        // Для MySQL
        elseif (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE {$this->getTable()} MODIFY COLUMN payment_type ENUM('cash', 'cashless', 'mixed', 'corporate') NOT NULL DEFAULT 'cash'");
        }
        // Для SQLite (требуется создание новой таблицы)
        else {
            // Создаем временную таблицу
            Schema::create('table_temp', function (Blueprint $table) {
                $table->increments('id');
                $table->enum('payment_type', ['cash', 'cashless', 'mixed', 'corporate'])->default('cash');
                // Добавьте другие колонки вашей таблицы
            });

            // Копируем данные
            DB::statement('INSERT INTO table_temp SELECT * FROM ' . $this->getTable());

            // Удаляем старую таблицу
            Schema::dropIfExists($this->getTable());

            // Переименовываем временную таблицу
            Schema::rename('table_temp', $this->getTable());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Для PostgreSQL
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Проверяем, нет ли записей с типом 'corporate'
            $hasCorporate = DB::table($this->getTable())
                ->where('payment_type', 'corporate')
                ->exists();

            if ($hasCorporate) {
                throw new \Exception('Cannot rollback migration: there are records with payment_type = "corporate"');
            }

            DB::statement("ALTER TABLE {$this->getTable()} ALTER COLUMN payment_type TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE {$this->getTable()} ALTER COLUMN payment_type SET DEFAULT 'cash'");
            DB::statement("ALTER TABLE {$this->getTable()} ADD CONSTRAINT payment_type_check CHECK (payment_type IN ('cash', 'cashless', 'mixed'))");
        }
        // Для MySQL
        elseif (DB::connection()->getDriverName() === 'mysql') {
            // Проверяем, нет ли записей с типом 'corporate'
            $hasCorporate = DB::table($this->getTable())
                ->where('payment_type', 'corporate')
                ->exists();

            if ($hasCorporate) {
                throw new \Exception('Cannot rollback migration: there are records with payment_type = "corporate"');
            }

            DB::statement("ALTER TABLE {$this->getTable()} MODIFY COLUMN payment_type ENUM('cash', 'cashless', 'mixed') NOT NULL DEFAULT 'cash'");
        }
        // Откат для SQLite сложнее, обычно не реализуется полностью
        else {
            throw new \Exception('Rollback for SQLite is not implemented for this migration');
        }
    }

    /**
     * Получить имя таблицы
     */
    private function getTable(): string
    {
        return 'payments'; // Замените на имя вашей таблицы
    }
}
