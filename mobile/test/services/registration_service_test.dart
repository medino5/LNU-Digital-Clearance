import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile/core/api_client.dart';
import 'package:mobile/services/registration_service.dart';

void main() {
  group('RegistrationService', () {
    test(
      'loadOptions parses programs, year levels, and suffix choices',
      () async {
        final service = RegistrationService(
          apiClient: ApiClient(
            client: MockClient((request) async {
              expect(request.url.path, '/api/registration/options');

              return http.Response(
                jsonEncode({
                  'programs': [
                    {
                      'id': 7,
                      'code': 'BSIT',
                      'name': 'Bachelor of Science in Information Technology',
                      'org_name': 'Information Technology Students Society',
                    },
                  ],
                  'year_levels': [
                    {'value': 1, 'label': '1st Year'},
                  ],
                  'name_extensions': ['Jr'],
                }),
                200,
                headers: {'content-type': 'application/json'},
              );
            }),
          ),
        );

        final options = await service.loadOptions();

        expect(options.programs.single.code, 'BSIT');
        expect(
          options.programs.single.organizationName,
          contains('Technology'),
        );
        expect(options.yearLevels.single.label, '1st Year');
        expect(options.nameExtensions.single, 'Jr');
      },
    );

    test('register posts the same payload expected by the backend API', () async {
      final service = RegistrationService(
        apiClient: ApiClient(
          client: MockClient((request) async {
            expect(request.url.path, '/api/register');

            final payload = jsonDecode(request.body) as Map<String, dynamic>;
            expect(payload['student_id_number'], '2407777');
            expect(payload['first_name'], 'niña');
            expect(payload['middle_initial'], 'ñ');
            expect(payload['program_id'], 7);
            expect(payload['year_level'], 2);
            expect(payload['date_of_birth'], '2005-05-21');

            return http.Response(
              jsonEncode({
                'message':
                    'Registration submitted. Please wait for admin approval before signing in.',
              }),
              202,
              headers: {'content-type': 'application/json'},
            );
          }),
        ),
      );

      final message = await service.register(
        const RegistrationRequest(
          studentIdNumber: '2407777',
          firstName: 'niña',
          middleInitial: 'ñ',
          lastName: 'dela cruz',
          nameExtension: '',
          email: '',
          programId: 7,
          yearLevel: 2,
          dateOfBirth: '2005-05-21',
          password: 'password',
          passwordConfirmation: 'password',
        ),
      );

      expect(
        message,
        'Registration submitted. Please wait for admin approval before signing in.',
      );
    });

    test('register surfaces the first validation error from Laravel', () async {
      final service = RegistrationService(
        apiClient: ApiClient(
          client: MockClient((request) async {
            return http.Response(
              jsonEncode({
                'message': 'The given data was invalid.',
                'errors': {
                  'student_id_number': ['Student ID must be exactly 7 digits.'],
                },
              }),
              422,
              headers: {'content-type': 'application/json'},
            );
          }),
        ),
      );

      expect(
        () => service.register(
          const RegistrationRequest(
            studentIdNumber: '123',
            firstName: 'Ana',
            middleInitial: '',
            lastName: 'Reyes',
            nameExtension: '',
            email: '',
            programId: 1,
            yearLevel: 1,
            dateOfBirth: '2005-05-21',
            password: 'password',
            passwordConfirmation: 'password',
          ),
        ),
        throwsA(
          isA<Exception>().having(
            (error) => error.toString(),
            'message',
            contains('Student ID must be exactly 7 digits.'),
          ),
        ),
      );
    });
  });
}
