# Project implementation rules

- Never load JavaScript, CSS, fonts, icons, images, or runtime libraries from a CDN or any external host.
- All frontend dependencies must be installed in the project, built locally, and served from this application's own domain.
- Uploaded permit templates, seals, and signatures must be stored on the application's own storage disk.
- Do not introduce an external SaaS dependency when an in-project implementation is possible.
- Existing payment, SMS, identity/inquiry, and regulatory integrations are business APIs, not frontend assets. Do not add, remove, or replace those integrations without explicit user approval.
- Ordinary external navigation links must never be required for core application behavior.

## Controlled work and deployment workflow

### Owner approval and scope

- Work only on the exact feature, bug, or files requested by the project owner.
- Before modifying code, configuration, the database, or server files, state the planned scope and the files involved, then wait for the owner's explicit approval.
- Do not make unrelated cleanup, refactors, dependency upgrades, formatting sweeps, or rewrites.
- Preserve other developers' work. Never reset, overwrite, discard, or delete unrelated changes.

### Start every task safely

- First inspect `git status` and read all applicable `AGENTS.md` files and project documentation.
- Start every task from the latest `origin/main`: update local `main`, then create a new task branch.
- Never develop, commit, push, or deploy directly from `main`.
- Task branches must use the `sina/` prefix:
  - Features: `sina/feature-<task-name>`
  - Fixes: `sina/fix-<bug-name>`
  - Documentation or maintenance: `sina/chore-<task-name>`

### Project boundaries and sensitive data

- Keep Dozoleh, CMR, and Shahbaz separated. Put code in shared areas only when it is genuinely shared by multiple modules.
- Do not commit `.env` files, passwords, tokens, SSH keys, backups, real databases, build outputs, APK files, temporary Codex files, or real user data.
- Do not change business integrations, payment, SMS, identity/inquiry, or regulatory APIs without the owner's explicit approval.
- Do not change the primary database or create/run migrations without the owner's explicit approval.

### Test-first deployment

- The default and only deployment target is the test environment:
  - Path: `/var/www/testdozbalagh`
  - URL: `https://doztest.itca-kh.com`
- A test deployment requires a separate explicit owner approval after implementation.
- Before an approved test deployment, back up only the affected test files.
- Deploy only the files related to the approved request, and rebuild Laravel caches only when required.
- Run the relevant validation in the test environment and report its result.

### Production protection

- Production is `/var/www/dozbalagh` at `https://doz.itca-kh.com`.
- Never deploy to production, modify production configuration or data, run production migrations, merge to `main`, or force-push unless the owner gives separate explicit approval after reviewing the test result.
- Before any separately approved production deployment, create the required backup and state exactly what will change.

### Review, pull requests, and reporting

- Run relevant syntax checks, tests, and validation commands. Review `git diff` and scan changed files for sensitive information.
- Make focused commits with clear messages, push only the task branch, and create a pull request targeting `main`.
- Do not merge pull requests. The project owner reviews and merges them.
- In every final report, state: changed files; tests/checks run; branch name and commit ID; pull request URL; whether test deployment occurred; whether production deployment occurred; and anything awaiting owner approval.
