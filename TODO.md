# Apparix To-Do

## Purchased plugin updates

Status: Implemented in 1.4.0
Release: Apparix 1.4.0.

### Implementation

- Include installed plugin slugs and manifest versions in the existing licensed update check.
- Resolve current plugin releases from protected Apparix product download packages.
- Match paid plugin orders to the owner of the requesting Apparix site license.
- Support explicit entitlements using one-way license-key hashes for manually provisioned installations.
- Show available plugin updates and purchase-linking problems separately on Admin > Updates.
- Download and install an authorized plugin update with one click.
- Require CSRF and administrator authentication for installation.
- Verify the requested slug, version, server metadata, and SHA-256 package hash.
- Reject ZIP traversal paths, symbolic links, duplicate paths, malformed manifests, oversized archives, and invalid PHP.
- Stage updates outside the live plugin directory, retain rollback backups, replace atomically, and preserve plugin settings and activation state.
- Keep independently installed plugins out of core update writes; continue treating bundled Stripe as core code.
- Report plugin update outcomes without storing raw site license keys.

### Acceptance Criteria

- Only plugins installed on the requesting site are included in update checks.
- A completed purchase linked to the site license permits an update; an unowned plugin cannot be downloaded.
- The Updates sidebar badge and Updates page include available purchased plugin updates.
- Plugin settings and active/inactive state are unchanged after updating.
- A failed install restores the prior plugin files and reports a useful error.
- Installed files use web-server-safe permissions and do not become root-owned.
- Core updates do not overwrite independently installed plugins.
- Tests cover authorization, integrity validation, traversal rejection, atomic replacement, rollback, permissions, HTTP failures, and a real plugin package.

## One-click sequential catch-up updates

Status: Pending
Release: Bundle with the next planned Apparix update.

### Problem

Installations that are several releases behind can only see and install the next sequential release. Administrators must repeatedly check for updates and install each version without knowing how many releases remain.

### Recommended Design

Keep the existing sequential packages and migrations, but present them as one **Update All** action. The browser should orchestrate one install request per release so each newly installed updater version is loaded before the next step. Do not replace the release chain with one universal archive, because skipping version-specific migrations and transforms increases recovery risk.

### Implementation

- Extend the update-check API to return the latest compatible version, the ordered compatible update path, and the number of releases behind.
- Show clear version distance in Admin > Updates, for example: `1.2.6 -> 1.3.9 (13 releases behind)`.
- Keep the existing single-release install option for administrators who do not want to update the entire chain.
- Add an **Update All** action that installs each release in order using a separate authenticated request per step.
- Display stable progress with the current step, total steps, version being installed, and the final target version.
- Re-check the available path after every successful step so edition, PHP-version, and release availability rules remain authoritative.
- Create and retain a catch-up recovery backup before the first step; preserve existing per-release migration safeguards.
- Stop immediately on a failed step, identify the failed version, preserve logs/backups, and allow a safe retry from the installed version.
- Prevent concurrent update chains with a server-side lock and an idempotent client operation identifier.
- Keep maintenance mode scoped to active install steps and always clear it after success or handled failure.
- Do not claim the site is current until a fresh update check confirms no compatible releases remain.

### Acceptance Criteria

- An installation several releases behind displays the correct number of available sequential releases and the latest compatible target.
- One administrator action installs all required releases in ascending order without skipping migrations.
- Each install step verifies package authorization, checksum, compatibility, extraction, migrations, and resulting version before continuing.
- A failure stops the chain without attempting later packages and provides a useful retry/recovery message.
- Refreshing or reopening the page during a chain cannot start a conflicting installation.
- After completion, the installed version and a fresh server check both confirm the site is current.
- Tests cover update-path calculation, edition/PHP filtering, interrupted chains, stale locks, checksum failures, migration failures, and successful multi-version catch-up.

## Admin order badge live refresh

Status: Pending
Release: Bundle with the next planned Apparix update; do not release a standalone version for this fix.

### Problem

The Orders sidebar badge is calculated in `app/Views/layouts/admin.php` only when an admin page renders. New-order alerts are sent separately through Ntfy and email, so they do not update an already-open admin page. The badge remains stale until the page is refreshed or another admin page is loaded.

### Implementation

- Add an authenticated, read-only admin JSON endpoint that returns the current `pending`, `processing`, and combined actionable order counts.
- Reuse `Order::countOrders()` so the endpoint and server-rendered badge use the same counting rules.
- Render a stable Orders badge element in the admin sidebar even when its count is zero, hiding it visually until needed.
- Add dedicated Orders badge styling: solid red background, white number, compact circular/pill shape, stable dimensions, and enough width for multi-digit counts.
- Update `public/assets/js/admin.js` to fetch the count immediately and approximately every 15 seconds while an admin page is visible.
- Refresh immediately when the tab regains focus or becomes visible.
- Stop polling after an authentication failure and use bounded backoff for temporary network/server failures.
- Update the badge text and visibility without reloading the page; include an `aria-live` label for accessibility.
- Keep order creation independent from badge refresh failures. Do not couple this to checkout, payment processing, Ntfy, or email delivery.
- Do not introduce WebSocket or SSE infrastructure solely for this badge. Revisit true server push only if Apparix later gains a shared real-time event system.

### Acceptance Criteria

- A successfully committed order appears in the open admin sidebar badge within 15 seconds without a page refresh.
- Changing an order out of `pending` or `processing` updates the badge within 15 seconds.
- The displayed count remains `pending + processing`, matching current behavior.
- The count appears as a clearly visible red badge with the number of actionable orders inside it; the badge is hidden when the count is zero.
- Unauthenticated and expired admin sessions cannot access the count endpoint.
- Multiple open admin tabs do not generate errors or alter order data.
- Endpoint or network failures do not affect checkout, payment, or order creation.
- Desktop and mobile admin sidebars display the updated count correctly.
- PHP lint, endpoint authorization tests, count tests, and browser-level badge refresh tests pass.

## Amazon tracking carrier

Status: Pending
Release: Bundle with the next planned Apparix update.

### Implementation

- Add **Amazon** (Amazon Logistics) to the shipping-carrier selector used when entering or sending order tracking information.
- Store and display the carrier consistently on the admin order page, customer order page, shipment notifications, and tracking emails.
- Generate a valid Amazon tracking link when possible, while preserving manually entered tracking URLs and tracking numbers.
- Keep existing carriers and saved shipment records unchanged.

### Acceptance Criteria

- Administrators can select Amazon when marking an order as shipped or updating tracking information.
- Amazon tracking information saves successfully and appears correctly in customer-facing order details and shipment messages.
- Existing carrier selections and tracking behavior continue to work without changes.
