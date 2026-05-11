import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:mobile/core/api_client.dart';
import 'package:mobile/services/auth_service.dart';

import '../helpers/fake_token_store.dart';

void main() {
  group('AuthService', () {
    test(
      'login stores the issued token for later authenticated requests',
      () async {
        // This test covers the happy path for mobile sign-in: a successful API
        // response should persist the returned auth token for later use.
        final tokenStore = FakeTokenStore();
        final authService = AuthService(
          tokenStore: tokenStore,
          apiClient: ApiClient(
            client: MockClient((request) async {
              expect(request.url.path, '/api/login');

              final payload = jsonDecode(request.body) as Map<String, dynamic>;
              expect(payload['student_id'], '2302314');

              return http.Response(
                jsonEncode({
                  'message': 'Login successful.',
                  'token': 'test-token',
                }),
                200,
                headers: {'content-type': 'application/json'},
              );
            }),
          ),
        );

        await authService.login('2302314', 'password');

        expect(await tokenStore.readToken(), 'test-token');
      },
    );

    test(
      'validateSession clears the token when the backend returns 401',
      () async {
        // This protects the re-login flow: an expired token must be removed so
        // the app does not stay stuck in a fake logged-in state.
        final tokenStore = FakeTokenStore(token: 'expired-token');
        final authService = AuthService(
          tokenStore: tokenStore,
          apiClient: ApiClient(
            client: MockClient((request) async {
              expect(request.url.path, '/api/me');

              return http.Response(
                jsonEncode({'message': 'Unauthenticated.'}),
                401,
                headers: {'content-type': 'application/json'},
              );
            }),
          ),
        );

        final isValid = await authService.validateSession();

        expect(isValid, isFalse);
        expect(await tokenStore.readToken(), isNull);
      },
    );

    test(
      'changePassword posts confirmed password to the authenticated API',
      () async {
        final tokenStore = FakeTokenStore(token: 'active-token');
        final authService = AuthService(
          tokenStore: tokenStore,
          apiClient: ApiClient(
            client: MockClient((request) async {
              expect(request.url.path, '/api/me/password');
              expect(request.headers['Authorization'], 'Bearer active-token');

              final payload = jsonDecode(request.body) as Map<String, dynamic>;
              expect(payload['password'], 'new-password');
              expect(payload['password_confirmation'], 'new-password');

              return http.Response(
                jsonEncode({'message': 'Password updated successfully.'}),
                200,
                headers: {'content-type': 'application/json'},
              );
            }),
          ),
        );

        final message = await authService.changePassword(
          password: 'new-password',
          passwordConfirmation: 'new-password',
        );

        expect(message, 'Password updated successfully.');
      },
    );

    test(
      'resetForgottenPassword posts student ID birthday and new password',
      () async {
        final authService = AuthService(
          apiClient: ApiClient(
            client: MockClient((request) async {
              expect(request.url.path, '/api/forgot-password');

              final payload = jsonDecode(request.body) as Map<String, dynamic>;
              expect(payload['student_id_number'], '2302314');
              expect(payload['date_of_birth'], '2005-03-14');
              expect(payload['password'], 'fresh-password');
              expect(payload['password_confirmation'], 'fresh-password');

              return http.Response(
                jsonEncode({
                  'message': 'Password reset successful. You can now sign in.',
                }),
                200,
                headers: {'content-type': 'application/json'},
              );
            }),
          ),
        );

        final message = await authService.resetForgottenPassword(
          studentIdNumber: '2302314',
          dateOfBirth: '2005-03-14',
          password: 'fresh-password',
          passwordConfirmation: 'fresh-password',
        );

        expect(message, 'Password reset successful. You can now sign in.');
      },
    );
  });
}
