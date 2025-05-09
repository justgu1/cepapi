<?php

namespace App\Http\Controllers;

use App\Models\Cep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class CepController extends Controller
{
    public function index()
    {
        return Inertia::render('Cep/Index');
    }

    public function inspect($cep)
    {
        $cepFormatted = preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);

        $cepData = Cep::where('cep', $cepFormatted)->first();

        if ($cepData) {
            return response()->json($cepData);
        }

        $response = $this->getExternalCep($cep);

        if ($response->failed()) {
            return response()->json(['message' => 'ZIP code not found or invalid'], 404);
        }

        $cepData = $this->storeCepData($cep, $response->json());

        return response()->json($cepData);
    }

    protected function getExternalCep($cep)
    {
        $cepApiUrl = env('CEP_API_URI');
        $completedUrl = "$cepApiUrl/ws/$cep/json/";

        return Http::get($completedUrl);
    }

    protected function storeCepData($cep, $data)
    {
        $cepData = ['cep' => $cep];

        foreach ($data as $label => $value) {
            $cepData[$label] = $value ?? null;
        }

        return Cep::updateOrCreate(['cep' => $cep], $cepData);
    }
}
