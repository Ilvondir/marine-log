# Lessons Learned

> Append-only register of recurring rules and patterns. Re-read at start by /10x-frame, /10x-research, /10x-plan, /10x-plan-review, /10x-implement, /10x-impl-review.

## Always run PHP/Sail/Artisan commands through WSL, not native shell

- **Context**: Any phase or skill that executes shell commands involving PHP, Composer, Artisan, or Laravel Sail (tests, migrations, linting, builds).
- **Problem**: The project lives on WSL2 (Ubuntu-22.04) at `/home/user/10x-project`, accessed from Windows IDE via `\\wsl.localhost\Ubuntu-22.04\...` UNC paths. Docker, PHP, and Sail only exist inside WSL. Running `./vendor/bin/sail artisan test` directly in PowerShell fails — the binary isn't executable from Windows and Docker socket isn't available. Git and Node work natively via UNC path with `cwd`, but PHP tooling does not.
- **Rule**: Always wrap PHP/Sail/Artisan/Composer commands in `wsl -d Ubuntu-22.04 -- bash -c "cd /home/user/10x-project && <command>"`. Use native PowerShell with `cwd` only for git, node/npm, and 10x CLI commands. Never attempt `./vendor/bin/sail` or `php artisan` directly from PowerShell.
- **Applies to**: implement, plan, impl-review, all
