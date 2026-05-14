import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/input_sanitizers.dart';
import '../../services/registration_service.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({
    super.key,
    required this.payload,
    required this.error, // ADDED: for consistent state messaging across tabs
    required this.isLoading,
    required this.isBusy,
    required this.isUploadingPhoto,
    required this.isChangingPassword,
    required this.isUpdatingAcademicProfile,
    required this.onLogout,
    required this.onRefresh,
    required this.onUpdateProfilePhoto,
    required this.onChangePassword,
    required this.onLoadAcademicOptions,
    required this.onUpdateAcademicProfile,
  });

  final Map<String, dynamic>? payload;
  final String? error; // ADDED: display errors similar to Dashboard/PDF
  final bool isLoading;
  final bool isBusy;
  final bool isUploadingPhoto;
  final bool isChangingPassword;
  final bool isUpdatingAcademicProfile;
  final Future<void> Function() onLogout;
  final Future<void> Function() onRefresh;
  final Future<void> Function({
    required List<int> bytes,
    required String filename,
  })
  onUpdateProfilePhoto;
  final Future<bool> Function({
    required String password,
    required String passwordConfirmation,
  })
  onChangePassword;
  final Future<RegistrationOptions> Function() onLoadAcademicOptions;
  final Future<bool> Function({
    required String firstName,
    required String middleInitial,
    required String lastName,
    required String nameExtension,
    required int? programId,
    required int? yearLevel,
    required String dateOfBirth,
  })
  onUpdateAcademicProfile;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return const Center(child: CircularProgressIndicator(color: _gold));
    }

    final student = payload?['student'] as Map<String, dynamic>?;
    final program = student?['program'] as Map<String, dynamic>?;
    final semester = payload?['active_semester'] as Map<String, dynamic>?;
    final profilePhotoUrl = student?['profile_photo_url'] as String?;
    final clearance = payload?['clearance'] as Map<String, dynamic>?;
    final canEditAcademicRouting =
        clearance == null || clearance['status']?.toString() == 'completed';

    return RefreshIndicator(
      color: _gold,
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 120),
        children: [
          // ADDED: consistent error display across tabs (ticket: stronger state messaging)
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
                      'Unable to refresh your profile right now. Pull down to try again.',
                      style: TextStyle(height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 18),
          ],

          Stack(
            alignment: Alignment.topCenter,
            clipBehavior: Clip.none,
            children: [
              Container(
                margin: const EdgeInsets.only(top: 42),
                padding: const EdgeInsets.fromLTRB(20, 62, 20, 22),
                decoration: BoxDecoration(
                  color: Colors.white,
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
                  children: [
                    Text(
                      student?['name'] as String? ?? 'Student',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: _navy,
                        fontSize: 24,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      (program?['org_name'] as String?)?.isNotEmpty == true
                          ? '${program?['org_name']} Student'
                          : 'Student',
                      style: TextStyle(
                        color: Colors.grey.shade700,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
              _ProfilePhotoButton(
                photoUrl: profilePhotoUrl,
                isUploading: isUploadingPhoto,
                onPressed: isUploadingPhoto ? null : _pickAndUploadPhoto,
              ),
            ],
          ),
          const SizedBox(height: 18),
          _AcademicProfileCard(
            student: student,
            program: program,
            canEditAcademicRouting: canEditAcademicRouting,
            isBusy: isUpdatingAcademicProfile,
            onLoadOptions: onLoadAcademicOptions,
            onSubmit: onUpdateAcademicProfile,
          ),
          const SizedBox(height: 18),
          _DetailCard(
            title: 'Academic Context',
            children: [
              _DetailRow(
                label: 'Program Code',
                value: program?['code'] as String? ?? 'Unavailable',
              ),
              _DetailRow(
                label: 'Organization',
                value: program?['org_name'] as String? ?? 'Unavailable',
              ),
              _DetailRow(
                label: 'Active Semester',
                value: semester?['label'] as String? ?? 'No active semester',
                isLast: true,
              ),
            ],
          ),
          const SizedBox(height: 18),
          _PasswordCard(isBusy: isChangingPassword, onSubmit: onChangePassword),
          const SizedBox(height: 18),
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF4DB),
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: _gold.withValues(alpha: 0.35)),
            ),
            child: const Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(Icons.verified_user_outlined, color: _navy),
                SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'This account is used for student clearance only. If any profile detail is wrong, please contact MIS before the semester closes.',
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
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: isBusy ? null : onLogout,
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
                  : const Icon(Icons.logout_rounded),
              label: const Text('Logout'),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _pickAndUploadPhoto() async {
    final picker = ImagePicker();
    final image = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1024,
      imageQuality: 82,
    );

    if (image == null) {
      return;
    }

    await onUpdateProfilePhoto(
      bytes: await image.readAsBytes(),
      filename: image.name,
    );
  }
}

