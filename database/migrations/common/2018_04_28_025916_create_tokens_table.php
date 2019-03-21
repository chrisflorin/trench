<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('token_type_id');
            $table->string('token');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('token_type_id')->references('id')->on('token_types');

            $table->unique('token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tokens');
    }
}
