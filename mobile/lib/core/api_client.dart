import 'dart:async';
import 'dart:io';

import 'package:http/http.dart' as http;

import 'network_config.dart';

class ApiClient {
  final http.Client _client;

  ApiClient({http.Client? client}) : _client = client ?? http.Client();

  Uri _buildUri(String path) {
    final normalizedPath = path.startsWith('/') ? path : '/$path';
    return Uri.parse('${NetworkConfig.baseUrl}$normalizedPath');
  }

  Future<http.Response> get(String path, {Map<String, String>? headers}) async {
    try {
      final response = await _client
          .get(_buildUri(path), headers: headers)
          .timeout(const Duration(seconds: 10));
      return response;
    } on SocketException {
      throw Exception(_deviceReachabilityMessage());
    } on TimeoutException {
      throw Exception(_timeoutMessage());
    } on HandshakeException {
      throw Exception(
        'Secure connection setup failed. Verify the app is still pointing to the local Docker backend.',
      );
    }
  }

  Future<http.Response> post(
    String path, {
    Map<String, String>? headers,
    Object? body,
  }) async {
    try {
      final response = await _client
          .post(_buildUri(path), headers: headers, body: body)
          .timeout(const Duration(seconds: 10));
      return response;
    } on SocketException {
      throw Exception(_deviceReachabilityMessage());
    } on TimeoutException {
      throw Exception(_timeoutMessage());
    } on HandshakeException {
      throw Exception(
        'Secure connection setup failed. Verify the app is still pointing to the local Docker backend.',
      );
    }
  }

  Future<http.Response> delete(
    String path, {
    Map<String, String>? headers,
    Object? body,
  }) async {
    try {
      final response = await _client
          .delete(_buildUri(path), headers: headers, body: body)
          .timeout(const Duration(seconds: 10));
      return response;
    } on SocketException {
      throw Exception(_deviceReachabilityMessage());
    } on TimeoutException {
      throw Exception(_timeoutMessage());
    } on HandshakeException {
      throw Exception(
        'Secure connection setup failed. Verify the app is still pointing to the local Docker backend.',
      );
    }
  }

  String _deviceReachabilityMessage() {
    return 'Unable to reach the local backend. For phone testing, keep Docker running, '
        'connect the device by USB, and run `${NetworkConfig.adbReverseCommand}` before opening the app.';
  }

  String _timeoutMessage() {
    return 'The request timed out before the backend responded. This is usually a local connection issue, '
        'not a student ID/password problem. Confirm Docker is up and `${NetworkConfig.adbReverseCommand}` is active.';
  }
}
