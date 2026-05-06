# Backend Test Explanations

This file explains why tests are placed in `tests/Unit` or `tests/Feature`, and what each test protects.

`tests/Unit` is used for isolated model, support, formatter, PDF, and payload-builder behavior. These tests may use factories and the test database, but they do not verify a full HTTP request/response flow.

`tests/Feature` is used for behavior that crosses Laravel routes, controllers, middleware, authentication, validation, sessions, views, database writes, downloads, or API responses.

## Unit Tests

### AdminClearanceDetailBuilderTest

- `builder_returns_student_snapshot_steps_and_flat_timeline_for_admin_history`: Verifies the admin detail payload is built from clearance snapshots and step events, not stale live account identity.

### StudentClearancePayloadBuilderTest

- `builder_returns_student_snapshot_and_step_summary_for_the_mobile_app`: Verifies the mobile payload includes student details, clearance progress, and step summaries in the shape the app expects.

### StudentNameFormatterTest

- `it_parses_and_recomposes_common_student_name_formats`: Verifies structured name parsing, middle initials, and extensions for student account creation and search.

### SupportServicesTest

- `simple_pdf_document_outputs_valid_pdf_header_and_trailer`: Verifies the lightweight PDF writer produces a real PDF-like document structure.
- `simple_pdf_document_escapes_parentheses_and_backslashes`: Verifies PDF text escaping so names or labels with special characters do not break the PDF.
- `simple_pdf_wrapped_text_returns_lower_y_position`: Verifies wrapped PDF text advances the cursor so later content does not overlap.
- `clearance_pdf_service_generates_file_under_clearances_folder`: Verifies completed clearance PDFs are generated in the expected storage path.
- `clearance_pdf_service_uses_fallback_name_when_reference_is_missing`: Verifies the PDF generator still creates a file if a reference number is absent.
- `excel_exporter_creates_summary_and_program_sheets`: Verifies the completed-clearance Excel export contains the summary sheet and program sheets.
- `excel_exporter_sanitizes_invalid_sheet_name_characters`: Verifies invalid Excel worksheet characters are removed from program sheet names.
- `excel_exporter_keeps_duplicate_program_sheet_names_unique`: Verifies long or duplicate worksheet names stay unique after Excel's 31-character limit.
- `excel_exporter_sorts_program_sheets_by_program_code`: Verifies report sheets are ordered predictably by program code.

### UserAndOfficeModelTest

- `admin_user_gets_admin_portal_route_and_label`: Verifies admin users resolve to the admin dashboard and label.
- `office_user_can_access_office_portal_without_extra_checks`: Verifies office users can enter the office portal.
- `student_without_designation_has_no_web_portal_route`: Verifies ordinary students cannot use the web office/admin portal.
- `student_with_active_office_designation_can_access_office_portal`: Verifies student designation holders can access the office dashboard.
- `inactive_designation_assignment_does_not_grant_office_access`: Verifies released designation assignments do not grant access.
- `student_formatted_name_uses_structured_parts_before_fallback`: Verifies structured name fields drive student display names.
- `non_student_formatted_name_uses_account_name`: Verifies staff/admin names use the account display name.
- `student_display_name_delegates_to_user_formatted_name`: Verifies student profile display uses the linked user's structured name.
- `student_year_level_labels_cover_standard_and_unknown_years`: Verifies year level labels handle normal and unexpected values.
- `office_account_form_type_options_only_show_staff_types_for_new_accounts`: Verifies new staff accounts cannot be created under student-led office types.
- `office_account_form_type_options_keep_legacy_student_led_type_when_editing`: Verifies older records with legacy student-led types can still be edited safely.
- `office_account_scope_requirements_are_based_on_office_type`: Verifies program/year/global scope rules for office account types.
- `office_account_scope_label_and_summary_reflect_program_scope`: Verifies program-scoped office accounts display their program scope.
- `office_account_scope_label_and_summary_reflect_year_level_scope`: Verifies year-level office accounts display their year scope.
- `global_office_account_summary_is_global`: Verifies global office accounts do not show program/year scope.
- `designation_display_names_use_program_and_year_context`: Verifies designation names use program organization and year-level labels.
- `non_student_designation_accepts_any_staff_office_account`: Verifies staff pool accounts are eligible for non-student designations.
- `student_led_designation_rejects_staff_office_account`: Verifies staff cannot be assigned to student-only designations.
- `program_treasurer_designation_accepts_student_from_same_program_only`: Verifies program treasurer eligibility is program-specific.
- `year_level_designation_accepts_student_from_same_year_only`: Verifies year treasurer eligibility is year-level-specific.
- `non_student_designation_rejects_student_record`: Verifies students are not eligible for staff-only designations.
- `active_office_designations_relationship_only_returns_active_assignments`: Verifies the active designation relationship filters out released assignments.
- `user_factory_password_is_usable_for_authentication_checks`: Verifies generated users can authenticate with the test password.

