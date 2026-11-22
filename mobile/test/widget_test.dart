import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:taqwim_app/main.dart';

void main() {
  testWidgets('App boots to the login screen when signed out', (tester) async {
    SharedPreferences.setMockInitialValues({});
    await tester.pumpWidget(const TaqwimApp());
    await tester.pumpAndSettle();
    expect(find.text('تسجيل الدخول'), findsOneWidget);
  });
}
