<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostViewersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->foreignId('user_id')->nullable();
            $table->string('ip_address', 30)->nullable();
            $table->string('http_user_agent', 130)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_viewers');
    }
}
