import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../models/store.dart';
import '../../models/booking_slot.dart';
import '../../services/store_service.dart';
import '../../services/booking_service.dart';
import 'booking_confirmation_screen.dart';

class BookingScreen extends StatefulWidget {
  final StoreModel store;
  final List<Map<String, dynamic>> cartItems;
  final double estimatedTotal;

  const BookingScreen({
    super.key,
    required this.store,
    required this.cartItems,
    required this.estimatedTotal,
  });

  @override
  State<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends State<BookingScreen> {
  DateTime _selectedDate = DateTime.now().add(const Duration(days: 1));
  BookingSlotModel? _selectedSlot;
  String _serviceType = 'store_visit';
  final _pickupAddressController = TextEditingController();
  final _notesController = TextEditingController();

  List<BookingSlotModel> _slots = [];
  bool _slotsLoading = false;
  bool _booking = false;

  @override
  void initState() {
    super.initState();
    _loadSlots();
  }

  Future<void> _loadSlots() async {
    setState(() { _slotsLoading = true; _selectedSlot = null; });
    try {
      final slots = await StoreService().getAvailableSlots(
        widget.store.id,
        DateFormat('yyyy-MM-dd').format(_selectedDate),
      );
      setState(() { _slots = slots; _slotsLoading = false; });
    } catch (_) {
      setState(() => _slotsLoading = false);
    }
  }

  Future<void> _confirmBooking() async {
    if (_selectedSlot == null) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please select a time slot')));
      return;
    }
    if (_serviceType == 'pickup' && _pickupAddressController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Please enter pickup address')));
      return;
    }

    setState(() => _booking = true);
    try {
      final booking = await BookingService().createBooking(
        storeId: widget.store.id,
        bookingSlotId: _selectedSlot!.id,
        serviceType: _serviceType,
        pickupAddress: _serviceType == 'pickup' ? _pickupAddressController.text : null,
        notes: _notesController.text.isNotEmpty ? _notesController.text : null,
        items: widget.cartItems,
      );
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => BookingConfirmationScreen(booking: booking)),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Booking failed. Slot may no longer be available.'), backgroundColor: AppTheme.error),
      );
    } finally {
      if (mounted) setState(() => _booking = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Book Service')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Store info
            Card(
              child: ListTile(
                leading: const Icon(Icons.local_laundry_service, color: AppTheme.primary, size: 36),
                title: Text(widget.store.name, style: const TextStyle(fontWeight: FontWeight.bold)),
                subtitle: Text(widget.store.address),
              ),
            ),
            const SizedBox(height: 20),

            // Date picker
            const Text('Select Date', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 8),
            SizedBox(
              height: 60,
              child: ListView.builder(
                scrollDirection: Axis.horizontal,
                itemCount: 14,
                itemBuilder: (_, i) {
                  final date = DateTime.now().add(Duration(days: i + 1));
                  final isSelected = DateFormat('yyyy-MM-dd').format(date) == DateFormat('yyyy-MM-dd').format(_selectedDate);
                  return GestureDetector(
                    onTap: () {
                      setState(() => _selectedDate = date);
                      _loadSlots();
                    },
                    child: Container(
                      width: 54,
                      margin: const EdgeInsets.only(right: 8),
                      decoration: BoxDecoration(
                        color: isSelected ? AppTheme.primary : Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: isSelected ? AppTheme.primary : Colors.grey.shade300),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(DateFormat('EEE').format(date), style: TextStyle(fontSize: 11, color: isSelected ? Colors.white70 : AppTheme.textSecondary)),
                          Text(DateFormat('d').format(date), style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: isSelected ? Colors.white : AppTheme.textPrimary)),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: 20),

            // Time slots
            const Text('Available Slots', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 8),
            if (_slotsLoading)
              const Center(child: CircularProgressIndicator())
            else if (_slots.isEmpty)
              const Text('No slots available for this date', style: TextStyle(color: AppTheme.textSecondary))
            else
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: _slots.map((slot) {
                  final isSelected = _selectedSlot?.id == slot.id;
                  return ChoiceChip(
                    label: Text(slot.slotTime.substring(0, 5)),
                    selected: isSelected,
                    selectedColor: AppTheme.primary,
                    labelStyle: TextStyle(color: isSelected ? Colors.white : AppTheme.textPrimary, fontWeight: FontWeight.w500),
                    onSelected: (_) => setState(() => _selectedSlot = slot),
                  );
                }).toList(),
              ),
            const SizedBox(height: 20),

            // Service type
            const Text('Service Type', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: _ServiceTypeCard(
                    title: 'Visit Store',
                    subtitle: 'Drop your clothes at the store',
                    icon: Icons.store,
                    isSelected: _serviceType == 'store_visit',
                    onTap: () => setState(() => _serviceType = 'store_visit'),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _ServiceTypeCard(
                    title: 'Pickup',
                    subtitle: 'We collect from your address',
                    icon: Icons.delivery_dining,
                    isSelected: _serviceType == 'pickup',
                    onTap: () => setState(() => _serviceType = 'pickup'),
                  ),
                ),
              ],
            ),
            if (_serviceType == 'pickup') ...[
              const SizedBox(height: 12),
              TextField(
                controller: _pickupAddressController,
                decoration: const InputDecoration(
                  labelText: 'Pickup Address',
                  prefixIcon: Icon(Icons.location_on),
                ),
                maxLines: 2,
              ),
            ],
            const SizedBox(height: 16),
            TextField(
              controller: _notesController,
              decoration: const InputDecoration(
                labelText: 'Special Instructions (optional)',
                prefixIcon: Icon(Icons.note),
              ),
            ),
            const SizedBox(height: 24),

            // Order summary
            Card(
              color: AppTheme.primaryLight,
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Estimated Total', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    Text('₹${widget.estimatedTotal.toStringAsFixed(0)}', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: AppTheme.primary)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              '* Final price is calculated at the store based on actual garments.',
              style: TextStyle(fontSize: 12, color: AppTheme.textSecondary),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _booking ? null : _confirmBooking,
              child: _booking
                  ? const SizedBox(height: 22, width: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text('Confirm Booking'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _pickupAddressController.dispose();
    _notesController.dispose();
    super.dispose();
  }
}

class _ServiceTypeCard extends StatelessWidget {
  final String title;
  final String subtitle;
  final IconData icon;
  final bool isSelected;
  final VoidCallback onTap;

  const _ServiceTypeCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isSelected ? AppTheme.primaryLight : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: isSelected ? AppTheme.primary : Colors.grey.shade300, width: isSelected ? 2 : 1),
        ),
        child: Column(
          children: [
            Icon(icon, color: isSelected ? AppTheme.primary : AppTheme.textSecondary, size: 28),
            const SizedBox(height: 6),
            Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: isSelected ? AppTheme.primary : AppTheme.textPrimary)),
            Text(subtitle, style: const TextStyle(fontSize: 11, color: AppTheme.textSecondary), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
