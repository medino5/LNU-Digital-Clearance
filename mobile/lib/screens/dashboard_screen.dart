import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import 'login_screen.dart'; // Make sure this path points to your login screen

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final AuthService _authService = AuthService();
  String _userName = 'Loading...';

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    final userData = await _authService.getUser();
    if (mounted) {
      setState(() {
        // 'name' is the default column in the Laravel users table
        _userName = userData != null ? userData['name'] : 'Student';
      });
    }
  }

  Future<void> _handleLogout() async {
    await _authService.logout();
    if (!mounted) return;

    // Kick the user back to the login screen and remove the back-button history
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (context) => const LoginScreen()),
      (Route<dynamic> route) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    // LNU Colors based on your mockup
    const Color lnuGold = Color(
      0xFFD4AF37,
    ); // Adjust hex if you have the exact one
    const Color lnuNavy = Color(0xFF001F54);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: lnuGold,
        title: const Text(
          'Leyte Normal University',
          style: TextStyle(
            color: lnuNavy,
            fontWeight: FontWeight.bold,
            letterSpacing: 1.2,
          ),
        ),
        elevation: 0,
      ),
      body: Column(
        children: [
          // Navy Header Block
          Container(
            width: double.infinity,
            color: lnuNavy,
            padding: const EdgeInsets.symmetric(vertical: 30, horizontal: 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Student Dashboard',
                  style: TextStyle(color: lnuGold, fontSize: 18),
                ),
                const SizedBox(height: 8),
                Text(
                  'Welcome, $_userName',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 28,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
          ),

          // Placeholder Content Area
          const Expanded(
            child: Center(
              child: Text(
                'Clearance status and routing will appear here.',
                style: TextStyle(color: Colors.grey, fontSize: 16),
              ),
            ),
          ),

          // Logout Button
          Padding(
            padding: const EdgeInsets.all(24.0),
            child: SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red.shade700,
                  foregroundColor: Colors.white,
                ),
                onPressed: _handleLogout,
                child: const Text('Logout', style: TextStyle(fontSize: 16)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
