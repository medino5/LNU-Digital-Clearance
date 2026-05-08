import 'dart:convert';

import '../core/api_client.dart';

class RegistrationProgram {
  const RegistrationProgram({
    required this.id,
    required this.code,
    required this.name,
    required this.organizationName,
  });

  final int id;
  final String code;
  final String name;
  final String organizationName;

  factory RegistrationProgram.fromJson(Map<String, dynamic> json) {
    return RegistrationProgram(
      id: json['id'] as int,
      code: json['code'] as String? ?? '',
      name: json['name'] as String? ?? '',
      organizationName: json['org_name'] as String? ?? '',
    );
  }
}

class RegistrationYearLevel {
  const RegistrationYearLevel({required this.value, required this.label});

  final int value;
  final String label;

  factory RegistrationYearLevel.fromJson(Map<String, dynamic> json) {
    return RegistrationYearLevel(
      value: json['value'] as int,
      label: json['label'] as String? ?? '',
    );
  }
}

class RegistrationOptions {
  const RegistrationOptions({
    required this.programs,
    required this.yearLevels,
    required this.nameExtensions,
  });

  final List<RegistrationProgram> programs;
  final List<RegistrationYearLevel> yearLevels;
  final List<String> nameExtensions;

  factory RegistrationOptions.empty() {
    return const RegistrationOptions(
      programs: [],
      yearLevels: [],
      nameExtensions: [],
    );
  }

  factory RegistrationOptions.fromJson(Map<String, dynamic> json) {
    final programs = (json['programs'] as List? ?? [])
        .whereType<Map>()
        .map((item) => RegistrationProgram.fromJson(item.cast()))
        .toList();
    final yearLevels = (json['year_levels'] as List? ?? [])
        .whereType<Map>()
        .map((item) => RegistrationYearLevel.fromJson(item.cast()))
        .toList();
    final nameExtensions = (json['name_extensions'] as List? ?? [])
        .map((item) => item.toString())
        .toList();

    return RegistrationOptions(
      programs: programs,
      yearLevels: yearLevels,
      nameExtensions: nameExtensions,
    );
  }
}

class RegistrationRequest {
  const RegistrationRequest({
    required this.studentIdNumber,
    required this.firstName,
    required this.middleInitial,
    required this.lastName,
    required this.nameExtension,
    required this.email,
    required this.programId,
    required this.yearLevel,
    required this.password,
    required this.passwordConfirmation,
  });

  final String studentIdNumber;
  final String firstName;
  final String middleInitial;
  final String lastName;
  final String nameExtension;
  final String email;
  final int programId;
  final int yearLevel;
  final String password;
  final String passwordConfirmation;

  Map<String, dynamic> toJson() {
    return {
      'student_id_number': studentIdNumber,
      'first_name': firstName,
      'middle_initial': middleInitial.isEmpty ? null : middleInitial,
      'last_name': lastName,
      'name_extension': nameExtension.isEmpty ? null : nameExtension,
      'email': email.isEmpty ? null : email,
      'program_id': programId,
      'year_level': yearLevel,
      'password': password,
      'password_confirmation': passwordConfirmation,
    };
  }
}

class RegistrationService {
  RegistrationService({ApiClient? apiClient})
    : _apiClient = apiClient ?? ApiClient();

  final ApiClient _apiClient;

  Future<RegistrationOptions> loadOptions() async {
    final response = await _apiClient.get(
      '/registration/options',
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw Exception(
        _extractMessage(response.body, 'Unable to load options.'),
      );
    }

    return RegistrationOptions.fromJson(
      (jsonDecode(response.body) as Map<String, dynamic>),
    );
  }

  Future<String> register(RegistrationRequest request) async {
    final response = await _apiClient.post(
      '/register',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode(request.toJson()),
    );

    final message = _extractMessage(
      response.body,
      'Unable to submit registration.',
    );

    if (response.statusCode != 202) {
      throw Exception(message);
    }

    return message;
  }

  String _extractMessage(String body, String fallback) {
    try {
      final payload = jsonDecode(body);

      if (payload is Map<String, dynamic>) {
        final errors = payload['errors'];

        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) {
            return first.first.toString();
          }
        }

        if (payload['message'] is String) {
          return payload['message'] as String;
        }
      }
    } catch (_) {
      // Use fallback for non-JSON failures.
    }

    return fallback;
  }
}
