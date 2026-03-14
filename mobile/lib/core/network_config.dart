class NetworkConfig {
  // Android physical-device debugging uses adb reverse so the phone can
  // reach the laptop's Docker-exposed backend through 127.0.0.1.
  static const String androidDebugHost = '127.0.0.1';

  // Browser portals still use the laptop's LAN IP from the host machine.
  static const String laptopLanIp = '192.168.254.115';

  static const String adbReverseCommand = 'adb reverse tcp:8000 tcp:8000';

  static const String baseUrl = 'http://$androidDebugHost:8000/api';

  /// Office web portal URL - open this in your laptop browser for office testing.
  static const String officePortalUrl = 'http://$laptopLanIp:8000/office/login';

  /// Super admin portal URL for MIS account access.
  static const String adminPortalUrl = 'http://$laptopLanIp:8000/admin/login';
}
