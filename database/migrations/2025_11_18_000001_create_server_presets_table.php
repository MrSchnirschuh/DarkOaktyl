<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServerPresetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // If a previous failed attempt left the table present, drop it so migration
        // can recreate the table cleanly. This is safe for dev environments.
        Schema::dropIfExists('server_presets');

        Schema::create('server_presets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedSmallInteger('port_start')->nullable();
            $table->unsignedSmallInteger('port_end')->nullable();
            $table->enum('visibility', ['global', 'private'])->default('global');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->json('naming')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('server_presets');
    }
}
