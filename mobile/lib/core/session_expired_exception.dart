class SessionExpiredException implements Exception {
  SessionExpiredException([this.message = 'Your session expired. Please sign in again.']);

  final String message;

  @override
  String toString() => message;
}
