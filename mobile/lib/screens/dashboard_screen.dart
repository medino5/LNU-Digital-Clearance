import 'package:flutter/material.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({
    super.key,
    required this.payload,
    required this.error,
    required this.isLoading,
    required this.isBusy,
    required this.onRefresh,
    required this.onStartOrResume,
    required this.onResubmitStep,
    required this.onDownloadPdf,
  });

  final Map<String, dynamic>? payload;
  final String? error;
  final bool isLoading;
  final bool isBusy;
  final Future<void> Function() onRefresh;
  final Future<void> Function() onStartOrResume;
  final Future<void> Function(Map<String, dynamic> step) onResubmitStep;
  final Future<void> Function() onDownloadPdf;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);
  static const Color _danger = Color(0xFFB84040);

  Map<String, dynamic>? get _profile =>
      payload?['student'] as Map<String, dynamic>?;

  Map<String, dynamic>? get _activeSemester =>
      payload?['active_semester'] as Map<String, dynamic>?;

  Map<String, dynamic>? get _clearance =>
      payload?['clearance'] as Map<String, dynamic>?;

  List<Map<String, dynamic>> get _steps {
    final steps = _clearance?['steps'];

    if (steps is! List) {
      return const [];
    }

    return steps
        .whereType<Map>()
        .map((step) => step.cast<String, dynamic>())
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator(color: _gold));
    }

    return RefreshIndicator(
      color: _gold,
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
        children: [
          _HeroCard(
            profile: _profile,
            activeSemester: _activeSemester,
            clearance: _clearance,
          ),
          const SizedBox(height: 18),
          if (error != null) ...[
            _InfoCard(
              title: 'Refresh needed',
              message: error!,
              icon: Icons.wifi_tethering_error_rounded,
              accent: _danger,
            ),
            const SizedBox(height: 18),
          ],
          if (_activeSemester == null) ...[
            const _InfoCard(
              title: 'No active semester yet',
              message:
                  'MIS has not opened a semester for clearance processing yet. Please check back later.',
              icon: Icons.event_busy_outlined,
              accent: _navy,
            ),
          ] else if (_clearance == null) ...[
            _EmptyClearanceCard(
              semesterLabel:
                  _activeSemester?['label'] as String? ?? 'Active semester',
              isBusy: isBusy,
              onPressed: onStartOrResume,
            ),
          ] else ...[
            _SummaryCard(
              clearance: _clearance!,
              isBusy: isBusy,
              onDownloadPdf: onDownloadPdf,
            ),
            const SizedBox(height: 18),
            _StepsSection(
              steps: _steps,
              isBusy: isBusy,
              onResubmitStep: onResubmitStep,
            ),
          ],
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.75),
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: _gold.withValues(alpha: 0.22)),
            ),
            child: const Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.info_outline, color: _navy),
                SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Your clearance is routed in parallel. Offices can approve at different times, so keep refreshing to see the latest progress.',
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
        ],
      ),
    );
  }
}

class _HeroCard extends StatelessWidget {
  const _HeroCard({
    required this.profile,
    required this.activeSemester,
    required this.clearance,
  });

  final Map<String, dynamic>? profile;
  final Map<String, dynamic>? activeSemester;
  final Map<String, dynamic>? clearance;

  static const Color _navy = Color(0xFF183A63);
  @override
  Widget build(BuildContext context) {
    final program = profile?['program'] as Map<String, dynamic>?;

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        gradient: const LinearGradient(
          colors: [_navy, Color(0xFF28558B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [
          BoxShadow(
            color: _navy.withValues(alpha: 0.28),
            blurRadius: 28,
            offset: const Offset(0, 16),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: const Text(
                  'Current Clearance',
                  style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w700,
                    letterSpacing: 0.2,
                  ),
                ),
              ),
              const Spacer(),
              _StatusPill(
                status: clearance?['status'] as String? ?? 'inactive',
              ),
            ],
          ),
          const SizedBox(height: 22),
          Text(
            profile?['name'] as String? ?? 'Student',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            activeSemester?['label'] as String? ??
                'Waiting for an active semester',
            style: const TextStyle(
              color: Color(0xFFF3D990),
              fontSize: 15,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _MetaChip(
                icon: Icons.badge_outlined,
                label:
                    profile?['student_id_number'] as String? ?? 'No student ID',
              ),
              _MetaChip(
                icon: Icons.school_outlined,
                label: program?['code'] as String? ?? 'No program',
              ),
              _MetaChip(
                icon: Icons.groups_2_outlined,
                label:
                    profile?['year_level_label'] as String? ?? 'No year level',
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MetaChip extends StatelessWidget {
  const _MetaChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 18, color: Colors.white),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.status});

  final String status;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);
  static const Color _success = Color(0xFF1F7A45);
  static const Color _danger = Color(0xFFB84040);

  @override
  Widget build(BuildContext context) {
    late final Color background;
    late final Color foreground;
    late final String label;

    switch (status) {
      case 'completed':
        background = const Color(0xFFDDF3E5);
        foreground = _success;
        label = 'Completed';
        break;
      case 'flagged':
        background = const Color(0xFFF7DFDF);
        foreground = _danger;
        label = 'Flagged';
        break;
      case 'in_progress':
        background = const Color(0xFFFFF1D0);
        foreground = _navy;
        label = 'In Progress';
        break;
      default:
        background = Colors.white.withValues(alpha: 0.18);
        foreground = _gold;
        label = 'Not Started';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(color: foreground, fontWeight: FontWeight.w800),
      ),
    );
  }
}

class _EmptyClearanceCard extends StatelessWidget {
  const _EmptyClearanceCard({
    required this.semesterLabel,
    required this.isBusy,
    required this.onPressed,
  });

