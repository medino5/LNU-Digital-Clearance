import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

class HistoryService {
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  // Use 10.0.2.2 for Android Emulator connecting to local Docker
  // Update this to your local IP if testing on a physical device.
  final String _baseUrl = 'http://10.0.2.2:8000/api';

  Future<String?> _getToken() async {
    return _storage.read(key: 'auth_token');
  }

  Future<Map<String, dynamic>> fetchHistory({int page = 1}) async {
    final token = await _getToken();
    if (token == null) {
      throw Exception('You are not logged in.');
    }

    try {
      final uri = Uri.parse('$_baseUrl/clearance/history').replace(
        queryParameters: {'page': page.toString()},
      );

      final response = await http
          .get(
            uri,
            headers: {
              'Accept': 'application/json',
              'Authorization': 'Bearer $token',
            },
          )
          .timeout(const Duration(seconds: 20));

      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic>) {
          return decoded;
        }
        throw Exception('Unexpected response format.');
      }

      String message = response.body;
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic>) {
          if (decoded['message'] is String) {
            message = decoded['message'] as String;
          } else if (decoded['error'] is String) {
            message = decoded['error'] as String;
          }
        }
      } catch (_) {
        // Keep fallback when response isn't JSON.
      }

      throw Exception(message);
    } on SocketException {
      throw Exception('Network error. Please check your connection.');
    } on TimeoutException {
      throw Exception('Request timed out. Please try again.');
    }
  }
}
