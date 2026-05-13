import 'package:flutter/material.dart';

class HistoryScreen extends StatefulWidget {
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
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  String _selectedAcademicYear = '';
  List<Map<String, dynamic>> _allHistory = const [];
  List<String> _academicYears = const [];

  @override
  void initState() {
    super.initState();
    _syncHistoryPayload();
  }

  @override
  void didUpdateWidget(covariant HistoryScreen oldWidget) {
    super.didUpdateWidget(oldWidget);

    if (!identical(oldWidget.payload, widget.payload)) {
      _syncHistoryPayload();
    }
  }

  void _syncHistoryPayload() {
    final history = (widget.payload?['history'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => item.cast<String, dynamic>())
        .toList(growable: false);

    final years =
        history
            .map((record) => record['academic_year']?.toString() ?? '')
            .where((year) => year.isNotEmpty)
            .toSet()
            .toList()
          ..sort((a, b) => b.compareTo(a));

    _allHistory = history;
    _academicYears = years;

    if (_selectedAcademicYear.isNotEmpty &&
        !_academicYears.contains(_selectedAcademicYear)) {
      _selectedAcademicYear = '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final filteredHistory = _selectedAcademicYear.isEmpty
        ? _allHistory
        : _allHistory
              .where(
                (record) =>
                    record['academic_year']?.toString() ==
                    _selectedAcademicYear,
              )
              .toList();
    final sections = _HistorySection.fromRecords(filteredHistory);

    return RefreshIndicator(
      onRefresh: widget.onRefresh,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(18, 18, 18, 0),
            sliver: SliverToBoxAdapter(
              child: _IntroCard(
                totalCount: filteredHistory.length,
                academicYears: _academicYears,
                selectedAcademicYear: _selectedAcademicYear,
                onAcademicYearChanged: (value) {
                  setState(() {
                    _selectedAcademicYear = value ?? '';
                  });
                },
              ),
            ),
          ),
          const SliverToBoxAdapter(child: SizedBox(height: 14)),
          if (widget.isLoading)
            const SliverPadding(
              padding: EdgeInsets.fromLTRB(18, 0, 18, 28),
              sliver: SliverToBoxAdapter(
                child: _StateCard(
                  icon: Icons.history_rounded,
                  title: 'Loading clearance history',
                  message: 'Checking previous semester records...',
                  showSpinner: true,
                ),
              ),
            )
          else if (widget.error != null)
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(18, 0, 18, 28),
              sliver: SliverToBoxAdapter(
                child: _StateCard(
                  icon: Icons.wifi_off_rounded,
                  title: 'History unavailable',
                  message: widget.error!,
                ),
              ),
            )
          else if (filteredHistory.isEmpty)
            const SliverPadding(
              padding: EdgeInsets.fromLTRB(18, 0, 18, 28),
              sliver: SliverToBoxAdapter(
                child: _StateCard(
                  icon: Icons.inventory_2_outlined,
                  title: 'No clearance history yet',
                  message:
                      'Completed and previous clearance records will appear here once available.',
                ),
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(18, 0, 18, 28),
              sliver: SliverList.builder(
                itemCount: sections.length,
                itemBuilder: (context, index) {
                  return RepaintBoundary(
                    child: _HistoryYearSection(section: sections[index]),
                  );
                },
              ),
            ),
        ],
      ),
    );
  }
}

class _HistorySection {
  const _HistorySection({required this.academicYear, required this.semesters});

  final String academicYear;
  final List<_SemesterHistoryGroup> semesters;

  static List<_HistorySection> fromRecords(List<Map<String, dynamic>> records) {
    final sorted = [...records]
      ..sort((a, b) {
        final yearCompare = _academicYearSortKey(
          b['academic_year']?.toString(),
        ).compareTo(_academicYearSortKey(a['academic_year']?.toString()));

        if (yearCompare != 0) {
          return yearCompare;
        }

        final semesterCompare = _semesterSortKey(
          a['semester_label']?.toString(),
        ).compareTo(_semesterSortKey(b['semester_label']?.toString()));

        if (semesterCompare != 0) {
          return semesterCompare;
        }

        return _dateSortKey(
          b['completed_at']?.toString() ?? b['created_at']?.toString(),
        ).compareTo(
          _dateSortKey(
            a['completed_at']?.toString() ?? a['created_at']?.toString(),
          ),
        );
      });

    final sectionMap = <String, Map<String, List<Map<String, dynamic>>>>{};

    for (final record in sorted) {
      final year = record['academic_year']?.toString().trim();
      final yearLabel = year == null || year.isEmpty ? 'Unknown SY' : year;
      final semester = record['semester_label']?.toString().trim();
      final semesterLabel = semester == null || semester.isEmpty
          ? 'Unknown Semester'
          : semester;

      sectionMap
          .putIfAbsent(yearLabel, () => <String, List<Map<String, dynamic>>>{})
          .putIfAbsent(semesterLabel, () => <Map<String, dynamic>>[])
          .add(record);
    }

    return sectionMap.entries
        .map(
          (yearEntry) => _HistorySection(
            academicYear: yearEntry.key,
            semesters: yearEntry.value.entries
                .map(
                  (semesterEntry) => _SemesterHistoryGroup(
                    semesterLabel: semesterEntry.key,
                    records: semesterEntry.value,
                  ),
                )
                .toList(growable: false),
          ),
        )
        .toList(growable: false);
  }

