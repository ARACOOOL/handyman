# Lion Home Services

A responsive handyman website built with **PHP 8.5, Docker, HTML, CSS, and plain JavaScript**. No frontend framework or build step is required to run it.

## Run locally

```sh
docker compose up -d --build
```

Open [http://localhost:8088](http://localhost:8088). Docker supplies PHP and Apache; PHP does not need to be installed on your computer.

To stop the site:

```sh
docker compose down
```

Requests remain in the Docker volume when the container stops. `docker compose down -v` deletes that volume and its saved requests.

## Edit the business details

```sh
cp .env.example .env
```

Set `BUSINESS_EMAIL`, `BUSINESS_PHONE`, and `SERVICE_AREA`, then run `docker compose up -d` to load them. Leave values blank to omit them. Setting an email displays the contact address; it does **not** enable outgoing email.

Set `FACEBOOK_URL` and `INSTAGRAM_URL` to the business's full HTTPS profile URLs, then run `docker compose up -d`. Footer icons link to those profiles in a new tab. With no valid profile URL, the icons remain visible with a "profile link coming soon" label and do not navigate anywhere.

The testimonial carousel in `public/index.php` summarizes five reviews Thumbtack marks as “Hired on Thumbtack.” It links to the original reviews and displays the public reviewer names and individual star ratings. The carousel supports arrow buttons, keyboard navigation, and native touch scrolling. It respects reduced motion and remains scrollable without JavaScript.

- `app/bootstrap.php`: the six suggested services and their example jobs.
- `public/index.php`: page content, FAQs, and form.
- `public/assets/site.css`: layout, green accent, light/dark tokens, and responsive rules.
- `public/assets/site.js`: theme switch, mobile menu, service selection, scroll reveals, and form feedback.
- `public/request.php`: validation and request storage.

The running Compose setup mounts `public/` and `app/`, so edits appear when you refresh. After changing Docker or Apache configuration, rebuild with `docker compose up -d --build`.

## What works

- Responsive desktop/mobile layouts and accessible navigation.
- Light and dark themes, following the system preference until manually changed.
- Expandable service details and FAQs.
- Service links that preselect the quote form.
- Server-side validation, session CSRF protection, consent checkbox, honeypot, per-session throttling, and duplicate detection.
- Persistent quote requests with a reference number, clear success/error states, and protection against losing form details after a network failure.
- Form submission and service disclosures also work without JavaScript.
- Self-hosted Manrope fonts, Phosphor icons, and optimized WebP images. No analytics or third-party browser requests.

## Read submitted requests

Requests are stored privately outside the web root at `/var/lib/lion/requests/requests.jsonl` inside the named Docker volume. Each line is one JSON record. View them locally:

```sh
docker compose exec web cat /var/lib/lion/requests/requests.jsonl
```

There is no public inbox or admin route. **Requests are saved locally; email notifications are not configured.** Connect a mail provider or CRM before relying on this site to notify the business. Contact details and the service area also need to be supplied before public launch.

The initial services are suggestions, not verified business capabilities. The hero uses a single AI-edited outdoor portrait based on the user's photographs, with an illustrative house setting. Supporting photography is also illustrative, not a portfolio of completed Lion jobs. The owner supplied the figures for happy customers, average small repair time, and owner operation. The Thumbtack rating is a snapshot of the profile on November 19, 2026 (4.9 out of 5 from 13 reviews). Update these figures in `public/index.php` as the business grows.

## Development checks

Node is needed only for asset updates and browser checks:

```sh
npm ci
npx playwright install chromium
npm test
npm run audit
```

Start Docker first. Tests use `http://localhost:8088` and create clearly named `Website QA` requests with `qa@example.com` in the local request store. The Lighthouse report is saved to `reports/lighthouse.html`.

If updating dependency versions, refresh the copied public assets with `npm run assets`. Font and icon licenses are included alongside their public assets. Image generation prompts and source notes are in `docs/imagery.md`.

## Hosting

The Docker image includes the application and public assets. The provided Compose configuration binds to localhost for development. A public deployment of the PHP application needs HTTPS, a persistent request volume and backups, real business details, and an inbox/notification integration. Throttling currently limits each browser session to five accepted requests per hour; use proxy-level rate limiting for a public service.

The `lionhs.co` server runs the application from `/opt/lion-home-services` with Docker Compose, behind Nginx and a Let's Encrypt certificate. Nginx handles HTTPS, redirects HTTP, and limits quote submissions. Apache recognizes the HTTPS header set by the server's proxy so PHP marks session cookies secure. Certbot's timer renews the certificate and a deployment hook reloads Nginx. Nginx trusts Cloudflare's published proxy IP ranges for the visitor address; those ranges must be kept current if Cloudflare changes them.

Quote requests are stored in the server's `lion-home-services_requests` Docker volume. They do not generate email notifications, and a backup for the volume has not been configured. From the server, inspect requests with `cd /opt/lion-home-services && docker compose exec web cat /var/lib/lion/requests/requests.jsonl`. This file contains private customer information. The deployment check created an identifiable `Website QA` test request.

## ChatGPT Sites design preview

[Shareable preview](https://lion-home-services-preview.tkdforever.chatgpt.site)

The separate static preview lives in `sites/lion-home-services-preview/` and is accessible to anyone with its link. It preserves the design and browser interactions, but the quote form is visibly disabled because Sites does not run PHP. No saved requests or session tokens are included.

To refresh the preview from the running local PHP app:

```sh
node scripts/export-sites-preview.mjs
```

Publish that preview directory using its existing `.openai/hosting.json` identity. The main PHP/Docker project continues to run independently.

## GitHub Actions deployment

The PHP site is deployed from the `main` branch of [ARACOOOL/handyman](https://github.com/ARACOOOL/handyman). A push to `main` builds and checks the PHP image in GitHub Actions, then sends that exact revision to the server. The workflow can also be started manually from the repository's Actions tab. The server builds the new image before changing the running files, checks the local health endpoint, and attempts to restore the previous version if activation fails. Deployments are serialized.

The deployment uses the repository secrets `DEPLOY_HOST`, `DEPLOY_SSH_KEY`, and `DEPLOY_KNOWN_HOSTS`. Its dedicated SSH key is restricted to the server's installed `/usr/local/sbin/lion-deploy` command. `scripts/deploy-server.sh` is the source for that command; changes to it require a separate, reviewed server installation. Keep the key and server configuration private. The existing `.env` and `lion-home-services_requests` volume are preserved; deployments do not back up requests or configure notifications.

The server's Nginx proxy sets `X-Forwarded-Proto: https`; Apache uses that trusted proxy header to set secure session cookies. The application port must remain bound to localhost so external visitors cannot bypass the proxy. The Sites preview remains a separate repository and is not included in this workflow.
