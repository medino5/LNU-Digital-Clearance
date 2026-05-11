import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({
    super.key,
    required this.payload,
    required this.error, // ADDED: for consistent state messaging across tabs
    required this.isLoading,
    required this.isBusy,
    required this.isUploadingPhoto,
    required this.isChangingPassword,
    required this.onLogout,
    required this.onRefresh,
    required this.onUpdateProfilePhoto,
    required this.onChangePassword,
  });

  final Map<String, dynamic>? payload;
  final String? error; // ADDED: display errors similar to Dashboard/PDF
  final bool isLoading;
  final bool isBusy;
  final bool isUploadingPhoto;
  final bool isChangingPassword;
  final Future<void> Function() onLogout;
  final Future<void> Function() onRefresh;
  final Future<void> Function({
    required List<int> bytes,
    required String filename,
  })
  onUpdateProfilePhoto;
  final Future<void> Function({
    required String password,
    required String passwordConfirmation,
  })
  onChangePassword;

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
          _DetailCard(
            children: [
              _DetailRow(
                label: 'Student ID',
                value:
                    student?['student_id_number'] as String? ?? 'Unavailable',
              ),
              _DetailRow(
                label: 'Birthday',
                value: _formatBirthday(student?['date_of_birth'] as String?),
              ),
              _DetailRow(
                label: 'Program',
                value: program?['name'] as String? ?? 'Unavailable',
              ),
              _DetailRow(
                label: 'Year Level',
                value: student?['year_level_label'] as String? ?? 'Unavailable',
                isLast: true,
              ),
            ],
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
          _PasswordCard(
            isBusy: isChangingPassword,
            onPressed: () => _showChangePasswordDialog(context),
          ),
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

  String _formatBirthday(String? value) {
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

  Future<void> _showChangePasswordDialog(BuildContext context) async {
    final passwordController = TextEditingController();
    final confirmController = TextEditingController();
    final formKey = GlobalKey<FormState>();
    var obscurePassword = true;
    var obscureConfirm = true;
    var isSubmitting = false;

    await showDialog<void>(
      context: context,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Change Password'),
              content: Form(
                key: formKey,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextFormField(
                      controller: passwordController,
                      obscureText: obscurePassword,
                      decoration: InputDecoration(
                        labelText: 'New password',
                        suffixIcon: IconButton(
                          tooltip: obscurePassword
                              ? 'Show password'
                              : 'Hide password',
                          onPressed: () {
                            setDialogState(() {
                              obscurePassword = !obscurePassword;
                            });
                          },
                          icon: Icon(
                            obscurePassword
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

                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: confirmController,
                      obscureText: obscureConfirm,
                      decoration: InputDecoration(
                        labelText: 'Confirm password',
                        suffixIcon: IconButton(
                          tooltip: obscureConfirm
                              ? 'Show password'
                              : 'Hide password',
                          onPressed: () {
                            setDialogState(() {
                              obscureConfirm = !obscureConfirm;
                            });
                          },
                          icon: Icon(
                            obscureConfirm
                                ? Icons.visibility_off
                                : Icons.visibility,
                          ),
                        ),
                      ),
                      validator: (value) {
                        if ((value ?? '').isEmpty) {
                          return 'Confirm your password.';
                        }

                        if (value != passwordController.text) {
                          return 'Passwords do not match.';
                        }

                        return null;
                      },
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: isSubmitting
                      ? null
                      : () => Navigator.of(dialogContext).pop(),
                  child: const Text('Cancel'),
                ),
                ElevatedButton(
                  onPressed: isSubmitting
                      ? null
                      : () async {
                          if (!formKey.currentState!.validate()) {
                            return;
                          }

                          setDialogState(() {
                            isSubmitting = true;
                          });

                          await onChangePassword(
                            password: passwordController.text,
                            passwordConfirmation: confirmController.text,
                          );

                          if (dialogContext.mounted) {
                            Navigator.of(dialogContext).pop();
                          }
                        },
                  child: isSubmitting
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Save'),
                ),
              ],
            );
          },
        );
      },
    );

    passwordController.dispose();
    confirmController.dispose();
  }
}

class _PasswordCard extends StatelessWidget {
  const _PasswordCard({required this.isBusy, required this.onPressed});

  final bool isBusy;
  final VoidCallback onPressed;

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
      child: Row(
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
            onPressed: isBusy ? null : onPressed,
            child: isBusy
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Change'),
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
  const _DetailCard({this.title, required this.children});

  final String? title;
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
            Align(
              alignment: Alignment.centerLeft,
              child: Text(
                title!,
                style: const TextStyle(
                  color: ProfileScreen._navy,
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                ),
              ),
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