  static int _academicYearSortKey(String? academicYear) {
    if (academicYear == null) {
      return 0;
    }

    final firstYear = RegExp(r'\d{4}').firstMatch(academicYear)?.group(0);

    return int.tryParse(firstYear ?? '') ?? 0;
  }

  static int _semesterSortKey(String? semester) {
    final label = (semester ?? '').toLowerCase();

    if (label.contains('1st')) {
      return 1;
    }

    if (label.contains('2nd')) {
      return 2;
    }

    if (label.contains('midyear')) {
      return 3;
    }

    return 9;
  }

  static int _dateSortKey(String? value) {
    return DateTime.tryParse(value ?? '')?.millisecondsSinceEpoch ?? 0;
  }
}

class _SemesterHistoryGroup {
  const _SemesterHistoryGroup({
    required this.semesterLabel,
    required this.records,
  });

  final String semesterLabel;
  final List<Map<String, dynamic>> records;
}

class _HistoryYearSection extends StatelessWidget {
  const _HistoryYearSection({required this.section});

  final _HistorySection section;

  @override
  Widget build(BuildContext context) {
    final recordCount = section.semesters.fold<int>(
      0,
      (sum, semester) => sum + semester.records.length,
    );

    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          _SectionHeader(
            title: 'SY ${section.academicYear}',
            subtitle: recordCount == 1
                ? '1 clearance record'
                : '$recordCount clearance records',
          ),
          const SizedBox(height: 10),
          ...section.semesters.map((semester) {
            return _SemesterGroupCard(group: semester);
          }),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  color: HistoryScreen._navy,
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                subtitle,
                style: const TextStyle(
                  color: HistoryScreen._muted,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _SemesterGroupCard extends StatelessWidget {
  const _SemesterGroupCard({required this.group});

  final _SemesterHistoryGroup group;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 2),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.72),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE4DACD)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(4, 0, 4, 10),
            child: Row(
              children: [
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: HistoryScreen._navy.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.calendar_month_rounded,
                    color: HistoryScreen._navy,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    group.semesterLabel,
                    style: const TextStyle(
                      color: HistoryScreen._navy,
                      fontSize: 15,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
                _InfoPill(
                  text: group.records.length == 1
                      ? '1 record'
                      : '${group.records.length} records',
                ),
              ],
            ),
          ),
          ...group.records.map((record) => _HistoryCard(record)),
        ],
      ),
    );
  }
}

class _IntroCard extends StatelessWidget {
  const _IntroCard({
    required this.totalCount,
    required this.academicYears,
    required this.selectedAcademicYear,
    required this.onAcademicYearChanged,
  });

  final int totalCount;
  final List<String> academicYears;
  final String selectedAcademicYear;
  final ValueChanged<String?> onAcademicYearChanged;

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
                if (academicYears.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  DropdownButtonFormField<String>(
                    initialValue: selectedAcademicYear,
                    isDense: true,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: const Color(0xFFF8F4EA),
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 8,
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: BorderSide.none,
                      ),
                    ),
                    items: [
                      const DropdownMenuItem(
                        value: '',
                        child: Text('All school years'),
                      ),
                      ...academicYears.map(
                        (year) => DropdownMenuItem(
                          value: year,
                          child: Text('SY $year'),
                        ),
                      ),
                    ],
                    onChanged: onAcademicYearChanged,
                  ),
                ],
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
          border: Border.all(color: const Color(0xFFE8EDF3)),
        ),
        child: ExpansionTile(
          maintainState: true,
          expansionAnimationStyle: AnimationStyle(
            duration: const Duration(milliseconds: 180),
            curve: Curves.easeOutCubic,
          ),
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
        color: const Color(0xFFF6F9FC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE8EDF3)),
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
        color: isCompleted ? const Color(0xFFE5F4EC) : const Color(0xFFEAF0F7),
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
        color: const Color(0xFFF6F9FC),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: const Color(0xFFE8EDF3)),
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
