<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Cep;

class CepControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $base_url;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_url = env('CEP_API_URI');
    }

    public function test_inspect_function_returns_existing_cep(): void
    {
        $cep = '12345-678';

        Cep::create([
            'cep' => $cep,
            'logradouro' => 'Rua Teste',
            'bairro' => 'Centro',
            'localidade' => 'Cidade',
            'uf' => 'SP'
        ]);

        $response = $this->getJson("/api/cep/$cep");

        $response->assertOk()
            ->assertJsonFragment([
                'cep' => $cep,
                'logradouro' => 'Rua Teste'
            ]);
    }

    public function test_inspect_function_fetches_and_stores_new_cep(): void
    {
        $cep = '01001000';
        $cepFormatted = '01001-000';

        // Fake external HTTP response
        Http::fake([
            "$this->base_url/ws/$cep/json/" => Http::response([
                'cep' => $cepFormatted,
                'logradouro' => 'Praça da Sé',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ], 200)
        ]);

        $response = $this->getJson("/api/cep/$cep");

        $response->assertOk()
            ->assertJsonFragment([
                'cep' => $cepFormatted,
                'logradouro' => 'Praça da Sé',
            ]);

        $this->assertDatabaseHas('ceps', [
            'cep' => $cepFormatted,
            'logradouro' => 'Praça da Sé',
        ]);
    }

    public function test_inspect_function_returns_404_for_invalid_cep(): void
    {
        $cep = '00000000';

        // Fake failed response
        Http::fake([
            "$this->base_url/ws/$cep/json/" => Http::response([], 404)
        ]);

        $response = $this->getJson("/api/cep/$cep");

        $response->assertStatus(404)
            ->assertJson(['message' => 'ZIP code not found or invalid']);
    }
}
