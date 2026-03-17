# Dazzle Drys – Laundry Store Discovery & Booking Platform

A **complete, runnable** mobile application platform for the Dazzle Drys laundry franchise.
Backend: **Laravel 11** (PHP 8.2+) | Mobile: **Flutter 3.x** | Maps: **OpenStreetMap** | Push: **Firebase FCM**

## Platform Components

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Super Admin Panel** | Laravel (Blade) | Manage all stores, users, bookings, categories |
| **Store Owner Panel** | Laravel (Blade) | Manage store profile, pricing, timings, slots |
| **Mobile App** | Flutter | Customer app – discover stores, book services |
| **Backend API** | Laravel REST API | Powers the mobile app |

---

## Tech Stack

- **Backend:** Laravel 10+ / PHP 8.1+
- **Database:** MySQL 8.0+
- **Mobile:** Flutter 3+ (Android & iOS)
- **Maps:** OpenStreetMap via flutter_map
- **Push Notifications:** Firebase Cloud Messaging (FCM)
- **Authentication:** Email OTP (no SMS cost)

---

## Backend Setup

### Requirements
- PHP 8.1+
- Composer
- MySQL 8.0+
- Laravel Sanctum (API auth)

### Installation

```bash
# 1. Install dependencies
composer install

# 2. Copy env file
cp .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Configure database and mail in .env:
#   DB_DATABASE=dazzle_drys
#   DB_USERNAME=your_db_user
#   DB_PASSWORD=your_db_password
#   MAIL_MAILER=smtp
#   MAIL_HOST=smtp.gmail.com
#   MAIL_PORT=587
#   MAIL_USERNAME=noreply@yourdomain.com
#   MAIL_PASSWORD=your_app_password
#   MAIL_ENCRYPTION=tls
#   FIREBASE_SERVER_KEY=your_fcm_legacy_server_key

# 5. Create database
mysql -u root -p -e "CREATE DATABASE dazzle_drys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Run migrations and seed default data
php artisan migrate --seed

# 7. Create storage symlink
php artisan storage:link

# 8. Start development server
php artisan serve
# API will be available at http://localhost:8000/api
# Admin panel: http://localhost:8000/admin/login
# Store panel: http://localhost:8000/store/login
```

### Production Deployment (Apache / Nginx)

```bash
# Point document root to /public
# Set correct permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Backend Project Structure

```
(Laravel 11 backend – project root)
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/
│   │   │   │   ├── AuthController.php      # Email OTP send + verify
│   │   │   │   ├── StoreController.php     # Nearby stores, slots, pricing
│   │   │   │   ├── BookingController.php   # Create/list/cancel bookings
│   │   │   │   └── NotificationController.php
│   │   │   └── Admin/
│   │   │       ├── SuperAdminController.php        # Super admin panel
│   │   │       ├── StoreOwnerAuthController.php    # Store owner login/register
│   │   │       └── StoreOwnerDashboardController.php
│   │   └── Middleware/
│   │       ├── AdminMiddleware.php
│   │       └── SecurityMiddleware.php
│   ├── Models/
│   │   ├── User.php, Admin.php, StoreOwner.php
│   │   ├── Store.php, StoreTiming.php, StorePricing.php
│   │   ├── BookingSlot.php, Booking.php, BookingItem.php
│   │   ├── GarmentCategory.php, Notification.php, DeviceToken.php
│   ├── Services/
│   │   └── NotificationService.php         # FCM push + DB notification
│   └── Providers/
│       └── AppServiceProvider.php
├── bootstrap/app.php                       # Laravel 11 app bootstrap
├── config/                                 # app, database, mail, cors, sanctum...
├── database/
│   ├── migrations/                         # 12 migration files
│   └── seeders/DatabaseSeeder.php          # Admin + garment categories
├── routes/
│   ├── api.php                             # REST API routes
│   └── web.php                             # Admin + store owner web routes
├── resources/views/
│   ├── admin/                              # Super admin Blade views
│   ├── store/                              # Store owner Blade views
│   └── layouts/                            # Shared layouts
├── public/index.php
├── artisan
└── composer.json
```

### Default Super Admin Credentials
- **Email:** admin@dazzledrys.com
- **Password:** Admin@123

---

## Admin Panels

| Panel | URL |
|-------|-----|
| Super Admin | `/admin/login` |
| Store Owner | `/store/login` |

---

## REST API

Base URL: `/api`

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/send-otp` | Send OTP to email |
| POST | `/api/auth/verify-otp` | Verify OTP, get token |
| PUT | `/api/user/profile` | Update profile (auth) |
| POST | `/api/user/logout` | Logout (auth) |

### Stores
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/stores?lat=&lon=` | Nearby stores |
| GET | `/api/stores/{id}` | Store details |
| GET | `/api/stores/{id}/pricing` | Store pricing |
| GET | `/api/stores/{id}/available-slots?date=` | Available slots |

### Bookings
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/bookings` | My bookings (auth) |
| POST | `/api/bookings` | Create booking (auth) |
| GET | `/api/bookings/{id}` | Booking details (auth) |
| POST | `/api/bookings/{id}/cancel` | Cancel booking (auth) |

---

## Flutter App Setup

