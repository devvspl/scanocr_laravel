# Bill Approver - Flutter App

Mobile app for bill approvers to view and approve/reject bills on the go.

## Theme
- Primary: #7F1D1D (Maroon - matching ScanOCR web theme)
- Accent: #B91C1C (Red)
- Success: #16A34A (Green - for approve actions)
- Danger: #DC2626 (Red - for reject actions)

## Setup

### 1. Prerequisites
- Flutter SDK 3.2+
- Android Studio / Xcode
- Firebase project

### 2. Firebase Setup

1. Go to [Firebase Console](https://console.firebase.google.com)
2. Create a new project or use existing
3. Add Android app:
   - Package name: `com.scanocr.billapprover`
   - Download `google-services.json`
   - Place it in `android/app/google-services.json`
4. Add iOS app:
   - Bundle ID: `com.scanocr.billapprover`
   - Download `GoogleService-Info.plist`
   - Place it in `ios/Runner/GoogleService-Info.plist`

### 3. Configure API URL

Edit `lib/config/app_config.dart`:
```dart
static const String baseUrl = 'https://your-domain.com/api';
```

### 4. Run

```bash
flutter pub get
flutter run
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/login | Login with email/password |
| GET | /api/profile | Get user profile |
| POST | /api/fcm-token | Update FCM token |
| GET | /api/bills/tab-counts | Get pending/approved/rejected counts |
| GET | /api/bills | List bills (with tab, page, search params) |
| GET | /api/bills/{id} | Bill detail with supporting files |
| POST | /api/bills/{id}/approve | Approve a bill |
| POST | /api/bills/{id}/reject | Reject a bill (requires reason) |

## Features

- JWT authentication (30-day token)
- Pull-to-refresh bill list
- Infinite scroll pagination
- Tab-based filtering (Pending/Approved/Rejected/All)
- Search bills by name, vendor, company
- View bill document (PDF/Image)
- Approve with optional remark
- Reject with mandatory reason
- Firebase push notifications
- Auto-logout on token expiry

## Project Structure

```
lib/
├── main.dart                 # App entry point + theme
├── config/
│   └── app_config.dart       # API URL + colors
├── models/
│   └── bill_model.dart       # Bill & SupportFile models
├── providers/
│   └── auth_provider.dart    # Auth state management
├── screens/
│   ├── login_screen.dart     # Login page
│   ├── bill_list_screen.dart # Bill list with tabs
│   └── bill_detail_screen.dart # Detail + approve/reject
├── services/
│   ├── api_service.dart      # HTTP API client
│   └── notification_service.dart # Firebase messaging
└── widgets/                  # Reusable widgets
```
