<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefferalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create('refferals', function (Blueprint $table) {
////            $table->id();
////            $table->integer('parent_id')->nullable();
////            $table->integer('child_id')->nullable();
////            $table->integer('level')->nullable();
////            $table->timestamps();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('refferals');
    }
}
