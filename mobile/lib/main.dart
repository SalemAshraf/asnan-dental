import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' hide TextDirection;
import 'package:url_launcher/url_launcher.dart';

import 'dart:async';

import 'src/api.dart';

const appointmentStatusAr = {
  'scheduled': 'مجدول',
  'confirmed': 'مؤكد',
  'checked_in': 'تم تسجيل الحضور',
  'completed': 'مكتمل',
  'cancelled': 'ملغي',
  'no_show': 'لم يحضر',
};

const reminderKindAr = {
  'recall': 'تذكير متابعة',
  'appointment': 'تذكير موعد',
};

String arLabel(Map<String, String> map, Object? key) =>
    map[key?.toString()] ?? key?.toString() ?? '';

void main() => runApp(const TaqwimApp());

class TaqwimApp extends StatefulWidget {
  const TaqwimApp({super.key});
  @override
  State<TaqwimApp> createState() => _TaqwimAppState();
}

class _TaqwimAppState extends State<TaqwimApp> {
  final ApiClient api = ApiClient();

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Taqwim',
      debugShowCheckedModeBanner: false,
      locale: const Locale('ar'),
      supportedLocales: const [Locale('ar'), Locale('en')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF0E7490)),
        useMaterial3: true,
      ),
      home: FutureBuilder<bool>(
        future: api.isSignedIn,
        builder: (context, snap) {
          if (!snap.hasData) {
            return const Scaffold(
                body: Center(child: CircularProgressIndicator()));
          }
          return snap.data! ? HomeScreen(api: api) : LoginScreen(api: api);
        },
      ),
    );
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key, required this.api});
  final ApiClient api;
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  String? _error;

  Future<void> _login() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.api.login(_email.text.trim(), _password.text, 'taqwim-app');
      if (!mounted) return;
      Navigator.of(context).pushReplacement(MaterialPageRoute(
          builder: (_) => HomeScreen(api: widget.api)));
    } on ApiException catch (e) {
      setState(() => _error = 'بيانات الدخول غير صحيحة (${e.statusCode})');
    } catch (_) {
      setState(() => _error = 'تعذر الاتصال بالخادم');
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 380),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Icon(Icons.medical_services_outlined,
                      size: 56, color: Color(0xFF0E7490)),
                  const SizedBox(height: 12),
                  const Text('تقويم',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                          fontSize: 28, fontWeight: FontWeight.bold)),
                  const Text('تطبيق العيادة المصاحب',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.grey)),
                  const SizedBox(height: 28),
                  TextField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    textDirection: TextDirection.ltr,
                    decoration: const InputDecoration(
                        labelText: 'البريد الإلكتروني',
                        border: OutlineInputBorder()),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: _password,
                    obscureText: true,
                    textDirection: TextDirection.ltr,
                    decoration: const InputDecoration(
                        labelText: 'كلمة المرور', border: OutlineInputBorder()),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(_error!, style: const TextStyle(color: Colors.red)),
                  ],
                  const SizedBox(height: 20),
                  FilledButton(
                    onPressed: _busy ? null : _login,
                    child: _busy
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2))
                        : const Text('تسجيل الدخول'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key, required this.api});
  final ApiClient api;
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _tab = 0;

  @override
  Widget build(BuildContext context) {
    final pages = [
      AppointmentsTab(api: widget.api),
      PatientsTab(api: widget.api),
      RemindersTab(api: widget.api),
    ];
    return Scaffold(
      appBar: AppBar(
        title: const Text('تقويم'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'تسجيل الخروج',
            onPressed: () async {
              await widget.api.logout();
              if (!context.mounted) return;
              Navigator.of(context).pushReplacement(MaterialPageRoute(
                  builder: (_) => LoginScreen(api: widget.api)));
            },
          ),
        ],
      ),
      body: pages[_tab],
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (i) => setState(() => _tab = i),
        destinations: const [
          NavigationDestination(
              icon: Icon(Icons.event), label: 'المواعيد'),
          NavigationDestination(icon: Icon(Icons.people), label: 'المرضى'),
          NavigationDestination(
              icon: Icon(Icons.notifications_active_outlined), label: 'التذكيرات'),
        ],
      ),
    );
  }
}

class AppointmentsTab extends StatefulWidget {
  const AppointmentsTab({super.key, required this.api});
  final ApiClient api;
  @override
  State<AppointmentsTab> createState() => _AppointmentsTabState();
}

class _AppointmentsTabState extends State<AppointmentsTab> {
  DateTime _date = DateTime.now();
  late Future<List<dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.appointments(_date);
  }

  void _reload() => setState(() {
        _future = widget.api.appointments(_date);
      });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  icon: const Icon(Icons.calendar_month),
                  label: Text(DateFormat.yMMMEd('ar').format(_date)),
                  onPressed: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: _date,
                      firstDate: DateTime.now().subtract(const Duration(days: 365)),
                      lastDate: DateTime.now().add(const Duration(days: 365)),
                    );
                    if (picked != null) {
                      _date = picked;
                      _reload();
                    }
                  },
                ),
              ),
              IconButton(icon: const Icon(Icons.refresh), onPressed: _reload),
            ],
          ),
        ),
        Expanded(
          child: FutureBuilder<List<dynamic>>(
            future: _future,
            builder: (context, snap) {
              if (!snap.hasData) {
                return const Center(child: CircularProgressIndicator());
              }
              final items = snap.data!;
              if (items.isEmpty) {
                return const Center(child: Text('لا مواعيد في هذا اليوم'));
              }
              return ListView.builder(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                itemCount: items.length,
                itemBuilder: (context, i) {
                  final a = items[i] as Map<String, dynamic>;
                  final starts = DateTime.parse(a['starts_at'] as String);
                  final patient = a['patient'] as Map<String, dynamic>?;
                  return Card(
                    child: ListTile(
                      leading: Container(
                        padding: const EdgeInsets.symmetric(
                            horizontal: 8, vertical: 10),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.primaryContainer,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(DateFormat.Hm().format(starts)),
                      ),
                      title: Text(patient?['name']?.toString() ?? '—'),
                      subtitle: Text(
                          '${a['branch']?['name'] ?? ''} · ${arLabel(appointmentStatusAr, a['status'])}'),
                    ),
                  );
                },
              );
            },
          ),
        ),
      ],
    );
  }
}

