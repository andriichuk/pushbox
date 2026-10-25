import 'dart:convert';
import 'package:http/http.dart' as http;

class DeviceRegistration {
  DeviceRegistration(this.client);
  final http.Client client;

  Future<void> register({
    required String serverUrl,
    required String registrationKey,
    required String token,
  }) async {
    final uri = Uri.tryParse(serverUrl.trim());
    if (uri == null ||
        !uri.hasAuthority ||
        !['http', 'https'].contains(uri.scheme) ||
        uri.userInfo.isNotEmpty ||
        uri.hasQuery ||
        uri.hasFragment ||
        (uri.path.isNotEmpty && uri.path != '/')) {
      throw const FormatException(
        'Enter the server origin, for example http://10.0.2.2:8080.',
      );
    }
    if (registrationKey.trim().isEmpty || token.isEmpty) {
      throw const FormatException(
        'The registration key and FCM token are required.',
      );
    }
    final response = await client
        .post(
          uri.resolve('/api/pushbox/device'),
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ${registrationKey.trim()}',
          },
          body: jsonEncode({'token': token}),
        )
        .timeout(const Duration(seconds: 15));
    if (response.statusCode != 200) {
      throw Exception(switch (response.statusCode) {
        401 =>
          'Registration key rejected. Check the key from your demo server.',
        404 => 'The local Pushbox registration endpoint is unavailable.',
        422 => 'The server rejected the device token.',
        429 => 'Too many attempts. Try again in a minute.',
        _ => 'Registration failed (HTTP ${response.statusCode}).',
      });
    }
    final body = jsonDecode(response.body);
    if (body is! Map || body['registered'] != true) {
      throw const FormatException('The server did not confirm registration.');
    }
  }
}
