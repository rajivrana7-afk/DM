import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../../core/theme/app_theme.dart';
import '../../models/store.dart';
import '../../services/store_service.dart';
import '../../widgets/store_card.dart';
import 'store_detail_screen.dart';

class StoreDiscoveryScreen extends StatefulWidget {
  const StoreDiscoveryScreen({super.key});

  @override
  State<StoreDiscoveryScreen> createState() => _StoreDiscoveryScreenState();
}

class _StoreDiscoveryScreenState extends State<StoreDiscoveryScreen>
    with SingleTickerProviderStateMixin {
  final _searchController = TextEditingController();
  late TabController _tabController;

  List<StoreModel> _stores = [];
  Position? _position;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadStores();
  }

  Future<void> _loadStores() async {
    setState(() { _loading = true; _error = null; });
    try {
      final permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
        setState(() { _error = 'Location permission denied. Please enable it in settings.'; _loading = false; });
        return;
      }

      _position = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.medium);
      final stores = await StoreService().getNearbyStores(
        lat: _position!.latitude,
        lon: _position!.longitude,
        search: _searchController.text.isNotEmpty ? _searchController.text : null,
      );
      setState(() { _stores = stores; _loading = false; });
    } catch (e) {
      setState(() { _error = 'Could not load stores. Check your connection.'; _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Dazzle Drys'),
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          indicatorColor: Colors.white,
          tabs: const [Tab(icon: Icon(Icons.list), text: 'List'), Tab(icon: Icon(Icons.map), text: 'Map')],
        ),
      ),
      body: Column(
        children: [
          Container(
            color: AppTheme.primary,
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Search laundry stores...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(icon: const Icon(Icons.clear), onPressed: () { _searchController.clear(); _loadStores(); })
                    : null,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              onSubmitted: (_) => _loadStores(),
            ),
          ),
          Expanded(
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildListView(),
                _buildMapView(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildListView() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_error != null) return Center(child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.location_off, size: 64, color: AppTheme.textSecondary),
        const SizedBox(height: 16),
        Text(_error!, textAlign: TextAlign.center, style: const TextStyle(color: AppTheme.textSecondary)),
        const SizedBox(height: 16),
        ElevatedButton(onPressed: _loadStores, child: const Text('Retry')),
      ],
    ));

    if (_stores.isEmpty) return const Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.store, size: 64, color: AppTheme.textSecondary),
          SizedBox(height: 16),
          Text('No stores found nearby', style: TextStyle(color: AppTheme.textSecondary)),
        ],
      ),
    );

    return RefreshIndicator(
      onRefresh: _loadStores,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _stores.length,
        itemBuilder: (_, i) => StoreCard(
          store: _stores[i],
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => StoreDetailScreen(store: _stores[i])),
          ),
        ),
      ),
    );
  }

  Widget _buildMapView() {
    if (_loading) return const Center(child: CircularProgressIndicator());
    if (_position == null) return const Center(child: Text('Location not available'));

    return FlutterMap(
      options: MapOptions(
        initialCenter: LatLng(_position!.latitude, _position!.longitude),
        initialZoom: 13,
      ),
      children: [
        TileLayer(
          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
          userAgentPackageName: 'com.dazzledrys.app',
        ),
        MarkerLayer(
          markers: [
            // Current location
            Marker(
              point: LatLng(_position!.latitude, _position!.longitude),
              child: const Icon(Icons.my_location, color: Colors.blue, size: 36),
            ),
            // Store markers
            ..._stores.where((s) => s.latitude != null && s.longitude != null).map(
              (s) => Marker(
                point: LatLng(s.latitude!, s.longitude!),
                child: GestureDetector(
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => StoreDetailScreen(store: s)),
                  ),
                  child: Column(
                    children: [
                      const Icon(Icons.location_pin, color: AppTheme.primary, size: 36),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(8), boxShadow: [BoxShadow(blurRadius: 4, color: Colors.black26)]),
                        child: Text(s.name, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold)),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }
}