## Feature Tests

### AdminActionSearchTest

- `admin_pages_include_global_action_search_items`: Verifies the global admin action search is rendered across admin pages.
- `global_action_search_targets_exist_on_admin_pages`: Verifies each search result points to an existing admin page or form target.

### AdminClearanceDetailTest

- `admin_can_fetch_completed_clearance_detail_json`: Verifies admins can load completed-clearance details as JSON.
- `admin_detail_endpoint_rejects_non_admin_users`: Verifies non-admins cannot access admin clearance details.
- `admin_detail_endpoint_only_exposes_completed_history_records`: Verifies in-progress clearances are not exposed as completed history.

### AdminClearanceReportExportTest

- `clearance_history_page_uses_explicit_report_filters`: Verifies history export UI requires semester and academic year filters.
- `admin_can_download_completed_clearance_excel_report_for_selected_period`: Verifies admins can download a valid completed-clearance XLSX.
- `export_redirects_with_validation_when_required_filters_are_missing`: Verifies missing report filters return validation errors.
- `export_redirects_with_message_when_semester_and_academic_year_do_not_match`: Verifies mismatched period filters do not export incorrect data.
- `export_redirects_with_message_when_selected_period_has_no_completed_clearances`: Verifies no-data report selections show a friendly message.
- `export_redirects_with_message_when_report_file_cannot_be_created`: Verifies export failures are handled without a broken download.

### AdminControllerValidationTest

- `program_index_lists_programs_ordered_by_code`: Verifies the programs page orders records by program code.
- `program_create_rejects_missing_organization_name`: Verifies program creation requires an organization name.
- `semester_create_rejects_invalid_academic_year_format`: Verifies academic years must use `YYYY-YYYY`.
- `semester_update_activation_deactivates_other_semesters`: Verifies only one semester is active after an update.
- `students_page_searches_by_first_name`: Verifies student search works by first name and remains SQLite-compatible in CI.
- `students_page_searches_by_last_name`: Verifies student search works by last name and remains SQLite-compatible in CI.
- `students_page_filters_by_program`: Verifies student list filtering by program.
- `students_page_filters_by_year_level`: Verifies student list filtering by year level.
- `student_create_rejects_emoji_in_name_fields`: Verifies student names reject emoji/special characters.
- `student_update_without_password_preserves_existing_password`: Verifies blank password on edit does not reset a student password.
- `office_accounts_page_filters_by_office_type`: Verifies office account table filtering by office type.
- `office_accounts_page_filters_university_wide_accounts`: Verifies global office accounts can be filtered separately.
- `office_account_create_trims_name_and_clears_global_scope_fields`: Verifies global office account creation ignores program/year fields.
- `office_account_update_without_password_preserves_existing_password`: Verifies blank password on office edit preserves the current password.
- `designation_assignment_requires_a_user_selection`: Verifies assignment form validation catches missing users.
- `designation_assignment_to_same_user_does_not_duplicate_history`: Verifies reassigning the same holder does not create duplicate assignment rows.
- `admin_cannot_create_office_account_with_duplicate_username`: Verifies office account usernames remain unique.

### AdminManagementTest