class PatientsTab extends StatefulWidget {
  const PatientsTab({super.key, required this.api});
  final ApiClient api;
  @override
  State<PatientsTab> createState() => _PatientsTabState();
}

class _PatientsTabState extends State<PatientsTab> {
  List<dynamic> _results = [];
  bool _searched = false;

  Future<void> _search(String q) async {
    if (q.trim().length < 2) return;
    final results = await widget.api.searchPatients(q.trim());
    setState(() {
      _results = results;
      _searched = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: TextField(
            decoration: const InputDecoration(
              hintText: 'ابحث بالاسم أو الهاتف…',
              prefixIcon: Icon(Icons.search),
              border: OutlineInputBorder(),
            ),
            onSubmitted: _search,
          ),
        ),
        Expanded(
          child: !_searched
              ? const Center(child: Text('اكتب حرفين على الأقل للبحث'))
              : _results.isEmpty
                  ? const Center(child: Text('لا نتائج'))
                  : ListView.builder(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      itemCount: _results.length,
                      itemBuilder: (context, i) {
                        final p = _results[i] as Map<String, dynamic>;
                        return Card(
                          child: ListTile(
                            leading: const Icon(Icons.person_outline),
                            title: Text(p['full_name']?.toString() ?? '—'),
                            subtitle: Text(p['phone']?.toString() ?? ''),
                          ),
                        );
                      },
                    ),
        ),
      ],
    );
  }
}

class RemindersTab extends StatefulWidget {
  const RemindersTab({super.key, required this.api});
  final ApiClient api;
  @override
  State<RemindersTab> createState() => _RemindersTabState();
}

class _RemindersTabState extends State<RemindersTab> {
  late Future<List<dynamic>> _future;
  int? _pendingConfirmId;

  @override
  void initState() {
    super.initState();
    _future = widget.api.reminders();
  }

  void _reload() => setState(() {
        _future = widget.api.reminders();
      });

  Future<void> _sendWhatsApp(Map<String, dynamic> reminder) async {
    final patient = reminder['patient'] as Map<String, dynamic>?;
    final phone =
        (patient?['phone'] ?? '').toString().replaceAll(RegExp(r'[^0-9+]'), '');
    if (phone.isEmpty) return;
    final text = reminder['template_text']?.toString() ?? '';
    final uri = Uri.parse(
        'https://wa.me/${phone.replaceAll('+', '')}?text=${Uri.encodeComponent(text)}');
    try {
      unawaited(launchUrl(uri, mode: LaunchMode.externalApplication));
    } catch (_) {
      // No WhatsApp on this device; staff can still confirm after sending.
    }
    // Nothing is marked yet: staff must explicitly confirm below.
    if (mounted) setState(() => _pendingConfirmId = reminder['id'] as int);
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<dynamic>>(
      future: _future,
      builder: (context, snap) {
        if (!snap.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final items = snap.data!;
        if (items.isEmpty) {
          return Center(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('لا تذكيرات في الانتظار'),
                TextButton.icon(
                    onPressed: _reload,
                    icon: const Icon(Icons.refresh),
                    label: const Text('تحديث')),
              ],
            ),
          );
        }
        return RefreshIndicator(
          onRefresh: () async => _reload(),
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            itemCount: items.length,
            itemBuilder: (context, i) {
              final r = items[i] as Map<String, dynamic>;
              final patient = r['patient'] as Map<String, dynamic>?;
              return Card(
                child: ListTile(
                  leading: const Icon(Icons.notifications_active_outlined),
                  title: Text(patient?['name']?.toString() ?? '—'),
                  subtitle: Text(
                      '${arLabel(reminderKindAr, r['kind'])} · ${r['scheduled_for'] ?? ''}\n${patient?['phone'] ?? ''}'),
                  isThreeLine: true,
                  trailing: _pendingConfirmId == r['id']
                      ? Wrap(
                          spacing: 4,
                          children: [
                            FilledButton(
                              onPressed: () async {
                                await widget.api
                                    .markReminderSent(r['id'] as int);
                                _pendingConfirmId = null;
                                _reload();
                              },
                              child: const Text('تم الإرسال'),
                            ),
                            TextButton(
                              onPressed: () =>
                                  setState(() => _pendingConfirmId = null),
                              child: const Text('لم تُرسل'),
                            ),
                          ],
                        )
                      : FilledButton.tonalIcon(
                          icon: const Icon(Icons.send, size: 16),
                          label: const Text('واتساب'),
                          onPressed: () => _sendWhatsApp(r),
                        ),
                ),
              );
            },
          ),
        );
      },
    );
  }
}
