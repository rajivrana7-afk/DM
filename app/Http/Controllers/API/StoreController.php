<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\BookingSlot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    /**
     * List nearby stores sorted by distance.
     *
     * Query params: lat, lon, radius (km, default 10), search
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'lat'    => 'required|numeric',
            'lon'    => 'required|numeric',
            'radius' => 'sometimes|numeric|min:1|max:100',
        ]);

        $lat    = (float) $request->lat;
        $lon    = (float) $request->lon;
        $radius = (float) ($request->radius ?? 10);

        $stores = Store::active()
            ->with(['timings', 'pricing.category'])
            ->get()
            ->map(function (Store $store) use ($lat, $lon) {
                $store->distance_km = $store->distanceFrom($lat, $lon);
                return $store;
            })
            ->filter(fn ($s) => $s->distance_km <= 999) // initial radius filter by model
            ->when($request->search, fn ($c) => $c->filter(
                fn ($s) => str_contains(strtolower($s->name), strtolower($request->search))
            ))
            ->sortBy('distance_km')
            ->values();

        return response()->json(['data' => $stores]);
    }

    /**
     * Show a single store with full details.
     */
    public function show(int $id): JsonResponse
    {
        $store = Store::with(['timings', 'pricing.category', 'owner:id,name,phone'])->findOrFail($id);

        return response()->json(['data' => $store]);
    }

    /**
     * Get available booking slots for a store on a given date.
     */
    public function availableSlots(Request $request, int $storeId): JsonResponse
    {
        $request->validate(['date' => 'required|date|after_or_equal:today']);

        $slots = BookingSlot::where('store_id', $storeId)
            ->where('slot_date', $request->date)
            ->where('is_available', true)
            ->whereRaw('current_bookings < max_bookings')
            ->orderBy('slot_time')
            ->get();

        return response()->json(['data' => $slots]);
    }

    /**
     * Get pricing list for a store.
     */
    public function pricing(int $storeId): JsonResponse
    {
        $store = Store::findOrFail($storeId);
        $pricing = $store->pricing()->with('category')->get()
            ->groupBy('category.name');

        return response()->json(['data' => $pricing]);
    }
}
