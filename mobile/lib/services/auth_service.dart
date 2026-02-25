import 'package:http/http.dart' as http;
import 'dart:convert';

class AuthService {
  // Use 10.0.2.2 for Android Emulator connecting to local Docker
  // Use 127.0.0.1 for iOS Simulator
  // Use your local IP (e.g., 192.168.x.x) if using a physical device
  final String _baseUrl = 'http://10.0.2.2:8000/api';

  Future<String?> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$_baseUrl/login'),
        headers: {'Accept': 'application/json'},
        body: {'email': email, 'password': password},
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        // Assuming your Laravel Sanctum returns: { "token": "1|xyz..." }
        return data['token'];
      } else {
        print('Login failed: ${response.body}');
        return null;
      }
    } catch (e) {
      print('Network error: $e');
      return null;
    }
  }
}
