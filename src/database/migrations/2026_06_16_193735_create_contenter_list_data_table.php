<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contenter_list_data', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->string('cookie', 191)->index();
            $table->string('key', 191)->index();
            $table->longText('list_data');
            $table->integer('requests')->default(0)->index();
            $table->integer('updates')->default(0)->index();
            $table->dateTime('last_request')->nullable()->index();
            $table->unique(['cookie', 'key']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contenter_list_data');
    }
};
