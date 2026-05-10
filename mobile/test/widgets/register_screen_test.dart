import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/screens/register_screen.dart';

import '../helpers/fake_services.dart';

void main() {
  group('RegisterScreen', () {
    testWidgets('renders student registration fields and academic options', (
      WidgetTester tester,
    ) async {
      final registrationService = FakeRegistrationService();

      await tester.pumpWidget(
        MaterialApp(
          home: RegisterScreen(registrationService: registrationService),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Create Account'), findsOneWidget);
      expect(find.text('Submit for Approval'), findsOneWidget);
      expect(find.text('First Name'), findsOneWidget);
      expect(find.text('Student ID'), findsOneWidget);
      expect(find.text('Academic Profile'), findsOneWidget);
      expect(find.text('Program'), findsOneWidget);
      expect(find.text('Already have an account? Sign In'), findsOneWidget);
    });

    testWidgets('submits a valid registration request and returns to login', (
      WidgetTester tester,
    ) async {
      final registrationService = FakeRegistrationService();
      bool? created;

      await tester.pumpWidget(
        MaterialApp(
          home: Builder(
            builder: (context) {
              return Scaffold(
                body: ElevatedButton(
                  onPressed: () async {
                    created = await Navigator.of(context).push<bool>(
                      MaterialPageRoute(
                        builder: (_) => RegisterScreen(
                          registrationService: registrationService,
                        ),
                      ),
                    );
                  },
                  child: const Text('Open Registration'),
                ),
              );
            },
          ),
        ),
      );

      await tester.tap(find.text('Open Registration'));
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextFormField).at(0), 'niña');
      await tester.enterText(find.byType(TextFormField).at(1), 'dela cruz');
      await tester.enterText(find.byType(TextFormField).at(2), 'ñ');
      await tester.enterText(find.byType(TextFormField).at(3), '2407777');
      await tester.enterText(
        find.byType(TextFormField).at(4),
        'nina@lnu.edu.ph',
      );
      tester.testTextInput.hide();
      await tester.pumpAndSettle();

      final programDropdown = find.byKey(
        const Key('registration-program-dropdown'),
      );
      await tester.ensureVisible(programDropdown);
      await tester.pumpAndSettle();
      await tester.tap(programDropdown);
      await tester.pumpAndSettle();
      await tester.tap(find.textContaining('BSIT').last);
      await tester.pumpAndSettle();

      final yearLevelDropdown = find.byKey(
        const Key('registration-year-level-dropdown'),
      );
      await tester.ensureVisible(yearLevelDropdown);
      await tester.pumpAndSettle();
      await tester.tap(yearLevelDropdown);
      await tester.pumpAndSettle();
      await tester.tap(find.text('2nd Year').last);
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextFormField).at(5), 'password');
      await tester.enterText(find.byType(TextFormField).at(6), 'password');
      tester.testTextInput.hide();
      await tester.pumpAndSettle();

      final termsCheckbox = find.byKey(
        const Key('registration-terms-checkbox'),
      );
      await tester.ensureVisible(termsCheckbox);
      await tester.pumpAndSettle();
      await _readAndAcceptPrivacyStatement(tester);

      final submitButton = find.byKey(const Key('registration-submit-button'));
      await tester.ensureVisible(submitButton);
      await tester.pumpAndSettle();
      await tester.tap(submitButton);
      await tester.pumpAndSettle();

      expect(created, isTrue);
      expect(registrationService.lastRequest?.studentIdNumber, '2407777');
      expect(registrationService.lastRequest?.firstName, 'niña');
      expect(registrationService.lastRequest?.middleInitial, 'ñ');
      expect(registrationService.lastRequest?.programId, 1);
      expect(registrationService.lastRequest?.yearLevel, 2);
    });

    testWidgets('shows validation when names contain unsupported characters', (
      WidgetTester tester,
    ) async {
      await tester.pumpWidget(
        MaterialApp(
          home: RegisterScreen(registrationService: FakeRegistrationService()),
        ),
      );
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextFormField).at(0), 'Bad😊');
      tester.testTextInput.hide();
      await tester.pumpAndSettle();

      final termsCheckbox = find.byKey(
        const Key('registration-terms-checkbox'),
      );
      await tester.ensureVisible(termsCheckbox);
      await tester.pumpAndSettle();
      await _readAndAcceptPrivacyStatement(tester);

      final submitButton = find.byKey(const Key('registration-submit-button'));
      await tester.ensureVisible(submitButton);
      await tester.pumpAndSettle();
      await tester.tap(submitButton);
      await tester.pumpAndSettle();

      expect(
        find.text(
          'First Name may only contain letters, spaces, apostrophes, and hyphens.',
        ),
        findsOneWidget,
      );
    });
  });
}

Future<void> _readAndAcceptPrivacyStatement(WidgetTester tester) async {
  await tester.tap(find.byKey(const Key('registration-terms-open-button')));
  await tester.pumpAndSettle();

  for (var i = 0; i < 8; i++) {
    await tester.drag(
      find.byType(SingleChildScrollView).last,
      const Offset(0, -700),
    );
    await tester.pump();
  }

  await tester.tap(find.byKey(const Key('privacy-agree-button')));
  await tester.pumpAndSettle();
}
