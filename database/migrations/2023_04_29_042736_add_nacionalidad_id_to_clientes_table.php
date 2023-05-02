<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('clientes', 'nacionalidad_id')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->unsignedBigInteger('nacionalidad_id')->nullable();
                $table->foreign('nacionalidad_id')->references('id')->on('nacionalidads');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('clientes', 'nacionalidad_id')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropForeign(['nacionalidad_id']);
                $table->dropColumn('nacionalidad_id');
            });
        }
    }
};
