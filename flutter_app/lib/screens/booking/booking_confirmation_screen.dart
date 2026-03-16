import 'package:flutter/material.dart';
import '../../core/theme/app_theme.dart';
import '../../models/booking.dart';
import '../home/home_screen.dart';

class BookingConfirmationScreen extends StatelessWidget {
  final BookingModel booking;
  const BookingConfirmationScreen({super.key, required this.booking});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                width: 100,
                height: 100,
                decoration: const BoxDecoration(color: Color(0xFFE8F5E9), shape: BoxShape.circle),
                child: const Icon(Icons.check_circle, size: 64, color: Color(0xFF2E7D32)),
              ),
              const SizedBox(height: 24),
              const Text('Booking Confirmed!', style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Text(
                'Your booking #${booking.bookingNumber} has been placed.',
                style: const TextStyle(color: AppTheme.textSecondary, fontSize: 15),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 32),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      _DetailRow(label: 'Booking #', value: booking.bookingNumber),
                      _DetailRow(label: 'Store', value: booking.storeName ?? '–'),
                      _DetailRow(label: 'Date', value: booking.slotDate),
                      _DetailRow(label: 'Time', value: booking.slotTime.substring(0, 5)),
                      _DetailRow(label: 'Service', value: booking.serviceType == 'pickup' ? 'Pickup' : 'Store Visit'),
                      _DetailRow(label: 'Est. Amount', value: '₹${booking.estimatedPrice.toStringAsFixed(0)}'),
                      _DetailRow(label: 'Status', value: 'Pending Confirmation'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Card(
                color: const Color(0xFFFFF9C4),
                child: const Padding(
                  padding: EdgeInsets.all(12),
                  child: Row(
                    children: [
                      Icon(Icons.info_outline, color: Color(0xFFF57F17)),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Payment is collected at the store or during pickup. No online payment required.',
                          style: TextStyle(fontSize: 13),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 32),
              ElevatedButton(
                onPressed: () => Navigator.of(context).pushAndRemoveUntil(
                  MaterialPageRoute(builder: (_) => const HomeScreen()),
                  (route) => false,
                ),
                child: const Text('Back to Home'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  const _DetailRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: AppTheme.textSecondary)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}
