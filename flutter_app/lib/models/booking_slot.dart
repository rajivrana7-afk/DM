class BookingSlotModel {
  final int id;
  final int storeId;
  final String slotDate;
  final String slotTime;
  final int maxBookings;
  final int currentBookings;
  final bool isAvailable;

  BookingSlotModel({
    required this.id,
    required this.storeId,
    required this.slotDate,
    required this.slotTime,
    required this.maxBookings,
    required this.currentBookings,
    required this.isAvailable,
  });

  bool get isFull => currentBookings >= maxBookings;

  factory BookingSlotModel.fromJson(Map<String, dynamic> json) => BookingSlotModel(
        id: json['id'],
        storeId: json['store_id'],
        slotDate: json['slot_date'],
        slotTime: json['slot_time'],
        maxBookings: json['max_bookings'],
        currentBookings: json['current_bookings'],
        isAvailable: json['is_available'] == true,
      );
}
