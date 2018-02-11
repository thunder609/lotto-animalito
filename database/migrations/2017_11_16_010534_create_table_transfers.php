<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableTransfers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ticket_id')->unsigned();
            $table->integer('from_id')->unsigned();
            $table->integer('to_id')->unsigned();
            $table->string('references', 30);
            $table->foreign('ticket_id')->references('id')->on('tickets');
            $table->foreign('from_id')->references('id')->on('banks');
            $table->foreign('to_id')->references('id')->on('banks');
            $table->float('amount');
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfers');
    }
}
