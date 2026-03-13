import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

class ClearanceService {
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  // Use 10.0.2.2 for Android Emulator connecting to local Docker
  // Update this to your local IP if testing on a physical device.
  final String baseUrl = 'http://10.0.2.2:8000/api';

  Future<String?> _getToken() async {
    return _storage.read(key: 'auth_token');
  }

  Future<Map<String, dynamic>?> getClearanceStatus() async {
    final token = await _getToken();
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
      }
    } catch (_) {}

    return null;
  }

  Future<List<dynamic>> getClearanceHistory() async {
  final token = await _getToken();
  if (token == null) return [];

  try {
    final response = await http.get(
      Uri.parse('$baseUrl/clearance/history'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      final decoded = jsonDecode(response.body) as Map<String, dynamic>;
      final data = decoded['data'];
      if (data is List) return data;
    }
  } catch (_) {}

  return [];
}

  Future<Map<String, dynamic>?> getClearanceHistoryDetail(int requestId) async {
    final token = await _getToken();
    if (token == null) return null;

    try {
      final response = await http.get(
        Uri.parse('$baseUrl/clearance/history/$requestId'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body) as Map<String, dynamic>;
      }
    } catch (_) {}

    return null;
  }

  Future<bool> requestClearance() async {
    final token = await _getToken();
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

      if (response.statusCode == 201 || response.statusCode == 200) {
        return true;
      }
    } catch (_) {}

    return false;
  }

  Future<bool> cancelClearance() async {
    final token = await _getToken();
    if (token == null) {
      throw Exception('You are not logged in.');
    }

    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/clearance'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        return true;
      }

      String message = 'Failed to cancel the current clearance request.';
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic> && decoded['message'] is String) {
          message = decoded['message'] as String;
        } else if (decoded is Map<String, dynamic> &&
            decoded['error'] is String) {
          message = decoded['error'] as String;
        }
      } catch (_) {
        // Keep fallback message when response is not JSON.
      }

      throw Exception(message);
    } catch (e) {
      if (e is Exception) {
        rethrow;
      }

      throw Exception('Network error while cancelling clearance: $e');
    }
  }
}
