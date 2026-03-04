import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  final _storage = const FlutterSecureStorage();

  // Use 10.0.2.2 for Android Emulator connecting to local Docker
  // Update this to your local IP if testing on a physical device.
  final String baseUrl = 'http://10.0.2.2:8000/api';

  // --- TICKET 10: Secure Login ---
  Future<bool> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({'email': email, 'password': password}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final token = data['token'];

        await _storage.write(key: 'auth_token', value: token);
        return true;
      } else {
        print('Login failed: ${response.body}');
        return false;
      }
    } catch (e) {
      print('Network error: $e');
      return false;
    }
  }

  Future<String?> getToken() async {
    return _storage.read(key: 'auth_token');
  }

  // --- TICKET 11: Get User Details ---
  Future<Map<String, dynamic>?> getUser() async {
    final token = await getToken();
    if (token == null) return null;

    try {
      final response = await http.get(
        Uri.parse('$baseUrl/user'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body) as Map<String, dynamic>;
      }
    } catch (e) {
      print('Failed to fetch user: $e');
    }
    return null;
  }

  // --- TICKET 11: Secure Logout ---
  Future<void> logout() async {
    final token = await getToken();
    if (token != null) {
      try {
        await http.post(
          Uri.parse('$baseUrl/logout'),
          headers: {
            'Accept': 'application/json',
            'Authorization': 'Bearer $token',
          },
        );
      } catch (e) {
        print(
          'Server logout failed, but local token will still be deleted: $e',
        );
      }
    }

    await _storage.delete(key: 'auth_token');
  }

  // --- TICKET 15: Clearance Status ---
  Future<Map<String, dynamic>?> getClearanceStatus() async {
    final token = await getToken();
    if (token == null) return null;

    try {
      final response = await http.get(
        Uri.parse('$baseUrl/clearance/status'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body) as Map<String, dynamic>;
      } else {
        print('Failed to fetch clearance status: ${response.body}');
      }
    } catch (e) {
      print('Network error while fetching clearance status: $e');
    }

    return null;
  }

  // --- TICKET 15: Request Clearance ---
  Future<bool> requestClearance() async {
    final token = await getToken();
    if (token == null) return false;

    try {
      final response = await http.post(
        Uri.parse('$baseUrl/clearance'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({}),
      );

      if (response.statusCode == 201) {
        return true;
      } else {
        print('Failed to request clearance: ${response.body}');
        return false;
      }
    } catch (e) {
      print('Network error while requesting clearance: $e');
      return false;
    }
  }
}