  final String semesterLabel;
  final bool isBusy;
  final Future<void> Function() onPressed;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
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
          const Icon(Icons.assignment_outlined, color: _gold, size: 34),
          const SizedBox(height: 16),
          const Text(
            'Ready to start your clearance?',
            style: TextStyle(
              color: _navy,
              fontSize: 22,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            'This will open your $semesterLabel clearance and route it to the required offices for your program and year level.',
            style: TextStyle(color: Colors.grey.shade700, height: 1.45),
          ),
          const SizedBox(height: 22),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: isBusy ? null : onPressed,
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
              icon: isBusy
                  ? const SizedBox(
                      height: 18,
                      width: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: _navy,
                      ),
                    )
                  : const Icon(Icons.play_arrow_rounded),
              label: Text(isBusy ? 'Starting...' : 'Initiate Clearance'),
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.clearance,
    required this.isBusy,
    required this.onDownloadPdf,
  });

  final Map<String, dynamic> clearance;
  final bool isBusy;
  final Future<void> Function() onDownloadPdf;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

  @override
  Widget build(BuildContext context) {
    final counts = (clearance['counts'] as Map?)?.cast<String, dynamic>() ?? {};
    final status = clearance['status'] as String? ?? 'in_progress';
    final isCompleted = status == 'completed';
    final referenceNumber = clearance['reference_number'] as String?;
    final completedAt = clearance['completed_at'] as String?;

    final summaryItems = [
      ('Approved', '${counts['approved'] ?? 0}'),
      ('Awaiting', '${counts['awaiting_action'] ?? 0}'),
      ('Flagged', '${counts['flagged'] ?? 0}'),
    ];

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
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
          const Text(
            'Clearance Summary',
            style: TextStyle(
              color: _navy,
              fontSize: 22,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: summaryItems
                .map(
                  (item) => Container(
                    width: 98,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF6F4EC),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Column(
                      children: [
                        Text(
                          item.$2,
                          style: const TextStyle(
                            color: _navy,
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          item.$1,
                          style: TextStyle(
                            color: Colors.grey.shade700,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                )
                .toList(),
          ),
          if (referenceNumber != null && referenceNumber.isNotEmpty) ...[
            const SizedBox(height: 18),
            _SummaryRow(label: 'Reference Number', value: referenceNumber),
          ],
          if (completedAt != null && completedAt.isNotEmpty) ...[
            const SizedBox(height: 10),
            _SummaryRow(
              label: 'Completed',
              value: _formatDateTime(completedAt),
            ),
          ],
          const SizedBox(height: 22),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isCompleted
                  ? const Color(0xFFE8F4EC)
                  : const Color(0xFFFFF5E1),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  isCompleted
                      ? Icons.verified_rounded
                      : Icons.timelapse_rounded,
                  color: isCompleted ? const Color(0xFF1F7A45) : _gold,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    isCompleted
                        ? 'All required offices have approved your clearance. You can download the official PDF below or from the PDF tab.'
                        : 'Your offices can act in parallel. Flagged steps can be resubmitted without resetting approved ones.',
                    style: const TextStyle(
                      color: _navy,
                      fontWeight: FontWeight.w600,
                      height: 1.4,
                    ),
                  ),
                ),
              ],
            ),
          ),
          if (isCompleted) ...[
            const SizedBox(height: 18),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: isBusy ? null : onDownloadPdf,
                style: ElevatedButton.styleFrom(
                  backgroundColor: _navy,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(18),
                  ),
                ),
                icon: isBusy
                    ? const SizedBox(
                        height: 18,
                        width: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Icon(Icons.picture_as_pdf_outlined),
                label: Text(
                  isBusy ? 'Preparing PDF...' : 'Download Clearance PDF',
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  const _SummaryRow({required this.label, required this.value});

  final String label;
  final String value;

  static const Color _navy = Color(0xFF183A63);

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        SizedBox(
          width: 132,
          child: Text(
            label,
            style: TextStyle(
              color: Colors.grey.shade700,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(color: _navy, fontWeight: FontWeight.w700),
          ),
        ),
      ],
    );
  }
}

class _StepsSection extends StatelessWidget {
  const _StepsSection({
    required this.steps,
    required this.isBusy,
    required this.onResubmitStep,
  });

  final List<Map<String, dynamic>> steps;
  final bool isBusy;
  final Future<void> Function(Map<String, dynamic> step) onResubmitStep;

  static const Color _navy = Color(0xFF183A63);

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Required Offices',
          style: TextStyle(
            color: _navy,
            fontSize: 22,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 14),
        ...steps.map(
          (step) => Padding(
            padding: const EdgeInsets.only(bottom: 14),
            child: _StepCard(
              step: step,
              isBusy: isBusy,
              onResubmitStep: onResubmitStep,
            ),
          ),
        ),
      ],
    );
  }
}

