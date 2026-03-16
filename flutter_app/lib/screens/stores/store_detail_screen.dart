import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/theme/app_theme.dart';
import '../../models/store.dart';
import '../../models/store_pricing.dart';
import '../../services/store_service.dart';
import '../../widgets/pricing_calculator.dart';
import '../booking/booking_screen.dart';

class StoreDetailScreen extends StatefulWidget {
  final StoreModel store;
  const StoreDetailScreen({super.key, required this.store});

  @override
  State<StoreDetailScreen> createState() => _StoreDetailScreenState();
}

class _StoreDetailScreenState extends State<StoreDetailScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  Map<String, List<StorePricingModel>> _pricing = {};
  bool _pricingLoading = true;

  // Cart: map pricingId -> quantity
  final Map<int, int> _cart = {};

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadPricing();
  }

  Future<void> _loadPricing() async {
    try {
      final pricing = await StoreService().getStorePricing(widget.store.id);
      setState(() { _pricing = pricing; _pricingLoading = false; });
    } catch (_) {
      setState(() => _pricingLoading = false);
    }
  }

  double get _cartTotal {
    double total = 0;
    _pricing.forEach((_, items) {
      for (final item in items) {
        if (_cart.containsKey(item.id)) {
          total += item.price * _cart[item.id]!;
        }
      }
    });
    return total;
  }

  List<Map<String, dynamic>> get _cartItems {
    final items = <Map<String, dynamic>>[];
    _pricing.forEach((_, priceItems) {
      for (final item in priceItems) {
        if (_cart.containsKey(item.id) && _cart[item.id]! > 0) {
          items.add({'store_pricing_id': item.id, 'quantity': _cart[item.id]!});
        }
      }
    });
    return items;
  }

  @override
  Widget build(BuildContext context) {
    final store = widget.store;
    return Scaffold(
      appBar: AppBar(title: Text(store.name)),
      body: Column(
        children: [
          // Store header
          Container(
            color: AppTheme.primary,
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
                  child: const Icon(Icons.local_laundry_service, size: 36, color: AppTheme.primary),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: store.isAvailable ? Colors.green : Colors.grey,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              store.isAvailable ? 'Open' : 'Closed',
                              style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                            ),
                          ),
                          if (store.distanceKm != null) ...[
                            const SizedBox(width: 8),
                            Text('${store.distanceKm!.toStringAsFixed(1)} km', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                          ],
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(store.address, style: const TextStyle(color: Colors.white70, fontSize: 13)),
                    ],
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.phone, color: Colors.white),
                  onPressed: () => launchUrl(Uri.parse('tel:${store.phone}')),
                ),
              ],
            ),
          ),

          // Tabs
          TabBar(
            controller: _tabController,
            tabs: const [
              Tab(text: 'Pricing'),
              Tab(text: 'Timings'),
              Tab(text: 'Info'),
            ],
          ),

          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildPricingTab(),
                _buildTimingsTab(),
                _buildInfoTab(),
              ],
            ),
          ),
        ],
      ),
      bottomNavigationBar: _cartTotal > 0
          ? Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(color: Colors.white, boxShadow: [BoxShadow(blurRadius: 8, color: Colors.black12)]),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Estimated Total', style: TextStyle(color: AppTheme.textSecondary, fontSize: 12)),
                        Text('₹${_cartTotal.toStringAsFixed(0)}', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.primary)),
                      ],
                    ),
                  ),
                  ElevatedButton(
                    onPressed: () => Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => BookingScreen(store: widget.store, cartItems: _cartItems, estimatedTotal: _cartTotal),
                      ),
                    ),
                    style: ElevatedButton.styleFrom(minimumSize: const Size(140, 48)),
                    child: const Text('Book Now'),
                  ),
                ],
              ),
            )
          : null,
    );
  }

  Widget _buildPricingTab() {
    if (_pricingLoading) return const Center(child: CircularProgressIndicator());
    if (_pricing.isEmpty) return const Center(child: Text('No pricing available', style: TextStyle(color: AppTheme.textSecondary)));

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        ..._pricing.entries.map((entry) => Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(entry.key, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            ),
            ...entry.value.map((item) => _buildPricingItem(item)),
            const Divider(),
          ],
        )),
      ],
    );
  }

  Widget _buildPricingItem(StorePricingModel item) {
    final qty = _cart[item.id] ?? 0;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item.garmentName, style: const TextStyle(fontWeight: FontWeight.w500)),
                Text('₹${item.price.toStringAsFixed(0)} / ${item.unit}', style: const TextStyle(color: AppTheme.textSecondary, fontSize: 13)),
              ],
            ),
          ),
          Row(
            children: [
              if (qty > 0) ...[
                IconButton(
                  onPressed: () => setState(() {
                    if (qty <= 1) _cart.remove(item.id);
                    else _cart[item.id] = qty - 1;
                  }),
                  icon: const Icon(Icons.remove_circle_outline, color: AppTheme.primary),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                ),
                SizedBox(
                  width: 32,
                  child: Text('$qty', textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                ),
              ],
              IconButton(
                onPressed: () => setState(() => _cart[item.id] = qty + 1),
                icon: const Icon(Icons.add_circle_outline, color: AppTheme.primary),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTimingsTab() {
    if (widget.store.timings.isEmpty) return const Center(child: Text('No timing info available', style: TextStyle(color: AppTheme.textSecondary)));

    return ListView(
      padding: const EdgeInsets.all(16),
      children: widget.store.timings.map((t) => ListTile(
        leading: const Icon(Icons.access_time, color: AppTheme.primary),
        title: Text(t.dayName, style: const TextStyle(fontWeight: FontWeight.w500)),
        trailing: t.isClosed
            ? const Text('Closed', style: TextStyle(color: Colors.red))
            : Text('${t.openTime} – ${t.closeTime}', style: const TextStyle(color: AppTheme.textSecondary)),
      )).toList(),
    );
  }

  Widget _buildInfoTab() {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (widget.store.description != null && widget.store.description!.isNotEmpty) ...[
          const Text('About', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          const SizedBox(height: 8),
          Text(widget.store.description!, style: const TextStyle(color: AppTheme.textSecondary, height: 1.5)),
          const SizedBox(height: 16),
        ],
        ListTile(
          leading: const Icon(Icons.location_on, color: AppTheme.primary),
          title: Text(widget.store.address),
          contentPadding: EdgeInsets.zero,
        ),
        ListTile(
          leading: const Icon(Icons.phone, color: AppTheme.primary),
          title: Text(widget.store.phone),
          onTap: () => launchUrl(Uri.parse('tel:${widget.store.phone}')),
          contentPadding: EdgeInsets.zero,
        ),
      ],
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
}
