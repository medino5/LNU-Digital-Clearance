import 'package:flutter/material.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({
    super.key,
    required this.payload,
    required this.error,
    required this.isLoading,
    required this.isStartingOrResuming,
    required this.isCancellingClearance,
    required this.isDownloadingPdf,
    required this.resubmittingStepId,
    required this.onRefresh,
    required this.onStartOrResume,
    required this.onCancelClearance,
    required this.onResubmitStep,
    required this.onDownloadPdf,
  });

  final Map<String, dynamic>? payload;
  final String? error;
  final bool isLoading;

  // Ticket polish: separate dashboard action states so unrelated controls
  // do not get disabled across the shell.
  final bool isStartingOrResuming;
  final bool isCancellingClearance;
  final bool isDownloadingPdf;
  final int? resubmittingStepId;
  final Future<void> Function() onRefresh;
  final Future<void> Function() onStartOrResume;
  final Future<void> Function() onCancelClearance;
  final Future<void> Function(Map<String, dynamic> step) onResubmitStep;
  final Future<void> Function() onDownloadPdf;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

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

  Map<String, dynamic>? get _student =>
      payload?['student'] as Map<String, dynamic>?;

  Map<String, dynamic>? get _program =>
      _student?['program'] as Map<String, dynamic>?;

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
          if (error != null) ...[
            _InfoCard(
              title: 'Unable to refresh right now',
              body: error!,
              accent: Colors.red.shade700,
            ),
            const SizedBox(height: 18),
          ],
          if (_activeSemester == null)
            _InfoCard(
              title: 'No active semester yet',
              body:
                  'MIS has not configured the active semester, so you cannot start a clearance request yet.',
              accent: _gold,
            )
          else if (_clearance == null)
            _StartClearanceCard(
              semesterLabel: _activeSemester?['label'] as String? ?? '',
              programCode: _program?['code'] as String?,
              programName: _program?['name'] as String?,
              yearLevelLabel: _student?['year_level_label'] as String?,
              sectionLabel: _student?['section_label'] as String?,
              isBusy: isStartingOrResuming,
              onPressed: onStartOrResume,
            )
          else ...[
            _ClearanceSummaryCard(
              clearance: _clearance!,
              activeSemesterLabel: _activeSemester?['label'] as String?,
              isBusy: isDownloadingPdf,
              isCancelling: isCancellingClearance,
              onDownload: onDownloadPdf,
              onCancel: onCancelClearance,
            ),
            const SizedBox(height: 18),
            Text(
              'Required Offices',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                color: _navy,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 10),
            if (_steps.isEmpty)
              _InfoCard(
                title: 'No office steps available',
                body:
                    'The clearance record exists, but no office routing steps are available yet. Pull down to refresh or contact MIS if this persists.',
                accent: _gold,
              )
            else
              ..._steps.map(
                (step) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _ClearanceStepCard(
                    step: step,
                    isResubmitting: resubmittingStepId == step['id'],
                    onResubmit: step['can_resubmit'] == true
                        ? () => onResubmitStep(step)
                        : null,
                  ),
                ),
              ),
          ],
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({
    required this.title,
    required this.body,
    required this.accent,
  });

  final String title;
  final String body;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: accent.withValues(alpha: 0.35)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              color: accent,
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 10),
          Text(body, style: const TextStyle(height: 1.5)),
        ],
      ),
    );
  }
}

class _StartClearanceCard extends StatelessWidget {
  const _StartClearanceCard({
    required this.semesterLabel,
    required this.programCode,
    required this.programName,
    required this.yearLevelLabel,
    required this.sectionLabel,
    required this.isBusy,
    required this.onPressed,
  });

  final String semesterLabel;
  final String? programCode;
  final String? programName;
  final String? yearLevelLabel;
  final String? sectionLabel;
  final bool isBusy;
  final Future<void> Function() onPressed;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

