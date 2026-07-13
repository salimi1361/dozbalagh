# Web panel access control

The web application has three explicit panel boundaries:

| Role | Entry route | Access |
| --- | --- | --- |
| `admin` | `admin.dashboard` | System administration and association operations |
| `association` | `association.dashboard` | Association operations and association CRM |
| `company` | `dashboard` | The authenticated company's own panel |

All panel route groups must use both `auth` and the matching `role` middleware. The
`admin` role may also enter association routes for supervision, but `association`
must not enter system administration routes. Existing route names and URLs are
kept stable to avoid breaking forms, redirects, bookmarks, and integrations.

Login redirection is based on `users.role_id`, not on the presence of a related
company record. Inactive and suspended accounts cannot authenticate or pass role
middleware.
