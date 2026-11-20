# Agent instructions for Lion Home Services

These instructions apply to this directory and its descendants. Read any more specific `AGENTS.md` before changing files in its scope. Follow the user's current instructions when they override project defaults.

## Working with the owner

- Use simple, clear English. Explain unfamiliar terms briefly when they matter.
- Before significant changes, explain what you intend to change and why.
- Make practical decisions within the requested scope. Ask only when missing information materially affects the result or authorization.
- Be honest about assumptions, limitations, and failed checks. Never claim that something works without evidence.
- Finish with a short description of the result, checks performed, and any remaining issue.

## Inspect first and preserve existing work

- Read `README.md` and the files involved before editing. Check for existing local changes, including inside the separate Sites repository.
- Use `rg` for text and file searches. Keep inspection output focused and never print secrets or customer records.
- Preserve user changes and unrelated work. Do not reset, clean, restore, stage, or commit files indiscriminately.
- Keep changes small and directly related to the request. Avoid unrelated refactors, dependency upgrades, or formatting whole files.
- Reuse existing components and conventions. Add dependencies only when they solve a concrete problem that the current stack cannot reasonably handle.
- Update relevant documentation when behavior, configuration, commands, or deployment requirements change.

## Project map and source of truth

| Path | Purpose |
| --- | --- |
| `app/bootstrap.php` | Shared initialization, session setup, helpers, and suggested services |
| `public/index.php` | Main PHP page, content, and quote form |
| `public/request.php` | Quote validation and private storage |
| `public/health.php` | Container health endpoint |
| `public/assets/site.css` | Responsive layout, theme tokens, and motion |
| `public/assets/site.js` | Progressive browser interactions |
| `docker/`, `Dockerfile`, `compose.yaml` | PHP/Apache runtime and local setup |
| `tests/site.spec.js` | Playwright browser and request checks |
| `scripts/` | Asset copying, preview export, and Lighthouse audit |
| `docs/imagery.md` | Image sources and editing notes |
| `sites/lion-home-services-preview/` | Separate managed Git repository for the static Sites preview |

The PHP application and the Sites preview are distinct deliverables. Changes can exist in either one. Inspect both before deciding what to copy or regenerate.

## Stack and local development

- Keep PHP 8.5, Docker, semantic HTML, native CSS, and plain JavaScript. Node tools support development; they are not required to serve the PHP app.
- Do not introduce a frontend framework, CSS framework, bundler, database, or service without a clear need within the task.
- Use npm and preserve `package-lock.json`. Use `npm ci` for reproducible installation.
- Docker supplies PHP; do not assume a host PHP installation.
- Start locally with `docker compose up -d --build`, then use `http://localhost:8088` unless `PORT` has been configured differently.
- Compose mounts `public/` and `app/` read-only into the container. Refresh to see source edits; rebuild after Docker or Apache configuration changes.
- Configure business details through `.env`, using `.env.example` as the template. Keep real values and secrets out of committed examples.
- Keep the development port bound to localhost. Treat public PHP hosting as a separate deployment task.

## PHP, request handling, and privacy

- Follow existing strict typing and PHP style. Keep shared behavior in small, clear helpers rather than duplicating it.
- Validate all request data on the server. Browser validation is an additional convenience, not a security boundary.
- Escape dynamic HTML with the existing `escape()` helper. Encode data appropriately for its destination; HTML escaping alone does not make JavaScript or URLs safe.
- Preserve POST-only handling, session CSRF protection, honeypot checks, consent validation, field limits, service allowlists, throttling, and duplicate detection.
- Preserve secure session settings and the security headers in the Docker configuration. Avoid inline scripts or unsafe exceptions that weaken the content security policy.
- Keep request storage outside `public/`. Preserve restrictive file permissions and the exclusive file lock around duplicate detection and writes.
- Return clear, appropriate HTTP status codes and useful public errors. Do not expose internal paths, stack traces, credentials, or submitted personal information.
- Keep form fields after recoverable errors. Preserve both JavaScript feedback and the normal no-JavaScript submission flow.
- Saved requests are stored locally; outgoing email and CRM delivery are not configured. Do not claim that an email was sent or that the business was notified.
- The current rate limit is per session. Do not describe it as protection against all public abuse; public PHP hosting needs suitable proxy-level controls.
- Never put `.env`, session data, customer submissions, credentials, or private storage into static exports, reports, logs, or deployment archives.
- Do not read customer records unless necessary for the task. Use synthetic data for checks and redact sensitive output.

## Data and process safety

- `docker compose down` preserves the request volume. **Do not use `docker compose down -v`, volume pruning, or destructive storage commands without explicit authorization to delete the affected data.**
- Do not change storage formats or move existing records without a migration and recovery plan appropriate to the task.
- Tests create identifiable QA submissions in the local request store. Clean up only records created by the current run when cleanup is needed; never truncate the store.
- Stop only temporary processes you started. Leave the owner's running services and browser sessions available.
- Do not commit, push, or deploy unless requested or already authorized for the active task. An explicit Sites publishing request authorizes its required managed-repository workflow.

## Design, accessibility, and motion

