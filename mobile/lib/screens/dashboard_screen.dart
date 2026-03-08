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

  static const Color _lnuGold = Color(0xFFC9A84C);
  static const Color _lnuNavy = Color(0xFF1B3A6B);

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
      MaterialPageRoute(builder: (_) => const LoginScreen()),
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
          ? const Center(child: CircularProgressIndicator())
          : Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      vertical: 24,
                      horizontal: 16,
                    ),
                    decoration: BoxDecoration(
                      color: _lnuNavy,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Student Dashboard',
                          style: TextStyle(color: _lnuGold, fontSize: 18),
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
                            onRefresh: () =>
                                _fetchClearanceStatus(showSpinner: false),
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
                                    physics:
                                        const AlwaysScrollableScrollPhysics(),
                                    itemCount: _signatures.length,
                                    itemBuilder: (context, index) {
                                      final sigData = _signatures[index];
                                      final sig =
                                          sigData is Map<String, dynamic>
                                          ? sigData
                                          : <String, dynamic>{};

                                      return SignatureCard(signature: sig);
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
                              onPressed: _isLoading
                                  ? null
                                  : _handleRequestClearance,
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
                        style: TextStyle(color: Colors.red, fontSize: 16),
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}

class SignatureCard extends StatefulWidget {
  const SignatureCard({super.key, required this.signature});

  final Map<String, dynamic> signature;

  @override
  State<SignatureCard> createState() => _SignatureCardState();
}

class _SignatureCardState extends State<SignatureCard> {
  static const Color _lnuNavy = Color(0xFF1B3A6B);
  static const Color _rejectedColor = Color(0xFFE53935);

  bool _remarksExpanded = false;

  @override
  Widget build(BuildContext context) {
    final designationData = widget.signature['designation'];
    final designation = designationData is Map<String, dynamic>
        ? designationData
        : <String, dynamic>{};

    final officeName = (designation['name'] as String?) ?? 'Unknown Office';
    final status = ((widget.signature['status'] as String?) ?? 'pending')
        .toLowerCase();
    final rejectionReason =
        ((widget.signature['rejection_reason'] as String?) ?? '').trim();
    final remarks = ((widget.signature['remarks'] as String?) ?? '').trim();

    final isRejected = status == 'rejected';
    final hasRejectionReason = rejectionReason.isNotEmpty;
    final hasRemarks = isRejected && remarks.isNotEmpty;

    IconData statusIcon;
    Color statusColor;
    String statusLabel;

    switch (status) {
      case 'approved':
        statusIcon = Icons.check_circle;
        statusColor = Colors.green;
        statusLabel = 'Approved';
        break;
      case 'rejected':
        statusIcon = Icons.close;
        statusColor = _rejectedColor;
        statusLabel = 'Rejected';
        break;
      case 'pending':
      default:
        statusIcon = Icons.access_time;
        statusColor = Colors.amber;
        statusLabel = 'Pending';
        break;
    }

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 8),
      color: Colors.white,
      elevation: 2,
      shadowColor: Colors.black.withValues(alpha: 0.12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    CircleAvatar(
                      radius: 26,
                      backgroundColor: _lnuNavy,
                      child: Icon(statusIcon, color: statusColor, size: 34),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        officeName,
                        style: const TextStyle(
                          color: _lnuNavy,
                          fontSize: 22,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                Text(
                  statusLabel,
                  style: TextStyle(
                    color: statusColor,
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                if (isRejected && hasRejectionReason) ...[
                  const SizedBox(height: 8),
                  Text(
                    rejectionReason,
                    style: TextStyle(
                      color: statusColor,
                      fontSize: 17,
                      fontStyle: FontStyle.italic,
                    ),
                  ),
                ],
              ],
            ),
          ),
          if (hasRemarks) ...[
            const Divider(height: 1),
            InkWell(
              onTap: () {
                setState(() {
                  _remarksExpanded = !_remarksExpanded;
                });
              },
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'View staff remarks',
                      style: TextStyle(
                        color: _lnuNavy,
                        fontSize: 16,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    Icon(
                      _remarksExpanded
                          ? Icons.keyboard_arrow_up
                          : Icons.keyboard_arrow_down,
                      color: _lnuNavy,
                    ),
                  ],
                ),
              ),
            ),
            AnimatedCrossFade(
              duration: const Duration(milliseconds: 180),
              firstChild: const SizedBox.shrink(),
              secondChild: Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 14),
                child: Text(
                  remarks,
                  style: TextStyle(color: Colors.grey.shade800, fontSize: 15),
                ),
              ),
              crossFadeState: _remarksExpanded
                  ? CrossFadeState.showSecond
                  : CrossFadeState.showFirst,
            ),
          ],
        ],
      ),
    );
  }
}
