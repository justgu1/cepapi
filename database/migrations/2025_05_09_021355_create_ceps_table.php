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
        Schema::create('ceps', function (Blueprint $table) {
            $table->id();
            $table->string('cep')->unique();
            $table->string('logradouro')->nullable();
            $table->string('complemento')->nullable();
            $table->string('unidade')->nullable();
            $table->string('bairro')->nullable();
            $table->string('localidade')->nullable();
            $table->string('uf')->nullable();
            $table->string('estado')->nullable();
            $table->string('regiao')->nullable();
            $table->string('ibge')->nullable();
            $table->string('gia')->nullable();
            $table->string('ddd')->nullable();
            $table->string('siafi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ceps');
    }
};
