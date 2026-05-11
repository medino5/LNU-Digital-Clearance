import 'package:flutter/material.dart';

import '../../core/session_expired_exception.dart';
import '../../screens/dashboard_screen.dart';
import '../../screens/login_screen.dart';
import '../../services/auth_service.dart';
import '../../services/clearance_service.dart';
import '../history/history_screen.dart';
import '../pdf/pdf_screen.dart';
import '../profile/profile_screen.dart';

class AppShell extends StatefulWidget {
  const AppShell({
    super.key,
    this.authService,
    this.clearanceService,
    this.loginScreenBuilder,
  });

  final AuthService? authService;
  final ClearanceService? clearanceService;
  final Widget Function(BuildContext context, String? initialMessage)?
  loginScreenBuilder;

  @override
  State<AppShell> createState() => _AppShellState();
}

// Ticket polish: distinguish initial load, pull-to-refresh, and silent reloads
// to make tab behavior feel more cohesive.
enum ShellLoadMode { initial, refresh, silent }

class _AppShellState extends State<AppShell> {
  late final AuthService _authService = widget.authService ?? AuthService();
  late final ClearanceService _clearanceService =
      widget.clearanceService ?? ClearanceService();

  Map<String, dynamic>? _payload;
  Map<String, dynamic>? _historyPayload;
  String? _error;

  // Ticket polish: separate first screen load from user-initiated refresh
  // so the shell can show clearer state handling across all 3 tabs.
  bool _isInitialLoading = true;
  bool _isRefreshing = false;

  // Ticket polish: replace one global busy flag with action-specific loading
  // so unrelated buttons in other tabs do not get disabled.
  bool _isStartingOrResuming = false;
  bool _isDownloadingPdf = false;
  bool _isLoggingOut = false;
  bool _isUploadingProfilePhoto = false;
  bool _isChangingPassword = false;
  int? _resubmittingStepId;

