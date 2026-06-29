import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../config/app_config.dart';
import '../models/bill_model.dart';

class ApiService {
  static String? _token;

  static Future<String?> get token async {
    if (_token != null) return _token;
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
    return _token;
  }

  static Future<void> setToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }

  static Future<void> clearToken() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
    await prefs.remove('user_name');
    await prefs.remove('user_email');
  }

  static Future<Map<String, String>> _headers() async {
    final t = await token;
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (t != null) 'Authorization': 'Bearer $t',
    };
  }

  // ── Auth ──────────────────────────────────────────────────────────────────

  static Future<Map<String, dynamic>> login(String email, String password, {String? fcmToken}) async {
    final res = await http.post(
      Uri.parse('${AppConfig.baseUrl}/login'),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
        if (fcmToken != null) 'fcm_token': fcmToken,
      }),
    );

    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['success'] == true) {
      await setToken(data['token']);
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('user_name', data['user']['name'] ?? '');
      await prefs.setString('user_email', data['user']['email'] ?? '');
    }
    return data;
  }

  // ── Bills ─────────────────────────────────────────────────────────────────

  static Future<Map<String, int>> tabCounts({Map<String, String>? filters}) async {
    final params = <String, String>{};
    if (filters != null) params.addAll(filters..removeWhere((k, v) => v.isEmpty));

    final uri = Uri.parse('${AppConfig.baseUrl}/bills/tab-counts').replace(queryParameters: params.isNotEmpty ? params : null);
    final res = await http.get(uri, headers: await _headers());

    if (res.statusCode == 401) throw AuthException();
    final data = jsonDecode(res.body);
    final counts = data['data'] as Map<String, dynamic>;
    return counts.map((k, v) => MapEntry(k, (v as num).toInt()));
  }

  static Future<Map<String, dynamic>> listBills({
    String tab = 'pending',
    int page = 1,
    int perPage = 20,
    String search = '',
    Map<String, String>? filters,
  }) async {
    final params = <String, String>{
      'tab': tab,
      'page': page.toString(),
      'per_page': perPage.toString(),
      if (search.isNotEmpty) 'search': search,
    };
    if (filters != null) params.addAll(filters..removeWhere((k, v) => v.isEmpty));

    final uri = Uri.parse('${AppConfig.baseUrl}/bills').replace(queryParameters: params);
    final res = await http.get(uri, headers: await _headers());
    if (res.statusCode == 401) throw AuthException();
    return jsonDecode(res.body);
  }

  static Future<BillDetail> billDetail(int scanId) async {
    final res = await http.get(
      Uri.parse('${AppConfig.baseUrl}/bills/$scanId'),
      headers: await _headers(),
    );

    if (res.statusCode == 401) throw AuthException();
    final data = jsonDecode(res.body);
    if (data['success'] != true) throw Exception(data['message'] ?? 'Failed to load');
    return BillDetail.fromJson(data['data']);
  }

  static Future<Map<String, dynamic>> approveBill(int scanId, {String? remark}) async {
    final res = await http.post(
      Uri.parse('${AppConfig.baseUrl}/bills/$scanId/approve'),
      headers: await _headers(),
      body: jsonEncode({'remark': remark}),
    );

    if (res.statusCode == 401) throw AuthException();
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> rejectBill(int scanId, String reason) async {
    final res = await http.post(
      Uri.parse('${AppConfig.baseUrl}/bills/$scanId/reject'),
      headers: await _headers(),
      body: jsonEncode({'reason': reason}),
    );

    if (res.statusCode == 401) throw AuthException();
    return jsonDecode(res.body);
  }

  // ── Filters (paginated + searchable) ────────────────────────────────────

  static Future<Map<String, dynamic>> fetchFilterData(String endpoint, {String q = '', int page = 1}) async {
    final params = <String, String>{
      'page': page.toString(),
      if (q.isNotEmpty) 'q': q,
    };
    final uri = Uri.parse('${AppConfig.baseUrl}/filters/$endpoint').replace(queryParameters: params);
    final res = await http.get(uri, headers: await _headers());
    if (res.statusCode == 401) throw AuthException();
    final data = jsonDecode(res.body);
    return {
      'items': List<Map<String, dynamic>>.from(data['data'] ?? []),
      'has_more': data['has_more'] ?? false,
    };
  }

  // ── FCM ───────────────────────────────────────────────────────────────────

  static Future<void> updateFcmToken(String fcmToken) async {
    await http.post(
      Uri.parse('${AppConfig.baseUrl}/fcm-token'),
      headers: await _headers(),
      body: jsonEncode({'fcm_token': fcmToken}),
    );
  }
}

class AuthException implements Exception {
  final String message = 'Session expired. Please login again.';
}
