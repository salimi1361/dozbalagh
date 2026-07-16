# CMR module

The e-CMR domain is isolated under `app/CMR`, `resources/views/CMR`, and
`routes/cmr.php`. Existing company, driver, fleet, permit, and wallet records are
referenced; CMR lifecycle, immutable versions, audit events, and billing entries
remain separate from the permit (Dozbalagh) workflow.

Issuance is server-side and atomic: the company wallet is locked, available funds
are checked, the configured fee is deducted, a version snapshot and SHA-256 hash
are stored, and the document moves from `draft` to `issued`. Billing is disabled
by default until an administrator configures and enables the issuance fee.

The current phase is admin-only. Company access, receiver access, signatures,
reservations, attachments, XML D25A/eFTI adapters, inspection access links, PDF/QR,
and driver offline synchronization are subsequent isolated components.

The administrator controls the active issuance tariff. Each change is retained in
`cmr_tariff_history`; an issued document permanently stores the fee and currency
that applied at issuance. Cancelling either a draft or issued document creates an
audit event. An issued-document cancellation is non-refundable and never mutates
the wallet ledger.
