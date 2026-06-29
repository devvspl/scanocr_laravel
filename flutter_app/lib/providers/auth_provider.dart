import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';

class AuthProvider extends ChangeNotifier {
  bool _isLoggedIn = false;
  bool _isLoading = true;
  String _userName = '';
  String _userEmail = '';

  bool get isLoggedIn => _isLoggedIn;
  bool get isLoading => _isLoading;
  String get userName => _userName;
  String get userEmail => _userEmail;

  Future<void> checkAuth() async {
    final token = await ApiService.token;
    final prefs = await SharedPreferences.getInstance();
    _userName = prefs.getString('user_name') ?? '';
    _userEmail = prefs.getString('user_email') ?? '';
    _isLoggedIn = token != null && token.isNotEmpty;
    _isLoading = false;
    notifyListeners();
  }

  Future<String?> login(String email, String password, {String? fcmToken}) async {
    final result = await ApiService.login(email, password, fcmToken: fcmToken);
    if (result['success'] == true) {
      _isLoggedIn = true;
      _userName = result['user']?['name'] ?? '';
      _userEmail = result['user']?['email'] ?? '';
      notifyListeners();
      return null; // success
    }
    return result['message'] ?? 'Login failed';
  }

  Future<void> logout() async {
    await ApiService.clearToken();
    _isLoggedIn = false;
    _userName = '';
    _userEmail = '';
    notifyListeners();
  }
}
