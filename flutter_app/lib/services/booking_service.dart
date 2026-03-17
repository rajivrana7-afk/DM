import 'package:dio/dio.dart';
import '../core/api/api_client.dart';
import '../models/booking.dart';

class BookingService {
  final Dio _dio = ApiClient.create();

  Future<BookingModel> createBooking({
    required int storeId,
    required int bookingSlotId,
    required String serviceType,
    String? pickupAddress,
    String? notes,
    required List<Map<String, dynamic>> items,
  }) async {
    final response = await _dio.post('/bookings', data: {
      'store_id': storeId,
      'booking_slot_id': bookingSlotId,
      'service_type': serviceType,
      if (pickupAddress != null) 'pickup_address': pickupAddress,
      if (notes != null) 'notes': notes,
      'items': items,
    });
    return BookingModel.fromJson(response.data['data']);
  }

  Future<List<BookingModel>> getMyBookings() async {
    final response = await _dio.get('/bookings');
    final list = response.data['data'] as List;
    return list.map((j) => BookingModel.fromJson(j)).toList();
  }

  Future<BookingModel> getBookingDetail(int id) async {
    final response = await _dio.get('/bookings/$id');
    return BookingModel.fromJson(response.data['data']);
  }

  Future<void> cancelBooking(int id) async {
    await _dio.post('/bookings/$id/cancel');
  }
}
