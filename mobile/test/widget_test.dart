import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/screens/login_screen.dart';

import 'helpers/fake_services.dart';

void main() {
  testWidgets('student login screen renders the current clearance entry form', (
    WidgetTester tester,
  ) async {
    // This smoke test replaces the default counter sample and proves the app
    // now boots into the real student login experience.
    await tester.pumpWidget(
      MaterialApp(home: LoginScreen(authService: FakeAuthService())),
    );
    await tester.pumpAndSettle();

    expect(find.text('Student Sign In'), findsOneWidget);
    expect(find.byType(TextField), findsNWidgets(2));
    expect(find.text('LNU Student Clearance Portal'), findsOneWidget);
    expect(find.text('Connection Test'), findsNothing);
  });
}
