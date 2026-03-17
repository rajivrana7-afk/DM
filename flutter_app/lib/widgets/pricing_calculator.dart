import 'package:flutter/material.dart';
import '../core/theme/app_theme.dart';

/// A simple widget to display and edit a pricing cart entry.
class PricingCalculatorRow extends StatelessWidget {
  final String itemName;
  final double price;
  final String unit;
  final int quantity;
  final ValueChanged<int> onQuantityChanged;

  const PricingCalculatorRow({
    super.key,
    required this.itemName,
    required this.price,
    required this.unit,
    required this.quantity,
    required this.onQuantityChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(itemName, style: const TextStyle(fontWeight: FontWeight.w500)),
              Text('₹${price.toStringAsFixed(0)} / $unit', style: const TextStyle(color: AppTheme.textSecondary, fontSize: 12)),
            ],
          ),
        ),
        Row(
          children: [
            if (quantity > 0)
              IconButton(
                icon: const Icon(Icons.remove_circle_outline, color: AppTheme.primary),
                onPressed: () => onQuantityChanged(quantity - 1),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
              ),
            if (quantity > 0)
              SizedBox(
                width: 28,
                child: Text('$quantity', textAlign: TextAlign.center, style: const TextStyle(fontWeight: FontWeight.bold)),
              ),
            IconButton(
              icon: const Icon(Icons.add_circle_outline, color: AppTheme.primary),
              onPressed: () => onQuantityChanged(quantity + 1),
              padding: EdgeInsets.zero,
              constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
            ),
          ],
        ),
      ],
    );
  }
}
