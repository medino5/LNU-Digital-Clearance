import 'dart:developer' as developer;
import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/api_client.dart';
import '../core/debug/build_identity_card.dart';
import '../core/network_config.dart';
import '../core/session_expired_exception.dart';

class AuthService {
  AuthService({ApiClient? apiClient}) : _apiClient = apiClient ?? ApiClient();

  final FlutterSecureStorage _storage = const FlutterSecureStorage();
  final ApiClient _apiClient;

  Future<void> login(String studentId, String password) async {
    final normalizedStudentId = studentId.trim();

    developer.log(
      'Student login request -> ${NetworkConfig.baseUrl}/login '
      '(student_id=$normalizedStudentId, build=${BuildIdentityCard.debugBuildLabel})',
      name: 'AuthService',
    );

    try {
      final response = await _apiClient.post(
        '/login',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'student_id': normalizedStudentId,
          'password': password,
        }),
      );

      final message = _extractMessage(response.body, 'Unable to sign in.');

      developer.log(
        'Student login response <- status=${response.statusCode} message=$message',
        name: 'AuthService',
      );

      if (response.statusCode != 200) {
        throw Exception(message);
      }

      final payload = jsonDecode(response.body) as Map<String, dynamic>;
      final token = payload['token'] as String?;

      if (token == null || token.isEmpty) {
        throw Exception('The server did not return an auth token.');
      }

      await _storage.write(key: 'auth_token', value: token);
    } catch (error, stackTrace) {
      developer.log(
        'Student login failed: $error',
        name: 'AuthService',
        error: error,
        stackTrace: stackTrace,
      );
      rethrow;
    }
  }

  Future<Map<String, dynamic>> getProfile() async {
    final token = await getToken();

    if (token == null) {
      throw Exception('You are not logged in.');
    }

    final response = await _apiClient.get(
      '/me',
      headers: _authHeaders(token),
    );

    if (response.statusCode == 401) {
      await clearStoredToken();
      throw SessionExpiredException();
    }

    if (response.statusCode != 200) {
      throw Exception(_extractMessage(response.body, 'Unable to load profile.'));
    }

    final payload = jsonDecode(response.body) as Map<String, dynamic>;
    return (payload['profile'] as Map?)?.cast<String, dynamic>() ??
        <String, dynamic>{};
  }

  Future<String?> getToken() async {
    return _storage.read(key: 'auth_token');
  }

  Future<bool> hasToken() async {
    return (await getToken()) != null;
  }

  Future<bool> validateSession() async {
    final token = await getToken();

    if (token == null) {
      return false;
    }

    final response = await _apiClient.get(
      '/me',
      headers: _authHeaders(token),
    );

    if (response.statusCode == 200) {
      return true;
    }

    if (response.statusCode == 401) {
      await clearStoredToken();
      return false;
    }

    throw Exception(
      _extractMessage(response.body, 'Unable to validate the current session.'),
    );
  }

  Future<void> clearStoredToken() async {
    await _storage.delete(key: 'auth_token');
  }

  Future<void> logout() async {
    final token = await getToken();

    if (token != null) {
      try {
        await _apiClient.post(
          '/logout',
          headers: _authHeaders(token),
        );
      } catch (_) {
        // Local logout still succeeds even if the server request fails.
      }
    }

    await clearStoredToken();
  }

  Map<String, String> _authHeaders(String token) {
    return {
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }

  String _extractMessage(String body, String fallback) {
    try {
      final payload = jsonDecode(body);

      if (payload is Map<String, dynamic> && payload['message'] is String) {
        return payload['message'] as String;
      }
    } catch (_) {
      // Ignore JSON parsing failures and use the fallback.
    }

    return fallback;
  }
}
