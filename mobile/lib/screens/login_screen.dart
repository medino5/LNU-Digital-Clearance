import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../core/input_sanitizers.dart';
import '../core/session_expired_exception.dart';
import '../features/shell/app_shell.dart';
import '../services/auth_service.dart';
import '../services/registration_service.dart';
import 'forgot_password_screen.dart';
import 'register_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({
    super.key,
    this.initialMessage,
    this.authService,
    this.registrationService,
    this.shellBuilder,
  });

  final String? initialMessage;
  final AuthService? authService;
  final RegistrationService? registrationService;
  final WidgetBuilder? shellBuilder;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _studentIdController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  late final AuthService _authService = widget.authService ?? AuthService();

  bool _isLoading = false;
  String? _notice;
  bool _obscurePassword = true;

  static const Color _navy = Color(0xFF183A63);
  static const Color _gold = Color(0xFFD1A33B);
  static const String _logoAsset = 'assets/branding/mobile_login_logo.png';

  @override
  void initState() {
    super.initState();
    _notice = widget.initialMessage;
    _restoreSession();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    precacheImage(const AssetImage(_logoAsset), context);
  }

  @override
  void dispose() {
    _studentIdController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _restoreSession() async {
    final hasToken = await _authService.hasToken();

    if (!mounted || !hasToken) return;

    try {
      final isValid = await _authService.validateSession();

      if (!mounted) return;

      if (!isValid) {
        setState(() {
          _notice = 'Your previous session expired. Please sign in again.';
        });
        return;
      }

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: widget.shellBuilder ?? (_) => const AppShell(),
        ),
      );
    } catch (error) {
      if (!mounted) return;

      setState(() {
        _notice = error.toString().replaceFirst('Exception: ', '');
      });
    }
  }

  Future<void> _handleLogin() async {
    FocusScope.of(context).unfocus();

    final studentId = _studentIdController.text.trim();

    if (!RegExp(r'^\d{7}$').hasMatch(studentId)) {
      setState(() {
        _notice = 'Student ID must be exactly 7 digits.';
      });
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      await _authService.login(studentId, _passwordController.text);

      if (!mounted) return;

      setState(() {
        _notice = null;
      });

      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: widget.shellBuilder ?? (_) => const AppShell(),
        ),
      );
    } catch (error) {
      if (!mounted) return;

      final message = error is SessionExpiredException
          ? error.message
          : error.toString().replaceFirst('Exception: ', '');

      setState(() {
        _notice = 'Sign in failed: $message';
      });

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _openRegistration() async {
    await precacheImage(
      const AssetImage(
        'assets/branding/lnu_digital_clearance_logo_compact.png',
      ),
      context,
    );

    if (!mounted) return;

    final created = await Navigator.of(context).push<bool>(
      PageRouteBuilder<bool>(
        transitionDuration: const Duration(milliseconds: 180),
        reverseTransitionDuration: const Duration(milliseconds: 140),
        pageBuilder: (routeContext, animation, secondaryAnimation) =>
            RegisterScreen(registrationService: widget.registrationService),
        transitionsBuilder:
            (routeContext, animation, secondaryAnimation, child) {
              final curved = CurvedAnimation(
                parent: animation,
                curve: Curves.easeOutCubic,
              );

              return FadeTransition(
                opacity: curved,
                child: SlideTransition(
                  position: Tween<Offset>(
                    begin: const Offset(0, 0.025),
                    end: Offset.zero,
                  ).animate(curved),
                  child: child,
                ),
              );
            },
      ),
    );

    if (!mounted || created != true) return;

    setState(() {
      _notice =
          'Registration submitted. Please wait for admin approval before signing in.';
    });
  }

  Future<void> _openForgotPassword() async {
    final message = await Navigator.of(context).push<String>(
      MaterialPageRoute(
        builder: (_) => ForgotPasswordScreen(authService: _authService),
      ),
    );

    if (!mounted || message == null) return;

    setState(() {
      _notice = message;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: false,
      backgroundColor: Colors.white,
      body: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: () => FocusScope.of(context).unfocus(),
        child: SafeArea(
          child: LayoutBuilder(
            builder: (context, constraints) {
              return Center(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(26, 18, 26, 18),
                  child: RepaintBoundary(
                    child: ConstrainedBox(
                      constraints: BoxConstraints(
                        maxWidth: 440,
                        minHeight: constraints.maxHeight - 36,
                      ),
                      child: AutofillGroup(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Center(
                              child: RepaintBoundary(
                                child: Image.asset(
                                  _logoAsset,
                                  height: 172,
                                  fit: BoxFit.contain,
                                  cacheWidth: 360,
                                ),
                              ),
                            ),
                            const SizedBox(height: 14),
                            const Text(
                              'LNU Student Clearance Portal',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: _navy,
                                fontSize: 26,
                                fontWeight: FontWeight.w900,
                                height: 1.12,
                              ),
                            ),
                            const SizedBox(height: 6),
                            const Text(
                              'Digital Clearance Mobile App',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: _gold,
                                fontSize: 13,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.8,
                              ),
                            ),
                            const SizedBox(height: 30),
                            const Text(
                              'Student Sign In',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: _navy,
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            const SizedBox(height: 24),
                            if (_notice != null) ...[
                              Container(
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFFF3D9),
                                  borderRadius: BorderRadius.circular(14),
                                  border: Border.all(
                                    color: const Color(0xFFD1A33B),
                                  ),
                                ),
                                child: Text(
                                  _notice!,
                                  style: const TextStyle(
                                    color: _navy,
                                    fontWeight: FontWeight.w600,
                                    height: 1.4,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 18),
                            ],
                            TextField(
                              controller: _studentIdController,
                              keyboardType: TextInputType.number,
                              textInputAction: TextInputAction.next,
                              autofillHints: const [AutofillHints.username],
                              enableSuggestions: false,
                              autocorrect: false,
                              inputFormatters: [
                                FilteringTextInputFormatter.digitsOnly,
                                LengthLimitingTextInputFormatter(7),
                              ],
                              decoration: InputDecoration(
                                labelText: 'Student ID',
                                counterText: '',
                                filled: true,
                                fillColor: const Color(0xFFF8F4EA),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(18),
                                  borderSide: BorderSide.none,
                                ),
                              ),
                            ),
                            const SizedBox(height: 16),
                            TextField(
                              controller: _passwordController,
                              obscureText: _obscurePassword,
                              textInputAction: TextInputAction.done,
                              autofillHints: const [AutofillHints.password],
                              enableSuggestions: false,
                              autocorrect: false,
                              inputFormatters: const [
                                NoEmojiTextInputFormatter(),
                              ],
                              onSubmitted: (_) {
                                if (!_isLoading) {
                                  _handleLogin();
                                }
                              },
                              decoration: InputDecoration(
                                labelText: 'Password',
                                suffixIcon: IconButton(
                                  tooltip: _obscurePassword
                                      ? 'Show password'
                                      : 'Hide password',
                                  icon: Icon(
                                    _obscurePassword
                                        ? Icons.visibility_off
                                        : Icons.visibility,
                                  ),
                                  onPressed: () {
                                    setState(() {
                                      _obscurePassword = !_obscurePassword;
                                    });
                                  },
                                ),
                                filled: true,
                                fillColor: const Color(0xFFF8F4EA),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(18),
                                  borderSide: BorderSide.none,
                                ),
                              ),
                            ),
                            const SizedBox(height: 24),
                            SizedBox(
                              height: 54,
                              child: ElevatedButton(
                                onPressed: _isLoading ? null : _handleLogin,
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
                                child: _isLoading
                                    ? const SizedBox(
                                        height: 22,
                                        width: 22,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2.4,
                                          color: _navy,
                                        ),
                                      )
                                    : const Text('Sign In'),
                              ),
                            ),
                            const SizedBox(height: 14),
                            TextButton(
                              onPressed: _isLoading
                                  ? null
                                  : _openForgotPassword,
                              child: const Text(
                                'Forgot password?',
                                style: TextStyle(fontWeight: FontWeight.w800),
                              ),
                            ),
                            const SizedBox(height: 4),
                            Wrap(
                              alignment: WrapAlignment.center,
                              crossAxisAlignment: WrapCrossAlignment.center,
                              children: [
                                Text(
                                  'Need an account?',
                                  style: TextStyle(color: Colors.grey.shade700),
                                ),
                                TextButton(
                                  onPressed: _isLoading
                                      ? null
                                      : _openRegistration,
                                  child: const Text(
                                    'Create Account',
                                    style: TextStyle(
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
