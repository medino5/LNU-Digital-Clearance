import 'package:flutter/foundation.dart';

class NetworkConfig {
  static const String androidDebugHost = '127.0.0.1';
  static const String adbReverseCommand = 'adb reverse tcp:8000 tcp:8000';
  static const String _configuredApiBaseUrl = String.fromEnvironment(
    'APP_API_BASE_URL',
    defaultValue: '',
  );
  static const String _configuredBuildLabel = String.fromEnvironment(
    'BUILD_LABEL',
    defaultValue: '',
  );
  static const bool _forceShowConnectionTools = bool.fromEnvironment(
    'ENABLE_CONNECTION_TEST',
    defaultValue: false,
  );

  static String get baseUrl {
    if (_configuredApiBaseUrl.isNotEmpty) {
      return _normalizeApiBaseUrl(_configuredApiBaseUrl);
    }

    return 'http://$androidDebugHost:8000/api';
  }

  static String get portalBaseUrl {
    final uri = Uri.parse(baseUrl);
    final pathSegments = uri.pathSegments
        .where((segment) => segment.isNotEmpty)
        .toList();

    if (pathSegments.isNotEmpty && pathSegments.last == 'api') {
      pathSegments.removeLast();
    }

    return _trimTrailingSlash(
      uri
          .replace(
            pathSegments: pathSegments,
            queryParameters: null,
            fragment: null,
          )
          .toString(),
    );
  }

  static String get sharedPortalUrl => '$portalBaseUrl/login';

  static String get officePortalUrl => sharedPortalUrl;

  static String get adminPortalUrl => sharedPortalUrl;

  static bool get usesLocalDockerBackend {
    final host = Uri.tryParse(baseUrl)?.host ?? '';

    return host == '127.0.0.1' || host == 'localhost' || host == '10.0.2.2';
  }

  static bool get showConnectionTest =>
      !kReleaseMode || _forceShowConnectionTools;

  static String get buildLabel {
    if (_configuredBuildLabel.isNotEmpty) {
      return _configuredBuildLabel;
    }

    return kReleaseMode ? 'release-local' : 'debug-local';
  }

  static String _normalizeApiBaseUrl(String value) {
    final trimmed = _trimTrailingSlash(value.trim());

    if (trimmed.endsWith('/api')) {
      return trimmed;
    }

    return '$trimmed/api';
  }

  static String _trimTrailingSlash(String value) {
    return value.replaceFirst(RegExp(r'/+$'), '');
  }
}
