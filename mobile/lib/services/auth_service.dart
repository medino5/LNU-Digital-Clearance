import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AuthService {
  // 1. Create the secure storage instance
  final _storage = const FlutterSecureStorage();

  // Update this to your local IP if testing on a physical device,
  // or 10.0.2.2 for Android Emulator
  final String baseUrl = 'http://10.0.2.2:8000/api';

  Future<bool> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final token = data['token'];

        // 2. Save the token securely to the phone
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

  // Bonus: A quick method to read the token later when making API requests
  Future<String?> getToken() async {
    return await _storage.read(key: 'auth_token');
  }
}
