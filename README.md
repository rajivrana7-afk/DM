# Dazzle Drys – Laundry Store Discovery & Booking Platform

A complete mobile application platform for the Dazzle Drys laundry franchise.

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

# 2. Copy env file and configure
cp .env.example .env
php artisan key:generate

# 3. Configure .env
DB_DATABASE=dazzle_drys
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
MAIL_HOST=smtp.your-provider.com
MAIL_USERNAME=noreply@dazzledrys.com
MAIL_PASSWORD=your_mail_password
FIREBASE_SERVER_KEY=your_fcm_server_key

# 4. Run migrations and seed
php artisan migrate --seed

# 5. Serve
php artisan serve
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

```bash
cd flutter_app

# Install dependencies
flutter pub get

# Configure API URL
# Edit lib/core/api/api_client.dart → baseUrl

# Add google-services.json (Android) from Firebase Console
# Add GoogleService-Info.plist (iOS) from Firebase Console

# Run
flutter run
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
