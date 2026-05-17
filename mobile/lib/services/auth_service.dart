import 'dart:developer' as developer;
import 'dart:convert';

import '../core/api_client.dart';
import '../core/network_config.dart';
import '../core/session_expired_exception.dart';
import 'auth_token_store.dart';
import 'registration_service.dart';

class AuthService {
  AuthService({ApiClient? apiClient, AuthTokenStore? tokenStore})
    : _apiClient = apiClient ?? ApiClient(),
      _tokenStore = tokenStore ?? SecureAuthTokenStore();

  final ApiClient _apiClient;
  final AuthTokenStore _tokenStore;

  Future<void> login(String studentId, String password) async {
    final normalizedStudentId = studentId.trim();

    developer.log(
      'Student login request -> ${NetworkConfig.baseUrl}/login '
      '(student_id=$normalizedStudentId, build=${NetworkConfig.buildLabel})',
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

      await _tokenStore.writeToken(token);
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

    final response = await _apiClient.get('/me', headers: _authHeaders(token));

    if (response.statusCode == 401) {
      await clearStoredToken();
      throw SessionExpiredException();
    }

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(response.body, 'Unable to load profile.'),
      );
    }

    final payload = jsonDecode(response.body) as Map<String, dynamic>;
    return (payload['profile'] as Map?)?.cast<String, dynamic>() ??
        <String, dynamic>{};
  }

  Future<Map<String, dynamic>> updateProfilePhoto({
    required List<int> bytes,
    required String filename,
  }) async {
    final token = await getToken();

    if (token == null) {
      throw Exception('You are not logged in.');
    }

    final streamedResponse = await _apiClient.multipartPost(
      '/me/profile-photo',
      headers: _authHeaders(token),
      fieldName: 'profile_photo',
      bytes: bytes,
      filename: filename,
    );

    final responseBody = await streamedResponse.stream.bytesToString();

    if (streamedResponse.statusCode == 401) {
      await clearStoredToken();
      throw SessionExpiredException();
    }

    if (streamedResponse.statusCode != 200) {
      throw Exception(
        _extractMessage(responseBody, 'Unable to upload profile picture.'),
      );
    }

    final payload = jsonDecode(responseBody) as Map<String, dynamic>;
    return (payload['profile'] as Map?)?.cast<String, dynamic>() ??
        <String, dynamic>{};
  }

  Future<String> changePassword({
    required String password,
    required String passwordConfirmation,
  }) async {
    final token = await getToken();

    if (token == null) {
      throw Exception('You are not logged in.');
    }

    final response = await _apiClient.post(
      '/me/password',
      headers: {..._authHeaders(token), 'Content-Type': 'application/json'},
      body: jsonEncode({
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );

    if (response.statusCode == 401) {
      await clearStoredToken();
      throw SessionExpiredException();
    }

    final message = _extractMessage(
      response.body,
      response.statusCode == 200
          ? 'Password updated successfully.'
          : 'Unable to update password.',
    );

    if (response.statusCode != 200) {
      throw Exception(message);
    }

    return message;
  }

  Future<RegistrationOptions> loadAcademicProfileOptions() async {
    final response = await _apiClient.get(
      '/registration/options',
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(response.body, 'Unable to load academic options.'),
      );
    }

    return RegistrationOptions.fromJson(
      (jsonDecode(response.body) as Map<String, dynamic>),
    );
  }

  Future<Map<String, dynamic>> updateAcademicProfile({
    required String firstName,
    required String middleInitial,
    required String lastName,
    required String nameExtension,
    required int? programId,
    required int? yearLevel,
    required String? section,
    required String dateOfBirth,
  }) async {
    final token = await getToken();

    if (token == null) {
      throw Exception('You are not logged in.');
    }

    final body = <String, dynamic>{
      'first_name': firstName.trim(),
      'middle_initial': middleInitial.trim().isEmpty
          ? null
          : middleInitial.trim(),
      'last_name': lastName.trim(),
      'name_extension': nameExtension.trim().isEmpty
          ? null
          : nameExtension.trim(),
      'date_of_birth': dateOfBirth,
    };

    if (programId != null) {
      body['program_id'] = programId;
    }

    if (yearLevel != null) {
      body['year_level'] = yearLevel;
    }

    if (section != null) {
      body['section'] = section;
    }

    final response = await _apiClient.patch(
      '/me/academic-profile',
      headers: {..._authHeaders(token), 'Content-Type': 'application/json'},
      body: jsonEncode(body),
    );

    if (response.statusCode == 401) {
      await clearStoredToken();
      throw SessionExpiredException();
    }

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(response.body, 'Unable to update academic profile.'),
      );
    }

    final payload = jsonDecode(response.body) as Map<String, dynamic>;
    final fullPayload = (payload['payload'] as Map?)?.cast<String, dynamic>();

    if (fullPayload != null) {
      return fullPayload;
    }

    final profile = (payload['profile'] as Map?)?.cast<String, dynamic>();
    return profile == null ? <String, dynamic>{} : {'student': profile};
  }

  Future<String> resetForgottenPassword({
    required String studentIdNumber,
    required String dateOfBirth,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _apiClient.post(
      '/forgot-password',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'student_id_number': studentIdNumber.trim(),
        'date_of_birth': dateOfBirth,
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );

    final message = _extractMessage(
      response.body,
      response.statusCode == 200
          ? 'Password reset successful. You can now sign in.'
          : 'Unable to reset password.',
    );

    if (response.statusCode != 200) {
      throw Exception(message);
    }

    return message;
  }

  Future<String?> getToken() async {
    return _tokenStore.readToken();
  }

  Future<bool> hasToken() async {
    return (await getToken()) != null;
  }

  Future<bool> validateSession() async {
    final token = await getToken();

    if (token == null) {
      return false;
    }

    final response = await _apiClient.get('/me', headers: _authHeaders(token));

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
    await _tokenStore.clearToken();
  }

  Future<void> logout() async {
    final token = await getToken();

    if (token != null) {
      try {
        await _apiClient.post('/logout', headers: _authHeaders(token));
      } catch (_) {
        // Local logout still succeeds even if the server request fails.
      }
    }

    await clearStoredToken();
  }

  Map<String, String> _authHeaders(String token) {
    return {'Accept': 'application/json', 'Authorization': 'Bearer $token'};
  }

  String _extractMessage(String body, String fallback) {
    try {
      final payload = jsonDecode(body);

      if (payload is Map<String, dynamic>) {
        final errors = payload['errors'];

        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) {
            return first.first.toString();
          }
        }

        if (payload['message'] is String) {
          return payload['message'] as String;
        }
      }
    } catch (_) {
      // Ignore JSON parsing failures and use the fallback.
    }

    return fallback;
  }
}
