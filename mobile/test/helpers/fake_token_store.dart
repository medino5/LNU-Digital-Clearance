import 'package:mobile/services/auth_token_store.dart';

class FakeTokenStore implements AuthTokenStore {
  FakeTokenStore({this.token});

  String? token;

  @override
  Future<void> clearToken() async {
    token = null;
  }

  @override
  Future<String?> readToken() async {
    return token;
  }

  @override
  Future<void> writeToken(String token) async {
    this.token = token;
  }
}
