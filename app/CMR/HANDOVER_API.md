# e-CMR origin and destination handover API

Authenticated driver endpoints are under `/api/v1/driver/cmr/{cmr}`.

- `GET /handover/requirements` returns the selected company's optional evidence policy.
- `POST /handover` accepts `multipart/form-data`.

Required fields: `client_uuid`, `stage` (`origin` or `destination`), `outcome`, and `confirmed=1`.
Optional/evidence fields: `signer_name`, `signer_identifier`, `signature`, `photos[]`, `latitude`, `longitude`, `accuracy`, `notes`, and `occurred_at`.

Origin outcomes: `accepted`, `accepted_with_reservations`, `refused`.
Destination outcomes: `delivered`, `partial`, `damaged`, `refused`.

The server enforces the company policy, current CMR status, driver ownership, file limits, timestamp limits and GPS ranges. `client_uuid` makes offline retries idempotent. Successful origin evidence changes `issued` to `accepted`; successful destination evidence changes `in_transit` to `delivered`. Refusal is recorded without incorrectly advancing the document.