- `admin_can_create_a_program_student_and_scoped_staff_office_account`: Verifies the main admin creation flow for programs, students, and staff accounts.
- `program_create_normalizes_code_and_collapses_extra_spaces`: Verifies program input normalization.
- `program_create_rejects_case_insensitive_duplicate_codes`: Verifies duplicate program codes are blocked.
- `program_create_rejects_unsupported_symbols_in_name_and_organization`: Verifies program/org names reject unsupported characters.
- `program_update_normalizes_fields_and_keeps_same_code_record_valid`: Verifies program edits normalize input without falsely failing uniqueness.
- `admin_dashboard_student_forms_show_the_7_digit_student_id_constraints`: Verifies the UI communicates student ID rules.
- `student_create_rejects_non_digit_student_ids_and_keeps_old_input`: Verifies invalid student IDs fail validation and preserve safe input.
- `student_create_rejects_future_enrollment_year_prefixes`: Verifies future-year student IDs are blocked.
- `updating_student_id_also_updates_the_linked_student_username`: Verifies student ID edits keep the login username synchronized.
- `activating_a_new_semester_turns_off_the_previous_one`: Verifies active semester switching.
- `program_scoped_office_type_requires_program_scope`: Verifies scoped office account validation.
- `global_office_type_can_be_saved_without_program_or_year_scope`: Verifies global office account creation.
- `admin_dashboard_shows_designation_assignment_section_with_filters`: Verifies routing assignment controls render.
- `admin_dashboard_highlights_common_admin_actions_and_clearer_filters`: Verifies dashboard UI highlights important actions.
- `admin_dashboard_renders_real_chart_data_when_clearances_exist`: Verifies dashboard chart data reflects real clearances.
- `admin_can_open_the_new_route_based_admin_pages`: Verifies split admin pages are routable.
- `admin_sidebar_highlights_each_current_route_based_page`: Verifies sidebar active state across admin pages.
- `admin_can_reassign_designation_to_an_eligible_office_user`: Verifies valid designation reassignment.
- `admin_can_assign_matching_student_to_a_student_led_designation`: Verifies student-led designation assignment.
- `admin_cannot_assign_an_ineligible_office_user_to_a_designation`: Verifies invalid staff/designation pairing is blocked.
- `admin_cannot_assign_student_to_a_non_student_designation`: Verifies students cannot hold staff-only roles.
- `admin_cannot_assign_student_to_a_different_program_designation`: Verifies program-restricted student assignments are enforced.
- `reassigning_the_current_designation_holder_does_not_create_duplicate_history`: Verifies duplicate assignment history is avoided.
- `student_role_cannot_open_admin_dashboard`: Verifies admin middleware rejects student users.

### AdminPaginationTest

- `admin_students_pagination_and_filters_work_together`: Verifies student pagination preserves filters.

### ClearanceWorkflowTest

- `student_clearance_creation_is_routed_to_five_required_offices`: Verifies clearance initiation creates the required routing steps.
- `clearance_snapshots_do_not_change_after_student_profile_edits`: Verifies historical clearance snapshots do not mutate after profile edits.
- `completed_student_can_download_clearance_pdf_after_all_signatories_approve`: Verifies PDF download unlocks only after full approval.
- `flagged_step_can_be_resubmitted_without_resetting_other_approved_steps`: Verifies flagged-step resubmission preserves other approvals.
- `office_dashboard_only_shows_students_routed_to_that_office`: Verifies office dashboard scope isolation.
- `office_dashboard_shows_empty_state_when_user_has_no_active_designation`: Verifies unassigned office users see a clear empty state.
- `office_dashboard_uses_step_snapshot_label_for_designation_cards`: Verifies office cards use step snapshot labels.
- `office_user_cannot_process_a_step_owned_by_a_different_office`: Verifies office users cannot approve unrelated steps.
- `office_dashboard_shows_validation_feedback_when_flag_reason_is_missing`: Verifies flagging requires a reason.
- `processed_approved_step_keeps_view_and_undo_actions_on_office_dashboard`: Verifies approved steps keep view and undo actions.
- `office_user_can_undo_an_approved_step_and_reopen_the_clearance`: Verifies approval undo works.
- `undoing_approval_on_completed_clearance_clears_completion_artifacts`: Verifies undoing a completed clearance deletes completion/PDF state.
- `processed_flagged_step_keeps_view_action_and_flag_reason_on_office_dashboard`: Verifies flagged steps show reason and view action.
- `office_user_can_undo_a_flagged_step_and_reopen_the_clearance`: Verifies flag undo works.
- `any_active_holder_of_a_designation_can_process_the_step`: Verifies processing authorization is designation-based.
- `student_designation_holder_can_process_matching_step`: Verifies student officers can process their assigned designation.
- `student_can_initiate_clearance_even_when_required_designation_has_no_active_holder`: Verifies unassigned designations do not block initiation.
- `unassigned_designation_step_becomes_visible_after_a_later_assignment`: Verifies later assignments reveal existing routed steps.

