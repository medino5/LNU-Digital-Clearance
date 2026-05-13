import 'dart:async';

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
    } on http.ClientException {
      throw Exception(_deviceReachabilityMessage());
    } on TimeoutException {
      throw Exception(_timeoutMessage());
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
    } on http.ClientException {
      throw Exception(_deviceReachabilityMessage());
    } on TimeoutException {
      throw Exception(_timeoutMessage());
    }
  }

  Future<http.Response> patch(
    String path, {
    Map<String, String>? headers,
    Object? body,
  }) async {
    try {
      final response = await _client
          .patch(_buildUri(path), headers: headers, body: body)
          .timeout(const Duration(seconds: 10));
      return response;
    } on http.ClientException {
      throw Exception(_deviceReachabilityMessage());
    } on TimeoutException {
      throw Exception(_timeoutMessage());
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
    } on http.ClientException {
      throw Exception(_deviceReachabilityMessage());
    } on TimeoutException {
      throw Exception(_timeoutMessage());
    }
  }

  Future<http.StreamedResponse> multipartPost(
    String path, {
    required Map<String, String> headers,
    required String fieldName,
    required List<int> bytes,
    required String filename,
  }) async {
    try {
      final request = http.MultipartRequest('POST', _buildUri(path));
      request.headers.addAll(headers);
      request.files.add(
        http.MultipartFile.fromBytes(fieldName, bytes, filename: filename),
      );

      return await request.send().timeout(const Duration(seconds: 20));
    } on http.ClientException {
      throw Exception(_deviceReachabilityMessage());
    } on TimeoutException {
      throw Exception(_timeoutMessage());
    }
  }

  String _deviceReachabilityMessage() {
    if (NetworkConfig.usesLocalDockerBackend) {
      return 'Unable to reach the local backend. For phone testing, keep Docker running, '
          'connect the device by USB, and run `${NetworkConfig.adbReverseCommand}` before opening the app.';
    }

    return 'Unable to reach the deployed backend at ${NetworkConfig.baseUrl}. '
        'Check your internet connection and confirm the Render service is running.';
  }

  String _timeoutMessage() {
    if (NetworkConfig.usesLocalDockerBackend) {
      return 'The request timed out before the backend responded. This is usually a local connection issue, '
          'not a student ID/password problem. Confirm Docker is up and `${NetworkConfig.adbReverseCommand}` is active.';
    }

    return 'The deployed backend at ${NetworkConfig.baseUrl} did not respond in time. '
        'Check Render logs or verify the server is still online.';
  }
}
