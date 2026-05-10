import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../services/registration_service.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key, this.registrationService});

  final RegistrationService? registrationService;

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _middleInitialController = TextEditingController();
  final _studentIdController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  late final RegistrationService _registrationService =
      widget.registrationService ?? RegistrationService();

  RegistrationOptions _options = RegistrationOptions.empty();
  RegistrationProgram? _selectedProgram;
  RegistrationYearLevel? _selectedYearLevel;
  String _selectedExtension = '';
  String? _error;
  bool _isLoadingOptions = true;
  bool _isSubmitting = false;
  bool _acceptedTerms = false;
  bool _hasReadPrivacyStatement = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  static const Color _navy = Color(0xFF16385F);
  static const Color _paper = Color(0xFFFCFBF7);
  static const Color _field = Colors.white;
  static const Color _line = Color(0xFFD7D3C8);
  static const Color _ink = Color(0xFF1B1B1B);
  static const Color _muted = Color(0xFF667085);
  static const Color _gold = Color(0xFFD2A83D);
  static const Color _danger = Color(0xFFB5442C);
  static const String _logoAsset =
      'assets/branding/lnu_digital_clearance_logo_compact.png';
  static final RegExp _supportedNameCharacterPattern = RegExp(
    r"^[A-Za-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF\u00D1\u00F1' -]+$",
    unicode: true,
  );
  static final RegExp _supportedMiddleInitialPattern = RegExp(
    r'^[A-Za-z\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u00FF\u00D1\u00F1]$',
    unicode: true,
  );
  static final RegExp _emailPattern = RegExp(
    r'^[A-Za-z0-9._%+-]+@lnu\.edu\.ph$',
  );
  static const String _privacyStatement = '''
LNU Data Privacy Statement

Leyte Normal University (LNU) highly values your right to data privacy. It is firmly committed to ensuring that all personal data collected from students, employees, partners, stakeholders, and the public is handled with utmost care, in accordance with Republic Act No. 10173, otherwise known as the Data Privacy Act of 2012. LNU likewise adhere to the data privacy principles of (1) legitimate purpose; (2) transparency; and (3) proportionality.

This Data Privacy Statement (DPS) outlines the policies and practices of LNU in collecting, using, storing, and disclosing your personal data. It seeks to inform you of your rights as a data subject and the ways in which the university protects and secures personal information under its control.

Whether you are a student enrolling in the university, an employee rendering service, or an individual engaging with LNU in any capacity, the DPS aims to assure you that the University observes the highest standards of transparency, accountability, and lawful processing in all its data-handling practices.

Purpose of Collection, Use and Disclosure of your Personal and Sensitive Personal Information

LNU collects various types of personal information from students, employees, partners, stakeholders, and other individuals engaging with the University. This includes personal information that can be used to identify an individual, such as full name, maiden name, or other names used; date and place of birth; gender, civil status, and nationality; contact details including home address, email address, and telephone or mobile number; photographs or other identifying images; and government-issued identification numbers such as TIN, GSIS, SSS, and PhilHealth.

It also includes sensitive personal information as defined under RA 10173, such as race or ethnic origin; religious, philosophical, or political affiliations; health and medical information including health records and medical clearances; educational background, academic records, and disciplinary records; criminal or administrative case records, if any; licenses or permits issued by government agencies along with details of their suspension, revocation, or denial; and tax returns or other financial records.

In the course of its operations, LNU may likewise collect other relevant information such as student performance data (grades, academic progress, and achievements); employment details (position, salary grade, benefits, and performance evaluations); scholarship or financial assistance records; research outputs and intellectual property records; attendance records for classes, events, and training; CCTV footage and other security-related recordings; and digital logs from the University's online systems, portals, and platforms. The collection of such personal information is limited to what is necessary to fulfill the University's academic, research, administrative, and statutory mandates.

Personal and sensitive personal information, as mentioned above, are collectively referred to as "personal data".

LNU collects, uses, and discloses personal data for purposes that are directly related to the performance of its academic, research, administrative, and statutory functions. These purposes include, but are not limited to, student account registration, identity verification, clearance processing, academic record management, administrative coordination, security monitoring, system access control, and compliance with applicable legal and institutional requirements.
''';

  @override
  void initState() {
    super.initState();
    _loadOptions();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    precacheImage(const AssetImage(_logoAsset), context);
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _middleInitialController.dispose();
    _studentIdController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _loadOptions() async {
    try {
      final options = await _registrationService.loadOptions();

      if (!mounted) return;

      setState(() {
        _options = options;
        _isLoadingOptions = false;
        _error = options.programs.isEmpty
            ? 'Academic options are temporarily unavailable. Please try again later.'
            : null;
      });
    } catch (error) {
      if (!mounted) return;

      setState(() {
        _isLoadingOptions = false;
        _error = error.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();

    if (!_hasReadPrivacyStatement || !_acceptedTerms) {
      setState(() {
        _error =
            'Please read and agree to the LNU Data Privacy Statement before creating an account.';
      });
      return;
    }

    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _isSubmitting = true;
      _error = null;
    });

    try {
      final message = await _registrationService.register(
        RegistrationRequest(
          studentIdNumber: _studentIdController.text.trim(),
          firstName: _firstNameController.text.trim(),
          middleInitial: _middleInitialController.text.trim(),
          lastName: _lastNameController.text.trim(),
          nameExtension: _selectedExtension,
          email: _emailController.text.trim().toLowerCase(),
          programId: _selectedProgram!.id,
          yearLevel: _selectedYearLevel!.value,
          password: _passwordController.text,
          passwordConfirmation: _confirmPasswordController.text,
        ),
      );

      if (!mounted) return;

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;

      setState(() {
        _error = error.toString().replaceFirst('Exception: ', '');
      });
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  Future<void> _showPrivacyStatement() async {
    bool hasReachedBottom = false;

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            void markReachedBottom(ScrollMetrics metrics) {
              if (!hasReachedBottom &&
                  metrics.pixels >= metrics.maxScrollExtent - 24) {
                setDialogState(() {
                  hasReachedBottom = true;
                });
              }
            }

            return AlertDialog(
              backgroundColor: _paper,
              insetPadding: const EdgeInsets.all(18),
              titlePadding: const EdgeInsets.fromLTRB(20, 18, 12, 0),
              contentPadding: const EdgeInsets.fromLTRB(20, 14, 20, 12),
              actionsPadding: const EdgeInsets.fromLTRB(20, 0, 20, 18),
              title: Row(
                children: [
                  const Expanded(
                    child: Text(
                      'Terms and Conditions',
                      style: TextStyle(
                        color: _navy,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                  IconButton(
                    tooltip: hasReachedBottom
                        ? 'Close'
                        : 'Scroll to the bottom first',
                    onPressed: hasReachedBottom
                        ? () => Navigator.of(dialogContext).pop()
                        : null,
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
              content: SizedBox(
                width: 520,
                height: MediaQuery.sizeOf(context).height * 0.62,
                child: NotificationListener<ScrollNotification>(
                  onNotification: (notification) {
                    markReachedBottom(notification.metrics);
                    return false;
                  },
                  child: SingleChildScrollView(
                    child: Text(
                      _privacyStatement,
                      style: const TextStyle(
                        color: _ink,
                        height: 1.48,
                        fontSize: 14,
                      ),
                    ),
                  ),
                ),
              ),
              actions: [
                Text(
                  hasReachedBottom
                      ? 'You can now agree to continue.'
                      : 'Scroll to the bottom to enable agreement.',
                  style: const TextStyle(
                    color: _muted,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 8),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    key: const Key('privacy-agree-button'),
                    onPressed: hasReachedBottom
                        ? () {
                            setState(() {
                              _hasReadPrivacyStatement = true;
                              _acceptedTerms = true;
                            });
                            Navigator.of(dialogContext).pop();
                          }
                        : null,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _navy,
                      foregroundColor: Colors.white,
                      disabledBackgroundColor: _line,
                      disabledForegroundColor: _muted,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    icon: const Icon(Icons.verified_user_outlined),
                    label: const Text('I have read and agree'),
                  ),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final canUseAcademicOptions =
        !_isLoadingOptions && _options.programs.isNotEmpty;

    return Scaffold(
      backgroundColor: _paper,
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(22, 24, 22, 28),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 520),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    _HeaderCard(onBack: () => Navigator.of(context).pop()),
                    const SizedBox(height: 28),
                    Column(
                      children: [
                        _nameField(
                          controller: _firstNameController,
                          label: 'First Name',
                          required: true,
                        ),
                        const SizedBox(height: 14),
                        _nameField(
                          controller: _lastNameController,
                          label: 'Last Name',
                          required: true,
                        ),
                        const SizedBox(height: 14),
                        _middleInitialField(),
                        const SizedBox(height: 14),
                        _suffixDropdown(),
                      ],
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _studentIdController,
                      keyboardType: TextInputType.number,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                        LengthLimitingTextInputFormatter(7),
                      ],
                      style: _inputTextStyle(),
                      decoration: _inputDecoration(
                        label: 'Student ID',
                        icon: Icons.badge_outlined,
                      ),
                      validator: _validateStudentId,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _emailController,
                      keyboardType: TextInputType.emailAddress,
                      textCapitalization: TextCapitalization.none,
                      style: _inputTextStyle(),
                      decoration: _inputDecoration(
                        label: 'LNU Institutional Email (optional)',
                        hint: 'name@lnu.edu.ph',
                        icon: Icons.mail_outline,
                      ),
                      validator: _validateEmail,
                    ),
                    const SizedBox(height: 24),
                    _SectionTitle(
                      title: 'Academic Profile',
                      subtitle:
                          'These choices come from the admin-managed directory.',
                    ),
                    const SizedBox(height: 14),
                    if (_error != null) ...[
                      _ErrorBanner(message: _error!),
                      const SizedBox(height: 14),
                    ],
                    DropdownButtonFormField<RegistrationProgram>(
                      key: const Key('registration-program-dropdown'),
                      initialValue: _selectedProgram,
                      isExpanded: true,
                      items: _options.programs
                          .map(
                            (program) => DropdownMenuItem(
                              value: program,
                              child: Text(
                                '${program.code} - ${program.name}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: canUseAcademicOptions
                          ? (program) {
                              setState(() {
                                _selectedProgram = program;
                                _selectedYearLevel = null;
                              });
                            }
                          : null,
                      dropdownColor: _field,
                      style: _inputTextStyle(),
                      decoration: _inputDecoration(
                        label: 'Program',
                        hint: _isLoadingOptions
                            ? 'Loading programs...'
                            : 'Select your program',
                        icon: Icons.school_outlined,
                      ),
                      validator: (value) =>
                          value == null ? 'Please select your program.' : null,
                    ),
                    const SizedBox(height: 14),
                    DropdownButtonFormField<RegistrationYearLevel>(
                      key: const Key('registration-year-level-dropdown'),
                      initialValue: _selectedYearLevel,
                      isExpanded: true,
                      items: _options.yearLevels
                          .map(
                            (yearLevel) => DropdownMenuItem(
                              value: yearLevel,
                              child: Text(yearLevel.label),
                            ),
                          )
                          .toList(),
                      onChanged:
                          canUseAcademicOptions && _selectedProgram != null
                          ? (yearLevel) {
                              setState(() {
                                _selectedYearLevel = yearLevel;
                              });
                            }
                          : null,
                      dropdownColor: _field,
                      style: _inputTextStyle(),
                      decoration: _inputDecoration(
                        label: 'Year Level',
                        hint: _selectedProgram == null
                            ? 'Select a program first'
                            : 'Select your year level',
                        icon: Icons.calendar_month_outlined,
                      ),
                      validator: (value) => value == null
                          ? 'Please select your year level.'
                          : null,
                    ),
                    const SizedBox(height: 14),
                    _OrganizationCard(program: _selectedProgram),
                    const SizedBox(height: 24),
                    TextFormField(
                      controller: _passwordController,
                      obscureText: _obscurePassword,
                      style: _inputTextStyle(),
                      decoration: _passwordDecoration(
                        label: 'Password',
                        obscure: _obscurePassword,
                        onToggle: () {
                          setState(() {
                            _obscurePassword = !_obscurePassword;
                          });
                        },
                      ),
                      validator: _validatePassword,
                    ),
                    const SizedBox(height: 14),
                    TextFormField(
                      controller: _confirmPasswordController,
                      obscureText: _obscureConfirmPassword,
                      style: _inputTextStyle(),
                      decoration: _passwordDecoration(
                        label: 'Confirm Password',
                        obscure: _obscureConfirmPassword,
                        onToggle: () {
                          setState(() {
                            _obscureConfirmPassword = !_obscureConfirmPassword;
                          });
                        },
                      ),
                      validator: _validatePasswordConfirmation,
                    ),
                    const SizedBox(height: 18),
                    _PrivacyAgreement(
                      acceptedTerms: _acceptedTerms,
                      hasReadPrivacyStatement: _hasReadPrivacyStatement,
                      onOpen: _showPrivacyStatement,
                      onChanged: (value) {
                        if (!_hasReadPrivacyStatement) {
                          _showPrivacyStatement();
                          return;
                        }

                        setState(() {
                          _acceptedTerms = value ?? false;
                        });
                      },
                    ),
                    const SizedBox(height: 18),
                    SizedBox(
                      height: 56,
                      child: ElevatedButton(
                        key: const Key('registration-submit-button'),
                        onPressed: _isSubmitting || _isLoadingOptions
                            ? null
                            : _submit,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _navy,
                          disabledBackgroundColor: Colors.white.withValues(
                            alpha: 0.7,
                          ),
                          foregroundColor: Colors.white,
                          disabledForegroundColor: _muted,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(18),
                          ),
                          textStyle: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 17,
                          ),
                        ),
                        child: _isSubmitting
                            ? const SizedBox(
                                height: 22,
                                width: 22,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.4,
                                  color: Colors.white,
                                ),
                              )
                            : const Text('Submit for Approval'),
                      ),
                    ),
                    const SizedBox(height: 20),
                    TextButton(
                      onPressed: _isSubmitting
                          ? null
                          : () => Navigator.of(context).pop(),
                      child: const Text(
                        'Already have an account? Sign In',
                        style: TextStyle(
                          color: _navy,
                          fontWeight: FontWeight.w700,
                          decoration: TextDecoration.underline,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _nameField({
    required TextEditingController controller,
    required String label,
    required bool required,
  }) {
    return TextFormField(
      controller: controller,
      textCapitalization: TextCapitalization.words,
      inputFormatters: [LengthLimitingTextInputFormatter(60)],
      style: _inputTextStyle(),
      decoration: _inputDecoration(label: label, icon: Icons.person_outline),
      validator: (value) => _validateName(value, label, required: required),
    );
  }

  Widget _middleInitialField() {
    return TextFormField(
      controller: _middleInitialController,
      textCapitalization: TextCapitalization.characters,
      inputFormatters: [LengthLimitingTextInputFormatter(1)],
      style: _inputTextStyle(),
      decoration: _inputDecoration(
        label: 'M.I. (optional)',
        icon: Icons.person_outline,
      ),
      validator: (value) {
        final normalized = value?.trim() ?? '';
        if (normalized.isEmpty) return null;
        if (!_supportedMiddleInitialPattern.hasMatch(normalized)) {
          return 'Middle initial must be one letter.';
        }
        return null;
      },
    );
  }

  Widget _suffixDropdown() {
    return DropdownButtonFormField<String>(
      key: const Key('registration-suffix-dropdown'),
      initialValue: _selectedExtension,
      isExpanded: true,
      items: [
        const DropdownMenuItem(value: '', child: Text('No suffix')),
        ..._options.nameExtensions.map(
          (extension) =>
              DropdownMenuItem(value: extension, child: Text(extension)),
        ),
      ],
      onChanged: (value) {
        setState(() {
          _selectedExtension = value ?? '';
        });
      },
      dropdownColor: _field,
      style: _inputTextStyle(),
      decoration: _inputDecoration(
        label: 'Suffix (optional)',
        icon: Icons.person_outline,
      ),
    );
  }

  InputDecoration _passwordDecoration({
    required String label,
    required bool obscure,
    required VoidCallback onToggle,
  }) {
    return _inputDecoration(label: label, icon: Icons.lock_outline).copyWith(
      suffixIcon: IconButton(
        tooltip: obscure ? 'Show password' : 'Hide password',
        color: _navy,
        icon: Icon(obscure ? Icons.visibility_off : Icons.visibility),
        onPressed: onToggle,
      ),
    );
  }

  InputDecoration _inputDecoration({
    required String label,
    required IconData icon,
    String? hint,
  }) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      prefixIcon: Icon(icon, color: _navy),
      labelStyle: const TextStyle(color: _navy, fontWeight: FontWeight.w700),
      hintStyle: const TextStyle(color: _muted),
      filled: true,
      fillColor: _field,
      errorMaxLines: 2,
      errorStyle: const TextStyle(color: _danger),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: const BorderSide(color: _line),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: const BorderSide(color: _gold, width: 1.4),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: const BorderSide(color: _danger, width: 1.2),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: const BorderSide(color: _danger, width: 1.4),
      ),
      disabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: const BorderSide(color: _line),
      ),
    );
  }

  TextStyle _inputTextStyle() {
    return const TextStyle(
      color: _ink,
      fontWeight: FontWeight.w600,
      fontSize: 15,
    );
  }

  String? _validateName(String? value, String label, {required bool required}) {
    final normalized = value?.trim() ?? '';

    if (normalized.isEmpty) {
      return required ? '$label is required.' : null;
    }

    if (!_supportedNameCharacterPattern.hasMatch(normalized)) {
      return '$label may only contain letters, spaces, apostrophes, and hyphens.';
    }

    return null;
  }

  String? _validateStudentId(String? value) {
    final normalized = value?.trim() ?? '';

    if (normalized.isEmpty) {
      return 'Student ID is required.';
    }

    if (!RegExp(r'^\d{7}$').hasMatch(normalized)) {
      return 'Student ID must be exactly 7 digits.';
    }

    final currentYearPrefix = DateTime.now().year % 100;
    final enrollmentYearPrefix = int.tryParse(normalized.substring(0, 2)) ?? 0;

    if (enrollmentYearPrefix > currentYearPrefix) {
      return 'Student ID cannot use a future enrollment year.';
    }

    return null;
  }

  String? _validateEmail(String? value) {
    final normalized = value?.trim().toLowerCase() ?? '';

    if (normalized.isEmpty) {
      return null;
    }

    if (!_emailPattern.hasMatch(normalized)) {
      return 'Use your LNU institutional email ending in @lnu.edu.ph.';
    }

    return null;
  }

  String? _validatePassword(String? value) {
    final password = value ?? '';

    if (password.isEmpty) {
      return 'Password is required.';
    }

    if (password.length < 8) {
      return 'Password must be at least 8 characters.';
    }

    if (password.length > 72) {
      return 'Password must not exceed 72 characters.';
    }

    return null;
  }

  String? _validatePasswordConfirmation(String? value) {
    if ((value ?? '').isEmpty) {
      return 'Confirm your password.';
    }

    if (value != _passwordController.text) {
      return 'Password confirmation does not match.';
    }

    return null;
  }
}

class _HeaderCard extends StatelessWidget {
  const _HeaderCard({required this.onBack});

  final VoidCallback onBack;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: const Color(0xFFD7D3C8)),
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
          Align(
            alignment: Alignment.centerLeft,
            child: IconButton(
              tooltip: 'Back to sign in',
              onPressed: onBack,
              color: const Color(0xFF16385F),
              icon: const Icon(Icons.arrow_back),
            ),
          ),
          Container(
            constraints: const BoxConstraints(maxWidth: 260),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFFCFBF7),
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: const Color(0xFFE6D9B8)),
            ),
            child: Image.asset(
              _RegisterScreenState._logoAsset,
              fit: BoxFit.contain,
              cacheWidth: 512,
            ),
          ),
          const SizedBox(height: 18),
          const Text(
            'Create Account',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Color(0xFF16385F),
              fontSize: 31,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Submit your student details for admin approval.',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: Color(0xFF667085),
              fontSize: 14,
              height: 1.35,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _PrivacyAgreement extends StatelessWidget {
  const _PrivacyAgreement({
    required this.acceptedTerms,
    required this.hasReadPrivacyStatement,
    required this.onOpen,
    required this.onChanged,
  });

  final bool acceptedTerms;
  final bool hasReadPrivacyStatement;
  final VoidCallback onOpen;
  final ValueChanged<bool?> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFD7D3C8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          TextButton.icon(
            key: const Key('registration-terms-open-button'),
            onPressed: onOpen,
            style: TextButton.styleFrom(
              foregroundColor: const Color(0xFF16385F),
              padding: EdgeInsets.zero,
              textStyle: const TextStyle(fontWeight: FontWeight.w800),
            ),
            icon: const Icon(Icons.privacy_tip_outlined),
            label: const Text('Read LNU Data Privacy Statement'),
          ),
          const SizedBox(height: 6),
          CheckboxListTile(
            key: const Key('registration-terms-checkbox'),
            value: acceptedTerms,
            onChanged: hasReadPrivacyStatement ? onChanged : (_) => onOpen(),
            controlAffinity: ListTileControlAffinity.leading,
            contentPadding: EdgeInsets.zero,
            activeColor: const Color(0xFF16385F),
            checkColor: Colors.white,
            title: Text(
              hasReadPrivacyStatement
                  ? 'I have read and agree to the LNU Data Privacy Statement.'
                  : 'Open and scroll through the statement before agreeing.',
              style: const TextStyle(
                color: Color(0xFF1B1B1B),
                fontSize: 14,
                height: 1.35,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({required this.title, required this.subtitle});

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(
            color: Color(0xFF16385F),
            fontSize: 25,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          subtitle,
          style: TextStyle(
            color: Color(0xFF667085),
            fontSize: 15,
            height: 1.35,
          ),
        ),
      ],
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF1ED),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFF4C8BE)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.error_outline, color: Color(0xFFB5442C)),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: Color(0xFFB5442C),
                fontWeight: FontWeight.w800,
                height: 1.35,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _OrganizationCard extends StatelessWidget {
  const _OrganizationCard({required this.program});

  final RegistrationProgram? program;

  @override
  Widget build(BuildContext context) {
    final text = program?.organizationName.isNotEmpty == true
        ? program!.organizationName
        : 'Select a program to view its organization';

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Color(0xFFD7D3C8)),
      ),
      child: Row(
        children: [
          const Icon(Icons.groups_2_outlined, color: Color(0xFF16385F)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Organization',
                  style: TextStyle(
                    color: Color(0xFF16385F),
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  text,
                  style: TextStyle(color: Color(0xFF667085), height: 1.35),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
