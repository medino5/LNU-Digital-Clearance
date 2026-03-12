import 'package:flutter/material.dart';
import 'package:mobile/services/clearance_service.dart';

class HistoryDetailScreen extends StatefulWidget {
  const HistoryDetailScreen({super.key, required this.requestId});

  final int requestId;

  @override
  State<HistoryDetailScreen> createState() => _HistoryDetailScreenState();
}

class _HistoryDetailScreenState extends State<HistoryDetailScreen> {
  final ClearanceService _clearanceService = ClearanceService();

  static const Color _lnuGold = Color(0xFFC9A84C);
  static const Color _lnuNavy = Color(0xFF1B3A6B);
  static const Color _pageBg = Color(0xFFF3F4F6);

  bool _isLoading = true;
  Map<String, dynamic>? _detail;
  List<dynamic> _signatures = const [];

  @override
  void initState() {
    super.initState();
    _fetchDetail();
  }

  Future<void> _fetchDetail() async {
    final detail = await _clearanceService.getClearanceHistoryDetail(
      widget.requestId,
    );

    if (!mounted) return;

    setState(() {
      _detail = detail;
      _signatures = detail != null && detail['signatures'] is List
        ? detail['signatures'] as List<dynamic>
        : const [];
      _isLoading = false;
    });
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'approved':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'rejected':
        return Colors.red;
      case 'cancelled':
        return Colors.grey;
      default:
        return _lnuNavy;
    }
  }

  String _statusLabel(String status) {
    if (status.isEmpty) return 'Unknown';
    return status[0].toUpperCase() + status.substring(1).toLowerCase();
    }

  @override
  Widget build(BuildContext context) {
    final semester = (_detail?['semester'] as String?) ?? 'History Detail';

    return Scaffold(
      backgroundColor: _pageBg,
      appBar: AppBar(
        backgroundColor: _lnuGold,
        title: Text(
          semester,
          style: const TextStyle(
            color: _lnuNavy,
            fontWeight: FontWeight.bold,
          ),
        ),
        iconTheme: const IconThemeData(color: _lnuNavy),
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
                : _signatures.isEmpty
                    ? const Center(
                        child: Text(
                          'No signatures found.',
                          style: TextStyle(color: Colors.grey, fontSize: 16),
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: _signatures.length,
                        itemBuilder: (context, index) {
                          final sigData = _signatures[index];
                          final sig = sigData is Map<String, dynamic>
                              ? sigData
                              : <String, dynamic>{};

                          final designationData = sig['designation'];
                          final designation = designationData is Map<String, dynamic>
                              ? designationData
                              : <String, dynamic>{};

                          final officeName =
                              (designation['name'] as String?) ?? 'Unknown Office';
                          final status =
                              ((sig['status'] as String?) ?? 'pending').toLowerCase();
                          final rejectionReason =
                              ((sig['rejection_reason'] as String?) ?? '').trim();
                          final remarks =
                              ((sig['remarks'] as String?) ?? '').trim();

                          return Container(
                            margin: const EdgeInsets.only(bottom: 14),
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
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
                                Text(
                                  officeName,
                                  style: const TextStyle(
                                    color: _lnuNavy,
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                Container(
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
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                                if (rejectionReason.isNotEmpty) ...[
                                  const SizedBox(height: 12),
                                  Text(
                                    'Reason: $rejectionReason',
                                    style: const TextStyle(
                                      color: Colors.red,
                                      fontSize: 14,
                                      fontStyle: FontStyle.italic,
                                    ),
                                  ),
                                ],
                                if (remarks.isNotEmpty) ...[
                                  const SizedBox(height: 8),
                                  Text(
                                    'Remarks: $remarks',
                                    style: const TextStyle(
                                      color: Colors.black87,
                                      fontSize: 14,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }
}