import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../models/booking.dart';
import '../../services/booking_service.dart';

class MyBookingsScreen extends StatefulWidget {
  const MyBookingsScreen({super.key});

  @override
  State<MyBookingsScreen> createState() => _MyBookingsScreenState();
}

class _MyBookingsScreenState extends State<MyBookingsScreen> {
  List<BookingModel> _bookings = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadBookings();
  }

  Future<void> _loadBookings() async {
    setState(() { _loading = true; _error = null; });
    try {
      final bookings = await BookingService().getMyBookings();
      setState(() { _bookings = bookings; _loading = false; });
    } catch (e) {
      setState(() { _error = 'Could not load bookings.'; _loading = false; });
    }
  }

  Future<void> _cancelBooking(BookingModel booking) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Cancel Booking'),
        content: Text('Cancel booking #${booking.bookingNumber}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('No')),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('Yes, Cancel')),
        ],
      ),
    );

    if (confirm != true) return;

    try {
      await BookingService().cancelBooking(booking.id);
      _loadBookings();
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not cancel booking.')));
    }
  }

  Color _statusColor(String status) {
    return switch (status) {
      'confirmed'  => const Color(0xFF2E7D32),
      'pending'    => const Color(0xFFF57F17),
      'completed'  => Colors.blueGrey,
      'cancelled'  => const Color(0xFFC62828),
      _ => Colors.grey,
    };
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Bookings')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(_error!, style: const TextStyle(color: AppTheme.textSecondary)),
                    const SizedBox(height: 16),
                    ElevatedButton(onPressed: _loadBookings, child: const Text('Retry')),
                  ],
                ))
              : _bookings.isEmpty
                  ? const Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.calendar_today, size: 64, color: AppTheme.textSecondary),
                          SizedBox(height: 16),
                          Text('No bookings yet', style: TextStyle(color: AppTheme.textSecondary, fontSize: 16)),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadBookings,
                      child: ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _bookings.length,
                        itemBuilder: (_, i) => _BookingCard(
                          booking: _bookings[i],
                          statusColor: _statusColor(_bookings[i].status),
                          onCancel: ['pending', 'confirmed'].contains(_bookings[i].status)
                              ? () => _cancelBooking(_bookings[i])
                              : null,
                        ),
                      ),
                    ),
    );
  }
}

class _BookingCard extends StatelessWidget {
  final BookingModel booking;
  final Color statusColor;
  final VoidCallback? onCancel;

  const _BookingCard({required this.booking, required this.statusColor, this.onCancel});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('#${booking.bookingNumber}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: statusColor.withOpacity(0.1), borderRadius: BorderRadius.circular(20)),
                  child: Text(booking.status.toUpperCase(), style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 11)),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.store, size: 16, color: AppTheme.textSecondary),
                const SizedBox(width: 4),
                Expanded(child: Text(booking.storeName ?? '–', style: const TextStyle(fontWeight: FontWeight.w500))),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.calendar_today, size: 16, color: AppTheme.textSecondary),
                const SizedBox(width: 4),
                Text('${booking.slotDate}  ${booking.slotTime.substring(0, 5)}', style: const TextStyle(color: AppTheme.textSecondary)),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.local_laundry_service, size: 16, color: AppTheme.textSecondary),
                const SizedBox(width: 4),
                Text(booking.serviceType == 'pickup' ? 'Pickup' : 'Store Visit', style: const TextStyle(color: AppTheme.textSecondary)),
                const Spacer(),
                Text('₹${booking.estimatedPrice.toStringAsFixed(0)}', style: const TextStyle(fontWeight: FontWeight.bold, color: AppTheme.primary, fontSize: 16)),
              ],
            ),
            if (onCancel != null) ...[
              const Divider(height: 16),
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: onCancel,
                  style: TextButton.styleFrom(foregroundColor: AppTheme.error),
                  child: const Text('Cancel Booking'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
