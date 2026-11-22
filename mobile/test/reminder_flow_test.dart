import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:taqwim_app/main.dart';
import 'package:taqwim_app/src/api.dart';

class FakeApiClient extends ApiClient {
  final List<int> markedSent = [];
  int remindersCalls = 0;

  @override
  Future<List<dynamic>> reminders() async {
    remindersCalls++;
    return [
      {
        'id': 7,
        'kind': 'recall',
        'scheduled_for': '2026-11-22',
        'patient': {'id': 3, 'name': 'سارة محمود عوض', 'phone': '01010000004'},
      }
    ];
  }

  @override
  Future<Map<String, dynamic>> markReminderSent(int id) async {
    markedSent.add(id);
    return {'status': 'sent'};
  }
}

Widget _wrap(FakeApiClient api) => MaterialApp(
      locale: const Locale('ar'),
      home: Scaffold(body: RemindersTab(api: api)),
    );

void main() {
  testWidgets('handoff alone marks nothing; explicit confirm marks sent',
      (tester) async {
    final api = FakeApiClient();
    await tester.pumpWidget(_wrap(api));
    await tester.pumpAndSettle();

    expect(find.text('واتساب'), findsOneWidget);
    expect(api.markedSent, isEmpty);

    await tester.tap(find.text('واتساب'));
    await tester.pumpAndSettle();

    // Handoff opened the confirm state; nothing marked yet.
    expect(find.text('تم الإرسال'), findsOneWidget);
    expect(find.text('لم تُرسل'), findsOneWidget);
    expect(api.markedSent, isEmpty);

    // Cancel path: stays queued, nothing marked.
    await tester.tap(find.text('لم تُرسل'));
    await tester.pumpAndSettle();
    expect(find.text('واتساب'), findsOneWidget);
    expect(api.markedSent, isEmpty);

    // Confirm path: now it marks sent and reloads.
    await tester.tap(find.text('واتساب'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('تم الإرسال'));
    await tester.pumpAndSettle();
    expect(api.markedSent, [7]);
  });
}
