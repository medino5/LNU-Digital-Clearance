import 'package:flutter/services.dart';

bool containsEmoji(String value) {
  for (final rune in value.runes) {
    if (_isEmojiRune(rune)) {
      return true;
    }
  }

  return false;
}

String stripEmoji(String value) {
  return String.fromCharCodes(value.runes.where((rune) => !_isEmojiRune(rune)));
}

class NoEmojiTextInputFormatter extends TextInputFormatter {
  const NoEmojiTextInputFormatter();

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    if (!containsEmoji(newValue.text)) {
      return newValue;
    }

    final cleaned = stripEmoji(newValue.text);
    final offset = cleaned.length.clamp(0, cleaned.length);

    return TextEditingValue(
      text: cleaned,
      selection: TextSelection.collapsed(offset: offset),
      composing: TextRange.empty,
    );
  }
}

bool _isEmojiRune(int rune) {
  return (rune >= 0x1F000 && rune <= 0x1FAFF) ||
      (rune >= 0x2600 && rune <= 0x27BF) ||
      (rune >= 0xFE00 && rune <= 0xFE0F) ||
      rune == 0x200D;
}
