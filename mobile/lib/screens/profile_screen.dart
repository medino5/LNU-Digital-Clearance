import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import 'login_screen.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  final AuthService _authService = AuthService();

  Map<String, dynamic>? _userData;
  bool _isLoading = true;
  bool _isLoggingOut = false;

  static const Color _lnuGold = Color(0xFFC9A84C);
  static const Color _lnuNavy = Color(0xFF1B3A6B);

  @override
  void initState() {
    super.initState();
    _loadUserData();
  }

  Future<void> _loadUserData() async {
    final user = await _authService.getUser();

    if (!mounted) return;

    setState(() {
      _userData = user;
      _isLoading = false;
    });
  }

  Future<void> _handleLogout() async {
    setState(() {
      _isLoggingOut = true;
    });

    await _authService.logout();

    if (!mounted) return;

    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      child: Row(
        children: [
          SizedBox(
            width: 95,
            child: Text(
              label,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                color: _lnuNavy,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(
                color: Colors.black87,
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final user = _userData;

    final String name = user?['name']?.toString() ?? 'N/A';
    final String studentId =
        user?['student_number']?.toString() ??
        user?['student_id']?.toString() ??
        'N/A';

    final dynamic programData = user?['program'];
    final String program = programData is Map<String, dynamic>
        ? (programData['name']?.toString() ?? 'N/A')
        : 'N/A';

    final String yearLevel =
        user?['year_level']?.toString() ??
        user?['current_semester']?.toString() ??
        'N/A';

    final String email = user?['email']?.toString() ?? 'N/A';

    return Scaffold(
      backgroundColor: const Color(0xFFF4F1F1),
      appBar: AppBar(
        backgroundColor: _lnuNavy,
        automaticallyImplyLeading: false,
        title: const Text(
          'Leyte Normal University',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(14),
              child: Column(
                children: [
                  const SizedBox(height: 8),
                  CircleAvatar(
                    radius: 38,
                    backgroundColor: Colors.grey.shade300,
                    child: const Icon(
                      Icons.person,
                      size: 44,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(
                      vertical: 18,
                      horizontal: 16,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Column(
                      children: [
                        Text(
                          name,
                          style: const TextStyle(
                            color: _lnuNavy,
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          'Student',
                          style: TextStyle(
                            color: Colors.grey.shade600,
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border.all(color: _lnuGold),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Column(
                      children: [
                        _buildInfoRow('Student ID', studentId),
                        Divider(height: 1, color: Colors.grey.shade300),
                        _buildInfoRow('Program', program),
                        Divider(height: 1, color: Colors.grey.shade300),
                        _buildInfoRow('Year Level', yearLevel),
                        Divider(height: 1, color: Colors.grey.shade300),
                        _buildInfoRow('Email', email),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    height: 46,
                    child: ElevatedButton.icon(
                      onPressed: _isLoggingOut ? null : _handleLogout,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _lnuGold,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                      ),
                      icon: _isLoggingOut
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.logout),
                      label: Text(_isLoggingOut ? 'Logging out...' : 'Logout'),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}