<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PropertySearchApiService
{
    public function search(string $search = '', string $filter = 'title,description', int $perPage = 10): array
    {
        $result = [];
        $baseUrl = rtrim((string) config('app.url'), '/');

        if ($baseUrl === '') {
            Log::warning('PropertySearch API não pode ser chamada: APP_URL vazio.');
        } else {
            try {
                $response = Http::timeout(12)->get($baseUrl . '/api/properties', [
                    'search' => $search,
                    'filter' => $filter,
                    'per_page' => max(1, min(50, $perPage)),
                ]);

                if (!$response->successful()) {
                    Log::warning('Falha ao consultar API de imóveis', [
                        'status' => $response->status(),
                        'response' => $response->json(),
                        'search' => $search,
                        'filter' => $filter,
                    ]);
                } else {
                    $data = $response->json('data', []);
                    $result = is_array($data) ? $data : [];
                }
            } catch (\Exception $e) {
                Log::error('Erro ao consultar API de imóveis: ' . $e->getMessage(), [
                    'search' => $search,
                    'filter' => $filter,
                ]);
            }
        }

        return $result;
    }
}
