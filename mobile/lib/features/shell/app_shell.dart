import 'package:flutter/material.dart';

import '../../core/session_expired_exception.dart';
import '../../screens/dashboard_screen.dart';
import '../../screens/login_screen.dart';
import '../../services/auth_service.dart';
import '../../services/clearance_service.dart';
import '../pdf/pdf_screen.dart';
import '../profile/profile_screen.dart';

class AppShell extends StatefulWidget {
  const AppShell({super.key});

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  final AuthService _authService = AuthService();
  final ClearanceService _clearanceService = ClearanceService();

  Map<String, dynamic>? _payload;
  String? _error;
  bool _isLoading = true;
  bool _isBusy = false;
  int _selectedIndex = 0;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);
  static const Color _paper = Color(0xFFF8F4EA);

  @override
  void initState() {
    super.initState();
    _loadClearance();
  }

  Future<void> _loadClearance({bool showLoading = true}) async {
    if (showLoading) {
      setState(() {
        _isLoading = true;
        _error = null;
      });
    }

    try {
      final payload = await _clearanceService.getCurrentClearance();

      if (!mounted) {
        return;
      }

      setState(() {
        _payload = payload;
        _error = null;
        _isLoading = false;
      });
    } catch (error) {
      if (await _handleSessionExpired(error)) {
        return;
      }

      if (!mounted) {
        return;
      }

      setState(() {
        _error = error.toString().replaceFirst('Exception: ', '');
        _isLoading = false;
      });
    }
  }

  Future<void> _startOrResumeClearance() async {
    setState(() {
      _isBusy = true;
    });

    try {
      final payload = await _clearanceService.createOrResumeClearance();

      if (!mounted) {
        return;
      }

      setState(() {
        _payload = payload;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Clearance request is now active.')),
      );
    } catch (error) {
      if (await _handleSessionExpired(error)) {
        return;
      }

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.toString().replaceFirst('Exception: ', '')),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isBusy = false;
        });
      }
    }
  }

  Future<void> _resubmitStep(Map<String, dynamic> step) async {
    final stepId = step['id'];

    if (stepId is! int) {
      return;
    }

    setState(() {
      _isBusy = true;
    });

    try {
      final payload = await _clearanceService.resubmitStep(stepId);

      if (!mounted) {
        return;
      }

      setState(() {
        _payload = payload;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('The flagged step has been sent back to the office.'),
        ),
      );
    } catch (error) {
      if (await _handleSessionExpired(error)) {
        return;
      }

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.toString().replaceFirst('Exception: ', '')),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isBusy = false;
        });
      }
    }
  }

  Future<void> _downloadPdf() async {
    final clearance = _payload?['clearance'] as Map<String, dynamic>?;
    final referenceNumber = clearance?['reference_number'] as String?;

    setState(() {
      _isBusy = true;
    });

    try {
      final path = await _clearanceService.downloadCurrentClearancePdf(
        referenceNumber: referenceNumber,
      );

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text('PDF saved to $path')));
    } catch (error) {
      if (await _handleSessionExpired(error)) {
        return;
      }

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error.toString().replaceFirst('Exception: ', '')),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isBusy = false;
        });
      }
    }
  }

  Future<void> _logout() async {
    setState(() {
      _isBusy = true;
    });

    await _authService.logout();

    if (!mounted) {
      return;
    }

    Navigator.of(
      context,
    ).pushReplacement(MaterialPageRoute(builder: (_) => const LoginScreen()));
  }

  Future<bool> _handleSessionExpired(Object error) async {
    if (error is! SessionExpiredException) {
      return false;
    }

    await _authService.clearStoredToken();

    if (!mounted) {
      return true;
    }

    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (_) => LoginScreen(initialMessage: error.message),
      ),
    );

    return true;
  }

  String _titleForIndex(int index) {
    switch (index) {
      case 0:
        return 'Clearance Dashboard';
      case 1:
        return 'Clearance PDF';
      case 2:
        return 'Student Profile';
      default:
        return 'Student Clearance';
    }
  }

  String _subtitleForIndex(int index) {
    switch (index) {
      case 0:
        return 'Track office approvals and take the next step.';
      case 1:
        return 'Download your official clearance once it is completed.';
      case 2:
        return 'Review your account details and sign out securely.';
      default:
        return '';
    }
  }

  Widget _buildTopBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
      decoration: const BoxDecoration(
        color: _navy,
        border: Border(bottom: BorderSide(color: _gold, width: 3)),
      ),
      child: SafeArea(
        bottom: false,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Leyte Normal University',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 19,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    _titleForIndex(_selectedIndex),
                    style: const TextStyle(
                      color: Color(0xFFF0D28A),
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    _subtitleForIndex(_selectedIndex),
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.82),
                      fontSize: 12.5,
                      height: 1.35,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            IconButton(
              tooltip: 'Refresh',
              onPressed: _isBusy
                  ? null
                  : () => _loadClearance(showLoading: false),
              style: IconButton.styleFrom(
                backgroundColor: Colors.white.withValues(alpha: 0.12),
                foregroundColor: Colors.white,
              ),
              icon: _isBusy
                  ? const SizedBox(
                      height: 18,
                      width: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.refresh_rounded),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final tabs = [
      DashboardScreen(
        payload: _payload,
        error: _error,
        isLoading: _isLoading,
        isBusy: _isBusy,
        onRefresh: () => _loadClearance(showLoading: false),
        onStartOrResume: _startOrResumeClearance,
        onResubmitStep: _resubmitStep,
        onDownloadPdf: _downloadPdf,
      ),
      PdfScreen(
        payload: _payload,
        isLoading: _isLoading,
        isBusy: _isBusy,
        onRefresh: () => _loadClearance(showLoading: false),
        onDownloadPdf: _downloadPdf,
      ),
      ProfileScreen(
        payload: _payload,
        isLoading: _isLoading,
        isBusy: _isBusy,
        onLogout: _logout,
        onRefresh: () => _loadClearance(showLoading: false),
      ),
    ];

    return PopScope<void>(
      canPop: _selectedIndex == 0,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop || _selectedIndex == 0) {
          return;
        }

        setState(() {
          _selectedIndex = 0;
        });
      },
      child: Scaffold(
        backgroundColor: _paper,
        body: Column(
          children: [
            _buildTopBar(),
            Expanded(
              child: IndexedStack(index: _selectedIndex, children: tabs),
            ),
          ],
        ),
        bottomNavigationBar: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.08),
                blurRadius: 18,
                offset: const Offset(0, -4),
              ),
            ],
          ),
          child: NavigationBarTheme(
            data: NavigationBarThemeData(
              backgroundColor: Colors.white,
              indicatorColor: _gold.withValues(alpha: 0.18),
              labelTextStyle: WidgetStateProperty.resolveWith((states) {
                final isSelected = states.contains(WidgetState.selected);
                return TextStyle(
                  color: isSelected ? _navy : const Color(0xFF8F9197),
                  fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                );
              }),
              iconTheme: WidgetStateProperty.resolveWith((states) {
                final isSelected = states.contains(WidgetState.selected);
                return IconThemeData(
                  color: isSelected ? _gold : const Color(0xFF8F9197),
                );
              }),
            ),
            child: NavigationBar(
              selectedIndex: _selectedIndex,
              onDestinationSelected: (index) {
                setState(() {
                  _selectedIndex = index;
                });
              },
              destinations: const [
                NavigationDestination(
                  icon: Icon(Icons.dashboard_outlined),
                  selectedIcon: Icon(Icons.dashboard_rounded),
                  label: 'Dashboard',
                ),
                NavigationDestination(
                  icon: Icon(Icons.picture_as_pdf_outlined),
                  selectedIcon: Icon(Icons.picture_as_pdf_rounded),
                  label: 'PDF',
                ),
                NavigationDestination(
                  icon: Icon(Icons.person_outline_rounded),
                  selectedIcon: Icon(Icons.person_rounded),
                  label: 'Profile',
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