- Preserve the established design: Manrope typography, green accent, charcoal and off-white surfaces, generous spacing, and consistent rounded media.
- Use semantic CSS variables and existing components. Support system and manually selected light/dark themes.
- Keep self-hosted fonts and Phosphor icons, including their licenses. Avoid unnecessary external browser requests or new tracking.
- For design work, read and follow the user's selected design skill, currently `design-taste-frontend`, when available. Audit the existing page before redesigning it.
- Use semantic elements, logical heading order, meaningful links, real buttons, associated form labels, accessible error messages, and visible keyboard focus.
- Keep navigation, disclosures, service selection, theme controls, and the testimonial carousel usable by keyboard and touch.
- Preserve progressive enhancement: essential content and the PHP quote form must remain usable without JavaScript.
- Check contrast in both themes. Do not rely on color alone to communicate state.
- Keep layouts usable on small screens without horizontal page overflow. Check narrow tablet widths as well as standard desktop and mobile sizes.
- Respect `prefers-reduced-motion`. Keep photo hover effects subtle and limited to devices that support hover with a fine pointer.
- Reuse `.photo-frame` and `.photo-frame__media` for photo motion. Avoid competing transforms between scroll reveals, frames, and images.
- Do not add autoplay, looping motion, or parallax without a concrete purpose and an accessible reduced-motion alternative.

## Images and content integrity

- Preserve the owner's original uploaded images. Create optimized derivatives rather than modifying originals.
- The current hero is `handyman-outdoor.webp`, a single finished outdoor portrait. Preserve natural lighting and perspective; the owner rejected a cutout layered over a separate house photo.
- The approach photo is `handyman-repair.webp`, replacing the old mirror and shoe-rack image. Preserve the visible face, hands, and work area when adjusting crops.
- Inspect image crops at desktop, mobile, and narrow tablet widths, including around 778px. Do not assume a desktop crop works everywhere.
- Supply descriptive alt text and intrinsic dimensions. Prioritize the hero image and lazy-load suitable below-the-fold images.
- Optimize image size and format without visible damage. Update `docs/imagery.md` when image sources or edits change.
- Do not invent contact details, service areas, licenses, insurance, credentials, customer quotes, or business performance claims.
- Suggested services remain editable examples until confirmed by the owner. Illustrative photography must not be presented as verified completed work.
- Keep fictional testimonials visibly labeled as samples until approved real testimonials replace them. Never silently turn sample star ratings into customer endorsements.
- Preserve the provenance of owner-supplied business figures; do not describe them as independently verified.
- Keep missing social links disabled or clearly unavailable instead of inventing destinations.
- Preserve working section anchors and consistent call-to-action wording when revising copy.

## Verification

Choose checks that fit the change. Documentation-only edits need an accuracy review, not an application test run. Behavior and layout changes need relevant checks before completion.

```sh
# Install browser tooling when needed.
npm ci
npx playwright install chromium

# Start the PHP application before browser or audit checks.
docker compose up -d --build

# Check changed PHP files using their container paths.
docker compose exec web php -l /var/www/html/index.php
docker compose exec web php -l /var/www/html/request.php
docker compose exec web php -l /var/www/app/bootstrap.php

# Run relevant tests, or the full suite when the scope warrants it.
npm test
npm run audit

# Refresh copied fonts/icons after relevant dependency changes.
npm run assets
```

- Use focused Playwright checks during development; run broader checks when shared behavior or security is affected.
- Cover success, validation, duplicate submission, network failure/retry, and no-JavaScript behavior when changing request handling.
- Visually inspect affected layouts, image crops, keyboard focus, both themes, and reduced motion when changing the frontend. Screenshots and automated checks complement each other.
- Check asset loading and browser errors. Fix the underlying issue rather than weakening tests to pass.
- Reports are generated under `reports/`; browser artifacts are ignored. Do not commit generated output or customer data.
- Report exactly what ran and passed. Local success does not establish that a public deployment or external delivery integration works.

## Static Sites preview and publishing

- Reuse `sites/lion-home-services-preview/.openai/hosting.json` and its existing project identity. Do not create a replacement site unless requested.
- **Inspect preview changes before running `node scripts/export-sites-preview.mjs`.** The exporter overwrites preview HTML, CSS, JavaScript, and assets and can erase direct user edits.
- If the owner edited `dist/` and asks to publish those edits, validate and publish that version. Reconcile differences before any regeneration; never assume generated files are disposable here.
- When an export is appropriate, verify that `http://localhost:8088` serves the intended current content first. Check the exported result afterward.
- Sites hosts the static design preview; it does not execute this PHP backend. Keep its quote form visibly disabled with a clear notice that it does not send or save requests.
- Verify that the preview contains no CSRF token, active `/request.php` submission, PHP source, private records, or secrets.
- For publishing, read the installed `sites:sites` skill and use its supported source preparation, packaging, version saving, and deployment tools.
- Use the managed Sites workflow for its repository synchronization and packaging. Do not force-push or include unrelated files; package the static output and required hosting configuration only.
- Handle write credentials in memory or hidden input as prescribed by the workflow. Never print or save tokens in source, shell history, or reports.
- Inspect the actual static preview before deployment, including images, interactions, responsive layout, and disabled submission behavior.
- Preserve the existing site identity and access settings unless the task includes changing them. Wait for a successful deployment status before reporting publication as complete.
- After publishing, verify the public page and return its shareable URL. Clearly distinguish the static preview from a production PHP service.
