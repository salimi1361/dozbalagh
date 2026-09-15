---
name: doz-controlled-change
description: "Implement an owner-approved Dozbalagh change with a scoped, test-first workflow. Use for requested Dozoleh, CMR, Shahbaz, or shared-core changes; not for production promotion without separate approval."
---

# Doz Controlled Change

Use this skill for implementation work in Dozbalagh. `AGENTS.md` is the authoritative policy; follow it in full.

## Scope gate

Before editing or deploying, identify the requested outcome, affected module, likely files, and relevant checks. If the owner has not explicitly approved that scope, stop after presenting it. Do not convert a request for analysis, planning, or review into a code or server change.

## Implement the approved change

Start from the latest `origin/main` on a new `sina/` task branch. Keep the change within Dozoleh, CMR, Shahbaz, or the shared core only where genuinely needed. Preserve unrelated work and avoid broad refactors.

Validate the affected behavior with the narrowest relevant checks, then inspect the diff and changed files for secrets before committing. Create a focused commit and pull request to `main`; do not merge it.

## Test deployment gate

Deploy only to `https://doztest.itca-kh.com` after the owner explicitly approves that deployment. First back up the affected test files, then transfer only approved files and rebuild Laravel caches only when necessary. Verify the requested behavior in the test environment and report the result.

## Production gate

Test success never authorizes production. Stop after the test report until the owner separately and explicitly approves a production deployment. That approval must cover the intended files and any database or migration action.

## Required handoff

Report changed files, validation run, branch, commit, pull request, test-deployment status, production-deployment status, and outstanding owner approvals.
