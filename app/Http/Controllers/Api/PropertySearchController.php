<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertySearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', $request->query('q', '')));
        $filter = trim((string) $request->query('filter', 'title,description'));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));

        $fields = collect(explode(',', $filter))
            ->map(fn ($field) => trim((string) $field))
            ->filter(fn ($field) => in_array($field, ['title', 'description'], true))
            ->unique()
            ->values();

        if ($fields->isEmpty()) {
            $fields = collect(['title', 'description']);
        }

        $query = Property::query()
            ->select([
                'id',
                'user_id',
                'title',
                'description',
                'price',
                'location',
                'contact_phone',
                'details',
                'photos',
                'created_at',
                'updated_at',
            ])
            ->latest();

        if ($search !== '') {
            $query->where(function ($builder) use ($fields, $search): void {
                foreach ($fields as $field) {
                    $builder->orWhere($field, 'like', '%' . $search . '%');
                }
            });
        }

        $properties = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'filters' => [
                'search' => $search,
                'filter' => $fields->all(),
                'per_page' => $perPage,
            ],
            'data' => $properties->items(),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'from' => $properties->firstItem(),
                'to' => $properties->lastItem(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }
}
