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
      expect(find.text('Birthday'), findsOneWidget);
      expect(find.text('Academic Profile'), findsOneWidget);
      expect(find.text('Program'), findsOneWidget);
      expect(find.text('Section'), findsOneWidget);
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
      tester.testTextInput.hide();
      await tester.pumpAndSettle();

      await _selectBirthday(tester);

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

      final sectionDropdown = find.byKey(
        const Key('registration-section-dropdown'),
      );
      await tester.ensureVisible(sectionDropdown);
      await tester.pumpAndSettle();
      await tester.tap(sectionDropdown);
      await tester.pumpAndSettle();
      await tester.tap(find.text('2-1').last);
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextFormField).at(4), 'password');
      await tester.enterText(find.byType(TextFormField).at(5), 'password');
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
      expect(registrationService.lastRequest?.section, '2-1');
      final expectedBirthYear = DateTime.now().year - 12;
      expect(
        registrationService.lastRequest?.dateOfBirth,
        '$expectedBirthYear-01-01',
      );
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
      await tester.enterText(find.byType(TextFormField).at(1), 'Bad123');
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
          'Last Name may only contain letters, spaces, apostrophes, and hyphens.',
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

Future<void> _selectBirthday(WidgetTester tester) async {
  final yearDropdown = find.byKey(
    const Key('registration-birth-year-dropdown'),
  );
  await tester.ensureVisible(yearDropdown);
  await tester.pumpAndSettle();
  await tester.tap(yearDropdown);
  await tester.pumpAndSettle();
  await tester.tap(find.text((DateTime.now().year - 12).toString()).last);
  await tester.pumpAndSettle();

  final monthDropdown = find.byKey(
    const Key('registration-birth-month-dropdown'),
  );
  await tester.ensureVisible(monthDropdown);
  await tester.pumpAndSettle();
  await tester.tap(monthDropdown);
  await tester.pumpAndSettle();
  await tester.tap(find.text('01').last);
  await tester.pumpAndSettle();

  final dayDropdown = find.byKey(const Key('registration-birth-day-dropdown'));
  await tester.ensureVisible(dayDropdown);
  await tester.pumpAndSettle();
  await tester.tap(dayDropdown);
  await tester.pumpAndSettle();
  await tester.tap(find.text('01').last);
  await tester.pumpAndSettle();
}
