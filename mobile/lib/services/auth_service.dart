import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  // 1. Create the secure storage instance
  final _storage = const FlutterSecureStorage();

  // Update this to your local IP if testing on a physical device,
  // or 10.0.2.2 for Android Emulator
  final String baseUrl = 'http://192.168.1.13:8000/api';

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

        // Save the token securely to the phone
        await _storage.write(key: 'auth_token', value: token);

        print('Login Success! Token saved securely.');
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

  // Helper to read the token
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
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
        return jsonDecode(response.body);
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
      // Tell Laravel to destroy the token on the server
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
    // Wipe the token from the phone's secure storage
    await _storage.delete(key: 'auth_token');
  }
}