### OfficeDesignationBackfillTest

- `core_system_seeder_backfills_office_accounts_into_designations`: Verifies seeders connect existing office accounts to designation assignments.
- `backfill_is_idempotent_for_shared_designations`: Verifies rerunning backfill does not duplicate assignments.

### OfficeDesignationFoundationTest

- `office_user_can_hold_multiple_active_designations`: Verifies one user can hold several designations.
- `unassigned_designation_has_no_active_holders`: Verifies unassigned designations correctly report no active holders.

### PortalAndStudentApiEdgeTest

- `shared_login_page_uses_digital_clearance_portal_copy`: Verifies login page wording uses the requested product name.
- `invalid_web_login_keeps_username_and_does_not_authenticate`: Verifies failed web login preserves username and denies authentication.
- `student_without_designation_cannot_use_web_portal_login`: Verifies ordinary students cannot sign into web portals.
- `student_designation_holder_can_use_web_portal_login`: Verifies student officers can sign into the office dashboard.
- `logout_clears_web_session_and_redirects_to_login`: Verifies web logout clears session state.
- `mobile_login_requires_student_id_and_password_fields`: Verifies mobile API login validation.
- `mobile_login_rejects_office_account_even_with_matching_username`: Verifies the mobile app is student-only.
- `mobile_logout_deletes_current_token_only`: Verifies logout removes only the active mobile token.
- `me_endpoint_returns_404_when_student_profile_is_missing`: Verifies malformed student accounts return a clear API error.
- `current_clearance_payload_handles_no_active_semester`: Verifies the mobile app gets a safe empty payload when no semester is active.

### PortalRoutingTest

- `guest_root_redirects_to_shared_portal_login`: Verifies guests land on login.
- `authenticated_admin_root_redirects_to_admin_dashboard`: Verifies admin root routing.
- `authenticated_office_root_redirects_to_office_dashboard`: Verifies office root routing.
- `authenticated_admin_can_view_shared_login_page`: Verifies logged-in admins can still view login page.
- `shared_login_routes_admin_to_admin_dashboard`: Verifies admin login routing.
- `authenticated_admin_can_switch_to_office_account_from_shared_login`: Verifies an authenticated session can be replaced by another valid login.
- `shared_login_rejects_student_accounts`: Verifies ordinary students are rejected from web login.
- `shared_login_routes_student_designation_holder_to_office_dashboard`: Verifies student officers reach office dashboard.
- `shared_login_renders_inline_validation_feedback_for_missing_fields`: Verifies login validation feedback.
- `legacy_portal_login_urls_redirect_to_shared_login`: Verifies old login URLs still work.
- `admin_dashboard_shows_logout_and_switch_actions`: Verifies admin dashboard session actions render.
- `office_dashboard_shows_logout_and_switch_actions`: Verifies office dashboard session actions render.

### StudentAuthApiTest

- `student_can_log_in_with_student_id_and_receive_profile_payload`: Verifies mobile login success and profile payload.
- `student_login_rejects_invalid_credentials`: Verifies invalid mobile credentials return 401.
- `student_login_validation_errors_return_message_and_errors_payload`: Verifies mobile validation format.
- `student_login_replaces_previous_mobile_tokens`: Verifies new mobile login invalidates old tokens.
- `authenticated_student_can_load_their_profile_from_me_endpoint`: Verifies authenticated profile loading.

### UatDatabaseSeederTest

- `default_database_seeder_only_loads_the_core_system_records`: Verifies the normal seeder stays small and core-only.
- `uat_database_seeder_adds_a_balanced_1400_student_roster`: Verifies UAT seeding creates 200 students per program plus the demo student.
