# ProjekIoT - MySQL connection timeout fix

## Plan
1. Inspect current DB-related configuration: `config/database.php`, `docker-compose.yml`, and container entrypoint.
2. Identify why Laravel resolves MySQL host to `172.18.0.3` and times out.
3. Update `docker-compose.yml` to pass correct DB env vars to the `app` container (use `DB_HOST=mysql` within Docker network).
4. (If needed) restart containers and run a quick Laravel command to verify DB connectivity.
5. Confirm Livewire/auth request no longer throws `Connection timed out`.

