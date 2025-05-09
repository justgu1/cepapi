<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Models\Cep;

class CepController extends Controller
{
    public function index()
    {
        return Inertia::render('Cep/Index');
    }

    public function inspect(string $cep): JsonResponse
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return response()->json(['message' => 'Invalid ZIP code format'], 422);
        }

        $cepFormatted = preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);

        $cepData = Cep::where('cep', $cepFormatted)->first();

        if ($cepData) {
            return response()->json($cepData);
        }

        $response = $this->getExternalCep($cep);

        if ($response->failed()) {
            return response()->json(['message' => 'ZIP code not found or invalid'], 404);
        }

        $cepData = $this->storeCepData($cepFormatted, $response->json());

        return response()->json($cepData);
    }

    protected function getExternalCep(string $cep): Response
    {
        $base_url = env('CEP_API_URI');
        $completedUrl = "$base_url/ws/$cep/json/";

        return Http::get($completedUrl);
    }

    protected function storeCepData(string $cep, array $data): Cep
    {
        $cepData = ['cep' => $cep];

        $allowed = [
            'logradouro',
            'complemento',
            'unidade',
            'bairro',
            'localidade',
            'uf',
            'estado',
            'regiao',
            'ibge',
            'gia',
            'ddd',
            'siafi'
        ];

        foreach ($allowed as $label) {
            $cepData[$label] = $data[$label] ?? null;
        }

        return Cep::updateOrCreate(['cep' => $cep], $cepData);
    }
}
