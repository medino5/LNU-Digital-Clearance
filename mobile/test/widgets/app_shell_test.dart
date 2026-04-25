import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/profile/profile_screen.dart';
import 'package:mobile/features/shell/app_shell.dart';

import '../helpers/fake_services.dart';
import '../helpers/test_payloads.dart';

void main() {
  group('AppShell', () {
    testWidgets(
      'shell shows the three student tabs from the rehauled mobile flow',
      (WidgetTester tester) async {
        // This is the navigation baseline for the current app: once logged in,
        // the student should land in a shell with Dashboard, PDF, and Profile.
        await tester.pumpWidget(
          MaterialApp(
            home: AppShell(
              authService: FakeAuthService(),
              clearanceService: FakeClearanceService(
                currentPayload: buildTestPayload(),
              ),
            ),
          ),
        );
        await tester.pumpAndSettle();

        expect(find.text('Dashboard'), findsOneWidget);
        expect(find.text('PDF'), findsOneWidget);
        expect(find.text('Profile'), findsOneWidget);
      },
    );

    testWidgets('pdf tab stays locked until the clearance is completed', (
      WidgetTester tester,
    ) async {
      // This mirrors the student UX rule: the PDF tab is always visible, but
      // the download action only unlocks after all offices approve.
      await tester.pumpWidget(
        MaterialApp(
          home: AppShell(
            authService: FakeAuthService(),
            clearanceService: FakeClearanceService(
              currentPayload: buildTestPayload(clearanceStatus: 'in_progress'),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();

      await tester.tap(find.text('PDF'));
      await tester.pumpAndSettle();

      expect(find.text('PDF Locked Until Completion'), findsOneWidget);
      expect(find.text('Download PDF'), findsNothing);
    });

    testWidgets('completed clearance shows a downloadable PDF state', (
      WidgetTester tester,
    ) async {
      // This checks the happy path on the PDF tab once the clearance reaches
      // completed status in the shared shell payload.
      final clearanceService = FakeClearanceService(
        currentPayload: buildTestPayload(clearanceStatus: 'completed'),
      );

      await tester.pumpWidget(
        MaterialApp(
          home: AppShell(
            authService: FakeAuthService(),
            clearanceService: clearanceService,
          ),
        ),
      );
      await tester.pumpAndSettle();

      await tester.tap(find.text('PDF'));
      await tester.pumpAndSettle();

      expect(find.text('Clearance PDF Ready'), findsOneWidget);
      expect(find.text('Download PDF'), findsOneWidget);

      await tester.tap(find.text('Download PDF'));
      await tester.pump();

      expect(clearanceService.downloadCalls, 1);
    });

    testWidgets(
      'refresh updates the shared clearance payload across the shell',
      (WidgetTester tester) async {
        final clearanceService = FakeClearanceService(
          payloadQueue: [
            buildTestPayload(clearanceStatus: 'in_progress'),
            buildTestPayload(clearanceStatus: 'completed'),
          ],
        );

        await tester.pumpWidget(
          MaterialApp(
            home: AppShell(
              authService: FakeAuthService(),
              clearanceService: clearanceService,
            ),
          ),
        );

        await tester.pumpAndSettle();

        expect(find.text('In Progress'), findsOneWidget);
        expect(find.text('Completed'), findsNothing);

        await tester.tap(find.byTooltip('Refresh'));
        await tester.pump();
        await tester.pumpAndSettle();

        expect(clearanceService.loadCalls, 2);
        expect(find.text('Completed'), findsWidgets);
        expect(find.text('Download Clearance PDF'), findsOneWidget);
      },
    );

    testWidgets(
      'profile screen exposes the logout action for the student session',
      (WidgetTester tester) async {
        // This verifies the new logout location after the mobile makeover: the
        // Profile screen owns the sign-out action instead of the dashboard.
        var logoutCalled = false;

        await tester.pumpWidget(
          MaterialApp(
            home: ProfileScreen(
              payload: buildTestPayload(),
              error: null, // ADDED: required after profile state messaging update
              isLoading: false,
              isBusy: false,
              onRefresh: () async {},
              onLogout: () async {
                logoutCalled = true;
              },
            ),
          ),
        );
        await tester.pumpAndSettle();

        await tester.scrollUntilVisible(find.text('Logout'), 250);
        await tester.tap(find.text('Logout'));
        await tester.pumpAndSettle();

        expect(logoutCalled, isTrue);
      },
    );

    testWidgets(
      'session-expired payload errors send the student back to login',
      (WidgetTester tester) async {
        // This protects the shell from stale tokens by ensuring a 401-style
        // failure routes back to the login screen with the expiry message.
        await tester.pumpWidget(
          MaterialApp(
            home: AppShell(
              authService: FakeAuthService(),
              clearanceService: FakeClearanceService(
                loadError: buildExpiredSession(),
              ),
              loginScreenBuilder: (_, message) =>
                  Scaffold(body: Text(message ?? 'Back on Login')),
            ),
          ),
        );
        await tester.pumpAndSettle();

        expect(
          find.text('Your session expired. Please sign in again.'),
          findsOneWidget,
        );
      },
    );
  });
}
