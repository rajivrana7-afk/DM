class BookingModel {
  final int id;
  final String bookingNumber;
  final int storeId;
  final String? storeName;
  final String? storeAddress;
  final String slotDate;
  final String slotTime;
  final String serviceType;
  final String? pickupAddress;
  final double estimatedPrice;
  final String status;
  final String? notes;
  final List<BookingItemModel> items;
  final String createdAt;

  BookingModel({
    required this.id,
    required this.bookingNumber,
    required this.storeId,
    this.storeName,
    this.storeAddress,
    required this.slotDate,
    required this.slotTime,
    required this.serviceType,
    this.pickupAddress,
    required this.estimatedPrice,
    required this.status,
    this.notes,
    this.items = const [],
    required this.createdAt,
  });

  factory BookingModel.fromJson(Map<String, dynamic> json) => BookingModel(
        id: json['id'],
        bookingNumber: json['booking_number'],
        storeId: json['store_id'],
        storeName: json['store']?['name'],
        storeAddress: json['store']?['address'],
        slotDate: json['slot']?['slot_date'] ?? '',
        slotTime: json['slot']?['slot_time'] ?? '',
        serviceType: json['service_type'],
        pickupAddress: json['pickup_address'],
        estimatedPrice: (json['estimated_price'] as num).toDouble(),
        status: json['status'],
        notes: json['notes'],
        items: (json['items'] as List? ?? [])
            .map((i) => BookingItemModel.fromJson(i))
            .toList(),
        createdAt: json['created_at'] ?? '',
      );
}

class BookingItemModel {
  final int id;
  final String garmentName;
  final int quantity;
  final double unitPrice;
  final double totalPrice;

  BookingItemModel({
    required this.id,
    required this.garmentName,
    required this.quantity,
    required this.unitPrice,
    required this.totalPrice,
  });

  factory BookingItemModel.fromJson(Map<String, dynamic> json) => BookingItemModel(
        id: json['id'],
        garmentName: json['garment_name'],
        quantity: json['quantity'],
        unitPrice: (json['unit_price'] as num).toDouble(),
        totalPrice: (json['total_price'] as num).toDouble(),
      );
}
