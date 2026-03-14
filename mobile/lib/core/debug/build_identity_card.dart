import 'package:flutter/material.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../network_config.dart';

class BuildIdentityCard extends StatefulWidget {
  const BuildIdentityCard({
    super.key,
    this.title = 'Installed Build',
    this.compact = false,
  });

  final String title;
  final bool compact;

  static const String debugBuildLabel = 'dbg-20260314-01';

  @override
  State<BuildIdentityCard> createState() => _BuildIdentityCardState();
}

class _BuildIdentityCardState extends State<BuildIdentityCard> {
  late final Future<PackageInfo> _packageInfo = PackageInfo.fromPlatform();

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<PackageInfo>(
      future: _packageInfo,
      builder: (context, snapshot) {
        final info = snapshot.data;
        final valueStyle = TextStyle(
          color: Colors.grey.shade800,
          fontSize: widget.compact ? 12 : 13,
          height: 1.45,
        );

        return Container(
          padding: EdgeInsets.all(widget.compact ? 12 : 14),
          decoration: BoxDecoration(
            color: const Color(0xFFF7F7F2),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFD9D3C4)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.title,
                style: const TextStyle(
                  color: Color(0xFF183A63),
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Package: ${info?.packageName ?? 'Loading...'}',
                style: valueStyle,
              ),
              Text(
                'Version: ${_versionText(info)}',
                style: valueStyle,
              ),
              Text(
                'Build label: ${BuildIdentityCard.debugBuildLabel}',
                style: valueStyle,
              ),
              Text(
                'Host: ${NetworkConfig.baseUrl}',
                style: valueStyle,
              ),
            ],
          ),
        );
      },
    );
  }

  String _versionText(PackageInfo? info) {
    if (info == null) {
      return 'Loading...';
    }

    return '${info.version}+${info.buildNumber}';
  }
}
