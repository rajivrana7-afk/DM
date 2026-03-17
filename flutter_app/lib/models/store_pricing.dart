class StorePricingModel {
  final int id;
  final String garmentName;
  final double price;
  final String unit;
  final String? categoryName;

  StorePricingModel({
    required this.id,
    required this.garmentName,
    required this.price,
    required this.unit,
    this.categoryName,
  });

  factory StorePricingModel.fromJson(Map<String, dynamic> json) => StorePricingModel(
        id: json['id'],
        garmentName: json['garment_name'],
        price: (json['price'] as num).toDouble(),
        unit: json['unit'] ?? 'per piece',
        categoryName: json['category']?['name'],
      );
}