  int _selectedIndex = 0;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);
  static const String _schoolSealAsset = 'assets/branding/lnu_school_seal.png';

  @override
  void initState() {
    super.initState();
    _loadClearance();
  }

  Future<void> _loadClearance({
    ShellLoadMode mode = ShellLoadMode.initial,
  }) async {
    if (mode == ShellLoadMode.refresh && _isRefreshing) {
      return;
    }

    if (mode == ShellLoadMode.initial) {
      setState(() {
        // Ticket polish: full loading only on first load
        _isInitialLoading = true;
        _error = null;
      });
    } else if (mode == ShellLoadMode.refresh) {
      setState(() {
        // Ticket polish: show refresh without removing UI
        _isRefreshing = true;
        _error = null;
      });
    }

    try {
      final results = await Future.wait([
        _clearanceService.getCurrentClearance(),
        _clearanceService.getClearanceHistory(),
      ]);
      final payload = results[0];
      final historyPayload = results[1];

      if (!mounted) return;

      setState(() {
        _payload = payload;
        _historyPayload = historyPayload;
        _error = null;
        _isInitialLoading = false;
        _isRefreshing = false;
      });
    } catch (error) {
      if (await _handleSessionExpired(error)) return;
      if (!mounted) return;

      setState(() {
        _error = _getErrorMessage(error);
        _isInitialLoading = false;
        _isRefreshing = false;
      });
    }
  }

  Future<void> _startOrResumeClearance() async {
    setState(() {
      // Ticket polish: isolate start/resume action loading
      _isStartingOrResuming = true;
    });

    try {
      final payload = await _clearanceService.createOrResumeClearance();

      if (!mounted) {
        return;
      }

      setState(() {
        // Ticket polish: clear stale shell error after a successful action refresh.
        _payload = payload;
        _error = null;
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

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_getErrorMessage(error))));
    } finally {
      if (mounted) {
        setState(() {
          _isStartingOrResuming = false;
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
      // Ticket polish: only the selected step shows loading
      _resubmittingStepId = stepId;
    });

    try {
      final payload = await _clearanceService.resubmitStep(stepId);

      if (!mounted) {
        return;
      }

      setState(() {
        // Ticket polish: clear stale shell error after a successful action refresh.
        _payload = payload;
        _error = null;
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

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_getErrorMessage(error))));
    } finally {
      if (mounted) {
        setState(() {
          _resubmittingStepId = null;
        });
      }
    }
  }

  Future<void> _downloadPdf() async {
    if (_isDownloadingPdf) {
      return;
    }

    final clearance = _payload?['clearance'] as Map<String, dynamic>?;
    final status = clearance?['status'] as String?;
    final referenceNumber = clearance?['reference_number'] as String?;

    if (status != 'completed') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Your clearance PDF is locked until all required signatories approve.',
          ),
        ),
      );
      return;
    }

    setState(() {
      _isDownloadingPdf = true;
      _error = null;
    });

    try {
      final result = await _clearanceService.downloadCurrentClearancePdf(
        referenceNumber: referenceNumber,
      );

      if (!mounted) {
        return;
      }

      final message = _getPdfMessage(result);

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    } catch (error) {
      if (await _handleSessionExpired(error)) {
        return;
      }

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_getErrorMessage(error))));
    } finally {
      if (mounted) {
        setState(() {
          _isDownloadingPdf = false;
        });
      }
    }
  }

  Future<void> _logout() async {
    setState(() {
      // Ticket polish: isolate logout loading state
      _isLoggingOut = true;
    });

    try {
      await _authService.logout();

      if (!mounted) {
        return;
      }

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (context) =>
              widget.loginScreenBuilder?.call(context, null) ??
              const LoginScreen(),
        ),
      );
    } finally {
      if (mounted) {
        setState(() {
          _isLoggingOut = false;
        });
      }
    }
  }

  Future<void> _updateProfilePhoto({
    required List<int> bytes,
    required String filename,
  }) async {
    if (_isUploadingProfilePhoto) {
      return;
    }

    setState(() {
      _isUploadingProfilePhoto = true;
    });

    try {
      final profile = await _authService.updateProfilePhoto(
        bytes: bytes,
        filename: filename,
      );

      if (!mounted) {
        return;
      }

      final updatedPayload = Map<String, dynamic>.from(_payload ?? {});
      updatedPayload['student'] = profile;

      setState(() {
        _payload = updatedPayload;
        _error = null;
      });

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Profile picture updated.')));
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
          _isUploadingProfilePhoto = false;
        });
      }
    }
  }

  Future<void> _changePassword({
    required String password,
    required String passwordConfirmation,
  }) async {
    if (_isChangingPassword) {
      return;
    }

    setState(() {
      _isChangingPassword = true;
    });

    try {
      final message = await _authService.changePassword(
        password: password,
        passwordConfirmation: passwordConfirmation,
      );

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    } catch (error) {
      if (await _handleSessionExpired(error)) {
        return;
      }

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_getErrorMessage(error))));
    } finally {
      if (mounted) {
        setState(() {
          _isChangingPassword = false;
        });
      }
    }
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
        builder: (context) =>
            widget.loginScreenBuilder?.call(context, error.message) ??
            LoginScreen(initialMessage: error.message),
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
        return 'Clearance History';
      case 3:
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
        return 'Review your previous semester and school year records.';
      case 3:
        return 'Review your account details and sign out securely.';
      default:
        return '';
    }
  }

  String _getPdfMessage(String result) {
    if (result == 'WEB_DOWNLOAD_TRIGGERED') {
      return 'PDF download started. Check your browser downloads.';
    }

    if (result.startsWith('content://')) {
      return 'PDF saved to your phone Downloads.';
    }

    return 'PDF saved: $result';
  }

  String _getErrorMessage(Object error) {
    final message = error.toString().replaceFirst('Exception: ', '');

    if (message.contains('internet') ||
        message.contains('reach') ||
        message.contains('SocketException')) {
      return 'Check your internet or backend connection.';
    }

    if (message.contains('timeout')) {
      return 'Request timed out. Please try again.';
    }

    return message;
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    precacheImage(const AssetImage(_schoolSealAsset), context);
  }

  Widget _buildTopBar() {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF0E2742), _navy, Color(0xFF214F82)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        border: Border(bottom: BorderSide(color: _gold, width: 3)),
      ),
      child: SafeArea(
        bottom: false,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipOval(
              child: Image.asset(
                _schoolSealAsset,
                width: 48,
                height: 48,
                fit: BoxFit.cover,
                cacheWidth: 144,
              ),
            ),
            const SizedBox(width: 14),
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
              onPressed: _isRefreshing
                  ? null
                  : () => _loadClearance(mode: ShellLoadMode.refresh),
              style: IconButton.styleFrom(
                backgroundColor: Colors.white.withValues(alpha: 0.12),
                foregroundColor: Colors.white,
              ),
              icon: _isRefreshing
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

  Widget _buildSelectedTab() {
    switch (_selectedIndex) {
      case 0:
        return DashboardScreen(
          payload: _payload,
          error: _error,
          isLoading: _isInitialLoading,
          isStartingOrResuming: _isStartingOrResuming,
          isDownloadingPdf: _isDownloadingPdf,
          resubmittingStepId: _resubmittingStepId,
          onRefresh: () => _loadClearance(mode: ShellLoadMode.refresh),
          onStartOrResume: _startOrResumeClearance,
          onResubmitStep: _resubmitStep,
          onDownloadPdf: _downloadPdf,
        );
      case 1:
        return PdfScreen(
          payload: _payload,
          error: _error,
          isLoading: _isInitialLoading,
          isDownloadingPdf: _isDownloadingPdf,
          onRefresh: () => _loadClearance(mode: ShellLoadMode.refresh),
          onDownloadPdf: _downloadPdf,
        );
      case 2:
        return HistoryScreen(
          payload: _historyPayload,
          error: _error,
          isLoading: _isInitialLoading,
          onRefresh: () => _loadClearance(mode: ShellLoadMode.refresh),
        );
      case 3:
        return ProfileScreen(
          payload: _payload,
          error: _error,
          isLoading: _isInitialLoading,
          isBusy: _isLoggingOut,
          isUploadingPhoto: _isUploadingProfilePhoto,
          isChangingPassword: _isChangingPassword,
          onLogout: _logout,
          onRefresh: () => _loadClearance(mode: ShellLoadMode.refresh),
          onUpdateProfilePhoto: _updateProfilePhoto,
          onChangePassword: _changePassword,
        );
      default:
        return const SizedBox.shrink();
    }
  }

  @override
  Widget build(BuildContext context) {
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
        backgroundColor: Colors.transparent,
        body: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [Color(0xFFF8F5ED), Color(0xFFF1EBDF)],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
          ),
          child: Column(
            children: [
              _buildTopBar(),
              Expanded(
                child: KeyedSubtree(
                  key: ValueKey<int>(_selectedIndex),
                  child: _buildSelectedTab(),
                ),
              ),
            ],
          ),
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
                  icon: Icon(Icons.history_outlined),
                  selectedIcon: Icon(Icons.history_rounded),
                  label: 'History',
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
