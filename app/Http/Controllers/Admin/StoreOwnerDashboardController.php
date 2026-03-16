<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\GarmentCategory;
use App\Models\Store;
use App\Models\StorePricing;
use App\Models\StoreTiming;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StoreOwnerDashboardController extends Controller
{
    private function owner()
    {
        return Auth::guard('store_owner')->user();
    }

    // ─── Dashboard ──────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $store = $this->owner()->stores()->first();
        $stats = [];
        if ($store) {
            $stats = [
                'total_bookings'     => $store->bookings()->count(),
                'pending_bookings'   => $store->bookings()->where('status', 'pending')->count(),
                'confirmed_bookings' => $store->bookings()->where('status', 'confirmed')->count(),
                'today_bookings'     => $store->bookings()
                    ->whereHas('slot', fn ($q) => $q->whereDate('slot_date', today()))
                    ->count(),
            ];
        }
        return view('store.dashboard', compact('store', 'stats'));
    }

    // ─── Store Profile ───────────────────────────────────────────────────────────

    public function storeCreate()
    {
        return view('store.store.create');
    }

    public function storeStore(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:150',
            'address'   => 'required|string',
            'city'      => 'required|string',
            'state'     => 'required|string',
            'pincode'   => 'required|string|max:10',
            'phone'     => 'required|string|max:20',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $this->owner()->stores()->create($request->only([
            'name', 'description', 'address', 'city', 'state',
            'pincode', 'phone', 'email', 'latitude', 'longitude',
        ]));

        return redirect()->route('store.dashboard')->with('success', 'Store registered. Awaiting admin approval.');
    }

    public function storeEdit()
    {
        $store = $this->owner()->stores()->firstOrFail();
        return view('store.store.edit', compact('store'));
    }

    public function storeUpdate(Request $request)
    {
        $store = $this->owner()->stores()->firstOrFail();
        $request->validate([
            'name'      => 'required|string|max:150',
            'address'   => 'required|string',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        $store->update($request->only([
            'name', 'description', 'address', 'city', 'state',
            'pincode', 'phone', 'email', 'latitude', 'longitude',
        ]));
        return back()->with('success', 'Store updated.');
    }

    public function toggleAvailability()
    {
        $store = $this->owner()->stores()->firstOrFail();
        $store->update(['is_available' => !$store->is_available]);
        return back()->with('success', 'Store availability updated.');
    }

    // ─── Timings ─────────────────────────────────────────────────────────────────

    public function timings()
    {
        $store   = $this->owner()->stores()->firstOrFail();
        $timings = $store->timings()->get()->keyBy('day_of_week');
        $days    = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        return view('store.timings', compact('store', 'timings', 'days'));
    }

    public function updateTimings(Request $request)
    {
        $store = $this->owner()->stores()->firstOrFail();

        foreach (range(0, 6) as $day) {
            StoreTiming::updateOrCreate(
                ['store_id' => $store->id, 'day_of_week' => $day],
                [
                    'is_closed'  => $request->boolean("days.{$day}.is_closed"),
                    'open_time'  => $request->input("days.{$day}.open_time"),
                    'close_time' => $request->input("days.{$day}.close_time"),
                ]
            );
        }

        return back()->with('success', 'Timings saved.');
    }

    // ─── Pricing ─────────────────────────────────────────────────────────────────

    public function pricing()
    {
        $store      = $this->owner()->stores()->firstOrFail();
        $pricing    = $store->pricing()->with('category')->get();
        $categories = GarmentCategory::where('is_active', true)->get();
        return view('store.pricing', compact('store', 'pricing', 'categories'));
    }

    public function pricingStore(Request $request)
    {
        $store = $this->owner()->stores()->firstOrFail();
        $request->validate([
            'garment_category_id' => 'required|exists:garment_categories,id',
            'garment_name'        => 'required|string|max:100',
            'price'               => 'required|numeric|min:0',
            'unit'                => 'required|string|max:50',
        ]);
        $store->pricing()->create($request->only(['garment_category_id','garment_name','price','unit']));
        return back()->with('success', 'Pricing item added.');
    }

    public function pricingUpdate(Request $request, int $id)
    {
        $store = $this->owner()->stores()->firstOrFail();
        $item  = $store->pricing()->findOrFail($id);
        $request->validate([
            'garment_name' => 'required|string|max:100',
            'price'        => 'required|numeric|min:0',
        ]);
        $item->update($request->only(['garment_name','price','unit']));
        return back()->with('success', 'Pricing updated.');
    }

    public function pricingDelete(int $id)
    {
        $store = $this->owner()->stores()->firstOrFail();
        $store->pricing()->findOrFail($id)->delete();
        return back()->with('success', 'Pricing item deleted.');
    }

    // ─── Slots ───────────────────────────────────────────────────────────────────

    public function slots(Request $request)
    {
        $store = $this->owner()->stores()->firstOrFail();
        $date  = $request->date ?? today()->toDateString();
        $slots = $store->bookingSlots()->where('slot_date', $date)->orderBy('slot_time')->get();
        return view('store.slots', compact('store', 'slots', 'date'));
    }

    public function slotsStore(Request $request)
    {
        $store = $this->owner()->stores()->firstOrFail();
        $request->validate([
            'slot_date'    => 'required|date|after_or_equal:today',
            'slot_time'    => 'required|date_format:H:i',
            'max_bookings' => 'required|integer|min:1',
        ]);

        BookingSlot::updateOrCreate(
            ['store_id' => $store->id, 'slot_date' => $request->slot_date, 'slot_time' => $request->slot_time],
            ['max_bookings' => $request->max_bookings, 'is_available' => true]
        );

        return back()->with('success', 'Slot added.');
    }

    public function slotsDelete(int $id)
    {
        $store = $this->owner()->stores()->firstOrFail();
        $store->bookingSlots()->findOrFail($id)->delete();
        return back()->with('success', 'Slot deleted.');
    }

    // ─── Bookings ────────────────────────────────────────────────────────────────

    public function bookings(Request $request)
    {
        $store    = $this->owner()->stores()->firstOrFail();
        $status   = $request->status ?? 'all';
        $query    = $store->bookings()->with(['user', 'slot', 'items'])->latest();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $bookings = $query->paginate(20);
        return view('store.bookings', compact('store', 'bookings', 'status'));
    }

    public function bookingUpdate(Request $request, int $id)
    {
        $store   = $this->owner()->stores()->firstOrFail();
        $booking = $store->bookings()->findOrFail($id);
        $request->validate(['status' => 'required|in:confirmed,completed,cancelled']);
        $booking->update(['status' => $request->status]);
        return back()->with('success', 'Booking status updated.');
    }
}
