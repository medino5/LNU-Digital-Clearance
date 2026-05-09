import 'package:flutter/material.dart';

class HistoryScreen extends StatelessWidget {
  const HistoryScreen({
    super.key,
    required this.payload,
    required this.error,
    required this.isLoading,
    required this.onRefresh,
  });

  final Map<String, dynamic>? payload;
  final String? error;
  final bool isLoading;
  final Future<void> Function() onRefresh;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);
  static const Color _paper = Color(0xFFF8F4EA);
  static const Color _muted = Color(0xFF667085);

  @override
  Widget build(BuildContext context) {
    final history = (payload?['history'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => item.cast<String, dynamic>())
        .toList();

    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 28),
        children: [
          _IntroCard(totalCount: history.length),
          const SizedBox(height: 14),
          if (isLoading)
            const _StateCard(
              icon: Icons.history_rounded,
              title: 'Loading clearance history',
              message: 'Checking previous semester records...',
              showSpinner: true,
            )
          else if (error != null)
            _StateCard(
              icon: Icons.wifi_off_rounded,
              title: 'History unavailable',
              message: error!,
            )
          else if (history.isEmpty)
            const _StateCard(
              icon: Icons.inventory_2_outlined,
              title: 'No clearance history yet',
              message:
                  'Completed and previous clearance records will appear here once available.',
            )
          else
            ...history.map(_HistoryCard.new),
        ],
      ),
    );
  }
}

class _IntroCard extends StatelessWidget {
  const _IntroCard({required this.totalCount});

  final int totalCount;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE4DACD)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: HistoryScreen._gold.withValues(alpha: 0.18),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(
              Icons.history_edu_rounded,
              color: HistoryScreen._navy,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Clearance History',
                  style: TextStyle(
                    color: HistoryScreen._navy,
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  totalCount == 1
                      ? '1 clearance record found'
                      : '$totalCount clearance records found',
                  style: const TextStyle(
                    color: HistoryScreen._muted,
                    height: 1.35,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _HistoryCard extends StatelessWidget {
  const _HistoryCard(this.record);

  final Map<String, dynamic> record;

  @override
  Widget build(BuildContext context) {
    final status = record['status']?.toString() ?? 'in_progress';
    final semester = record['semester_label']?.toString() ?? 'Unknown semester';
    final academicYear = record['academic_year']?.toString() ?? 'Unknown SY';
    final reference = record['reference_number']?.toString();
    final programCode = record['program_code']?.toString() ?? '';
    final counts = (record['counts'] as Map?)?.cast<String, dynamic>() ?? {};
    final approved = counts['approved'] ?? 0;
    final total = counts['total'] ?? 0;
    final completedAt = _formatDate(record['completed_at']?.toString());
    final steps = (record['steps'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => item.cast<String, dynamic>())
        .toList();

    return Theme(
      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: const Color(0xFFE4DACD)),
        ),
        child: ExpansionTile(
          tilePadding: const EdgeInsets.fromLTRB(18, 14, 18, 10),
          childrenPadding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
          iconColor: HistoryScreen._navy,
          collapsedIconColor: HistoryScreen._muted,
          title: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      semester,
                      style: const TextStyle(
                        color: HistoryScreen._navy,
                        fontSize: 17,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'School Year $academicYear',
                      style: const TextStyle(
                        color: HistoryScreen._muted,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              _StatusPill(status: status),
            ],
          ),
          subtitle: Padding(
            padding: const EdgeInsets.only(top: 14),
            child: Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (programCode.isNotEmpty) _InfoPill(text: programCode),
                _InfoPill(text: '$approved/$total offices signed'),
                if (completedAt != null) _InfoPill(text: completedAt),
              ],
            ),
          ),
          children: [
            if (reference != null && reference.isNotEmpty)
              Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'Reference: $reference',
                  style: const TextStyle(
                    color: HistoryScreen._navy,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            if (reference != null && reference.isNotEmpty)
              const SizedBox(height: 12),
            if (steps.isEmpty)
              const _StepDetailEmpty()
            else
              ...steps.map(
                (step) => _StepDetailCard(
                  step: step,
                  formatDate: _formatDate,
                  formatStatus: _formatStatus,
                ),
              ),
          ],
        ),
      ),
    );
  }

  static String? _formatDate(String? value) {
    if (value == null || value.isEmpty) {
      return null;
    }

    final parsed = DateTime.tryParse(value);
    if (parsed == null) {
      return null;
    }

    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];

    return '${months[parsed.month - 1]} ${parsed.day}, ${parsed.year}';
  }

  static String _formatStatus(String value) {
    return value
        .replaceAll('_', ' ')
        .split(' ')
        .map(
          (part) => part.isEmpty
              ? part
              : '${part[0].toUpperCase()}${part.substring(1)}',
        )
        .join(' ');
  }
}

