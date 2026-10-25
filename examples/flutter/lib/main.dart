import 'dart:async';
import 'dart:convert';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'device_registration.dart';

const notificationChannel = AndroidNotificationChannel(
  'pushbox_demo',
  'Pushbox Demo',
  description: 'Test notifications sent from Pushbox.',
  importance: Importance.high,
);

Map<String, dynamic> messageContent(RemoteMessage message) => {
  'title':
      message.notification?.title ?? message.data['title'] ?? 'Push received',
  'body': message.notification?.body ?? message.data['body'] ?? '',
  'data': message.data,
  'received_at': DateTime.now().toIso8601String(),
};

@pragma('vm:entry-point')
Future<void> receiveBackgroundMessage(RemoteMessage message) async {
  await Firebase.initializeApp();
  await SharedPreferencesAsync().setString(
    'last_message',
    jsonEncode(messageContent(message)),
  );
}

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const PushboxDemo());
}

class PushboxDemo extends StatelessWidget {
  const PushboxDemo({super.key});
  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'Pushbox Demo',
    debugShowCheckedModeBanner: false,
    theme: ThemeData(
      colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xff151a22)),
      inputDecorationTheme: const InputDecorationTheme(
        border: OutlineInputBorder(),
      ),
      useMaterial3: true,
    ),
    home: const DeviceScreen(),
  );
}

class DeviceScreen extends StatefulWidget {
  const DeviceScreen({super.key});
  @override
  State<DeviceScreen> createState() => _DeviceScreenState();
}

