import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/input_sanitizers.dart';
import '../services/auth_service.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key, this.authService});

  final AuthService? authService;

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _studentIdController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  late final AuthService _authService = widget.authService ?? AuthService();

  int? _selectedBirthYear;
  int? _selectedBirthMonth;
  int? _selectedBirthDay;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  bool _isSubmitting = false;
  String? _error;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);
  static const Color _paper = Color(0xFFF8F4EA);

  @override
  void dispose() {
    _studentIdController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();

    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _isSubmitting = true;
      _error = null;
    });

    try {
      final message = await _authService.resetForgottenPassword(
        studentIdNumber: _studentIdController.text.trim(),
        dateOfBirth: _birthDateValue!,
        password: _passwordController.text,
        passwordConfirmation: _confirmPasswordController.text,
      );

      if (!mounted) return;

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
      Navigator.of(context).pop(message);
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

  List<int> get _birthYearOptions {
    final currentYear = DateTime.now().year;
    return List<int>.generate(70, (index) => currentYear - 12 - index);
  }

  List<int> get _birthDayOptions {
    final year = _selectedBirthYear ?? 2000;
    final month = _selectedBirthMonth ?? 1;
    final lastDay = DateTime(year, month + 1, 0).day;
    return List<int>.generate(lastDay, (index) => index + 1);
  }

  String? get _birthDateValue {
    final year = _selectedBirthYear;
    final month = _selectedBirthMonth;
    final day = _selectedBirthDay;

    if (year == null || month == null || day == null) {
      return null;
    }

    return '${year.toString().padLeft(4, '0')}-'
        '${month.toString().padLeft(2, '0')}-'
        '${day.toString().padLeft(2, '0')}';
  }

  void _normalizeSelectedBirthDay() {
    final selectedDay = _selectedBirthDay;
    if (selectedDay == null) return;

    if (!_birthDayOptions.contains(selectedDay)) {
      _selectedBirthDay = null;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: _navy,
        elevation: 0,
        title: const Text(
          'Forgot Password',
          style: TextStyle(fontWeight: FontWeight.w900),
        ),
      ),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(24, 12, 24, 28),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 440),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Text(
                      'Verify your account using your student number and birthday, then set a new password.',
                      style: TextStyle(
                        color: Color(0xFF667085),
                        height: 1.45,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 22),
                    if (_error != null) ...[
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFF3D9),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: _gold),
                        ),
                        child: Text(
                          _error!,
                          style: const TextStyle(
                            color: _navy,
                            height: 1.35,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                    TextFormField(
                      controller: _studentIdController,
                      keyboardType: TextInputType.number,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                        LengthLimitingTextInputFormatter(7),
                      ],
                      decoration: _inputDecoration(
                        label: 'Student ID',
                        icon: Icons.badge_outlined,
                      ),
                      validator: (value) {
                        final normalized = value?.trim() ?? '';
                        if (normalized.isEmpty) {
                          return 'Student ID is required.';
                        }
                        if (!RegExp(r'^\d{7}$').hasMatch(normalized)) {
                          return 'Student ID must be exactly 7 digits.';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    _birthdayFields(),
                    const SizedBox(height: 18),
                    TextFormField(
                      controller: _passwordController,
                      obscureText: _obscurePassword,
                      inputFormatters: const [NoEmojiTextInputFormatter()],
                      decoration: _passwordDecoration(
                        label: 'New Password',
                        obscure: _obscurePassword,
                        onToggle: () {
                          setState(() {
                            _obscurePassword = !_obscurePassword;
                          });
                        },
                      ),
                      validator: _validatePassword,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _confirmPasswordController,
                      obscureText: _obscureConfirmPassword,
                      inputFormatters: const [NoEmojiTextInputFormatter()],
                      decoration: _passwordDecoration(
                        label: 'Confirm New Password',
                        obscure: _obscureConfirmPassword,
                        onToggle: () {
                          setState(() {
                            _obscureConfirmPassword = !_obscureConfirmPassword;
                          });
                        },
                      ),
                      validator: (value) {
                        if ((value ?? '').isEmpty) {
                          return 'Confirm your new password.';
                        }

                        if (value != _passwordController.text) {
                          return 'Password confirmation does not match.';
                        }

                        return null;
                      },
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      height: 54,
                      child: ElevatedButton(
                        onPressed: _isSubmitting ? null : _submit,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _gold,
                          foregroundColor: _navy,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          textStyle: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        child: _isSubmitting
                            ? const SizedBox(
                                width: 22,
                                height: 22,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2.4,
                                  color: _navy,
                                ),
                              )
                            : const Text('Reset Password'),
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

  Widget _birthdayFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.only(left: 4, bottom: 8),
          child: Text(
            'Birthday',
            style: TextStyle(color: _navy, fontWeight: FontWeight.w800),
          ),
        ),
        Row(
          children: [
            Expanded(
              flex: 11,
              child: DropdownButtonFormField<int>(
                key: const Key('forgot-birth-year-dropdown'),
                initialValue: _selectedBirthYear,
                isExpanded: true,
                items: _birthYearOptions
                    .map(
                      (year) => DropdownMenuItem(
                        value: year,
                        child: Text(year.toString()),
                      ),
                    )
                    .toList(),
                onChanged: (year) {
                  setState(() {
                    _selectedBirthYear = year;
                    _normalizeSelectedBirthDay();
                  });
                },
                decoration: _inputDecoration(
                  label: 'Year',
                  icon: Icons.cake_outlined,
                ),
                validator: (value) =>
                    value == null ? 'Select birth year.' : null,
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              flex: 10,
              child: DropdownButtonFormField<int>(
                key: const Key('forgot-birth-month-dropdown'),
                initialValue: _selectedBirthMonth,
                isExpanded: true,
                items: List<int>.generate(12, (index) => index + 1)
                    .map(
                      (month) => DropdownMenuItem(
                        value: month,
                        child: Text(month.toString().padLeft(2, '0')),
                      ),
                    )
                    .toList(),
                onChanged: (month) {
                  setState(() {
                    _selectedBirthMonth = month;
                    _normalizeSelectedBirthDay();
                  });
                },
                decoration: _inputDecoration(
                  label: 'Month',
                  icon: Icons.calendar_month_outlined,
                ),
                validator: (value) =>
                    value == null ? 'Select birth month.' : null,
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              flex: 9,
              child: DropdownButtonFormField<int>(
                key: const Key('forgot-birth-day-dropdown'),
                initialValue: _selectedBirthDay,
                isExpanded: true,
                items: _birthDayOptions
                    .map(
                      (day) => DropdownMenuItem(
                        value: day,
                        child: Text(day.toString().padLeft(2, '0')),
                      ),
                    )
                    .toList(),
                onChanged: (day) {
                  setState(() {
                    _selectedBirthDay = day;
                  });
                },
                decoration: _inputDecoration(
                  label: 'Day',
                  icon: Icons.today_outlined,
                ),
                validator: (value) =>
                    value == null ? 'Select birth day.' : null,
              ),
            ),
          ],
        ),
      ],
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
  }) {
    return InputDecoration(
      labelText: label,
      prefixIcon: Icon(icon, color: _navy),
      filled: true,
      fillColor: _paper,
      errorMaxLines: 2,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: BorderSide.none,
      ),
    );
  }

  String? _validatePassword(String? value) {
    final password = value ?? '';

    if (password.isEmpty) return 'New password is required.';
    if (password.length < 8) return 'Password must be at least 8 characters.';
    if (password.length > 72) return 'Password must not exceed 72 characters.';
    if (containsEmoji(password)) return 'Password cannot contain emoji.';

    return null;
  }
}
