<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingSlot;
use App\Models\Notification;
use App\Models\StorePricing;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

    /**
     * Create a new booking.
     *
     * Body: store_id, booking_slot_id, service_type, pickup_address?, items[]
     *       items: [{store_pricing_id, quantity}]
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'store_id'        => 'required|exists:stores,id',
            'booking_slot_id' => 'required|exists:booking_slots,id',
            'service_type'    => 'required|in:store_visit,pickup',
            'pickup_address'  => 'required_if:service_type,pickup|string',
            'notes'           => 'nullable|string|max:500',
            'items'           => 'required|array|min:1',
            'items.*.store_pricing_id' => 'required|exists:store_pricing,id',
            'items.*.quantity'         => 'required|integer|min:1',
        ]);

        $slot = BookingSlot::findOrFail($request->booking_slot_id);

        if ($slot->store_id != $request->store_id) {
            return response()->json(['message' => 'Slot does not belong to this store.'], 422);
        }

        if ($slot->isFull() || !$slot->is_available) {
            return response()->json(['message' => 'This slot is no longer available.'], 422);
        }

        DB::beginTransaction();
        try {
            $estimatedPrice = 0;
            $itemsData = [];

            foreach ($request->items as $item) {
                $pricing = StorePricing::where('id', $item['store_pricing_id'])
                    ->where('store_id', $request->store_id)
                    ->firstOrFail();

                $totalPrice = $pricing->price * $item['quantity'];
                $estimatedPrice += $totalPrice;

                $itemsData[] = [
                    'store_pricing_id' => $pricing->id,
                    'garment_name'     => $pricing->garment_name,
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $pricing->price,
                    'total_price'      => $totalPrice,
                ];
            }

            $booking = Booking::create([
                'booking_number'  => Booking::generateBookingNumber(),
                'user_id'         => $request->user()->id,
                'store_id'        => $request->store_id,
                'booking_slot_id' => $request->booking_slot_id,
                'service_type'    => $request->service_type,
                'pickup_address'  => $request->pickup_address,
                'estimated_price' => $estimatedPrice,
                'status'          => 'pending',
                'notes'           => $request->notes,
            ]);

            $booking->items()->createMany($itemsData);

            // Increment slot booking count
            $slot->increment('current_bookings');
            if ($slot->current_bookings >= $slot->max_bookings) {
                $slot->update(['is_available' => false]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Booking failed. Please try again.'], 500);
        }

        // Send push notification (async-friendly)
        $this->notificationService->sendToUser(
            $request->user(),
            'Booking Confirmed!',
            "Your booking #{$booking->booking_number} has been received.",
            'booking_confirmed',
            ['booking_id' => $booking->id]
        );

        return response()->json([
            'message' => 'Booking created successfully.',
            'data'    => $booking->load(['items', 'slot', 'store:id,name,address,phone']),
        ], 201);
    }

    /**
     * List bookings for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['store:id,name,address', 'slot', 'items'])
            ->latest()
            ->paginate(20);

        return response()->json($bookings);
    }

    /**
     * Show a single booking.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $booking = $request->user()
            ->bookings()
            ->with(['store', 'slot', 'items'])
            ->findOrFail($id);

        return response()->json(['data' => $booking]);
    }

    /**
     * Cancel a booking (user-initiated).
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = $request->user()->bookings()->findOrFail($id);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'This booking cannot be cancelled.'], 422);
        }

        $booking->update(['status' => 'cancelled']);
        $booking->slot->decrement('current_bookings');

        return response()->json(['message' => 'Booking cancelled.']);
    }
}