class _StepDetailCard extends StatelessWidget {
  const _StepDetailCard({
    required this.step,
    required this.formatDate,
    required this.formatStatus,
  });

  final Map<String, dynamic> step;
  final String? Function(String?) formatDate;
  final String Function(String) formatStatus;

  @override
  Widget build(BuildContext context) {
    final office = step['office_label']?.toString() ?? 'Office';
    final status = step['status']?.toString() ?? 'awaiting_action';
    final scope = step['scope_label']?.toString();
    final signer = step['signed_by']?.toString();
    final signedAt = formatDate(step['signed_at']?.toString());
    final remarks = step['remarks']?.toString();

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: HistoryScreen._paper,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE4DACD)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  office,
                  style: const TextStyle(
                    color: HistoryScreen._navy,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              _StatusPill(status: status),
            ],
          ),
          if (scope != null && scope.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              scope,
              style: const TextStyle(
                color: HistoryScreen._muted,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
          const SizedBox(height: 8),
          Text(
            signer != null && signer.isNotEmpty
                ? 'Signed by $signer${signedAt != null ? ' on $signedAt' : ''}'
                : formatStatus(status),
            style: const TextStyle(color: HistoryScreen._navy, height: 1.35),
          ),
          if (remarks != null && remarks.isNotEmpty) ...[
            const SizedBox(height: 6),
            Text(
              'Remarks: $remarks',
              style: const TextStyle(color: HistoryScreen._muted, height: 1.35),
            ),
          ],
        ],
      ),
    );
  }
}

class _StepDetailEmpty extends StatelessWidget {
  const _StepDetailEmpty();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: HistoryScreen._paper,
        borderRadius: BorderRadius.circular(16),
      ),
      child: const Text(
        'No signing details are available for this clearance yet.',
        style: TextStyle(color: HistoryScreen._muted),
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final isCompleted = status == 'completed';
    final label = status
        .replaceAll('_', ' ')
        .split(' ')
        .map(
          (part) => part.isEmpty
              ? part
              : '${part[0].toUpperCase()}${part.substring(1)}',
        )
        .join(' ');

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: isCompleted ? const Color(0xFFDFF3E7) : HistoryScreen._paper,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: isCompleted ? const Color(0xFF1F7A4F) : HistoryScreen._navy,
          fontSize: 12,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
      decoration: BoxDecoration(
        color: HistoryScreen._paper,
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: const Color(0xFFE4DACD)),
      ),
      child: Text(
        text,
        style: const TextStyle(
          color: HistoryScreen._navy,
          fontSize: 12,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

class _StateCard extends StatelessWidget {
  const _StateCard({
    required this.icon,
    required this.title,
    required this.message,
    this.showSpinner = false,
  });

  final IconData icon;
  final String title;
  final String message;
  final bool showSpinner;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE4DACD)),
      ),
      child: Column(
        children: [
          if (showSpinner)
            const CircularProgressIndicator(color: HistoryScreen._navy)
          else
            Icon(icon, color: HistoryScreen._navy, size: 38),
          const SizedBox(height: 14),
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: HistoryScreen._navy,
              fontSize: 17,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: HistoryScreen._muted, height: 1.4),
          ),
        ],
      ),
    );
  }
}
