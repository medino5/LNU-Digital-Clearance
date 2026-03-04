import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import 'login_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final AuthService _authService = AuthService();

  bool _isLoading = false;
  Map<String, dynamic>? _clearanceStatus;
  Map<String, dynamic>? _activeRequest;

  static const Color _lnuGold = Color(0xFFD4AF37);
  static const Color _lnuNavy = Color(0xFF001F54);

  @override
  void initState() {
    super.initState();
    _fetchClearanceStatus();
  }

  Future<void> _fetchClearanceStatus({bool showSpinner = true}) async {
    if (showSpinner) {
      setState(() {
        _isLoading = true;
      });
    }

    final status = await _authService.getClearanceStatus();

    if (!mounted) return;

    setState(() {
      _clearanceStatus = status;
      _activeRequest = status != null ? status['active_request'] as Map<String, dynamic>? : null;
      _isLoading = false;
    });
  }

  Future<void> _handleRequestClearance() async {
    if (_isLoading) return;

    setState(() {
      _isLoading = true;
    });

    final success = await _authService.requestClearance();

    if (!mounted) return;

    if (!success) {
      setState(() {
        _isLoading = false;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Unable to initiate clearance. Please try again.'),
        ),
      );
      return;
    }

    // On success, refresh status and then stop loading
    await _fetchClearanceStatus(showSpinner: false);

    if (!mounted) return;
    setState(() {
      _isLoading = false;
    });
  }

  Future<void> _handleLogout() async {
    await _authService.logout();

    if (!mounted) return;

    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (_) => const LoginScreen(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: _lnuGold,
        automaticallyImplyLeading: false,
        title: const Text(
          'Leyte Normal University',
          style: TextStyle(
            color: _lnuNavy,
            fontWeight: FontWeight.bold,
            letterSpacing: 1.2,
          ),
        ),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(),
            )
          : Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
                    decoration: BoxDecoration(
                      color: _lnuNavy,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Student Dashboard',
                          style: TextStyle(
                            color: _lnuGold,
                            fontSize: 18,
                          ),
                        ),
                        SizedBox(height: 8),
                        Text(
                          'Clearance Portal',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 32),
                  Expanded(
                    child: Center(
                      child: _activeRequest != null
                          ? const Text(
                              'Checklist UI goes here',
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey,
                              ),
                            )
                          : ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: _lnuGold,
                                foregroundColor: _lnuNavy,
                                padding: const EdgeInsets.symmetric(
                                  vertical: 16,
                                  horizontal: 24,
                                ),
                                textStyle: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                              onPressed: _isLoading ? null : _handleRequestClearance,
                              child: const Text('Initiate Clearance'),
                            ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: OutlinedButton(
                      onPressed: _isLoading ? null : _handleLogout,
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(color: Colors.red.shade700),
                      ),
                      child: const Text(
                        'Logout',
                        style: TextStyle(
                          color: Colors.red,
                          fontSize: 16,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}