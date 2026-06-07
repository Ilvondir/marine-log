---
project: MarineLog
researched_at: 2026-06-07T00:00:00Z
recommended_platform: Hetzner + Coolify
runner_up: Fly.io
context_type: mvp
tech_stack:
  language: php
  framework: laravel
  runtime: php
---

## Recommendation

**Deploy on Hetzner using Coolify, with GitHub Actions as the CI driver and Cloudflare as the global CDN.**

Rationale: you already own a Hetzner server and run Coolify there, which minimizes monthly costs and keeps full operational control. For a Laravel MVP with medium users and low QPS, this stack gives fast iteration (Coolify automates builds and releases), inexpensive hosting, and the ability to colocate managed services (Databases, Spaces) on the same provider. Add Cloudflare or a similar CDN in front of Coolify to meet the global latency requirement.

## Platform Comparison

Scoring against the five agent-friendly criteria (Pass / Partial / Fail). Notes below justify each score.

| Platform | CLI-first | Managed / Serverless | Agent-readable docs | Stable deploy API | MCP / Integration | Notes |
|---|---:|---:|---:|---:|---:|---|
| Hetzner + Coolify | Partial | Partial | Partial | Partial | Fail | Self-hosted; CLI tools exist (hcloud) but several operations require SSH/GUI work; no published MCP server. Good for cost control and co-location. |
| Fly.io | Pass | Pass | Pass | Pass | Partial | PaaS with global edge VMs, strong CLI (`flyctl`) and good docs. Good for durable processes. |
| Render | Pass | Pass | Pass | Pass | Partial | PaaS with simple deploys and managed DBs; solid CLI and docs. |
| Vercel | Pass | Pass | Pass | Pass | Partial | Best for Next.js/frontends; serverless limitations for long-running processes. |
| Netlify | Pass | Pass | Pass | Pass | Partial | Suited for JAMstack; serverless only. |
| Cloudflare Workers/Pages | Pass | Pass | Pass | Pass | Pass | Edge-first, global CDN, excellent docs and `wrangler`. Not ideal for full Laravel runtime without adaptation. |

### Shortlisted Platforms

1. Hetzner + Coolify — recommended: minimal ongoing cost, full control, easy colocation of DB/storage on Hetzner; matches your expressed preference.
2. Fly.io — runner-up: excellent for small global apps and long-running processes; simpler horizontal scaling than self-hosting.
3. Render — good managed alternative with simple DB co-location and a friendly CLI.

Running anti-bias cross-check on the top recommendation (Hetzner + Coolify)...

## Anti-Bias Cross-Check: Hetzner + Coolify

### Devil's Advocate — Weaknesses
1. Single-host single point of failure: if the Hetzner droplet or its network fails, the whole app goes down unless you architect multi-region failover (complex and out of scope for MVP).  
2. Operational surface: self-hosted Coolify shifts patching, OS upgrades, Docker/security fixes and backup responsibility to you. These are non-trivial and easy to miss.  
3. Limited agent operability: many recovery and billing actions require SSH or dashboard interaction; an agent cannot complete some operations end-to-end.  
4. Scaling friction: autoscaling on self-hosted Coolify is manual — unexpected traffic spikes require operator intervention or pre-provisioned capacity.  
5. Documentation and automation gaps: Coolify and Hetzner lack the standardized MCP/agent primitives common on commercial PaaS, reducing safe automation surface.

### Pre-Mortem — How This Could Fail
Six months after launch, MarineLog suffers an extended outage during a holiday weekend. The Hetzner host receives a noisy neighbor-induced IO slowdown combined with an unattended OS security update that reboots containers. Because Coolify was self-hosted and backups were not automated, the database replica lagged and a recent migration left the schema incompatible with the old release. The team had no automatic failover; GitHub Actions ran but failed to build a fallback release due to a missing `PRODUCTION` secret. Users experienced data loss for recent uploads and public pages were down for hours. The resulting reputation damage and frantic manual recovery consumed the solo developer's time, delaying roadmap work and making growth decisions harder.

