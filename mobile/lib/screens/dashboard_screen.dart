import 'package:flutter/material.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({
    super.key,
    required this.payload,
    required this.error,
    required this.isLoading,
    required this.isStartingOrResuming,
    required this.isDownloadingPdf,
    required this.resubmittingStepId,
    required this.onRefresh,
    required this.onStartOrResume,
    required this.onResubmitStep,
    required this.onDownloadPdf,
  });

  final Map<String, dynamic>? payload;
  final String? error;
  final bool isLoading;

  // Ticket polish: separate dashboard action states so unrelated controls
  // do not get disabled across the shell.
  final bool isStartingOrResuming;
  final bool isDownloadingPdf;
  final int? resubmittingStepId;
  final Future<void> Function() onRefresh;
  final Future<void> Function() onStartOrResume;
  final Future<void> Function(Map<String, dynamic> step) onResubmitStep;
  final Future<void> Function() onDownloadPdf;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

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
          _HeaderCard(
            profile: _profile,
            activeSemester: _activeSemester,
          ),
          const SizedBox(height: 18),
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
              isBusy: isStartingOrResuming,
              onPressed: onStartOrResume,
            )
          else ...[
            _ClearanceSummaryCard(
              clearance: _clearance!,
              isBusy: isDownloadingPdf,
              onDownload: onDownloadPdf,
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

class _HeaderCard extends StatelessWidget {
  const _HeaderCard({
    required this.profile,
    required this.activeSemester,
  });

  final Map<String, dynamic>? profile;
  final Map<String, dynamic>? activeSemester;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

  @override
  Widget build(BuildContext context) {
    final program = profile?['program'] as Map<String, dynamic>?;

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        gradient: const LinearGradient(
          colors: [_navy, Color(0xFF254F84)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Student Overview',
            style: TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            profile?['name'] as String? ?? 'Student',
            style: const TextStyle(
              color: _gold,
              fontSize: 18,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 12,
            runSpacing: 10,
            children: [
              _MetaPill(label: 'Student ID', value: profile?['student_id_number']),
              _MetaPill(label: 'Program', value: program?['code']),
              _MetaPill(label: 'Year', value: profile?['year_level_label']),
              _MetaPill(
                label: 'Semester',
                value: activeSemester?['label'] ?? 'Not set',
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MetaPill extends StatelessWidget {
  const _MetaPill({required this.label, required this.value});

  final String label;
  final Object? value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(16),
      ),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(fontFamily: 'serif'),
          children: [
            TextSpan(
              text: '$label\n',
              style: TextStyle(
                color: Colors.white.withValues(alpha: 0.7),
                fontSize: 12,
                height: 1.35,
              ),
            ),
            TextSpan(
              text: '${value ?? '-'}',
              style: const TextStyle(
                color: Colors.white,
                fontSize: 15,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
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
            style: TextStyle(
              color: Colors.grey.shade700,
              height: 1.5,
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: isBusy ? null : onPressed,
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
}

class _ClearanceSummaryCard extends StatelessWidget {
  const _ClearanceSummaryCard({
    required this.clearance,
    required this.isBusy,
    required this.onDownload,
  });

  final Map<String, dynamic> clearance;
  final bool isBusy;
  final Future<void> Function() onDownload;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

  @override
  Widget build(BuildContext context) {
    final counts = clearance['counts'] as Map<String, dynamic>? ?? {};
    final status = clearance['status'] as String? ?? 'in_progress';
    final referenceNumber = clearance['reference_number'] as String?;
    final completedAt = clearance['completed_at'] as String?;
    final pdfAvailable = clearance['pdf_available'] == true;

    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: 12,
            runSpacing: 12,
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
          const SizedBox(height: 14),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _SummaryChip(
                label: 'Approved',
                value: '${counts['approved'] ?? 0}',
                color: Colors.green.shade700,
              ),
              _SummaryChip(
                label: 'Awaiting Action',
                value: '${counts['awaiting_action'] ?? 0}',
                color: Colors.orange.shade700,
              ),
              _SummaryChip(
                label: 'Flagged',
                value: '${counts['flagged'] ?? 0}',
                color: Colors.red.shade700,
              ),
            ],
          ),
          if (referenceNumber != null) ...[
            const SizedBox(height: 18),
            Text(
              'Reference Number: $referenceNumber',
              style: const TextStyle(
                color: _navy,
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
          if (completedAt != null) ...[
            const SizedBox(height: 8),
            Text(
              'Completed: $completedAt',
              style: TextStyle(color: Colors.grey.shade700),
            ),
          ],
          if (status == 'flagged') ...[
            const SizedBox(height: 16),
            Text(
              'Approved offices stay approved. Re-submit only the flagged office steps once your issue is resolved.',
              style: TextStyle(
                color: Colors.grey.shade800,
                height: 1.45,
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
                label: Text(isBusy ? 'Preparing PDF...' : 'Download Clearance PDF'),
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
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        '$label: $value',
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
        ),
      ),
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
        style: TextStyle(
          color: foreground,
          fontWeight: FontWeight.bold,
        ),
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
              CircleAvatar(
                backgroundColor: theme.background,
                foregroundColor: theme.foreground,
                child: Icon(theme.icon),
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
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
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
              'Last action: $signedAt',
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
              style: TextStyle(
                color: Colors.grey.shade800,
                height: 1.45,
              ),
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
