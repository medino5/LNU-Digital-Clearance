import 'package:mobile/core/session_expired_exception.dart';
import 'package:mobile/services/auth_service.dart';
import 'package:mobile/services/clearance_service.dart';
import 'package:mobile/services/registration_service.dart';

import 'fake_token_store.dart';

class FakeAuthService extends AuthService {
  FakeAuthService({
    this.hasTokenResult = false,
    this.validateSessionResult = false,
    this.loginError,
    this.validationError,
    this.logoutError,
    this.profile = const <String, dynamic>{},
  }) : super(tokenStore: FakeTokenStore());

  bool hasTokenResult;
  bool validateSessionResult;
  Object? loginError;
  Object? validationError;
  Object? logoutError;
  Map<String, dynamic> profile;
  String? lastStudentId;
  String? lastPassword;
  bool clearedStoredToken = false;
  int logoutCalls = 0;

  @override
  Future<Map<String, dynamic>> getProfile() async {
    return profile;
  }

  @override
  Future<bool> hasToken() async {
    return hasTokenResult;
  }

  @override
  Future<void> login(String studentId, String password) async {
    lastStudentId = studentId;
    lastPassword = password;

    if (loginError != null) {
      throw loginError!;
    }
  }

  @override
  Future<void> logout() async {
    logoutCalls += 1;

    if (logoutError != null) {
      throw logoutError!;
    }
  }

  @override
  Future<bool> validateSession() async {
    if (validationError != null) {
      throw validationError!;
    }

    return validateSessionResult;
  }

  @override
  Future<void> clearStoredToken() async {
    clearedStoredToken = true;
  }
}

class FakeClearanceService extends ClearanceService {
  FakeClearanceService({
    this.currentPayload,
    this.startOrResumePayload,
    this.resubmitPayload,
    this.loadError,
    this.startError,
    this.resubmitError,
    this.downloadError,
    this.downloadPath = '/tmp/student-clearance.pdf',
    this.payloadQueue = const [],
  }) : super(tokenStore: FakeTokenStore());

  Map<String, dynamic>? currentPayload;
  Map<String, dynamic>? startOrResumePayload;
  Map<String, dynamic>? resubmitPayload;
  Object? loadError;
  Object? startError;
  Object? resubmitError;
  Object? downloadError;
  String downloadPath;
  int downloadCalls = 0;

  final List<Map<String, dynamic>> payloadQueue;
  int loadCalls = 0;

  @override
  Future<Map<String, dynamic>> createOrResumeClearance() async {
    if (startError != null) {
      throw startError!;
    }

    return startOrResumePayload ?? currentPayload ?? const <String, dynamic>{};
  }

  @override
  Future<String> downloadCurrentClearancePdf({String? referenceNumber}) async {
    downloadCalls += 1;

    if (downloadError != null) {
      throw downloadError!;
    }

    return downloadPath;
  }

  @override
  Future<Map<String, dynamic>> getCurrentClearance() async {
    if (loadError != null) {
      throw loadError!;
    }

    if (payloadQueue.isNotEmpty) {
      final index = loadCalls.clamp(0, payloadQueue.length - 1);
      loadCalls += 1;
      return payloadQueue[index];
    }

    loadCalls += 1;
    return currentPayload ?? const <String, dynamic>{};
  }

  @override
  Future<Map<String, dynamic>> resubmitStep(int stepId) async {
    if (resubmitError != null) {
      throw resubmitError!;
    }

    return resubmitPayload ?? currentPayload ?? const <String, dynamic>{};
  }
}

class FakeRegistrationService extends RegistrationService {
  FakeRegistrationService({
    RegistrationOptions? options,
    this.loadError,
    this.registerError,
    this.registerMessage = 'Account created successfully. Please sign in.',
  }) : options =
           options ??
           const RegistrationOptions(
             programs: [
               RegistrationProgram(
                 id: 1,
                 code: 'BSIT',
                 name: 'Bachelor of Science in Information Technology',
                 organizationName: 'Information Technology Students Society',
               ),
             ],
             yearLevels: [
               RegistrationYearLevel(value: 1, label: '1st Year'),
               RegistrationYearLevel(value: 2, label: '2nd Year'),
             ],
             nameExtensions: ['Jr', 'Sr'],
           );

  RegistrationOptions options;
  Object? loadError;
  Object? registerError;
  String registerMessage;
  RegistrationRequest? lastRequest;

  @override
  Future<RegistrationOptions> loadOptions() async {
    if (loadError != null) {
      throw loadError!;
    }

    return options;
  }

  @override
  Future<String> register(RegistrationRequest request) async {
    lastRequest = request;

    if (registerError != null) {
      throw registerError!;
    }

    return registerMessage;
  }
}

SessionExpiredException buildExpiredSession([String? message]) {
  return SessionExpiredException(
    message ?? 'Your session expired. Please sign in again.',
  );
}
