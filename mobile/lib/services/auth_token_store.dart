import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Provides a tiny abstraction around auth-token persistence so tests can use
/// an in-memory store instead of the platform secure-storage plugin.
abstract class AuthTokenStore {
  Future<String?> readToken();

  Future<void> writeToken(String token);

  Future<void> clearToken();
}

class SecureAuthTokenStore implements AuthTokenStore {
  SecureAuthTokenStore({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  final FlutterSecureStorage _storage;

  @override
  Future<void> clearToken() {
    return _storage.delete(key: 'auth_token');
  }

  @override
  Future<String?> readToken() {
    return _storage.read(key: 'auth_token');
  }

  @override
  Future<void> writeToken(String token) {
    return _storage.write(key: 'auth_token', value: token);
  }
}