  @override
  Widget build(BuildContext context) {
    final programLabel = [
      if (programCode?.isNotEmpty ?? false) programCode,
      if (programName?.isNotEmpty ?? false) programName,
    ].join(' - ');

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Ready to start your clearance?',
            style: TextStyle(
              color: _navy,
              fontSize: 24,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            'The active semester is $semesterLabel. Starting a clearance now will immediately route the request to all required offices.',
            style: TextStyle(color: Colors.grey.shade700, height: 1.5),
          ),
          const SizedBox(height: 16),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF6DF),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: _gold.withValues(alpha: 0.36)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Row(
                  children: [
                    Icon(Icons.info_outline_rounded, color: _navy, size: 20),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Check before starting',
                        style: TextStyle(
                          color: _navy,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'Program: ${programLabel.isEmpty ? 'Not set' : programLabel}',
                  style: const TextStyle(
                    color: _navy,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Year Level: ${(yearLevelLabel?.isNotEmpty ?? false) ? yearLevelLabel : 'Not set'}',
                  style: const TextStyle(
                    color: _navy,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Section: ${(sectionLabel?.isNotEmpty ?? false) ? sectionLabel : 'Not set'}',
                  style: const TextStyle(
                    color: _navy,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'If any detail is wrong, open Profile and update it first. Program and year level decide routing, while section helps offices filter your request.',
                  style: TextStyle(color: Colors.grey.shade800, height: 1.4),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: isBusy ? null : () => _confirmStart(context),
              style: ElevatedButton.styleFrom(
                backgroundColor: _gold,
                foregroundColor: _navy,
                padding: const EdgeInsets.symmetric(vertical: 15),
                textStyle: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
              child: isBusy
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2.4,
                        color: _navy,
                      ),
                    )
                  : const Text('Initiate Clearance'),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _confirmStart(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Start clearance now?'),
        content: const Text(
          'Make sure your program, year level, and section are correct before starting. Program and year level control routing; section helps offices find your request faster.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Review Profile First'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Start Clearance'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      await onPressed();
    }
  }
}

class _ClearanceSummaryCard extends StatelessWidget {
  const _ClearanceSummaryCard({
    required this.clearance,
    required this.activeSemesterLabel,
    required this.isBusy,
    required this.isCancelling,
    required this.onDownload,
    required this.onCancel,
  });

  final Map<String, dynamic> clearance;
  final String? activeSemesterLabel;
  final bool isBusy;
  final bool isCancelling;
  final Future<void> Function() onDownload;
  final Future<void> Function() onCancel;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

  @override
  Widget build(BuildContext context) {
    final counts = clearance['counts'] as Map<String, dynamic>? ?? {};
    final status = clearance['status'] as String? ?? 'in_progress';
    final referenceNumber = clearance['reference_number'] as String?;
    final completedAt = clearance['completed_at'] as String?;
    final pdfAvailable = clearance['pdf_available'] == true;
    final approved = counts['approved'] ?? 0;
    final total = counts['total'] ?? 0;
    final awaiting = counts['awaiting_action'] ?? 0;
    final flagged = counts['flagged'] ?? 0;
    final canCancel = clearance['can_cancel'] == true;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: 12,
            runSpacing: 10,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              const Text(
                'Current Clearance',
                style: TextStyle(
                  color: _navy,
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              _StatusChip(status: status),
            ],
          ),
          if ((activeSemesterLabel?.isNotEmpty ?? false)) ...[
            const SizedBox(height: 8),
            Text(
              activeSemesterLabel!,
              style: TextStyle(
                color: Colors.grey.shade700,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
          const SizedBox(height: 14),
          _ProgressBar(approved: approved, total: total),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _SummaryChip(
                  label: 'Approved',
                  value: '$approved',
                  color: Colors.green.shade700,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _SummaryChip(
                  label: 'Waiting',
                  value: '$awaiting',
                  color: Colors.orange.shade700,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _SummaryChip(
                  label: 'Issues',
                  value: '$flagged',
                  color: Colors.red.shade700,
                ),
              ),
            ],
          ),
          if (referenceNumber != null) ...[
            const SizedBox(height: 18),
            Text(
              'Reference Number: $referenceNumber',
              style: const TextStyle(color: _navy, fontWeight: FontWeight.bold),
            ),
          ],
          if (completedAt != null) ...[
            const SizedBox(height: 8),
            Text(
              'Completed: ${formatMobileDateTime(completedAt)}',
              style: TextStyle(color: Colors.grey.shade700),
            ),
          ],
          if (status == 'flagged') ...[
            const SizedBox(height: 16),
            Text(
              'Approved offices stay approved. Re-submit only the flagged office steps once your issue is resolved.',
              style: TextStyle(color: Colors.grey.shade800, height: 1.45),
            ),
          ],
          if (canCancel) ...[
            const SizedBox(height: 16),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFFFF7ED),
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: Colors.orange.withValues(alpha: 0.3)),
              ),
              child: Text(
                'Wrong program or year level? Cancel this clearance before any office acts, update your Profile, then start again.',
                style: TextStyle(color: Colors.grey.shade800, height: 1.4),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: isCancelling ? null : onCancel,
                icon: isCancelling
                    ? const SizedBox(
                        height: 18,
                        width: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.cancel_outlined),
                label: Text(
                  isCancelling ? 'Cancelling...' : 'Cancel Wrong Clearance',
                ),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.red.shade700,
                  side: BorderSide(color: Colors.red.shade200),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  textStyle: const TextStyle(fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
          if (pdfAvailable) ...[
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: isBusy ? null : onDownload,
                icon: isBusy
                    ? const SizedBox(
                        height: 18,
                        width: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: _navy,
                        ),
                      )
                    : const Icon(Icons.picture_as_pdf_rounded),
                label: Text(
                  isBusy ? 'Preparing PDF...' : 'Download Clearance PDF',
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: _gold,
                  foregroundColor: _navy,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  textStyle: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 15,
                  ),
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _SummaryChip extends StatelessWidget {
  const _SummaryChip({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(minHeight: 66),
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              value,
              maxLines: 1,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: color,
                fontSize: 18,
                fontWeight: FontWeight.w900,
                height: 1,
              ),
            ),
          ),
          const SizedBox(height: 4),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(
              label,
              maxLines: 1,
              softWrap: false,
              overflow: TextOverflow.visible,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: color,
                fontSize: 12,
                fontWeight: FontWeight.w800,
                height: 1,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ProgressBar extends StatelessWidget {
  const _ProgressBar({required this.approved, required this.total});

  final Object approved;
  final Object total;

  @override
  Widget build(BuildContext context) {
    final approvedCount = int.tryParse('$approved') ?? 0;
    final totalCount = int.tryParse('$total') ?? 0;
    final progress = totalCount == 0 ? 0.0 : approvedCount / totalCount;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(999),
          child: LinearProgressIndicator(
            value: progress.clamp(0.0, 1.0),
            minHeight: 9,
            backgroundColor: const Color(0xFFE9EEF5),
            color: const Color(0xFFD1A33B),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          '$approvedCount of $totalCount offices signed',
          style: TextStyle(
            color: Colors.grey.shade700,
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    late final Color background;
    late final Color foreground;
    late final String label;

    switch (status) {
      case 'completed':
        background = const Color(0xFFDFF3E7);
        foreground = Colors.green.shade800;
        label = 'Completed';
        break;
      case 'flagged':
        background = const Color(0xFFFBE2DC);
        foreground = Colors.red.shade700;
        label = 'Flagged';
        break;
      case 'in_progress':
      default:
        background = const Color(0xFFDDE8F7);
        foreground = const Color(0xFF183A63);
        label = 'In Progress';
        break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(color: foreground, fontWeight: FontWeight.bold),
      ),
    );
  }
}

class _ClearanceStepCard extends StatelessWidget {
  const _ClearanceStepCard({
    required this.step,
    required this.isResubmitting,
    this.onResubmit,
  });

  final Map<String, dynamic> step;

  // Ticket polish: only the selected flagged step should show a loading state
  // during re-submit.
  final bool isResubmitting;
  final VoidCallback? onResubmit;

  @override
  Widget build(BuildContext context) {
    final status = step['status'] as String? ?? 'awaiting_action';
    final remarks = step['remarks'] as String?;
    final signedAt = step['signed_at'] as String?;
    final scopeLabel = step['scope_label'] as String?;
    final assignedOfficer =
        step['assigned_officer'] as Map<String, dynamic>? ?? {};
    final officerPhotoUrl = assignedOfficer['profile_photo_url'] as String?;
    final officerName = assignedOfficer['name'] as String?;

    final theme = switch (status) {
      'approved' => (
        background: const Color(0xFFE9F6EF),
        foreground: Colors.green.shade800,
        icon: Icons.check_circle_rounded,
        label: 'Approved',
      ),
      'flagged' => (
        background: const Color(0xFFFFEEEA),
        foreground: Colors.red.shade700,
        icon: Icons.flag_rounded,
        label: 'Flagged',
      ),
      _ => (
        background: const Color(0xFFFFF4DA),
        foreground: Colors.orange.shade800,
        icon: Icons.pending_actions_rounded,
        label: 'Awaiting Action',
      ),
    };

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _OfficerAvatar(
                photoUrl: officerPhotoUrl,
                icon: theme.icon,
                backgroundColor: theme.background,
                foregroundColor: theme.foreground,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      step['office_label'] as String? ?? 'Required Office',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    if ((scopeLabel?.isNotEmpty ?? false)) ...[
                      const SizedBox(height: 4),
                      Text(
                        'Scope: $scopeLabel',
                        style: TextStyle(color: Colors.grey.shade700),
                      ),
                    ],
                    if ((officerName?.isNotEmpty ?? false)) ...[
                      const SizedBox(height: 4),
                      Text(
                        'Officer: $officerName',
                        style: TextStyle(color: Colors.grey.shade700),
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
                  color: theme.background,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  theme.label,
                  style: TextStyle(
                    color: theme.foreground,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          if (signedAt != null) ...[
            const SizedBox(height: 14),
            Text(
              'Last action: ${formatMobileDateTime(signedAt)}',
              style: TextStyle(color: Colors.grey.shade700),
            ),
          ],
          if ((remarks?.trim().isNotEmpty ?? false)) ...[
            const SizedBox(height: 14),
            Text(
              'Office remarks',
              style: TextStyle(
                color: theme.foreground,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              remarks!,
              style: TextStyle(color: Colors.grey.shade800, height: 1.45),
            ),
          ],
          if (onResubmit != null) ...[
            const SizedBox(height: 18),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: isResubmitting ? null : onResubmit,
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.red.shade700,
                  side: BorderSide(color: Colors.red.shade300),
                  padding: const EdgeInsets.symmetric(vertical: 14),
                ),
                child: isResubmitting
                    ? const SizedBox(
                        height: 18,
                        width: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Re-Submit to This Office'),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _OfficerAvatar extends StatelessWidget {
  const _OfficerAvatar({
    required this.photoUrl,
    required this.icon,
    required this.backgroundColor,
    required this.foregroundColor,
  });

  final String? photoUrl;
  final IconData icon;
  final Color backgroundColor;
  final Color foregroundColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 48,
      height: 48,
      decoration: BoxDecoration(color: backgroundColor, shape: BoxShape.circle),
      clipBehavior: Clip.antiAlias,
      child: photoUrl != null && photoUrl!.isNotEmpty
          ? Image.network(
              photoUrl!,
              fit: BoxFit.cover,
              width: 48,
              height: 48,
              errorBuilder: (context, error, stackTrace) =>
                  Icon(icon, color: foregroundColor),
              loadingBuilder: (context, child, progress) =>
                  progress == null ? child : Icon(icon, color: foregroundColor),
            )
          : Icon(icon, color: foregroundColor),
    );
  }
}

String formatMobileDateTime(String? value) {
  if (value == null || value.isEmpty) {
    return 'Pending';
  }

  final parsed = DateTime.tryParse(value)?.toLocal();

  if (parsed == null) {
    return value;
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
  final hour = parsed.hour == 0
      ? 12
      : parsed.hour > 12
      ? parsed.hour - 12
      : parsed.hour;
  final minute = parsed.minute.toString().padLeft(2, '0');
  final period = parsed.hour >= 12 ? 'PM' : 'AM';

  return '${months[parsed.month - 1]} ${parsed.day}, ${parsed.year}, $hour:$minute $period';
}
