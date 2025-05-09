<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Models\Cep;

/**
 * @OA\Info(
 *     title="CEP API",
 *     version="1.0.0",
 *     description="API para consulta e cache de CEPs"
 * )
 *
 * @OA\Tag(
 *     name="CEP",
 *     description="Operações relacionadas a CEP"
 * )
 */
class CepController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/cep/{cep}",
 *     summary="Consulta um CEP",
 *     tags={"CEP"},
 *     @OA\Parameter(
 *         name="cep",
 *         in="path",
 *         required=true,
 *         description="CEP a ser consultado (somente números ou com traço)",
 *         @OA\Schema(type="string", example="01001-000")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Dados do CEP encontrado",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="cep", type="string", example="01001-000"),
 *             @OA\Property(property="logradouro", type="string", example="Praça da Sé"),
 *             @OA\Property(property="complemento", type="string", example="lado ímpar"),
 *             @OA\Property(property="unidade", type="string", example=""),
 *             @OA\Property(property="bairro", type="string", example="Sé"),
 *             @OA\Property(property="localidade", type="string", example="São Paulo"),
 *             @OA\Property(property="uf", type="string", example="SP"),
 *             @OA\Property(property="estado", type="string", example="São Paulo"),
 *             @OA\Property(property="regiao", type="string", example="Sudeste"),
 *             @OA\Property(property="ibge", type="string", example="3550308"),
 *             @OA\Property(property="gia", type="string", example="1004"),
 *             @OA\Property(property="ddd", type="string", example="11"),
 *             @OA\Property(property="siafi", type="string", example="7107")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="CEP inválido",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Invalid ZIP code format")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="CEP não encontrado",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="ZIP code not found or invalid")
 *         )
 *     )
 * )
 */

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
            return response()->json($cepData->makeHidden('id', 'created_at', 'updated_at'));
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
