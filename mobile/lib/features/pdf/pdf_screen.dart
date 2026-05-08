import 'package:flutter/material.dart';

class PdfScreen extends StatelessWidget {
  const PdfScreen({
    super.key,
    required this.payload,
    required this.error,
    required this.isLoading,
    required this.isDownloadingPdf,
    required this.onRefresh,
    required this.onDownloadPdf,
  });

  final Map<String, dynamic>? payload;
  final String? error;
  final bool isLoading;

  // Ticket polish: downloading state is isolated to PDF actions only.
  final bool isDownloadingPdf;
  final Future<void> Function() onRefresh;
  final Future<void> Function() onDownloadPdf;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator(color: _gold));
    }

    final clearance = payload?['clearance'] as Map<String, dynamic>?;
    final status = clearance?['status'] as String?;
    final isCompleted = status == 'completed';
    final counts =
        (clearance?['counts'] as Map?)?.cast<String, dynamic>() ?? {};
    final referenceNumber = clearance?['reference_number'] as String?;
    final completedAt = clearance?['completed_at'] as String?;

    return RefreshIndicator(
      color: _gold,
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
        children: [
          if (error != null) ...[
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: Colors.red.withValues(alpha: 0.25)),
              ),
              child: const Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.error_outline_rounded, color: Colors.red),
                  SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Unable to refresh the latest PDF status right now. Pull down to try again.',
                      style: TextStyle(height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
          ],
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: isCompleted ? _navy : Colors.white,
              borderRadius: BorderRadius.circular(28),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.06),
                  blurRadius: 18,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 58,
                  height: 58,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: isCompleted
                        ? Colors.white.withValues(alpha: 0.15)
                        : const Color(0xFFFFF4DB),
                  ),
                  child: Icon(
                    isCompleted
                        ? Icons.verified_rounded
                        : Icons.lock_outline_rounded,
                    color: isCompleted ? Colors.white : _gold,
                    size: 30,
                  ),
                ),
                const SizedBox(height: 20),
                Text(
                  isCompleted
                      ? 'Clearance PDF Ready'
                      : 'PDF Locked Until Completion',
                  style: TextStyle(
                    color: isCompleted ? Colors.white : _navy,
                    fontSize: 25,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  isCompleted
                      ? 'All required offices have already approved your clearance. You can download the official PDF anytime from this page.'
                      : 'This page unlocks once every required office approves your clearance. Keep checking your dashboard while the offices finish their review.',
                  style: TextStyle(
                    color: isCompleted
                        ? Colors.white.withValues(alpha: 0.86)
                        : Colors.grey.shade700,
                    height: 1.45,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                if (isCompleted) ...[
                  const SizedBox(height: 22),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: isDownloadingPdf ? null : onDownloadPdf,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _gold,
                        foregroundColor: _navy,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(18),
                        ),
                        textStyle: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      icon: isDownloadingPdf
                          ? const SizedBox(
                              height: 18,
                              width: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: _navy,
                              ),
                            )
                          : const Icon(Icons.download_rounded),
                      label: Text(
                        isDownloadingPdf ? 'Preparing PDF...' : 'Download PDF',
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 18),
          if (isCompleted) ...[
            _DetailsCard(
              title: 'Download Details',
              rows: [
                (
                  'Reference Number',
                  referenceNumber ?? 'Generated at completion',
                ),
                ('Completed', _formatDateTime(completedAt)),
                (
                  'Approved Offices',
                  '${counts['approved'] ?? 0} of ${counts['total'] ?? 0}',
                ),
              ],
            ),
            const SizedBox(height: 18),
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: const Color(0xFFE9F6EE),
                borderRadius: BorderRadius.circular(24),
              ),
              child: const Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.picture_as_pdf_outlined, color: Color(0xFF1F7A45)),
                  SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Your clearance PDF will download automatically. On Android, it will be saved using the app-safe file path. On Chrome/web, your browser will handle the download.',
                      style: TextStyle(
                        color: _navy,
                        fontWeight: FontWeight.w600,
                        height: 1.4,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ] else ...[
            _DetailsCard(
              title: 'Current Progress',
              rows: [
                ('Approved', '${counts['approved'] ?? 0}'),
                ('Awaiting Action', '${counts['awaiting_action'] ?? 0}'),
                ('Flagged', '${counts['flagged'] ?? 0}'),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _DetailsCard extends StatelessWidget {
  const _DetailsCard({required this.title, required this.rows});

  final String title;
  final List<(String, String)> rows;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              color: PdfScreen._navy,
              fontSize: 19,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 12),
          ...rows.asMap().entries.map((entry) {
            final row = entry.value;
            final isLast = entry.key == rows.length - 1;

            return Container(
              padding: const EdgeInsets.symmetric(vertical: 12),
              decoration: BoxDecoration(
                border: Border(
                  bottom: isLast
                      ? BorderSide.none
                      : BorderSide(color: Colors.grey.shade200),
                ),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      row.$1,
                      style: TextStyle(
                        color: Colors.grey.shade700,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    row.$2,
                    style: const TextStyle(
                      color: PdfScreen._navy,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }
}

String _formatDateTime(String? value) {
  if (value == null || value.isEmpty) {
    return 'Pending';
  }

  final parsed = DateTime.tryParse(value)?.toLocal();

  if (parsed == null) {
    return value;
  }

  final month = <int, String>{
    1: 'Jan',
    2: 'Feb',
    3: 'Mar',
    4: 'Apr',
    5: 'May',
    6: 'Jun',
    7: 'Jul',
    8: 'Aug',
    9: 'Sep',
    10: 'Oct',
    11: 'Nov',
    12: 'Dec',
  }[parsed.month];

  final hour = parsed.hour == 0
      ? 12
      : parsed.hour > 12
      ? parsed.hour - 12
      : parsed.hour;
  final minute = parsed.minute.toString().padLeft(2, '0');
  final period = parsed.hour >= 12 ? 'PM' : 'AM';

  return '$month ${parsed.day}, ${parsed.year} • $hour:$minute $period';
}
