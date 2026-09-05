# Phase 1: Tenant-Safe Foundation

| Table | Purpose | Tenant boundary |
|---|---|---|
| `companies` | SaaS tenant identity, lifecycle status, and onboarding metadata. | Platform-managed; no operational data. |
| `users` | Authenticated identities. Tenant users belong to one company; platform identities have no `company_id`. | `company_id` identifies the tenant boundary. |
| `company_settings` | Company-specific terminology and operational preferences. | Queries receive a company context global scope. |
| `roles`, `permissions` | Reusable role and capability catalogue. | Global metadata. |
| `role_assignments`, `permission_role` | Scoped permission assignment. | A company role assignment must match the user's company. |

Every tenant-owned model uses the `BelongsToCompany` concern. It adds a company global scope when a request has an authenticated company context and rejects cross-company creation attempts. Tenant routes use `company.context` followed by `tenant`; policies then validate both ownership and permission. Platform users are intentionally excluded from tenant routes and receive no broad authorization bypass.

The application targets PostgreSQL through Laravel's normal `DB_CONNECTION=pgsql` configuration. Before production migrations and seeding, configure `PLATFORM_SUPER_ADMIN_NAME`, `PLATFORM_SUPER_ADMIN_EMAIL`, and `PLATFORM_SUPER_ADMIN_PASSWORD`. The seeder creates a Super Admin only when an email and password are supplied, avoiding default credentials in source control.

## Visual verification

The local preview was checked with a temporary platform-only bootstrap account. The protected dashboard rendered its sidebar navigation, platform metrics, empty company review state, Phase 2 onboarding cue, and tenant-isolation security panel. The temporary local preview account is not a source-controlled credential and is created only when explicit environment values are supplied to the seeder.

## Quality checks

Laravel Pint completed with no remaining style issues. The full Laravel suite passed with 32 tests and 75 assertions, including login, password management, Super Admin access, company scope enforcement, IDOR-resistant tenant resource access, responsive dashboard markup, and intentionally disabled public registration. The Vite production build also completed successfully. A real Chromium pass at a 390px by 844px viewport confirmed no horizontal overflow, hid the desktop sidebar, exposed the mobile menu trigger, opened a 320px drawer containing the Overview navigation, and closed the drawer successfully.


## Mobile-width QA

The local script `scripts/mobile-qa.mjs` uses Chromium at a real 390px by 844px viewport against the authenticated local dashboard. It confirmed no horizontal overflow, correct hiding of the desktop sidebar, presence of the mobile trigger, a 320px navigation drawer containing Overview, and successful drawer close behavior.