class _AcademicProfileCard extends StatefulWidget {
  const _AcademicProfileCard({
    required this.student,
    required this.program,
    required this.canEditAcademicRouting,
    required this.isBusy,
    required this.onLoadOptions,
    required this.onSubmit,
  });

  final Map<String, dynamic>? student;
  final Map<String, dynamic>? program;
  final bool canEditAcademicRouting;
  final bool isBusy;
  final Future<RegistrationOptions> Function() onLoadOptions;
  final Future<bool> Function({
    required String firstName,
    required String middleInitial,
    required String lastName,
    required String nameExtension,
    required int? programId,
    required int? yearLevel,
    required String dateOfBirth,
  })
  onSubmit;

  @override
  State<_AcademicProfileCard> createState() => _AcademicProfileCardState();
}

class _AcademicProfileCardState extends State<_AcademicProfileCard> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _middleInitialController = TextEditingController();
  final _lastNameController = TextEditingController();
  RegistrationOptions? _options;
  bool _isEditing = false;
  bool _isLoadingOptions = false;
  String? _loadError;
  String? _formError;
  String _selectedNameExtension = '';
  int? _selectedProgramId;
  int? _selectedYearLevel;
  DateTime? _selectedBirthday;

  @override
  void initState() {
    super.initState();
    _syncSelectionsFromProfile();
  }

  @override
  void didUpdateWidget(covariant _AcademicProfileCard oldWidget) {
    super.didUpdateWidget(oldWidget);

    if (!identical(oldWidget.student, widget.student) ||
        !identical(oldWidget.program, widget.program)) {
      _syncSelectionsFromProfile();
    }
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _middleInitialController.dispose();
    _lastNameController.dispose();
    super.dispose();
  }

  void _syncSelectionsFromProfile() {
    _firstNameController.text = widget.student?['first_name']?.toString() ?? '';
    _middleInitialController.text =
        widget.student?['middle_initial']?.toString() ?? '';
    _lastNameController.text = widget.student?['last_name']?.toString() ?? '';
    _selectedNameExtension =
        widget.student?['name_extension']?.toString() ?? '';
    _selectedProgramId = _asInt(widget.program?['id']);
    _selectedYearLevel = _asInt(widget.student?['year_level']);
    _selectedBirthday = DateTime.tryParse(
      widget.student?['date_of_birth']?.toString() ?? '',
    );
  }

  Future<void> _beginEdit() async {
    setState(() {
      _isEditing = true;
      _loadError = null;
      _formError = null;
    });
    _syncSelectionsFromProfile();

    if (!widget.canEditAcademicRouting) {
      return;
    }

    if (_options != null || _isLoadingOptions) {
      return;
    }

    setState(() {
      _isLoadingOptions = true;
    });

    try {
      final options = await widget.onLoadOptions();

      if (!mounted) {
        return;
      }

      setState(() {
        _options = options;
        _isLoadingOptions = false;
      });
    } catch (error) {
      if (!mounted) {
        return;
      }

      setState(() {
        _loadError = error.toString().replaceFirst('Exception: ', '');
        _isLoadingOptions = false;
      });
    }
  }

  Future<void> _pickBirthday() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate:
          _selectedBirthday ?? DateTime(now.year - 18, now.month, now.day),
      firstDate: DateTime(1900),
      lastDate: now,
    );

    if (picked == null || !mounted) {
      return;
    }

    setState(() {
      _selectedBirthday = picked;
      _formError = null;
    });
  }

  Future<void> _submit() async {
    if (widget.isBusy || !_formKey.currentState!.validate()) {
      return;
    }

    final birthday = _selectedBirthday;
    final programId = widget.canEditAcademicRouting
        ? _selectedProgramId
        : _asInt(widget.program?['id']);
    final yearLevel = widget.canEditAcademicRouting
        ? _selectedYearLevel
        : _asInt(widget.student?['year_level']);

    if (birthday == null) {
      setState(() {
        _formError = 'Choose your birthday.';
      });
      return;
    }

    if (widget.canEditAcademicRouting &&
        (programId == null || yearLevel == null)) {
      setState(() {
        _formError = 'Choose your program and year level.';
      });
      return;
    }

    final didUpdate = await widget.onSubmit(
      firstName: _firstNameController.text.trim(),
      middleInitial: _middleInitialController.text.trim(),
      lastName: _lastNameController.text.trim(),
      nameExtension: _selectedNameExtension,
      programId: programId,
      yearLevel: yearLevel,
      dateOfBirth: _toDateString(birthday),
    );

    if (!mounted || !didUpdate) {
      return;
    }

    setState(() {
      _isEditing = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    final options = _options;
    final programs = options?.programs ?? const <RegistrationProgram>[];
    final yearLevels =
        options?.yearLevels ??
        const [
          RegistrationYearLevel(value: 1, label: '1st Year'),
          RegistrationYearLevel(value: 2, label: '2nd Year'),
          RegistrationYearLevel(value: 3, label: '3rd Year'),
          RegistrationYearLevel(value: 4, label: '4th Year'),
        ];
    final nameExtensions = {
      '',
      ...?options?.nameExtensions,
      'Jr',
      'Sr',
      'II',
      'III',
      'IV',
    }.toList();
    final hasSelectedProgram =
        _selectedProgramId != null &&
        programs.any((program) => program.id == _selectedProgramId);
    final hasSelectedYear =
        _selectedYearLevel != null &&
        yearLevels.any((year) => year.value == _selectedYearLevel);

    return _DetailCard(
      title: 'Profile Details',
      action: TextButton.icon(
        onPressed: widget.isBusy
            ? null
            : () {
                if (_isEditing) {
                  setState(() {
                    _isEditing = false;
                    _loadError = null;
                    _formError = null;
                  });
                } else {
                  _beginEdit();
                }
              },
        icon: Icon(_isEditing ? Icons.close_rounded : Icons.edit_rounded),
        label: Text(_isEditing ? 'Cancel' : 'Edit'),
      ),
      children: [
        _DetailRow(
          label: 'Student ID',
          value:
              widget.student?['student_id_number'] as String? ?? 'Unavailable',
        ),
        _DetailRow(
          label: 'Name',
          value: widget.student?['name'] as String? ?? 'Unavailable',
        ),
        _DetailRow(
          label: 'Birthday',
          value: _formatBirthday(widget.student?['date_of_birth'] as String?),
        ),
        _DetailRow(
          label: 'Program',
          value: widget.program?['name'] as String? ?? 'Unavailable',
        ),
        _DetailRow(
          label: 'Year Level',
          value:
              widget.student?['year_level_label'] as String? ?? 'Unavailable',
          isLast: !_isEditing,
        ),
        if (!widget.canEditAcademicRouting) ...[
          const SizedBox(height: 10),
          _ProfileNotice(
            icon: Icons.lock_clock_rounded,
            text:
                'Name and birthday can be edited anytime. Program and year level unlock before starting clearance or after completing the current clearance.',
          ),
        ],
        if (_isEditing) ...[
          const SizedBox(height: 14),
          if (_isLoadingOptions)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 10),
              child: Center(child: CircularProgressIndicator()),
            )
          else
            Form(
              key: _formKey,
              child: Column(
                children: [
                  if (_loadError != null || _formError != null) ...[
                    _ProfileNotice(
                      icon: Icons.error_outline_rounded,
                      text: _loadError ?? _formError!,
                      isError: true,
                    ),
                    const SizedBox(height: 12),
                  ],
                  TextFormField(
                    controller: _firstNameController,
                    textCapitalization: TextCapitalization.words,
                    inputFormatters: [
                      const NoEmojiTextInputFormatter(),
                      LengthLimitingTextInputFormatter(60),
                    ],
                    decoration: const InputDecoration(labelText: 'First Name'),
                    validator: (value) => _validateName(value, 'First name'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _lastNameController,
                    textCapitalization: TextCapitalization.words,
                    inputFormatters: [
                      const NoEmojiTextInputFormatter(),
                      LengthLimitingTextInputFormatter(60),
                    ],
                    decoration: const InputDecoration(labelText: 'Last Name'),
                    validator: (value) => _validateName(value, 'Last name'),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _middleInitialController,
                          textCapitalization: TextCapitalization.characters,
                          inputFormatters: [
                            const NoEmojiTextInputFormatter(),
                            LengthLimitingTextInputFormatter(1),
                          ],
                          decoration: const InputDecoration(
                            labelText: 'M.I. (Optional)',
                          ),
                          validator: _validateMiddleInitial,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          initialValue:
                              nameExtensions.contains(_selectedNameExtension)
                              ? _selectedNameExtension
                              : '',
                          isExpanded: true,
                          decoration: const InputDecoration(
                            labelText: 'Suffix',
                          ),
                          items: nameExtensions
                              .map(
                                (extension) => DropdownMenuItem(
                                  value: extension,
                                  child: Text(
                                    extension.isEmpty ? 'No suffix' : extension,
                                  ),
                                ),
                              )
                              .toList(),
                          onChanged: widget.isBusy
                              ? null
                              : (value) {
                                  setState(() {
                                    _selectedNameExtension = value ?? '';
                                  });
                                },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: widget.isBusy ? null : _pickBirthday,
                    child: InputDecorator(
                      decoration: const InputDecoration(labelText: 'Birthday'),
                      child: Text(
                        _selectedBirthday == null
                            ? 'Choose birthday'
                            : _formatBirthday(
                                _toDateString(_selectedBirthday!),
                              ),
                        style: const TextStyle(
                          color: ProfileScreen._navy,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                  if (widget.canEditAcademicRouting && _loadError == null) ...[
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int>(
                      initialValue: hasSelectedProgram
                          ? _selectedProgramId
                          : null,
                      isExpanded: true,
                      decoration: const InputDecoration(labelText: 'Program'),
                      items: programs
                          .map(
                            (program) => DropdownMenuItem(
                              value: program.id,
                              child: Text('${program.code} - ${program.name}'),
                            ),
                          )
                          .toList(),
                      validator: (value) =>
                          value == null ? 'Choose a program.' : null,
                      onChanged: widget.isBusy
                          ? null
                          : (value) {
                              setState(() {
                                _selectedProgramId = value;
                                _formError = null;
                              });
                            },
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int>(
                      initialValue: hasSelectedYear ? _selectedYearLevel : null,
                      decoration: const InputDecoration(
                        labelText: 'Year Level',
                      ),
                      items: yearLevels
                          .map(
                            (year) => DropdownMenuItem(
                              value: year.value,
                              child: Text(year.label),
                            ),
                          )
                          .toList(),
                      validator: (value) =>
                          value == null ? 'Choose a year level.' : null,
                      onChanged: widget.isBusy
                          ? null
                          : (value) {
                              setState(() {
                                _selectedYearLevel = value;
                                _formError = null;
                              });
                            },
                    ),
                  ],
                  const SizedBox(height: 14),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: widget.isBusy ? null : _submit,
                      child: widget.isBusy
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Text('Save Profile Details'),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ],
    );
  }

  static int? _asInt(dynamic value) {
    if (value is int) {
      return value;
    }

    return int.tryParse(value?.toString() ?? '');
  }

  static String? _validateName(String? value, String label) {
    final trimmed = value?.trim() ?? '';

    if (trimmed.isEmpty) {
      return '$label is required.';
    }

    if (trimmed.length > 60) {
      return '$label is too long.';
    }

    if (containsEmoji(trimmed) ||
        RegExp(r'[0-9_~`!@#$%^&*()=+\[\]{}\\|;:"<>,.?/]').hasMatch(trimmed)) {
      return '$label can only use letters, spaces, apostrophes, and hyphens.';
    }

    return null;
  }

  static String? _validateMiddleInitial(String? value) {
    final trimmed = value?.trim() ?? '';

    if (trimmed.isEmpty) {
      return null;
    }

    if (trimmed.length != 1 ||
        containsEmoji(trimmed) ||
        RegExp(r'[0-9_~`!@#$%^&*()=+\[\]{}\\|;:"<>,.?/\s]').hasMatch(trimmed)) {
      return 'Use one letter.';
    }

    return null;
  }

  static String _toDateString(DateTime value) {
    final month = value.month.toString().padLeft(2, '0');
    final day = value.day.toString().padLeft(2, '0');

    return '${value.year}-$month-$day';
  }

  static String _formatBirthday(String? value) {
    if (value == null || value.isEmpty) {
      return 'Not set';
    }

    final parsed = DateTime.tryParse(value);
    if (parsed == null) {
      return value;
    }

    const monthNames = [
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

    return '${monthNames[parsed.month - 1]} ${parsed.day}, ${parsed.year}';
  }
}

class _ProfileNotice extends StatelessWidget {
  const _ProfileNotice({
    required this.icon,
    required this.text,
    this.isError = false,
  });

  final IconData icon;
  final String text;
  final bool isError;

  @override
  Widget build(BuildContext context) {
    final color = isError ? Colors.red.shade700 : ProfileScreen._navy;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isError ? const Color(0xFFFFE7E7) : const Color(0xFFFFF4DB),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isError
              ? Colors.red.withValues(alpha: 0.22)
              : ProfileScreen._gold.withValues(alpha: 0.28),
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                color: color,
                fontWeight: FontWeight.w700,
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PasswordCard extends StatefulWidget {
  const _PasswordCard({required this.isBusy, required this.onSubmit});

  final bool isBusy;
  final Future<bool> Function({
    required String password,
    required String passwordConfirmation,
  })
  onSubmit;

  @override
  State<_PasswordCard> createState() => _PasswordCardState();
}

class _PasswordCardState extends State<_PasswordCard> {
  final _formKey = GlobalKey<FormState>();
  final _passwordController = TextEditingController();
  final _confirmController = TextEditingController();
  bool _isExpanded = false;
  bool _obscurePassword = true;
  bool _obscureConfirm = true;

  @override
  void dispose() {
    _passwordController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (widget.isBusy || !_formKey.currentState!.validate()) {
      return;
    }

    final didUpdate = await widget.onSubmit(
      password: _passwordController.text,
      passwordConfirmation: _confirmController.text,
    );

    if (!mounted || !didUpdate) {
      return;
    }

    _passwordController.clear();
    _confirmController.clear();

    setState(() {
      _isExpanded = false;
      _obscurePassword = true;
      _obscureConfirm = true;
    });
  }

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
        children: [
          Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: ProfileScreen._gold.withValues(alpha: 0.18),
                  shape: BoxShape.circle,
                ),
                child: const Icon(
                  Icons.lock_reset_rounded,
                  color: ProfileScreen._navy,
                ),
              ),
              const SizedBox(width: 14),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Password',
                      style: TextStyle(
                        color: ProfileScreen._navy,
                        fontWeight: FontWeight.w900,
                        fontSize: 17,
                      ),
                    ),
                    SizedBox(height: 3),
                    Text(
                      'Update your student app password.',
                      style: TextStyle(color: Colors.black54, height: 1.35),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: widget.isBusy
                    ? null
                    : () {
                        setState(() {
                          _isExpanded = !_isExpanded;
                        });
                      },
                child: Text(_isExpanded ? 'Cancel' : 'Change'),
              ),
            ],
          ),
          AnimatedSwitcher(
            duration: const Duration(milliseconds: 160),
            switchInCurve: Curves.easeOutCubic,
            switchOutCurve: Curves.easeInCubic,
            child: !_isExpanded
                ? const SizedBox.shrink()
                : Form(
                    key: _formKey,
                    child: Padding(
                      padding: const EdgeInsets.only(top: 16),
                      child: Column(
                        children: [
                          TextFormField(
                            controller: _passwordController,
                            obscureText: _obscurePassword,
                            inputFormatters: const [
                              NoEmojiTextInputFormatter(),
                            ],
                            decoration: InputDecoration(
                              labelText: 'New password',
                              suffixIcon: IconButton(
                                tooltip: _obscurePassword
                                    ? 'Show password'
                                    : 'Hide password',
                                onPressed: () {
                                  setState(() {
                                    _obscurePassword = !_obscurePassword;
                                  });
                                },
                                icon: Icon(
                                  _obscurePassword
                                      ? Icons.visibility_off
                                      : Icons.visibility,
                                ),
                              ),
                            ),
                            validator: (value) {
                              final password = value ?? '';

                              if (password.length < 8) {
                                return 'Use at least 8 characters.';
                              }

                              if (password.length > 72) {
                                return 'Password is too long.';
                              }

                              if (containsEmoji(password)) {
                                return 'Password cannot contain emoji.';
                              }

                              return null;
                            },
                          ),
                          const SizedBox(height: 12),
                          TextFormField(
                            controller: _confirmController,
                            obscureText: _obscureConfirm,
                            inputFormatters: const [
                              NoEmojiTextInputFormatter(),
                            ],
                            decoration: InputDecoration(
                              labelText: 'Confirm password',
                              suffixIcon: IconButton(
                                tooltip: _obscureConfirm
                                    ? 'Show password'
                                    : 'Hide password',
                                onPressed: () {
                                  setState(() {
                                    _obscureConfirm = !_obscureConfirm;
                                  });
                                },
                                icon: Icon(
                                  _obscureConfirm
                                      ? Icons.visibility_off
                                      : Icons.visibility,
                                ),
                              ),
                            ),
                            validator: (value) {
                              if ((value ?? '').isEmpty) {
                                return 'Confirm your password.';
                              }

                              if (value != _passwordController.text) {
                                return 'Passwords do not match.';
                              }

                              return null;
                            },
                          ),
                          const SizedBox(height: 14),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton(
                              onPressed: widget.isBusy ? null : _submit,
                              child: widget.isBusy
                                  ? const SizedBox(
                                      width: 18,
                                      height: 18,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Text('Save New Password'),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

class _ProfilePhotoButton extends StatelessWidget {
  const _ProfilePhotoButton({
    required this.photoUrl,
    required this.isUploading,
    required this.onPressed,
  });

  final String? photoUrl;
  final bool isUploading;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 104,
      height: 104,
      child: Stack(
        alignment: Alignment.center,
        children: [
          Container(
            width: 92,
            height: 92,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 4),
              gradient: const LinearGradient(
                colors: [Color(0xFFE7E3E1), Color(0xFFD1CFCF)],
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.08),
                  blurRadius: 18,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: ClipOval(
              child: photoUrl != null && photoUrl!.isNotEmpty
                  ? Image.network(
                      photoUrl!,
                      fit: BoxFit.cover,
                      width: 92,
                      height: 92,
                      errorBuilder: (context, error, stackTrace) => const Icon(
                        Icons.person_rounded,
                        size: 46,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(
                      Icons.person_rounded,
                      size: 46,
                      color: Colors.white,
                    ),
            ),
          ),
          Positioned(
            right: 4,
            bottom: 8,
            child: IconButton.filled(
              tooltip: 'Update profile picture',
              onPressed: onPressed,
              style: IconButton.styleFrom(
                backgroundColor: ProfileScreen._gold,
                foregroundColor: ProfileScreen._navy,
                minimumSize: const Size(34, 34),
              ),
              icon: isUploading
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.camera_alt_rounded, size: 18),
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailCard extends StatelessWidget {
  const _DetailCard({this.title, this.action, required this.children});

  final String? title;
  final Widget? action;
  final List<Widget> children;

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
        children: [
          if (title != null) ...[
            Row(
              children: [
                Expanded(
                  child: Text(
                    title!,
                    style: const TextStyle(
                      color: ProfileScreen._navy,
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                ?action,
              ],
            ),
            const SizedBox(height: 12),
          ],
          ...children,
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({
    required this.label,
    required this.value,
    this.isLast = false,
  });

  final String label;
  final String value;
  final bool isLast;

  @override
  Widget build(BuildContext context) {
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 106,
            child: Text(
              label,
              style: TextStyle(
                color: Colors.grey.shade700,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(
                color: ProfileScreen._navy,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