class _StepCard extends StatelessWidget {
  const _StepCard({
    required this.step,
    required this.isBusy,
    required this.onResubmitStep,
  });

  final Map<String, dynamic> step;
  final bool isBusy;
  final Future<void> Function(Map<String, dynamic> step) onResubmitStep;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);
  static const Color _success = Color(0xFF1F7A45);
  static const Color _danger = Color(0xFFB84040);

  @override
  Widget build(BuildContext context) {
    final status = step['status'] as String? ?? 'awaiting_action';
    final lastEvent = (step['last_event'] as Map?)?.cast<String, dynamic>();
    final remarks = (step['remarks'] as String?)?.trim();
    final signedAt = step['signed_at'] as String?;

    late final Color borderColor;
    late final Color badgeColor;
    late final Color badgeTextColor;
    late final String label;

    switch (status) {
      case 'approved':
        borderColor = _success.withValues(alpha: 0.25);
        badgeColor = const Color(0xFFDDF3E5);
        badgeTextColor = _success;
        label = 'Approved';
        break;
      case 'flagged':
        borderColor = _danger.withValues(alpha: 0.22);
        badgeColor = const Color(0xFFF7DFDF);
        badgeTextColor = _danger;
        label = 'Flagged';
        break;
      default:
        borderColor = _gold.withValues(alpha: 0.25);
        badgeColor = const Color(0xFFFFF5E1);
        badgeTextColor = _navy;
        label = 'Awaiting Action';
    }

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: borderColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      step['office_label'] as String? ?? 'Office',
                      style: const TextStyle(
                        color: _navy,
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    if ((step['scope_label'] as String?)?.isNotEmpty ??
                        false) ...[
                      const SizedBox(height: 6),
                      Text(
                        step['scope_label'] as String,
                        style: TextStyle(
                          color: Colors.grey.shade700,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                decoration: BoxDecoration(
                  color: badgeColor,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  label,
                  style: TextStyle(
                    color: badgeTextColor,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ],
          ),
          if (signedAt != null && signedAt.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(
              'Signed: ${_formatDateTime(signedAt)}',
              style: TextStyle(
                color: Colors.grey.shade700,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
          if (remarks != null && remarks.isNotEmpty) ...[
            const SizedBox(height: 12),
            _RemarksBox(
              title: status == 'flagged' ? 'Flag reason' : 'Remarks',
              message: remarks,
            ),
          ] else if (lastEvent != null &&
              (lastEvent['remarks'] as String?)?.trim().isNotEmpty == true) ...[
            const SizedBox(height: 12),
            _RemarksBox(
              title: 'Latest note',
              message: (lastEvent['remarks'] as String).trim(),
            ),
          ],
          if (step['can_resubmit'] == true) ...[
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: isBusy ? null : () => onResubmitStep(step),
                style: OutlinedButton.styleFrom(
                  foregroundColor: _navy,
                  side: BorderSide(color: _gold.withValues(alpha: 0.7)),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                icon: const Icon(Icons.refresh_rounded),
                label: const Text(
                  'Re-Submit to This Office',
                  style: TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _RemarksBox extends StatelessWidget {
  const _RemarksBox({required this.title, required this.message});

  final String title;
  final String message;

  static const Color _navy = Color(0xFF183A63);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF9F5EA),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(color: _navy, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 6),
          Text(
            message,
            style: TextStyle(color: Colors.grey.shade800, height: 1.4),
          ),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({
    required this.title,
    required this.message,
    required this.icon,
    required this.accent,
  });

  final String title;
  final String message;
  final IconData icon;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: accent.withValues(alpha: 0.24)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: accent),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: DashboardScreen._navy,
                    fontWeight: FontWeight.w800,
                    fontSize: 16,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  message,
                  style: TextStyle(color: Colors.grey.shade700, height: 1.4),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

String _formatDateTime(String value) {
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
