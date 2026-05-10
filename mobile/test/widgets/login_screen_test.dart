import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/screens/login_screen.dart';

import '../helpers/fake_services.dart';

void main() {
  group('LoginScreen', () {
    testWidgets(
      'valid restored session sends the student straight to the app shell',
      (WidgetTester tester) async {
        // This covers the session-restore path used when a student reopens the
        // app with a still-valid token already stored on the device.
        final authService = FakeAuthService(
          hasTokenResult: true,
          validateSessionResult: true,
        );

        await tester.pumpWidget(
          MaterialApp(
            home: LoginScreen(
              authService: authService,
              shellBuilder: (_) => const Scaffold(body: Text('Shell Loaded')),
            ),
          ),
        );
        await tester.pumpAndSettle();

        expect(find.text('Shell Loaded'), findsOneWidget);
      },
    );

    testWidgets(
      'expired restored session keeps the student on login with a clear message',
      (WidgetTester tester) async {
        // This verifies that a stale token does not trap the student in a broken
        // state; the app should stay on the login page and explain what happened.
        final authService = FakeAuthService(
          hasTokenResult: true,
          validateSessionResult: false,
        );

        await tester.pumpWidget(
          MaterialApp(home: LoginScreen(authService: authService)),
        );
        await tester.pumpAndSettle();

        expect(find.text('Student Sign In'), findsOneWidget);
        expect(
          find.text('Your previous session expired. Please sign in again.'),
          findsOneWidget,
        );
      },
    );

    testWidgets('successful student sign-in navigates to the shell builder', (
      WidgetTester tester,
    ) async {
      // This is the direct login flow used on a fresh install or after logout.
      final authService = FakeAuthService();

      await tester.pumpWidget(
        MaterialApp(
          home: LoginScreen(
            authService: authService,
            shellBuilder: (_) => const Scaffold(body: Text('Shell Loaded')),
          ),
        ),
      );

      await tester.enterText(find.byType(TextField).at(0), '2302314');
      await tester.enterText(find.byType(TextField).at(1), 'password');
      await tester.ensureVisible(find.text('Sign In'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Sign In'));
      await tester.pumpAndSettle();

      expect(authService.lastStudentId, '2302314');
      expect(find.text('Shell Loaded'), findsOneWidget);
    });
  });
}
