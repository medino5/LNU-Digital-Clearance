import 'package:flutter/material.dart';
import 'package:mobile/services/clearance_service.dart';
import 'package:mobile/screens/history_detail_screen.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final ClearanceService _clearanceService = ClearanceService();

  static const Color _lnuGold = Color(0xFFC9A84C);
  static const Color _lnuNavy = Color(0xFF1B3A6B);
  static const Color _pageBg = Color(0xFFF3F4F6);

  bool _isLoading = true;
  List<dynamic> _history = const [];

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory({bool showSpinner = true}) async {
    if (showSpinner) {
      setState(() {
        _isLoading = true;
      });
    }

    final history = await _clearanceService.getClearanceHistory();

    if (!mounted) return;

    setState(() {
      _history = history;
      _isLoading = false;
    });
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'completed':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'cancelled':
        return Colors.grey;
      case 'rejected':
        return Colors.red;
      default:
        return _lnuNavy;
    }
  }

  String _statusLabel(String status) {
    switch (status.toLowerCase()) {
      case 'completed':
        return 'COMPLETED';
      case 'pending':
        return 'PENDING';
      case 'cancelled':
        return 'CANCELLED';
      case 'rejected':
        return 'REJECTED';
      default:
        return status.toUpperCase();
    }
  }

  String _formatDate(String? isoDate) {
    if (isoDate == null || isoDate.isEmpty) return '—';

    try {
      final date = DateTime.parse(isoDate).toLocal();
      const months = [
        '',
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
      ];
      return '${months[date.month]} ${date.day}, ${date.year}';
    } catch (_) {
      return isoDate;
    }
  }

  Future<void> _handleTap(Map<String, dynamic> item) async {
    final status = ((item['status'] as String?) ?? '').toLowerCase();
    final id = item['id'];

    if (status != 'completed' || id is! int) {
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => HistoryDetailScreen(requestId: id),
      ),
    );
  }

  Widget _buildHistoryCard(Map<String, dynamic> item) {
    final semester = (item['semester'] as String?) ?? 'Unknown Semester';
    final status = (item['status'] as String?) ?? 'pending';
    final createdAt = item['created_at'] as String?;
    final approved = item['approved_signatures'] ?? 0;
    final total = item['total_signatures'] ?? 0;
    final isCompleted = status.toLowerCase() == 'completed';

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => _handleTap(item),
      child: Container(
        margin: const EdgeInsets.only(bottom: 16),
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Align(
              alignment: Alignment.centerRight,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: _statusColor(status),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  _statusLabel(status),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Semester:',
              style: TextStyle(
                color: Colors.grey.shade700,
                fontSize: 13,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              semester,
              style: const TextStyle(
                color: _lnuNavy,
                fontSize: 18,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 14),
            Text(
              'Date Initiated:',
              style: TextStyle(
                color: Colors.grey.shade700,
                fontSize: 13,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              _formatDate(createdAt),
              style: const TextStyle(
                color: Colors.black87,
                fontSize: 15,
              ),
            ),
            const SizedBox(height: 14),
            Text(
              'Signature Progress:',
              style: TextStyle(
                color: Colors.grey.shade700,
                fontSize: 13,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              '$approved/$total Offices Approved',
              style: const TextStyle(
                color: Colors.black87,
                fontSize: 15,
                fontWeight: FontWeight.w600,
              ),
            ),
            if (isCompleted) ...[
              const SizedBox(height: 14),
              const Row(
                children: [
                  Icon(
                    Icons.visibility_outlined,
                    size: 18,
                    color: _lnuNavy,
                  ),
                  SizedBox(width: 6),
                  Text(
                    'Tap to view details',
                    style: TextStyle(
                      color: _lnuNavy,
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _pageBg,
      appBar: AppBar(
        backgroundColor: _lnuGold,
        elevation: 0,
        centerTitle: true,
        title: const Text(
          'Clearance History',
          style: TextStyle(
            color: _lnuNavy,
            fontWeight: FontWeight.bold,
            letterSpacing: 0.3,
          ),
        ),
      ),
      body: Column(
        children: [
          Container(
            height: 4,
            color: _lnuNavy,
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : RefreshIndicator(
                    onRefresh: () => _fetchHistory(showSpinner: false),
                    child: _history.isEmpty
                        ? ListView(
                            physics: const AlwaysScrollableScrollPhysics(),
                            padding: const EdgeInsets.all(24),
                            children: const [
                              SizedBox(height: 80),
                              Icon(
                                Icons.history,
                                size: 58,
                                color: Colors.grey,
                              ),
                              SizedBox(height: 16),
                              Center(
                                child: Text(
                                  'No clearance history found.',
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
                            padding: const EdgeInsets.all(16),
                            itemCount: _history.length,
                            itemBuilder: (context, index) {
                              final item = _history[index];
                              if (item is! Map<String, dynamic>) {
                                return const SizedBox.shrink();
                              }
                              return _buildHistoryCard(item);
                            },
                          ),
                  ),
          ),
        ],
      ),
    );
  }
}