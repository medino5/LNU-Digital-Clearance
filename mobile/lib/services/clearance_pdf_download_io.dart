import 'dart:io';

import 'package:path_provider/path_provider.dart';

Future<String> saveClearancePdfBytes(
  List<int> bytes,
  String safeFileName,
) async {
  final directory = await getApplicationDocumentsDirectory();

  final file = File(
    '${directory.path}${Platform.pathSeparator}$safeFileName',
  );

  await file.writeAsBytes(bytes, flush: true);

  return file.path;
}