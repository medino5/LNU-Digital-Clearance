// DEBUG ONLY - remove before production
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../api_client.dart';
import '../input_sanitizers.dart';
import '../network_config.dart';
import 'build_identity_card.dart';

class ConnectionTestScreen extends StatefulWidget {
  const ConnectionTestScreen({super.key});

  @override
  State<ConnectionTestScreen> createState() => _ConnectionTestScreenState();
}

class _ConnectionTestScreenState extends State<ConnectionTestScreen> {
  final TextEditingController _studentIdController = TextEditingController(
    text: '2302314',
  );
  final TextEditingController _passwordController = TextEditingController(
    text: 'password',
  );
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  String _status = 'Not tested yet';
  bool _isTesting = false;

  @override
  void dispose() {
    _studentIdController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _testConnection() async {
    setState(() {
      _isTesting = true;
      _status = 'Testing backend reachability...';
    });

    try {
      final response = await ApiClient().get('/me');
      if (response.statusCode == 401) {
        final extraLines = NetworkConfig.usesLocalDockerBackend
            ? 'Mobile host: ${NetworkConfig.androidDebugHost}\n'
                  'adb reverse: ${NetworkConfig.adbReverseCommand}\n'
            : 'Shared portal: ${NetworkConfig.sharedPortalUrl}\n';
        setState(() {
          _status =
              'OK: Backend reachable.\n'
              'GET /me returned 401, which is expected before login.\n\n'
              '$extraLines'
              'URL: ${NetworkConfig.baseUrl}';
        });
      } else {
        setState(() {
          _status =
              'Unexpected response from GET /me:\n'
              'Status: ${response.statusCode}\n\n'
              'Body:\n${response.body}';
        });
      }
    } catch (e) {
      final troubleshooting = NetworkConfig.usesLocalDockerBackend
          ? '1. Docker is running and the backend is up\n'
                '2. The phone is connected by USB\n'
                '3. `${NetworkConfig.adbReverseCommand}` has been run\n'
                '4. Reinstall the app after changing NetworkConfig'
          : '1. The phone has internet access\n'
                '2. The Railway deployment is healthy\n'
                '3. APP_API_BASE_URL points to the live backend\n'
                '4. The latest tester APK is installed';
      setState(() {
        _status = 'Error: Connection failed.\n\n$e\n\nCheck:\n$troubleshooting';
      });
    } finally {
      setState(() {
        _isTesting = false;
      });
    }
  }

  Future<void> _testStudentLogin() async {
    setState(() {
      _isTesting = true;
      _status = 'Testing student login...';
    });

    try {
      final response = await ApiClient().post(
        '/login',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'student_id': _studentIdController.text.trim(),
          'password': _passwordController.text,
        }),
      );

      final payload = _tryDecode(response.body);
      final token = payload?['token'] as String?;
      final message = payload?['message'] as String?;

      if (response.statusCode == 200 && token != null && token.isNotEmpty) {
        await _storage.write(key: 'auth_token', value: token);
        setState(() {
          _status =
              'OK: Student login succeeded.\n'
              'Message: ${message ?? 'Login successful.'}\n'
              'Token saved to secure storage.\n\n'
              'Student ID: ${_studentIdController.text.trim()}\n'
              'API URL: ${NetworkConfig.baseUrl}/login';
        });
      } else {
        setState(() {
          _status =
              'Login failed.\n'
              'Status: ${response.statusCode}\n'
              'Message: ${message ?? 'Unknown error'}\n\n'
              'Body:\n${response.body}';
        });
      }
    } catch (e) {
      final troubleshooting = NetworkConfig.usesLocalDockerBackend
          ? '1. Docker is still running\n'
                '2. The USB cable is connected\n'
                '3. `${NetworkConfig.adbReverseCommand}` is active\n'
                '4. The app was rebuilt after config changes'
          : '1. The Railway backend is reachable\n'
                '2. The mobile build points at the live API URL\n'
                '3. The deployed API has migrated successfully\n'
                '4. The student credentials are correct';
      setState(() {
        _status =
            'Error: Student login request failed.\n\n$e\n\n'
            'Check:\n$troubleshooting';
      });
    } finally {
      setState(() {
        _isTesting = false;
      });
    }
  }

  Map<String, dynamic>? _tryDecode(String body) {
    try {
      final decoded = jsonDecode(body);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
    } catch (_) {
      // Ignore invalid JSON and fall back to raw body display.
    }

    return null;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Connection Test'),
        backgroundColor: const Color(0xFF1B3A6B),
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Backend URL:',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 4),
            Text(
              NetworkConfig.baseUrl,
              style: TextStyle(color: Colors.grey.shade700, fontSize: 13),
            ),
            const SizedBox(height: 10),
            Text(
              'Shared portal login:\n${NetworkConfig.sharedPortalUrl}',
              style: TextStyle(
                color: Colors.grey.shade700,
                fontSize: 13,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 24),
            const BuildIdentityCard(title: 'Running Build Identity'),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFFFF3D9),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                NetworkConfig.usesLocalDockerBackend
                    ? 'Local phone testing expects Docker on port 8000 and an active USB reverse tunnel. '
                          'Run `adb reverse tcp:8000 tcp:8000` before using the app. '
                          'Test Student Login stores the returned token in secure storage and can replace the current mobile session.'
                    : 'This build is pointed at the deployed backend. '
                          'Test Student Login stores the returned token in secure storage and can replace the current mobile session. '
                          'If requests fail, check the Railway deployment and the configured APP_API_BASE_URL.',
                style: TextStyle(
                  color: Color(0xFF1B3A6B),
                  fontWeight: FontWeight.w600,
                  height: 1.4,
                ),
              ),
            ),
            const SizedBox(height: 24),
            TextField(
              controller: _studentIdController,
              keyboardType: TextInputType.number,
              inputFormatters: [
                FilteringTextInputFormatter.digitsOnly,
                LengthLimitingTextInputFormatter(7),
              ],
              decoration: const InputDecoration(
                labelText: 'Student ID for login test',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _passwordController,
              obscureText: true,
              inputFormatters: const [NoEmojiTextInputFormatter()],
              decoration: const InputDecoration(
                labelText: 'Password for login test',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: _isTesting ? null : _testConnection,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF1B3A6B),
                minimumSize: const Size(double.infinity, 52),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: _isTesting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    )
                  : const Text(
                      'Test Backend Reachability',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: _isTesting ? null : _testStudentLogin,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFD1A33B),
                foregroundColor: const Color(0xFF1B3A6B),
                minimumSize: const Size(double.infinity, 52),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
              child: _isTesting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                        color: Color(0xFF1B3A6B),
                        strokeWidth: 2,
                      ),
                    )
                  : const Text(
                      'Test Student Login',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    ),
            ),
            const SizedBox(height: 24),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                _status,
                style: const TextStyle(fontSize: 14, height: 1.5),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
