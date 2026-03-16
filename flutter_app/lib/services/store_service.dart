import 'package:dio/dio.dart';
import '../core/api/api_client.dart';
import '../models/store.dart';
import '../models/booking_slot.dart';
import '../models/store_pricing.dart';

class StoreService {
  final Dio _dio = ApiClient.create();

  Future<List<StoreModel>> getNearbyStores({
    required double lat,
    required double lon,
    double radius = 10,
    String? search,
  }) async {
    final response = await _dio.get('/stores', queryParameters: {
      'lat': lat,
      'lon': lon,
      'radius': radius,
      if (search != null) 'search': search,
    });
    final list = response.data['data'] as List;
    return list.map((j) => StoreModel.fromJson(j)).toList();
  }

  Future<StoreModel> getStoreDetail(int storeId) async {
    final response = await _dio.get('/stores/$storeId');
    return StoreModel.fromJson(response.data['data']);
  }

  Future<List<BookingSlotModel>> getAvailableSlots(int storeId, String date) async {
    final response = await _dio.get(
      '/stores/$storeId/available-slots',
      queryParameters: {'date': date},
    );
    final list = response.data['data'] as List;
    return list.map((j) => BookingSlotModel.fromJson(j)).toList();
  }

  Future<Map<String, List<StorePricingModel>>> getStorePricing(int storeId) async {
    final response = await _dio.get('/stores/$storeId/pricing');
    final data = response.data['data'] as Map<String, dynamic>;
    return data.map((key, value) {
      final items = (value as List).map((j) => StorePricingModel.fromJson(j)).toList();
      return MapEntry(key, items);
    });
  }
}
