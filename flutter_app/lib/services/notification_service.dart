/// Notification service stub.
/// Enable Firebase by adding firebase_core, firebase_messaging,
/// flutter_local_notifications to pubspec.yaml and uncommenting the code.
class NotificationService {
  static Future<void> initialize() async {
    // Firebase not configured yet — skip initialization
    // When ready, add Firebase packages and implement here.
  }

  static Future<void> updateToken() async {
    // Will send FCM token to server when Firebase is configured
  }
}
