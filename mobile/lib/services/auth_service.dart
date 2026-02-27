import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  // 1. Create the secure storage instance
  final _storage = const FlutterSecureStorage();

<<<<<<< HEAD
  // Update this to your local IP if testing on a physical device,
  // or 10.0.2.2 for Android Emulator
  final String baseUrl = 'http://10.0.2.2:8000/api';

=======
  // Use 10.0.2.2 for Android Emulator connecting to local Docker
  final String baseUrl = 'http://10.0.2.2:8000/api';

  // --- TICKET 10: Secure Login ---
>>>>>>> DC-11-create-dashboard
  Future<bool> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
<<<<<<< HEAD
        headers: {'Content-Type': 'application/json'},
=======
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
>>>>>>> DC-11-create-dashboard
        body: jsonEncode({'email': email, 'password': password}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final token = data['token'];

<<<<<<< HEAD
        // 2. Save the token securely to the phone
        await _storage.write(key: 'auth_token', value: token);

        print('Login Success! Token saved securely.');
=======
        // Save the token securely to the phone
        await _storage.write(key: 'auth_token', value: token);
>>>>>>> DC-11-create-dashboard
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

<<<<<<< HEAD
  // Bonus: A quick method to read the token later when making API requests
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }
=======
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
>>>>>>> DC-11-create-dashboard
}