### Requirements
- Flutter SDK 3.x (stable channel)
- Android Studio / VS Code with Flutter plugin
- Android SDK (API 21+) / Xcode 14+ for iOS

### Firebase Setup (required before running)

1. Go to [Firebase Console](https://console.firebase.google.com) → Create project "dazzle-drys"
2. Add Android app: package `com.dazzledrys.app` → download `google-services.json`
3. Place `google-services.json` in `flutter_app/android/app/`
4. Add iOS app: bundle ID `com.dazzledrys.app` → download `GoogleService-Info.plist`
5. Place `GoogleService-Info.plist` in `flutter_app/ios/Runner/`
6. Update `flutter_app/lib/firebase_options.dart` with your Firebase config values
   OR run: `flutterfire configure` (requires FlutterFire CLI)

### Installation & Run

```bash
cd flutter_app

# 1. Install Flutter dependencies
flutter pub get

# 2. Configure your backend API URL
# Edit lib/core/api/api_client.dart
# Change: static const String baseUrl = 'https://your-api-domain.com/api';
# To:     static const String baseUrl = 'http://10.0.2.2:8000/api'; // Android emulator
# Or:     static const String baseUrl = 'http://YOUR_LOCAL_IP:8000/api'; // Physical device

# 3. Run on Android
flutter run -d android

# 4. Run on iOS (Mac only)
cd ios && pod install && cd ..
flutter run -d ios

# 5. Build release APK
flutter build apk --release

# 6. Build release iOS (Mac only)
flutter build ios --release
```

### Project Structure

```
flutter_app/
├── lib/
│   ├── main.dart                         # App entry point + Firebase init
│   ├── firebase_options.dart             # Firebase configuration
│   ├── core/
│   │   ├── api/api_client.dart           # Dio HTTP client with auth interceptor
│   │   └── theme/app_theme.dart          # Material 3 theme
│   ├── models/
│   │   ├── store.dart                    # StoreModel, StoreTimingModel
│   │   ├── booking.dart                  # BookingModel, BookingItemModel
│   │   ├── booking_slot.dart             # BookingSlotModel
│   │   └── store_pricing.dart            # StorePricingModel
│   ├── services/
│   │   ├── auth_service.dart             # OTP login, profile, FCM token
│   │   ├── store_service.dart            # Nearby stores, pricing, slots
│   │   └── booking_service.dart          # Create, list, cancel bookings
│   ├── screens/
│   │   ├── auth/
│   │   │   ├── splash_screen.dart        # Auth check → route to home/login
│   │   │   ├── login_screen.dart         # Email input → send OTP
│   │   │   └── otp_screen.dart           # 6-box OTP entry + verify
│   │   ├── home/
│   │   │   └── home_screen.dart          # Bottom nav shell
│   │   ├── stores/
│   │   │   ├── store_discovery_screen.dart # List + Map tabs, search
│   │   │   └── store_detail_screen.dart    # Pricing cart + timings + info
│   │   ├── booking/
│   │   │   ├── booking_screen.dart         # Date/slot/service picker
│   │   │   ├── booking_confirmation_screen.dart
│   │   │   └── my_bookings_screen.dart     # Booking history + cancel
│   │   ├── notifications/
│   │   │   └── notifications_screen.dart   # Push notification list
│   │   └── profile/
│   │       └── profile_screen.dart         # Edit name/phone/address + logout
│   └── widgets/
│       ├── store_card.dart               # Store list item widget
│       └── pricing_calculator.dart       # Garment counter widget
├── android/                              # Android project
│   ├── app/
│   │   ├── build.gradle
│   │   ├── google-services.json          # ⚠️ Replace with your Firebase config
│   │   └── src/main/
│   │       ├── AndroidManifest.xml
│   │       ├── kotlin/.../MainActivity.kt
│   │       └── res/
│   └── build.gradle
├── ios/                                  # iOS project
│   ├── Runner/
│   │   ├── AppDelegate.swift
│   │   ├── Info.plist
│   │   └── GoogleService-Info.plist      # ⚠️ Replace with your Firebase config
│   └── Podfile
└── pubspec.yaml
```

---

## Database Schema

```
users              - App customers (email OTP auth)
admins             - Super admins (password auth)
store_owners       - Store owner accounts (password auth)
stores             - Store profiles (pending/approved/rejected)
store_timings      - Operating hours per day
garment_categories - Global item categories
store_pricing      - Per-store garment prices
booking_slots      - Available time slots per store
bookings           - Customer bookings
booking_items      - Garments in each booking
notifications      - In-app notification records
device_tokens      - FCM tokens for push notifications
```

---

## Development Timeline (as per proposal)

| Phase | Weeks | Activities |
|-------|-------|------------|
| Planning & Architecture | 1–2 | Requirements, DB schema, API design |
| UI/UX Design | 3–4 | Wireframes, design approval |
| Backend Development | 5–8 | APIs, auth, booking engine |
| Admin Panel Development | 9–11 | Super Admin + Store Owner panels |
| Mobile App Development | 12–14 | Flutter screens |
| Testing & Deployment | 15–16 | QA, bug fixes, app store submission |

---

## Future Enhancements
- Online payment gateway (Razorpay / PayU)
- Pickup & delivery tracking
- Store ratings and reviews
- Loyalty points program
- Multi-city expansion
