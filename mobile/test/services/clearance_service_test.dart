import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile/core/api_client.dart';
import 'package:mobile/core/session_expired_exception.dart';
import 'package:mobile/services/clearance_service.dart';

import '../helpers/fake_token_store.dart';

void main() {
  group('ClearanceService', () {
    test(
      'getCurrentClearance returns the payload expected by the student shell',
      () async {
        // This verifies the student shell can rely on the service to decode the
        // backend JSON payload instead of reading raw HTTP responses itself.
        final tokenStore = FakeTokenStore(token: 'active-token');
        final clearanceService = ClearanceService(
          tokenStore: tokenStore,
          apiClient: ApiClient(
            client: MockClient((request) async {
              expect(request.url.path, '/api/clearance/current');
              expect(request.headers['Authorization'], 'Bearer active-token');

              return http.Response(
                jsonEncode({
                  'student': {'name': 'John A. Doe'},
                  'active_semester': {'label': '2nd Semester 2024-2025'},
                  'clearance': {'status': 'in_progress'},
                }),
                200,
                headers: {'content-type': 'application/json'},
              );
            }),
          ),
        );

        final payload = await clearanceService.getCurrentClearance();

        expect(payload['student']['name'], 'John A. Doe');
        expect(payload['clearance']['status'], 'in_progress');
      },
    );

    test(
      'getCurrentClearance throws SessionExpiredException on 401 responses',
      () async {
        // This keeps the mobile app honest: if the backend says the token is no
        // longer valid, the service must surface a session-expired signal.
        final tokenStore = FakeTokenStore(token: 'expired-token');
        final clearanceService = ClearanceService(
          tokenStore: tokenStore,
          apiClient: ApiClient(
            client: MockClient((request) async {
              return http.Response(
                jsonEncode({'message': 'Unauthenticated.'}),
                401,
                headers: {'content-type': 'application/json'},
              );
            }),
          ),
        );

        expect(
          () => clearanceService.getCurrentClearance(),
          throwsA(isA<SessionExpiredException>()),
        );
      },
    );

    test('downloadCurrentClearancePdf saves PDF bytes with a safe file name', () async {
      final tokenStore = FakeTokenStore(token: 'active-token');
      final clearanceService = ClearanceService(
        tokenStore: tokenStore,
        apiClient: ApiClient(
          client: MockClient((request) async {
            expect(request.url.path, '/api/clearance/current/pdf');
            expect(request.headers['Authorization'], 'Bearer active-token');

            return http.Response.bytes(
              [37, 80, 68, 70, 45, 49, 46, 52],
              200,
              headers: {'content-type': 'application/pdf'},
            );
          }),
        ),
      );

      final path = await clearanceService.downloadCurrentClearancePdf(
        referenceNumber: 'CLR 1/ABC?',
      );
      final file = File(path);

      expect(path, contains('CLR_1_ABC_.pdf'));
      expect(await file.exists(), isTrue);
      expect(await file.readAsBytes(), [37, 80, 68, 70, 45, 49, 46, 52]);

      await file.delete();
    });

    test(
      'downloadCurrentClearancePdf surfaces backend message when not completed',
      () async {
        final clearanceService = ClearanceService(
          tokenStore: FakeTokenStore(token: 'active-token'),
          apiClient: ApiClient(
            client: MockClient((request) async {
              return http.Response(
                jsonEncode({'message': 'Your clearance is not completed yet.'}),
                422,
                headers: {'content-type': 'application/json'},
              );
            }),
          ),
        );

        expect(
          () => clearanceService.downloadCurrentClearancePdf(),
          throwsA(
            isA<Exception>().having(
              (error) => error.toString(),
              'message',
              contains('Your clearance is not completed yet.'),
            ),
          ),
        );
      },
    );
  });
}
