<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\Cep;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;

class CepControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $base_url;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_url = env('CEP_API_URI');
    }

    #[Test]
    public function inspect_function_returns_existing_cep(): void
    {
        $cep = '12345-678';

        Cep::create([
            'cep' => $cep,
            'logradouro' => 'Test Street',
            'bairro' => 'Downtown',
            'localidade' => 'City',
            'uf' => 'SP'
        ]);

        $response = $this->getJson("/api/cep/$cep");

        $response->assertOk()
            ->assertJsonFragment([
                'cep' => $cep,
                'logradouro' => 'Test Street'
            ]);
    }

    #[Test]
    public function inspect_function_fetches_and_stores_new_cep(): void
    {
        $cep = '01001000';
        $cepFormatted = '01001-000';

        Http::fake([
            "$this->base_url/ws/$cep/json/" => Http::response([
                'cep' => $cepFormatted,
                'logradouro' => 'Sé Square',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ], 200)
        ]);

        $response = $this->getJson("/api/cep/$cep");

        $response->assertOk()
            ->assertJsonFragment([
                'cep' => $cepFormatted,
                'logradouro' => 'Sé Square',
            ]);

        $this->assertDatabaseHas('ceps', [
            'cep' => $cepFormatted,
            'logradouro' => 'Sé Square',
        ]);
    }

    #[Test]
    public function inspect_function_returns_404_for_invalid_cep(): void
    {
        $cep = '00000000';

        Http::fake([
            "$this->base_url/ws/$cep/json/" => Http::response([], 404)
        ]);

        $response = $this->getJson("/api/cep/$cep");

        $response->assertStatus(404)
            ->assertJson(['message' => 'ZIP code not found or invalid']);
    }

    #[Test]
    public function user_can_add_cep_to_favorites(): void
    {

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $cep = '12345-678';

        Cep::create([
            'cep' => $cep,
            'logradouro' => 'Favorite Street',
            'bairro' => 'Downtown',
            'localidade' => 'City',
            'uf' => 'SP'
        ]);

        $response = $this->actingAs($user)->postJson("/api/favorite/$cep", [
            'nickname' => 'Grandma\'s house'
        ]);

        $response->assertOk()->assertJson(['message' => 'ZIP code added to favorites']);

        $this->assertDatabaseHas('cep_user_pivot', [
            'user_id' => $user->id,
            'nickname' => 'Grandma\'s house'
        ]);
    }

    #[Test]
    public function user_can_list_favorite_ceps(): void
    {

        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        $cep = Cep::create([
            'cep' => '12345-678',
            'logradouro' => 'Favorite Street',
            'bairro' => 'Downtown',
            'localidade' => 'City',
            'uf' => 'SP'
        ]);

        $user->ceps()->attach($cep->id, ['nickname' => 'Work']);

        $response = $this->actingAs($user)->getJson('/api/my-list');

        $response->assertOk()->assertJsonFragment([
            'nickname' => 'Work',
            'cep' => '12345-678',
        ]);
    }
}
