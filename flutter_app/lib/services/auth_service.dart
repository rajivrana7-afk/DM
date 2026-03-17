import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/api/api_client.dart';

class AuthService {
  final Dio _dio = ApiClient.create();
  final _storage = const FlutterSecureStorage();

  Future<void> sendOtp(String email) async {
    await _dio.post('/auth/send-otp', data: {'email': email});
  }

  Future<Map<String, dynamic>> verifyOtp(String email, String otp) async {
    final response = await _dio.post('/auth/verify-otp', data: {
      'email': email,
      'otp': otp,
    });
    final data = response.data as Map<String, dynamic>;
    await _storage.write(key: 'auth_token', value: data['token']);
    await _storage.write(key: 'user_name', value: data['user']['name']);
    return data;
  }

  Future<bool> isLoggedIn() async {
    final token = await _storage.read(key: 'auth_token');
    return token != null;
  }

  Future<void> logout() async {
    try {
      await _dio.post('/user/logout');
    } catch (_) {}
    await _storage.deleteAll();
  }

  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> data) async {
    final response = await _dio.put('/user/profile', data: data);
    return response.data;
  }

  Future<void> registerDeviceToken(String fcmToken, String platform) async {
    await _dio.post('/user/device-token', data: {
      'fcm_token': fcmToken,
      'platform': platform,
    });
  }
}
