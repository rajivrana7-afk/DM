class StoreModel {
  final int id;
  final String name;
  final String? description;
  final String address;
  final String? city;
  final String phone;
  final double? latitude;
  final double? longitude;
  final String status;
  final bool isAvailable;
  final double? distanceKm;
  final List<StoreTimingModel> timings;
  final List<StorePricingSimple> pricing;

  StoreModel({
    required this.id,
    required this.name,
    this.description,
    required this.address,
    this.city,
    required this.phone,
    this.latitude,
    this.longitude,
    required this.status,
    required this.isAvailable,
    this.distanceKm,
    this.timings = const [],
    this.pricing = const [],
  });

  factory StoreModel.fromJson(Map<String, dynamic> json) => StoreModel(
        id: json['id'],
        name: json['name'],
        description: json['description'],
        address: json['address'],
        city: json['city'],
        phone: json['phone'],
        latitude: (json['latitude'] as num?)?.toDouble(),
        longitude: (json['longitude'] as num?)?.toDouble(),
        status: json['status'] ?? 'approved',
        isAvailable: json['is_available'] == true,
        distanceKm: (json['distance_km'] as num?)?.toDouble(),
        timings: (json['timings'] as List? ?? [])
            .map((t) => StoreTimingModel.fromJson(t))
            .toList(),
        pricing: (json['pricing'] as List? ?? [])
            .map((p) => StorePricingSimple.fromJson(p))
            .toList(),
      );
}

class StoreTimingModel {
  final int dayOfWeek;
  final String dayName;
  final String? openTime;
  final String? closeTime;
  final bool isClosed;

  StoreTimingModel({
    required this.dayOfWeek,
    required this.dayName,
    this.openTime,
    this.closeTime,
    required this.isClosed,
  });

  factory StoreTimingModel.fromJson(Map<String, dynamic> json) => StoreTimingModel(
        dayOfWeek: json['day_of_week'],
        dayName: json['day_name'] ?? '',
        openTime: json['open_time'],
        closeTime: json['close_time'],
        isClosed: json['is_closed'] == true,
      );
}

class StorePricingSimple {
  final int id;
  final String garmentName;
  final double price;
  final String unit;
  final String? categoryName;

  StorePricingSimple({
    required this.id,
    required this.garmentName,
    required this.price,
    required this.unit,
    this.categoryName,
  });

  factory StorePricingSimple.fromJson(Map<String, dynamic> json) => StorePricingSimple(
        id: json['id'],
        garmentName: json['garment_name'],
        price: (json['price'] as num).toDouble(),
        unit: json['unit'] ?? 'per piece',
        categoryName: json['category']?['name'],
      );
}
