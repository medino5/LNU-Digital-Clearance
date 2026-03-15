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
  });
}
