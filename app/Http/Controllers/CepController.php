<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use App\Models\Cep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *     title="CEP API",
 *     version="1.0.0",
 *     description="API for retrieving and managing Brazilian ZIP code data"
 * )
 *
 * @OA\Tag(
 *     name="CEP",
 *     description="ZIP code operations"
 * )
 */
class CepController extends Controller
{
    /**
     * Retrieves information for a given ZIP code, using cache if available.
     *
     * @OA\Get(
     *     path="/api/cep/{cep}",
     *     summary="Fetch ZIP code data",
     *     tags={"CEP"},
     *     @OA\Parameter(
     *         name="cep",
     *         in="path",
     *         required=true,
     *         description="ZIP code (with or without dash)",
     *         @OA\Schema(type="string", example="01001-000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ZIP code data returned successfully"
     *     ),
     *     @OA\Response(response=422, description="Invalid ZIP code"),
     *     @OA\Response(response=404, description="ZIP code not found")
     * )
     */
    public function inspect(string $cep): JsonResponse
    {
        $cep = preg_replace('/\D/', '', $cep); // Remove non-digits

        if (strlen($cep) !== 8) {
            return response()->json(['message' => 'Invalid ZIP code format'], 422);
        }

        $formatted = preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cep);

        // Try to fetch from cache
        $cepData = Cep::where('cep', $formatted)->first();

        if ($cepData) {
            return response()->json($cepData->makeHidden(['id', 'created_at', 'updated_at']));
        }

        // If not cached, fetch from external API
        $response = $this->getExternalCep($cep);

        if ($response->failed()) {
            return response()->json(['message' => 'ZIP code not found or invalid'], 404);
        }

        // Store and return the data
        $cepData = $this->storeCepData($formatted, $response->json());

        return response()->json($cepData);
    }

    /**
     * Requests ZIP code data from external API.
     */
    protected function getExternalCep(string $cep): Response
    {
        $url = env('CEP_API_URI') . "/ws/$cep/json/";
        return Http::get($url);
    }

    /**
     * Saves ZIP code data to the database.
     */
    protected function storeCepData(string $cep, array $data): Cep
    {
        $cepData = ['cep' => $cep];

        $fields = [
            'logradouro', 'complemento', 'unidade', 'bairro',
            'localidade', 'uf', 'estado', 'regiao', 'ibge',
            'gia', 'ddd', 'siafi'
        ];

        foreach ($fields as $field) {
            $cepData[$field] = $data[$field] ?? null;
        }

        return Cep::updateOrCreate(['cep' => $cep], $cepData);
    }

    /**
     * Adds a ZIP code to the authenticated user's favorites.
     *
     * @OA\Post(
     *     path="/api/cep/{cep}/favorite",
     *     summary="Add ZIP code to favorites",
     *     tags={"CEP"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="cep",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="01001-000")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nickname"},
     *             @OA\Property(property="nickname", type="string", example="Grandma's house")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Favorite added successfully"),
     *     @OA\Response(response=409, description="Already a favorite"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function addToFavorites(Request $request, string $cep): JsonResponse
    {
        try {
            $validated = validator($request->all(), [
                'nickname' => 'required|string|max:255',
            ])->validate();
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        }

        $cleanCep = preg_replace('/\D/', '', $cep);

        if (strlen($cleanCep) !== 8) {
            return response()->json(['message' => 'Invalid ZIP code'], 422);
        }

        $formatted = preg_replace('/(\d{5})(\d{3})/', '$1-$2', $cleanCep);

        // Check if CEP exists in DB, if not fetch and save
        $cepModel = Cep::where('cep', $formatted)->first() ??
            $this->storeCepData($formatted, $this->getExternalCep($cleanCep)->json());

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->ceps()->where('cep_id', $cepModel->id)->exists()) {
            return response()->json(['message' => 'ZIP code already in favorites'], 409);
        }

        // Attach ZIP code to user's favorites
        $user->ceps()->attach($cepModel->id, ['nickname' => $validated['nickname']]);

        return response()->json(['message' => 'ZIP code added to favorites']);
    }

    /**
     * Returns a paginated list of the authenticated user's favorite ZIP codes.
     *
     * @OA\Get(
     *     path="/api/cep/favorites",
     *     summary="List user favorites",
     *     tags={"CEP"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(response=200, description="Paginated favorites list")
     * )
     */
    public function myList(Request $request): JsonResponse
    {
        $user = Auth::user();

        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);

        /** @var \App\Models\User $user */
        $favorites = $user->ceps()->paginate($perPage, ['*'], 'page', $page);

        $data = $favorites->getCollection()->map(function ($cep) {
            $item = $cep->makeHidden(['id', 'created_at', 'updated_at'])->toArray();
            $item['nickname'] = $cep->pivot->nickname;
            return $item;
        });

        return response()->json([
            'total' => $favorites->total(),
            'per_page' => $favorites->perPage(),
            'current_page' => $favorites->currentPage(),
            'last_page' => $favorites->lastPage(),
            'favorites' => $data,
        ]);
    }

    /**
     * Displays the front-end page (Inertia).
     */
    public function index()
    {
        return Inertia::render('Cep/Index');
    }
}