### Unknown Unknowns
- Coolify upgrade path: upgrade-related breaking changes or migrations in Coolify's release cadence that require manual intervention.  
- Hetzner region/network maintenance windows that affect latency for global users unexpectedly.  
- Behavioural differences between Coolify preview deployments and production (cache invalidation, assets paths).  
- Edge integration nuance: Cloudflare origin-protection or headers interplay with Coolify and signed URLs.  
- GitHub Actions runner limits (concurrency or minutes) affecting CI if you do heavy builds on merges.

## Operational Story
- **Preview deploys**: create branch previews via Coolify/GitHub Actions; protect by requiring Cloudflare Access or HTTP basic auth on preview apps.  
- **Secrets**: store runtime env vars in Coolify's encrypted vault; store CI-only secrets in GitHub Actions Secrets. Limit who can view secrets in the server UI.  
- **Rollback**: roll back by re-deploying a tagged commit or switching Coolify to a previous release image; plan DB migration rollbacks carefully—migrations may be irreversible.  
- **Approval**: production publish should be gated by merging to `main` (GitHub Actions) and a human review; consider a manual workflow dispatch for final release.  
- **Logs**: use Coolify's runtime logs (container stdout/stderr), `journalctl`/`docker logs` via SSH for deeper debugging, and GitHub Actions logs for CI artifacts.

### Domain

- Your domain `marine-log.michal-komsa.xyz` (hosted at OVH) should point to the Hetzner droplet. Recommended options:
  - Put Cloudflare in front (change nameservers at OVH to Cloudflare) and proxy the A record for `marine-log` to the droplet IP for CDN, TLS and WAF.  
  - Or create an A record in OVH DNS pointing `marine-log` to the droplet IP and obtain TLS via Let's Encrypt in Coolify.  

Ensure any OVH MX/TXT records are copied to Cloudflare if you switch nameservers.

## Risk Register

| Risk | Source | Likelihood | Impact | Mitigation |
|---|---|---:|---:|---|
| Single-host outage | Devil's advocate | M | H | Provision small standby instance or daily offsite backups; document recovery runbook. |
| Missed OS/Docker security updates | Devil's advocate | M | H | Automate patching windows and test upgrades in a staging instance. |
| DB migration failure on rollback | Pre-mortem | M | H | Use backward-compatible migrations and run migrations in CI against a staging DB; snapshot DB before migrate. |
| Agent automation gaps (no MCP) | Research/Unknown | H | M | Keep critical ops gated for human approval; create precise CLI scripts for agent-safe steps. |
| CDN/origin mismatch causing cache bugs | Unknown unknowns | M | M | Test Cloudflare cache rules thoroughly; use consistent asset URL strategy and signed URLs if needed. |

## Getting Started (specific to Laravel + Coolify + Hetzner + GitHub Actions)
1. Provision a Hetzner droplet (minimal 2 vCPU / 4GB RAM recommended) and install Coolify following the official Coolify documentation.  
2. Create an application in Coolify pointing to your GitHub repository and configure build commands to run `composer install --no-dev --optimize-autoloader` and `php artisan migrate --force` in your build/release pipeline.  
3. Add required environment variables in Coolify (APP_KEY, DB_* credentials, QUEUE_CONNECTION, CACHE_DRIVER, etc.). Keep CI-only secrets in GitHub Actions Secrets.  
4. Configure object storage: use Hetzner Spaces or Cloudflare R2 for user media, and point Laravel's `filesystems.php` to that storage; set appropriate signed URL or CDN caching rules.  
5. Add a minimal GitHub Actions workflow that runs tests and triggers Coolify deploy on merge to `main` (or use Coolify's GitHub integration/webhook if preferred). Protect production merges with branch protections and required reviews.

## Out of Scope
- Building Docker images or authoring Dockerfiles (see implement step).  
- Full CI/CD pipeline authoring beyond a basic workflow dispatch.  
- Production multi-region topology and HA beyond simple standby or snapshots.
