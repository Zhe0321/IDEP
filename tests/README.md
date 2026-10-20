# Black-box tests

`idep_blackbox_tests.py` runs 61 black-box tests against a running copy of the platform.
It calls the real HTTP endpoints as a sensor, a public visitor, a manager and an
administrator, and checks status codes, responses and the database. The test cases
and results are documented in Sections 8 and 9 of the technical report.

## Before you start

- **Use a copy of the project.** The tests add and delete wells, sensors, readings and
  users, and change the admin password briefly. Never run them on the copy you demo or submit.
- You need PHP 8.1+ (the same as for the site) and Python 3.
- One-off setup: `pip3 install requests pillow`

## Run the tests

1. Copy the whole `IDEP` folder, for example to `IDEP-test`.
2. In the copy, set up the database (the same commands as the main README):

   ```bash
   php database/add_missing_columns.php
   php database/add_photo_column.php
   php database/add_location_types.php
   php database/import_bali_wilayah.php
   php database/migrate_user_roles.php
   php database/import_recharge_wells.php
   php database/register_idep_device.php
   ```

3. Terminal 1, inside the copy:

   ```bash
   ./start-server.sh
   ```

4. Terminal 2, inside the copy:

   ```bash
   python3 tests/idep_blackbox_tests.py
   ```

The script first checks that the server is running, the database is set up and the server
is using this copy's database, then asks you to confirm (add `--yes` to skip the question).
Each test prints `PASS` or `FAIL` with what it observed, then the total
(for example `61/61 passed`). Results are also saved to `blackbox-results.json`.

## Notes

- On older macOS Python you may see a `NotOpenSSLWarning` from urllib3. It is harmless and
  the script now hides it.

- Run the tests from the project folder, or set `IDEP_ROOT` to its path:
  `IDEP_ROOT=/path/to/IDEP-test python3 /path/to/idep_blackbox_tests.py`
- The server must be on `http://127.0.0.1:8000`, started with `start-server.sh`
  (the router and upload limit are part of what is tested).
- TC40 briefly starts a second PHP server on this computer's network address to test
  the Host-header login bypass, and stops it again.
- If the sign-in tests fail with HTTP 429, a previous run locked sign-in. Wait 15 minutes
  or clear it: `sqlite3 database/idep_groundwater.db "DELETE FROM login_attempts;"`
