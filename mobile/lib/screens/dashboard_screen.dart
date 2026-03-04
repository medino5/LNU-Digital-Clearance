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
  List<dynamic> _signatures = const [];

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
      // Support either { active_request: {..., clearance_signatures: [...] } }
      // or { clearance_request: {...}, clearance_signatures: [...] } shapes.
      Map<String, dynamic>? active;
      List<dynamic> signatures = const [];

      if (status != null) {
        if (status['active_request'] is Map<String, dynamic>) {
          active = status['active_request'] as Map<String, dynamic>;
          if (active?['clearance_signatures'] is List) {
            signatures = active!['clearance_signatures'] as List<dynamic>;
          }
        } else if (status['clearance_request'] is Map<String, dynamic>) {
          active = status['clearance_request'] as Map<String, dynamic>;
          if (status['clearance_signatures'] is List) {
            signatures = status['clearance_signatures'] as List<dynamic>;
          }
        }
      }

      _activeRequest = active;
      _signatures = signatures;
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
                    child: _activeRequest != null
                        ? RefreshIndicator(
                            onRefresh: () => _fetchClearanceStatus(showSpinner: false),
                            child: _signatures.isEmpty
                                ? ListView(
                                    children: const [
                                      SizedBox(height: 40),
                                      Center(
                                        child: Text(
                                          'No checklist items found.',
                                          style: TextStyle(
                                            fontSize: 16,
                                            color: Colors.grey,
                                          ),
                                        ),
                                      ),
                                    ],
                                  )
                                : ListView.builder(
                                    physics: const AlwaysScrollableScrollPhysics(),
                                    itemCount: _signatures.length,
                                    itemBuilder: (context, index) {
                                      final sig = _signatures[index] as Map<String, dynamic>? ?? {};
                                      final designation = sig['designation'] as Map<String, dynamic>? ?? {};
                                      final officeName =
                                          (designation['name'] as String?) ?? 'Unknown Office';
                                      final status =
                                          (sig['status'] as String?)?.toLowerCase() ?? 'pending';

                                      IconData icon;
                                      Color iconColor;

                                      switch (status) {
                                        case 'approved':
                                          icon = Icons.check_circle;
                                          iconColor = Colors.green;
                                          break;
                                        case 'rejected':
                                          icon = Icons.cancel;
                                          iconColor = Colors.red;
                                          break;
                                        case 'pending':
                                        default:
                                          icon = Icons.access_time;
                                          iconColor = Colors.amber;
                                          break;
                                      }

                                      return Card(
                                        margin: const EdgeInsets.symmetric(vertical: 8),
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(8),
                                          side: BorderSide(color: _lnuGold.withOpacity(0.7)),
                                        ),
                                        child: ListTile(
                                          leading: CircleAvatar(
                                            backgroundColor: _lnuNavy,
                                            child: Icon(
                                              icon,
                                              color: iconColor,
                                            ),
                                          ),
                                          title: Text(
                                            officeName,
                                            style: const TextStyle(
                                              fontWeight: FontWeight.bold,
                                            ),
                                          ),
                                          subtitle: Text(
                                            status[0].toUpperCase() + status.substring(1),
                                          ),
                                          trailing: Icon(
                                            icon,
                                            color: iconColor,
                                          ),
                                        ),
                                      );
                                    },
                                  ),
                          )
                        : Center(
                            child: ElevatedButton(
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