class _DeviceScreenState extends State<DeviceScreen>
    with WidgetsBindingObserver {
  final _server = TextEditingController();
  final _key = TextEditingController();
  final _preferences = SharedPreferencesAsync();
  final _http = http.Client();
  final _notifications = FlutterLocalNotificationsPlugin();
  final List<StreamSubscription<dynamic>> _subscriptions = [];
  String _status = 'Starting Firebase…';
  String? _token;
  Map<String, dynamic>? _lastMessage;
  bool _firebaseReady = false;
  bool _busy = false;
  bool _registered = false;
  bool _registerAgain = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    unawaited(_initialize());
  }

  Future<void> _initialize() async {
    try {
      _server.text =
          await _preferences.getString('server_url') ??
          const String.fromEnvironment(
            'PUSHBOX_SERVER_URL',
            defaultValue: 'http://10.0.2.2:8080',
          );
      _key.text = await _preferences.getString('registration_key') ?? '';
      await _loadLastMessage();
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(receiveBackgroundMessage);
      await _notifications.initialize(
        settings: const InitializationSettings(
          android: AndroidInitializationSettings('ic_notification'),
        ),
        onDidReceiveNotificationResponse: (_) => unawaited(_loadLastMessage()),
      );
      await _notifications
          .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin
          >()
          ?.createNotificationChannel(notificationChannel);
      _subscriptions.add(
        FirebaseMessaging.onMessage.listen((message) {
          unawaited(_receive(message, showNotification: true));
        }),
      );
      _subscriptions.add(
        FirebaseMessaging.onMessageOpenedApp.listen((message) {
          unawaited(_receive(message));
        }),
      );
      _subscriptions.add(
        FirebaseMessaging.instance.onTokenRefresh.listen(
          (token) {
            _token = token;
            _registered = false;
            if (_key.text.trim().isNotEmpty) unawaited(_register());
          },
          onError: (Object error) =>
              _setStatus('Token refresh failed. Tap Register device to retry.'),
        ),
      );
      final initialMessage = await FirebaseMessaging.instance
          .getInitialMessage();
      if (initialMessage != null) await _receive(initialMessage);
      _firebaseReady = true;
      _setStatus('Ready to register this device.');
      if (_key.text.trim().isNotEmpty) await _register();
    } catch (_) {
      _setStatus(
        'Firebase setup is incomplete. Add this app’s google-services.json and rebuild.',
      );
    }
  }

  Future<void> _register() async {
    if (!_firebaseReady || !mounted) return;
    if (_busy) {
      _registerAgain = true;
      return;
    }
    setState(() {
      _busy = true;
      _registered = false;
      _status = 'Registering device…';
    });
    try {
      final permission = await FirebaseMessaging.instance.requestPermission();
      if (permission.authorizationStatus == AuthorizationStatus.denied) {
        throw Exception(
          'Notifications are disabled. Enable them in Android settings, then retry.',
        );
      }
      final token = await FirebaseMessaging.instance.getToken();
      if (token == null) {
        throw Exception('FCM has not issued a token yet. Try again.');
      }
      _token = token;
      await DeviceRegistration(_http).register(
        serverUrl: _server.text,
        registrationKey: _key.text,
        token: token,
      );
      await _preferences.setString('server_url', _server.text.trim());
      await _preferences.setString('registration_key', _key.text.trim());
      _registered = true;
      _setStatus(
        'Device registered. Open Pushbox on your computer and send a notification.',
      );
    } catch (error) {
      _setStatus(
        error is TimeoutException
            ? 'Server timed out. Check the URL and network connection.'
            : error is http.ClientException
            ? 'Cannot reach the server. Check the URL and network connection.'
            : error.toString().replaceFirst(
                RegExp(r'^(Exception|FormatException): '),
                '',
              ),
      );
    } finally {
      if (mounted) {
        setState(() => _busy = false);
        if (_registerAgain) {
          _registerAgain = false;
          unawaited(_register());
        }
      }
    }
  }

  Future<void> _receive(
    RemoteMessage message, {
    bool showNotification = false,
  }) async {
    final content = messageContent(message);
    await _preferences.setString('last_message', jsonEncode(content));
    if (mounted) setState(() => _lastMessage = content);
    if (showNotification) {
      try {
        await _notifications.show(
          id: DateTime.now().millisecondsSinceEpoch.remainder(2147483647),
          title: content['title'] as String,
          body: content['body'] as String,
          notificationDetails: NotificationDetails(
            android: AndroidNotificationDetails(
              notificationChannel.id,
              notificationChannel.name,
              channelDescription: notificationChannel.description,
              importance: Importance.high,
              priority: Priority.high,
              styleInformation: BigTextStyleInformation(
                content['body'] as String,
              ),
            ),
          ),
        );
      } catch (_) {
        _setStatus(
          'Push received. Android could not display the system notification.',
        );
      }
    }
  }

  Future<void> _loadLastMessage() async {
    final saved = await _preferences.getString('last_message');
    if (saved != null && mounted) {
      setState(() => _lastMessage = jsonDecode(saved) as Map<String, dynamic>);
    }
  }

  void _setStatus(String status) {
    if (mounted) setState(() => _status = status);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) unawaited(_loadLastMessage());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    for (final subscription in _subscriptions) {
      unawaited(subscription.cancel());
    }
    _server.dispose();
    _key.dispose();
    _http.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Pushbox Demo')),
    body: ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text(
          'Your test device',
          style: Theme.of(context).textTheme.headlineMedium,
        ),
        const SizedBox(height: 8),
        const Text(
          'Register this phone, then send a push from the Pushbox workspace.',
        ),
        const SizedBox(height: 24),
        TextField(
          controller: _server,
          enabled: !_busy,
          decoration: const InputDecoration(labelText: 'Server URL'),
          keyboardType: TextInputType.url,
          autocorrect: false,
        ),
        const SizedBox(height: 16),
        TextField(
          controller: _key,
          enabled: !_busy,
          obscureText: true,
          autocorrect: false,
          enableSuggestions: false,
          decoration: const InputDecoration(labelText: 'Registration key'),
        ),
        const SizedBox(height: 16),
        FilledButton.icon(
          onPressed: _busy || !_firebaseReady ? null : _register,
          icon: const Icon(Icons.phonelink_ring),
          label: Text(_busy ? 'Registering…' : 'Register device'),
        ),
        const SizedBox(height: 12),
        Semantics(
          liveRegion: true,
          child: Text(
            _status,
            style: TextStyle(color: _registered ? Colors.green.shade800 : null),
          ),
        ),
        if (_token != null) ...[
          const SizedBox(height: 20),
          Row(
            children: [
              const Expanded(child: Text('FCM token')),
              TextButton.icon(
                onPressed: () async {
                  await Clipboard.setData(ClipboardData(text: _token!));
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Token copied')),
                    );
                  }
                },
                icon: const Icon(Icons.copy, size: 16),
                label: const Text('Copy'),
              ),
            ],
          ),
          SelectableText(_token!, style: Theme.of(context).textTheme.bodySmall),
        ],
        const SizedBox(height: 28),
        Text(
          'Latest notification',
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: 12),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: _lastMessage == null
                ? const Text('Waiting for a push from Pushbox…')
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _lastMessage!['title'] as String,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      Text(_lastMessage!['body'] as String),
                      const SizedBox(height: 16),
                      SelectableText(
                        const JsonEncoder.withIndent(
                          '  ',
                        ).convert(_lastMessage!['data']),
                        style: const TextStyle(
                          fontFamily: 'monospace',
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
          ),
        ),
      ],
    ),
  );
}
