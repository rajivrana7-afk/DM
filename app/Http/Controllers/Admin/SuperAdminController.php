<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\GarmentCategory;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SuperAdminController extends Controller
{
    // ─── Auth ────────────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (Auth::guard('admin')->attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        return redirect()->route('admin.login');
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────────

    public function dashboard()
    {
        $stats = [
            'total_stores'          => Store::count(),
            'approved_stores'       => Store::where('status', 'approved')->count(),
            'pending_stores'        => Store::where('status', 'pending')->count(),
            'total_users'           => User::count(),
            'total_bookings'        => Booking::count(),
            'bookings_today'        => Booking::whereDate('created_at', today())->count(),
        ];

        $recentStores   = Store::latest()->take(5)->with('owner')->get();
        $recentBookings = Booking::latest()->take(5)->with(['user','store'])->get();

        return view('admin.dashboard', compact('stats', 'recentStores', 'recentBookings'));
    }

    // ─── Stores ──────────────────────────────────────────────────────────────────

    public function stores(Request $request)
    {
        $status = $request->status ?? 'all';
        $query  = Store::with('owner')->latest();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $stores = $query->paginate(20);
        return view('admin.stores.index', compact('stores', 'status'));
    }

    public function storeShow(int $id)
    {
        $store = Store::with(['owner', 'timings', 'pricing.category'])->findOrFail($id);
        return view('admin.stores.show', compact('store'));
    }

    public function storeApprove(int $id)
    {
        Store::findOrFail($id)->update(['status' => 'approved']);
        return back()->with('success', 'Store approved.');
    }

    public function storeReject(int $id)
    {
        Store::findOrFail($id)->update(['status' => 'rejected']);
        return back()->with('success', 'Store rejected.');
    }

    public function storeToggle(int $id)
    {
        $store = Store::findOrFail($id);
        $store->update(['status' => $store->status === 'disabled' ? 'approved' : 'disabled']);
        return back()->with('success', 'Store status updated.');
    }

    // ─── Users ───────────────────────────────────────────────────────────────────

    public function users(Request $request)
    {
        $users = User::latest()->paginate(30);
        return view('admin.users.index', compact('users'));
    }

    public function userToggle(int $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'User status updated.');
    }

    // ─── Bookings ────────────────────────────────────────────────────────────────

    public function bookings(Request $request)
    {
        $status   = $request->status ?? 'all';
        $query    = Booking::with(['user','store','slot'])->latest();
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $bookings = $query->paginate(30);
        return view('admin.bookings.index', compact('bookings', 'status'));
    }

    // ─── Garment Categories ──────────────────────────────────────────────────────

    public function categories()
    {
        $categories = GarmentCategory::orderBy('sort_order')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function categoryStore(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:garment_categories',
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'required|integer|min:0',
        ]);
        GarmentCategory::create($request->only(['name','icon','sort_order']));
        return back()->with('success', 'Category added.');
    }

    public function categoryDelete(int $id)
    {
        GarmentCategory::findOrFail($id)->delete();
        return back()->with('success', 'Category deleted.');
    }

    // ─── Store Owners ────────────────────────────────────────────────────────────

    public function storeOwners()
    {
        $owners = StoreOwner::withCount('stores')->latest()->paginate(20);
        return view('admin.store_owners.index', compact('owners'));
    }

    public function storeOwnerToggle(int $id)
    {
        $owner = StoreOwner::findOrFail($id);
        $owner->update(['is_active' => !$owner->is_active]);
        return back()->with('success', 'Owner status updated.');
    }
}
