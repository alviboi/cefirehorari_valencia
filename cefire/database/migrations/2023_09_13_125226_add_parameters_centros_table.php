<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParametersCentrosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('centres', function (Blueprint $table) {
            //
            $table->boolean("2anys");
            $table->boolean("PROA");
            $table->boolean("UECO");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('centres', function (Blueprint $table) {
            //
        });
    }
}
