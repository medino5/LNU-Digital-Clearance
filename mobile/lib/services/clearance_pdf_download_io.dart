import 'dart:io';

import 'package:flutter/services.dart';
import 'package:path_provider/path_provider.dart';

Future<String> saveClearancePdfBytes(
  List<int> bytes,
  String safeFileName,
) async {
  if (Platform.isAndroid) {
    const channel = MethodChannel('digital_clearance/downloads');
    final savedPath = await channel.invokeMethod<String>(
      'savePdfToDownloads',
      <String, Object>{
        'fileName': safeFileName,
        'bytes': Uint8List.fromList(bytes),
      },
    );

    if (savedPath != null && savedPath.isNotEmpty) {
      return savedPath;
    }
  }

  final directory =
      await getDownloadsDirectory() ?? await getApplicationDocumentsDirectory();

  final file = File('${directory.path}${Platform.pathSeparator}$safeFileName');

  await file.writeAsBytes(bytes, flush: true);

  return file.path;
}
