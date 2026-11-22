import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

/// Thin client for the Taqwim token API (Laravel Sanctum, /api/v1).
class ApiClient {
  ApiClient({String? baseUrl})
      : baseUrl = baseUrl ?? const String.fromEnvironment(
          'TAQWIM_API_URL',
          defaultValue: 'http://10.0.2.2:8899',
        );

  final String baseUrl;
  String? _token;

  Future<void> restoreToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('api_token');
  }

  Future<bool> get isSignedIn async {
    if (_token != null) return true;
    await restoreToken();
    return _token != null;
  }

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<Map<String, dynamic>> login(
      String email, String password, String deviceName) async {
    final res = await http.post(
      Uri.parse('$baseUrl/api/v1/login'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode(
          {'email': email, 'password': password, 'device_name': deviceName}),
    );
    final body = jsonDecode(res.body) as Map<String, dynamic>;
    if (res.statusCode != 200) {
      throw ApiException(
          res.statusCode, (body['message'] ?? 'Login failed').toString());
    }
    _token = body['token'] as String;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('api_token', _token!);
    return body;
  }

  Future<void> logout() async {
    try {
      await http.post(Uri.parse('$baseUrl/api/v1/logout'), headers: _headers);
    } finally {
      _token = null;
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('api_token');
    }
  }

  Future<Map<String, dynamic>> me() async =>
      _getMap('/api/v1/me');

  Future<List<dynamic>> appointments(DateTime date) async => (await _getMap(
          '/api/v1/appointments?date=${date.toIso8601String().substring(0, 10)}'))[
      'appointments'] as List<dynamic>;

  Future<List<dynamic>> searchPatients(String query) async => (await _getMap(
          '/api/v1/patients?q=${Uri.encodeQueryComponent(query)}'))['patients']
      as List<dynamic>;

  Future<List<dynamic>> reminders() async =>
      (await _getMap('/api/v1/reminders'))['reminders'] as List<dynamic>;

  Future<Map<String, dynamic>> markReminderSent(int id) async {
    final res = await http.post(Uri.parse('$baseUrl/api/v1/reminders/$id/sent'),
        headers: _headers);
    if (res.statusCode == 401) throw const ApiException(401, 'Unauthorized');
    if (res.statusCode != 200) {
      throw ApiException(res.statusCode, 'Failed to mark reminder sent');
    }
    return jsonDecode(res.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> _getMap(String path) async {
    final res = await http.get(Uri.parse('$baseUrl$path'), headers: _headers);
    if (res.statusCode == 401) throw const ApiException(401, 'Unauthorized');
    if (res.statusCode != 200) {
      throw ApiException(res.statusCode, 'Request failed ($path)');
    }
    return jsonDecode(utf8.decode(res.bodyBytes)) as Map<String, dynamic>;
  }
}

class ApiException implements Exception {
  const ApiException(this.statusCode, this.message);
  final int statusCode;
  final String message;
  @override
  String toString() => message;
}
