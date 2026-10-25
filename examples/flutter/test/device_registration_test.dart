import 'dart:convert';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:pushbox_demo/device_registration.dart';

void main() {
  test(
    'registers the FCM token using the local endpoint and bearer key',
    () async {
      final client = MockClient((request) async {
        expect(
          request.url.toString(),
          'http://10.0.2.2:8080/api/pushbox/device',
        );
        expect(request.headers['Authorization'], 'Bearer pairing-key');
        expect(jsonDecode(request.body), {'token': 'device-token'});
        return http.Response('{"registered":true}', 200);
      });
      await DeviceRegistration(client).register(
        serverUrl: 'http://10.0.2.2:8080/',
        registrationKey: 'pairing-key',
        token: 'device-token',
      );
    },
  );

  test('rejects an invalid URL without transmitting the token', () async {
    final registration = DeviceRegistration(
      MockClient((_) async => fail('HTTP must not run')),
    );
    for (final url in [
      'file:///tmp',
      'not-a-url',
      'https://user:secret@example.com',
      'https://example.com?key=secret',
      'https://example.com/pushbox',
    ]) {
      await expectLater(
        registration.register(
          serverUrl: url,
          registrationKey: 'key',
          token: 'token',
        ),
        throwsFormatException,
      );
    }
  });

  test('does not display raw server errors or tokens', () async {
    final registration = DeviceRegistration(
      MockClient(
        (_) async => http.Response('private debug dump with token', 500),
      ),
    );
    await expectLater(
      registration.register(
        serverUrl: 'http://localhost:8080',
        registrationKey: 'key',
        token: 'token',
      ),
      throwsA(
        predicate(
          (Object error) =>
              error.toString().contains('HTTP 500') &&
              !error.toString().contains('private debug dump'),
        ),
      ),
    );
  });

  test(
    'does not claim success when a server returns an unrelated response',
    () async {
      final registration = DeviceRegistration(
        MockClient((_) async => http.Response('{"ok":true}', 200)),
      );
      await expectLater(
        registration.register(
          serverUrl: 'http://localhost:8080',
          registrationKey: 'key',
          token: 'token',
        ),
        throwsFormatException,
      );
    },
  );
}
