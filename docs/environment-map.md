# Environment Map

Last checked: 2026-08-07

## Local workspace

- Path: `D:\Projects\dozbalagh`
- Branch: `feature/cmr`
- Mobile app path: `D:\Projects\dozbalagh\mobile_driver`
- Mobile default API origin in `mobile_driver/lib/src/config/app_config.dart`: `https://doz.itca-kh.com`
- To build mobile against test API, pass `--dart-define=API_BASE_URL=https://doztest.itca-kh.com`.

## Server

- SSH host: `doz.itca-kh.com`
- Server IP in known_hosts: `185.239.0.130`

## Production

- Domain: `https://doz.itca-kh.com`
- Server path: `/var/www/dozbalagh`
- Nginx root: `/var/www/dozbalagh/public`
- Laravel env:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://doz.itca-kh.com`
- Git: repository exists, branch `feature/cmr`
- Mobile app path on server: `/var/www/dozbalagh/mobile_driver`
- Mobile default API origin on server: `https://doz.itca-kh.com`

## Test

- Domain: `https://doztest.itca-kh.com`
- Server path: `/var/www/testdozbalagh`
- Nginx root: `/var/www/testdozbalagh/public`
- Nginx basic auth: enabled
- Laravel env:
  - `APP_ENV=staging`
  - `APP_DEBUG=false`
  - `APP_URL=http://doztest.itca-kh.com`
- Git: no `.git` directory found in `/var/www/testdozbalagh`; this appears to be a deployed copy, not a git checkout.
- Mobile app path on server: `/var/www/testdozbalagh/mobile_driver`
- Mobile default API origin on server: `https://doz.itca-kh.com`

## Important notes

- The earlier assumed test path `/var/www/dozbalagh-cmr-test` was not present.
- The actual test path found on the server is `/var/www/testdozbalagh`.
- The earlier assumed test domain `cmr.test.itca-kh.com` was not found in enabled Nginx sites.
- The active test domain found in Nginx is `doztest.itca-kh.com`.
- Both server copies contain `mobile_driver`, but both mobile configs default to production API unless build-time `API_BASE_URL` is provided.
- Do not reset or overwrite server-side `package-lock.json` without explicit approval.
