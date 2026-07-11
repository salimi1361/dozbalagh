# Project implementation rules

- Never load JavaScript, CSS, fonts, icons, images, or runtime libraries from a CDN or any external host.
- All frontend dependencies must be installed in the project, built locally, and served from this application's own domain.
- Uploaded permit templates, seals, and signatures must be stored on the application's own storage disk.
- Do not introduce an external SaaS dependency when an in-project implementation is possible.
- Existing payment, SMS, identity/inquiry, and regulatory integrations are business APIs, not frontend assets. Do not add, remove, or replace those integrations without explicit user approval.
- Ordinary external navigation links must never be required for core application behavior.
