import 'dart:io';

Future<String> saveClearancePdfBytes(
  List<int> bytes,
  String safeFileName,
) async {
  final file = File(
    '${Directory.systemTemp.path}${Platform.pathSeparator}$safeFileName',
  );

  await file.writeAsBytes(bytes, flush: true);

  return file.path;
}
