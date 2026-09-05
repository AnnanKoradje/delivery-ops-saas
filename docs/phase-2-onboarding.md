# Phase 2: Controlled Company Onboarding

Public applicants submit a company application at `/apply`. This creates a `pending` company and an immutable review event, but never creates a tenant user or grants workspace access. A Super Admin with the explicit `platform.companies.manage` permission can review the application at `/platform/companies`, approve it, reject it with an audit note, suspend an active workspace, or reactivate a suspended workspace.

Approval creates default company settings and a seven-day, one-time Company Admin invitation. The stored database value is a SHA-256 token hash; the raw token is shown only once to the Super Admin for secure delivery. Invitation acceptance creates a user for the approved company, assigns only the Company Admin role, marks the invitation accepted, and records an audit event. Tenant workspace routes now require an active company; pending, suspended, and rejected states receive status-specific dashboard views rather than operational access.

The Phase 2 suite passed with 40 tests and 104 assertions, and the frontend production build completed successfully. The public form was visually inspected through the external preview. The preview terminates TLS in front of a local HTTP Laravel server, so Chrome warned before an external HTTPS-to-HTTP form submission; the actual application submission flow is covered by automated feature tests and operates normally when accessed locally over `http://127.0.0.1:8000`.

The authenticated platform dashboard was also visually verified through an HTTPS-compatible preview. The Companies navigation link, management entry point, lifecycle metrics, empty onboarding state, and controlled-onboarding security panel all rendered correctly.

The Company applications queue and the Super Admin manual company creation form were visually verified in the same preview. The status filters, create-company action, responsive form layout, and return navigation rendered correctly.
