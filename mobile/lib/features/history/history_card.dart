import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import 'clearance_history_item.dart';

class HistoryCard extends StatelessWidget {
  const HistoryCard({super.key, required this.item, this.onTap});

  final ClearanceHistoryItem item;
  final VoidCallback? onTap;

  static const Color _lnuNavy = Color(0xFF1B3A6B);

  @override
  Widget build(BuildContext context) {
    final status = item.status.toLowerCase();
    final config = _statusConfig(status);
    final isInteractive = status != 'cancelled' && onTap != null;
    final createdAtLabel = DateFormat('MMM d, yyyy').format(item.createdAt);
    final completedAtLabel = item.completedAt != null
        ? DateFormat('MMM d, yyyy').format(item.completedAt!)
        : null;

    final totalSignatures = item.totalSignatures;
    final progressValue = totalSignatures > 0
        ? item.approvedSignatures / totalSignatures
        : 0.0;

    final content = Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  item.semester,
                  style: const TextStyle(
                    color: _lnuNavy,
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: config.background,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  config.label,
                  style: TextStyle(
                    color: config.text,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text.rich(
            TextSpan(
              children: [
                TextSpan(
                  text: 'Initiated: ',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
                TextSpan(
                  text: createdAtLabel,
                  style: const TextStyle(color: Colors.black87),
                ),
              ],
            ),
          ),
          if (status == 'completed' && completedAtLabel != null) ...[
            const SizedBox(height: 6),
            Text.rich(
              TextSpan(
                children: [
                  TextSpan(
                    text: 'Completed: ',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                  TextSpan(
                    text: completedAtLabel,
                    style: const TextStyle(color: Colors.black87),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 16),
          Text(
            '${item.approvedSignatures} of ${item.totalSignatures} offices cleared',
            style: TextStyle(
              color: Colors.grey.shade600,
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 8),
          LinearProgressIndicator(
            value: progressValue,
            minHeight: 6,
            backgroundColor: Colors.grey.shade200,
            color: config.progress,
          ),
        ],
      ),
    );

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 2,
      color: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: isInteractive
          ? InkWell(
              borderRadius: BorderRadius.circular(12),
              onTap: onTap,
              child: content,
            )
          : content,
    );
  }

  _StatusConfig _statusConfig(String status) {
    switch (status) {
      case 'completed':
        return const _StatusConfig(
          label: 'Completed',
          background: Color(0xFFE8F5E9),
          text: Color(0xFF2E7D32),
          progress: Color(0xFF2E7D32),
        );
      case 'cancelled':
        return const _StatusConfig(
          label: 'Cancelled',
          background: Color(0xFFFFEBEE),
          text: Color(0xFFB71C1C),
          progress: Colors.grey,
        );
      case 'pending':
      default:
        return const _StatusConfig(
          label: 'In Progress',
          background: Color(0xFFFFF8E1),
          text: Color(0xFFF57F17),
          progress: Colors.amber,
        );
    }
  }
}

class _StatusConfig {
  const _StatusConfig({
    required this.label,
    required this.background,
    required this.text,
    required this.progress,
  });

  final String label;
  final Color background;
  final Color text;
  final Color progress;
}
