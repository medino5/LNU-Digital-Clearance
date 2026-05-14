import 'dart:convert';

import '../core/api_client.dart';
import '../core/session_expired_exception.dart';
import 'auth_token_store.dart';
import 'clearance_pdf_download.dart';

class ClearanceService {
  ClearanceService({ApiClient? apiClient, AuthTokenStore? tokenStore})
    : _apiClient = apiClient ?? ApiClient(),
      _tokenStore = tokenStore ?? SecureAuthTokenStore();

  final ApiClient _apiClient;
  final AuthTokenStore _tokenStore;

  Future<Map<String, dynamic>> getCurrentClearance() async {
    final response = await _apiClient.get(
      '/clearance/current',
      headers: await _authHeaders(),
    );

    _throwIfSessionExpired(response.statusCode);

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(response.body, 'Unable to load the current clearance.'),
      );
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> getClearanceHistory() async {
    final response = await _apiClient.get(
      '/clearance/history',
      headers: await _authHeaders(),
    );

    _throwIfSessionExpired(response.statusCode);

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(response.body, 'Unable to load clearance history.'),
      );
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> createOrResumeClearance() async {
    final response = await _apiClient.post(
      '/clearance',
      headers: await _authHeaders(contentType: true),
      body: jsonEncode({}),
    );

    _throwIfSessionExpired(response.statusCode);

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(
          response.body,
          'Unable to start the clearance request.',
        ),
      );
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> cancelCurrentClearance() async {
    final response = await _apiClient.delete(
      '/clearance/current',
      headers: await _authHeaders(),
    );

    _throwIfSessionExpired(response.statusCode);

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(response.body, 'Unable to cancel the clearance.'),
      );
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> resubmitStep(int stepId) async {
    final response = await _apiClient.post(
      '/clearance/steps/$stepId/resubmit',
      headers: await _authHeaders(contentType: true),
      body: jsonEncode({}),
    );

    _throwIfSessionExpired(response.statusCode);

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(
          response.body,
          'Unable to resubmit the flagged clearance step.',
        ),
      );
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<String> downloadCurrentClearancePdf({String? referenceNumber}) async {
    final response = await _apiClient.get(
      '/clearance/current/pdf',
      headers: await _authHeaders(),
    );

    _throwIfSessionExpired(response.statusCode);

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(response.body, 'Unable to download the clearance PDF.'),
      );
    }

    final fileName = (referenceNumber?.isNotEmpty ?? false)
        ? '$referenceNumber.pdf'
        : 'student-clearance.pdf';
    final safeName = fileName.replaceAll(RegExp(r'[^A-Za-z0-9._-]'), '_');
    return saveClearancePdfBytes(response.bodyBytes, safeName);
  }

  Future<Map<String, String>> _authHeaders({bool contentType = false}) async {
    final token = await _tokenStore.readToken();

    if (token == null) {
      throw Exception('You are not logged in.');
    }

    return {
      'Accept': 'application/json',
      if (contentType) 'Content-Type': 'application/json',
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
      // Use the fallback when the response is not JSON.
    }

    return fallback;
  }

  void _throwIfSessionExpired(int statusCode) {
    if (statusCode == 401) {
      throw SessionExpiredException();
    }
  }
}
