Map<String, dynamic> buildTestPayload({
  String clearanceStatus = 'in_progress',
  bool includeClearance = true,
}) {
  return {
    'student': {
      'name': 'John A. Doe',
      'student_id_number': '2302314',
      'date_of_birth': '2005-03-14',
      'year_level': 3,
      'year_level_label': '3rd Year',
      'program': {
        'id': 1,
        'code': 'BSIT',
        'name': 'Bachelor of Science in Information Technology',
        'org_name': 'DIGITS',
      },
    },
    'active_semester': {'id': 1, 'label': '2nd Semester 2024-2025'},
    'clearance': includeClearance
        ? {
            'id': 1,
            'status': clearanceStatus,
            'reference_number': clearanceStatus == 'completed'
                ? 'CLR-1-00001-1234'
                : null,
            'completed_at': clearanceStatus == 'completed'
                ? '2026-03-15T10:30:00.000000Z'
                : null,
            'pdf_available': clearanceStatus == 'completed',
            'counts': {
              'total': 5,
              'approved': clearanceStatus == 'completed' ? 5 : 2,
              'flagged': clearanceStatus == 'flagged' ? 1 : 0,
              'awaiting_action': clearanceStatus == 'completed' ? 0 : 3,
            },
            'steps': [
              {
                'id': 1,
                'status': 'approved',
                'remarks': null,
                'signed_at': '2026-03-15T08:30:00.000000Z',
                'office_label': 'DIGITS Academic Organization Treasurer',
                'office_type': 'acad_org_treasurer',
                'scope_label': 'BSIT',
                'can_resubmit': false,
                'last_event': {
                  'action': 'approved',
                  'remarks': null,
                  'created_at': '2026-03-15T08:30:00.000000Z',
                },
              },
              {
                'id': 2,
                'status': clearanceStatus == 'flagged'
                    ? 'flagged'
                    : (clearanceStatus == 'completed'
                          ? 'approved'
                          : 'awaiting_action'),
                'remarks': clearanceStatus == 'flagged'
                    ? 'Please settle your concern first.'
                    : null,
                'signed_at': clearanceStatus == 'completed'
                    ? '2026-03-15T09:15:00.000000Z'
                    : null,
                'office_label': '3rd Year Level Organization Treasurer',
                'office_type': 'year_level_treasurer',
                'scope_label': '3rd Year',
                'can_resubmit': clearanceStatus == 'flagged',
                'last_event': clearanceStatus == 'flagged'
                    ? {
                        'action': 'flagged',
                        'remarks': 'Please settle your concern first.',
                        'created_at': '2026-03-15T09:15:00.000000Z',
                      }
                    : null,
              },
            ],
          }
        : null,
  };
}
