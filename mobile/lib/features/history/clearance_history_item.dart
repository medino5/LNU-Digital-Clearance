class ClearanceHistoryItem {
  const ClearanceHistoryItem({
    required this.id,
    required this.semester,
    required this.status,
    required this.createdAt,
    required this.completedAt,
    required this.totalSignatures,
    required this.approvedSignatures,
    required this.rejectedSignatures,
    required this.pendingSignatures,
  });

  final int id;
  final String semester;
  final String status; // "pending", "completed", "cancelled"
  final DateTime createdAt;
  final DateTime? completedAt;
  final int totalSignatures;
  final int approvedSignatures;
  final int rejectedSignatures;
  final int pendingSignatures;

  factory ClearanceHistoryItem.fromJson(Map<String, dynamic> json) {
    final createdAt = DateTime.tryParse(
      (json['created_at'] ?? '').toString(),
    );
    final completedAt = DateTime.tryParse(
      (json['completed_at'] ?? '').toString(),
    );

    return ClearanceHistoryItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      semester: (json['semester'] as String?) ?? '',
      status: (json['status'] as String?) ?? 'pending',
      createdAt:
          createdAt ?? DateTime.fromMillisecondsSinceEpoch(0, isUtc: true),
      completedAt: completedAt,
      totalSignatures: (json['total_signatures'] as num?)?.toInt() ?? 0,
      approvedSignatures: (json['approved_signatures'] as num?)?.toInt() ?? 0,
      rejectedSignatures: (json['rejected_signatures'] as num?)?.toInt() ?? 0,
      pendingSignatures: (json['pending_signatures'] as num?)?.toInt() ?? 0,
    );
  }
}
