---
title: "Deploy plan — MarineLog"
created_at: 2026-06-07T00:00:00Z
based_on:
  - context/foundation/infrastructure.md
  - context/foundation/tech-stack.md
---

Goal
----
Wykonać pierwsze produkcyjne wdrożenie MVP `MarineLog` korzystając z Hetzner + Coolify, przy użyciu GitHub Actions jako CI i frontu CDN (Cloudflare) dla globalnych użytkowników.

Assumptions
-----------
- Tech stack: PHP / Laravel (see context/foundation/tech-stack.md).
- Chosen infra: Hetzner droplet running Coolify (self-host).  
- CI: GitHub Actions will run tests and trigger Coolify/GitHub integration.  
- Storage: Hetzner Spaces or Cloudflare R2 for user media.  
- Database: Managed on the same Hetzner host (Postgres or MariaDB) or an external managed DB if preferred.

Prerequisites (what to prepare before first deploy)
---------------------------------------------------
1. Hetzner account and an available droplet (2 vCPU / 4GB recommended).  
2. Root or sudo access to the droplet and DNS control for the domain.  
3. Domain owner: `marine-log.michal-komsa.xyz` managed at OVH (you will point records).
3. GitHub repository with `main` branch and branch protection rules.  
4. Coolify installed on the droplet and reachable via its admin UI.  
5. GitHub App / webhook or deploy key configured between GitHub and Coolify (or use Coolify's GitHub integration).  
6. Cloudflare account (optional but recommended) with the domain added for global CDN and WAF.  
7. Hetzner Spaces or Cloudflare R2 bucket, credentials ready.  
8. Secrets: GitHub Actions Secrets + Coolify env vault values (APP_KEY, DB_URL, S3 credentials, etc.).

High-level plan (ordered steps)
-------------------------------
1. Provision droplet and install Coolify.  
2. Configure DNS and Cloudflare for the domain (set Cloudflare in front of the origin).  
3. Create managed object storage (Spaces/R2) and test access.  
4. Create a database instance (local managed on droplet or external managed DB).  
5. Configure Coolify app pointing to GitHub repo and set build/release commands.  
6. Add environment variables and secrets into Coolify and GitHub Actions.  
7. Add minimal GitHub Actions workflow: run tests, build assets, and notify Coolify (or trigger deploy webhook).  
8. Run an initial deploy to staging/preview and test end-to-end flows (auth, uploads, migrations).  
9. Run production deploy (merge to `main`) and verify health checks, logs, and CDN caching.  
10. Enable scheduled backups and monitoring; bake a quick rollback runbook.

Concrete steps & commands
-------------------------

1) Provision Hetzner droplet (example using `hcloud`):

```bash
# create droplet (adjust type/region)
hcloud server create --name marinelog-coolify --type cx21 --image ubuntu-22.04 --ssh-key <your-key>
```

2) Install Coolify (follow official instructions; example install via Docker Compose):

```bash
# on the droplet
curl -fsSL https://raw.githubusercontent.com/coollabsio/coolify/master/scripts/install.sh | bash
# follow prompts and secure the admin user
```

3) Create Hetzner Spaces and keys (console) or use Cloudflare R2.

4) Create DB (Postgres example with Docker on droplet) or use managed DB:

```bash
# docker run --name marinelog-db -e POSTGRES_PASSWORD=secret -e POSTGRES_USER=ml_user -e POSTGRES_DB=marinelog -p 5432:5432 -d postgres:15
```

5) Configure Coolify App
- In Coolify admin: create new app -> connect GitHub repo -> set build command (composer install...), set release command to run migrations.

Suggested build/release commands for Laravel (Coolify settings):

Build command:
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run prod # if you build frontend assets
```

Release command:
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

6) GitHub Actions minimal workflow (place in `.github/workflows/ci-deploy.yml`):

```yaml
name: CI Deploy
on:
  push:
    branches: [ main ]
jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v3
        with:
          php-version: '8.2'
      - name: Install deps
        run: composer install --no-interaction --prefer-dist --optimize-autoloader
      - name: Run tests
        run: composer test
  deploy:
    needs: tests
    runs-on: ubuntu-latest
    steps:
      - name: Trigger Coolify Deploy
        run: |
          curl -X POST -H "Content-Type: application/json" -d '{"ref":"main"}' "$COOLIFY_DEPLOY_WEBHOOK"
        env:
          COOLIFY_DEPLOY_WEBHOOK: ${{ secrets.COOLIFY_DEPLOY_WEBHOOK }}
```

7) Run staging deploy and smoke tests
- Verify login, create observation, upload media, public index performance.  
- Check logs via Coolify UI and via SSH (`docker logs` / `journalctl`).

8) Production deploy and verification
- Merge to `main` (protected) -> GitHub Actions run -> Coolify deploy -> verify.

Rollback and backups
--------------------
- Database: enable nightly DB dumps to object storage and keep 7-day retention.  
- Application rollback: deploy previous tag via Coolify UI or trigger webhook with previous commit SHA.  
- Document runbook: steps to restore DB from snapshot, point DNS to standby, and redeploy known-good image.

Post-deploy tasks
-----------------
- Configure Cloudflare caching rules and page rules for media and API paths.  
- Add uptime monitoring (UptimeRobot / Cloudflare Healthchecks) and alerting.  
- Automate weekly backup verification to ensure snapshots are restorable.

Domain & DNS (OVH)
------------------
- You will point `marine-log.michal-komsa.xyz` from OVH to the Hetzner droplet. Two recommended approaches:
  - Use Cloudflare as CDN/WAF: change your domain's nameservers at OVH to Cloudflare's nameservers, then in Cloudflare create an A record for `marine-log` pointing to the droplet IP and enable proxying (orange cloud). This gives global CDN, TLS, and WAF.
  - Use OVH DNS directly: create an A record for `marine-log` pointing to the droplet IP and a CNAME for `www` to `marine-log`. Configure TLS on Coolify/Let's Encrypt. TTL can be set low (e.g., 300s) during cutover.

- After DNS changes, verify propagation (`dig +short A marine-log.michal-komsa.xyz`) and then obtain/verify TLS certs (Cloudflare will provide TLS if proxied; otherwise use Let's Encrypt in Coolify).

Specific DNS reminder:
- While switching nameservers to Cloudflare, remember to copy any required OVH DNS records (MX, TXT) into Cloudflare to avoid downtime for email or verification TXT records.
Notes & open questions
----------------------
- If you expect frequent background workers, consider running a small supervisor on the droplet (or use an external worker host); Coolify can run services but long-running background jobs may need attention.  
- If global latency is critical, consider pairing Cloudflare Workers for edge caching of public content.

References
----------
- `context/foundation/infrastructure.md`  
- `context/foundation/tech-stack.md`
