import 'dart:convert';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter/material.dart';
import 'api_service.dart';

/// Background message handler — must be top-level
@pragma('vm:entry-point')
Future<void> _firebaseBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

class NotificationService {
  static final FlutterLocalNotificationsPlugin _localPlugin = FlutterLocalNotificationsPlugin();
  static GlobalKey<NavigatorState>? navigatorKey;

  static const _androidChannel = AndroidNotificationChannel(
    'bill_approval_channel',
    'Bill Approvals',
    description: 'Notifications for bill approval workflow',
    importance: Importance.high,
  );

  /// Call once after Firebase.initializeApp() and user login
  static Future<void> initialize({GlobalKey<NavigatorState>? navKey}) async {
    navigatorKey = navKey;

    // Local notification setup
    const androidInit = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosInit = DarwinInitializationSettings();
    await _localPlugin.initialize(
      const InitializationSettings(android: androidInit, iOS: iosInit),
      onDidReceiveNotificationResponse: _onNotificationTap,
    );

    // Create Android channel
    await _localPlugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(_androidChannel);

    // Firebase Messaging
    final messaging = FirebaseMessaging.instance;

    // Request permission
    await messaging.requestPermission(alert: true, badge: true, sound: true);

    // Get and send FCM token to server
    final token = await messaging.getToken();
    if (token != null) {
      try { await ApiService.updateFcmToken(token); } catch (_) {}
    }

    // Listen for token refresh
    messaging.onTokenRefresh.listen((newToken) async {
      try { await ApiService.updateFcmToken(newToken); } catch (_) {}
    });

    // Foreground messages — show local notification
    FirebaseMessaging.onMessage.listen(_showLocalNotification);

    // Background handler
    FirebaseMessaging.onBackgroundMessage(_firebaseBackgroundHandler);

    // Handle notification tap when app is in background/terminated
    FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);

    // Check if app was opened from a notification (terminated state)
    final initialMessage = await messaging.getInitialMessage();
    if (initialMessage != null) {
      _handleNotificationTap(initialMessage);
    }
  }

  /// Show notification in foreground
  static void _showLocalNotification(RemoteMessage message) {
    final notification = message.notification;
    if (notification == null) return;

    _localPlugin.show(
      notification.hashCode,
      notification.title ?? 'ScanOCR',
      notification.body ?? '',
      NotificationDetails(
        android: AndroidNotificationDetails(
          _androidChannel.id,
          _androidChannel.name,
          channelDescription: _androidChannel.description,
          importance: Importance.high,
          priority: Priority.high,
          icon: '@mipmap/ic_launcher',
          color: const Color(0xFF7F1D1D),
        ),
        iOS: const DarwinNotificationDetails(),
      ),
      payload: jsonEncode(message.data),
    );
  }

  /// Handle notification tap (from background)
  static void _handleNotificationTap(RemoteMessage message) {
    final data = message.data;
    final scanId = data['scan_id'];
    if (scanId != null && navigatorKey?.currentState != null) {
      // Navigate to bill detail
      navigatorKey!.currentState!.pushNamed('/bill-detail', arguments: int.parse(scanId.toString()));
    }
  }

  /// Handle local notification tap
  static void _onNotificationTap(NotificationResponse response) {
    if (response.payload == null) return;
    try {
      final data = jsonDecode(response.payload!);
      final scanId = data['scan_id'];
      if (scanId != null && navigatorKey?.currentState != null) {
        navigatorKey!.currentState!.pushNamed('/bill-detail', arguments: int.parse(scanId.toString()));
      }
    } catch (_) {}
  }
}
