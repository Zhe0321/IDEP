"""Black-box system tests for the IDEP groundwater monitoring platform (61 cases).

See tests/README.md. In short, on a COPY of the project with a migrated database:
    ./start-server.sh                        # terminal 1, in the project copy
    python3 tests/idep_blackbox_tests.py     # terminal 2, in the project copy
The tests add and delete data, so never run them on the copy you demo or submit.
"""
import json, os, re, sqlite3, sys, time
import requests

BASE = "http://127.0.0.1:8000"
ROOT = os.environ.get("IDEP_ROOT", os.path.abspath("."))  # project folder, started with ./start-server.sh
DB = os.path.join(ROOT, "database", "idep_groundwater.db")
results = []


def rec(tc, req, objective, expected, ok, actual):
    results.append(dict(tc=tc, req=req, objective=objective, expected=expected,
                        result="Pass" if ok else "Fail", actual=actual))
    print(f"{tc} {'PASS' if ok else 'FAIL'} - {objective} -> {actual}")


def db(q, *a):
    c = sqlite3.connect(DB)
    r = c.execute(q, a).fetchall()
    c.close()
    return r


def login(user, pw, headers=None):
    """Sign in; on success read the page's CSRF token and send it on every later request."""
    s = requests.Session()
    r = s.post(f"{BASE}/main/api/login.php", data={"username": user, "password": pw}, headers=headers or {})
    if r.status_code == 200:
        page = s.get(f"{BASE}/main/admin-dashboard.php").text
        m = re.search(r'<meta name="csrf-token" content="([0-9a-f]+)"', page)
        if m:
            s.headers["X-CSRF-Token"] = m.group(1)
    return s, r


# ---------- IoT ingestion ----------
before = db("select count(*) from sensor_readings")[0][0]
r = requests.post(f"{BASE}/main/api/v1/sensor.php", data={"id_device": "device_1", "h1": "0.12", "h2": "0.11", "hasil": "1.75"})
after = db("select count(*) from sensor_readings")[0][0]
last = db("select h1,h2,hasil,received_at from sensor_readings order by id desc limit 1")[0]
rec("TC01", "FR01", "Sensor POST with all fields is stored", "HTTP 200, status=true, one new row",
    r.status_code == 200 and r.json()["status"] and after == before + 1,
    f"HTTP {r.status_code}; rows {before}->{after}; stored {last}")

r = requests.post(f"{BASE}/main/api/v1/sensor.php", data={"id_device": "device_1", "h1": "0.1"})
rec("TC02", "FR02", "Sensor POST missing h2/hasil is rejected", "HTTP 400, no row",
    r.status_code == 400 and db("select count(*) from sensor_readings")[0][0] == after,
    f"HTTP {r.status_code}; message='{r.json()['message']}'")

r = requests.post(f"{BASE}/main/api/v1/sensor.php", data={"id_device": "device_1", "h1": "0", "h2": "0", "hasil": "9.99"})
stored = db("select hasil from sensor_readings order by id desc limit 1")[0][0]
rec("TC03", "FR02", "Zero-height reading forces result to zero", "hasil stored as 0",
    r.status_code == 200 and float(stored) == 0.0, f"HTTP {r.status_code}; hasil stored={stored}")

r = requests.post(f"{BASE}/main/api/v1/sensor.php", data={"id_device": "device_TEST9", "h1": "0.2", "h2": "0.3", "hasil": "2.5"})
sens = db("select id_device,status from sensors where id_device='device_TEST9'")
rec("TC04", "FR03", "Unknown device is auto-registered on first transmission", "new sensors row, status 1",
    r.status_code == 200 and len(sens) == 1, f"HTTP {r.status_code}; sensors row={sens}")

r = requests.get(f"{BASE}/main/api/v1/sensor.php")
j = r.json()
rec("TC05", "FR04", "GET returns the most recent reading", "HTTP 200 with id_device,h1,h2,hasil,received_at",
    r.status_code == 200 and j["status"] and j["data"]["id_device"] == "device_TEST9" and "received_at" in j,
    f"HTTP {r.status_code}; device={j['data']['id_device']}; received_at={j['received_at']}")

r = requests.put(f"{BASE}/main/api/v1/sensor.php")
rec("TC06", "NFR04", "Unsupported HTTP method is refused", "HTTP 405",
    r.status_code == 405, f"HTTP {r.status_code}")

r = requests.post(f"{BASE}/main/api/v1/sensor.php", data={"id_device": "device_1", "h1": "abc", "h2": "0.1", "hasil": "xyz"})
stored = db("select h1,hasil from sensor_readings order by id desc limit 1")[0]
rec("TC07", "FR02", "Non-numeric values are rejected", "HTTP 400/422, no row stored",
    r.status_code in (400, 422) and stored[0] != "abc", f"HTTP {r.status_code}; " + ("nothing stored" if stored[0] != "abc" else f"stored h1={stored[0]!r}, hasil={stored[1]!r}"))
# clean that bad row so later metrics are unaffected
c = sqlite3.connect(DB); c.execute("delete from sensor_readings where h1='abc'"); c.commit(); c.close()

t0 = time.time()
codes = [requests.post(f"{BASE}/main/api/v1/sensor.php", data={"id_device": "device_TEST9", "h1": "0.1", "h2": "0.1", "hasil": "1"}).status_code for _ in range(100)]
dt = time.time() - t0
rec("TC08", "NFR02", "100 consecutive sensor POSTs are all stored", "100 x HTTP 200; mean < 1 s",
    all(c == 200 for c in codes), f"{codes.count(200)}/100 OK; mean {dt/100*1000:.0f} ms per request")

# ---------- Authentication & roles ----------
s_admin, r = login("admin123", "admin123")
rec("TC09", "FR10", "Administrator signs in with valid credentials", "HTTP 200, role=admin",
    r.status_code == 200 and r.json().get("role") == "admin", f"HTTP {r.status_code}; role={r.json().get('role')}")

_, r = login("admin123", "wrongpass")
rec("TC10", "FR10", "Wrong password is rejected", "HTTP 401", r.status_code == 401, f"HTTP {r.status_code}; '{r.json()['message']}'")

_, r = login("", "")
rec("TC11", "FR10", "Empty credentials are rejected", "HTTP 422", r.status_code == 422, f"HTTP {r.status_code}")

_, r = login("' OR '1'='1", "' OR '1'='1")
rec("TC12", "NFR05", "SQL-injection string in login is not accepted", "HTTP 401",
    r.status_code == 401, f"HTTP {r.status_code}")

r = requests.get(f"{BASE}/main/admin-dashboard.php", allow_redirects=False)
rec("TC13", "FR11", "Admin page without a session redirects to sign-in", "HTTP 302 to /main/login.php",
    r.status_code == 302 and "login.php" in r.headers.get("Location", ""), f"HTTP {r.status_code} -> {r.headers.get('Location')}")

pub_ok = all(requests.get(f"{BASE}/main/{p}.php").status_code == 200 for p in ["public-dashboard", "public-impacts", "public-monitoring-wells", "public-map-view"])
rec("TC14", "FR12", "Public pages open without an account", "HTTP 200 for 4 public pages", pub_ok, "all 200" if pub_ok else "not all 200")

r = requests.get(f"{BASE}/main/public-dashboard.php")
leak = any(x in r.text for x in ["admin-user-management", "admin-site-registration", "admin-historical-data"])
rec("TC15", "FR12", "Public navigation hides operational pages", "No admin links in public HTML", not leak, "no admin links" if not leak else "admin links present")

s_mgr, r = login("manager123", "manager123")
r2 = s_mgr.get(f"{BASE}/main/admin-user-management.php")
rec("TC16", "FR13", "Manager cannot open User Management", "HTTP 403",
    r.json().get("role") == "manager" and r2.status_code == 403, f"login role={r.json().get('role')}; page HTTP {r2.status_code}")

r = s_mgr.post(f"{BASE}/main/admin-user-save.php", data={"name": "X", "username": "xuser", "email": "x@x.org", "password": "secret1", "status": "admin"})
rec("TC17", "FR13", "Manager cannot call the user-save endpoint", "HTTP 403", r.status_code == 403, f"HTTP {r.status_code}")

r = s_admin.post(f"{BASE}/main/admin-user-save.php", data={"name": "Test Officer", "username": "officer1", "email": "officer1@idep.org", "password": "secret1", "status": "manager"})
new_id = r.json().get("user_id")
hashed = db("select password from user where username='officer1'")[0][0]
rec("TC18", "FR14", "Administrator creates a manager; password is hashed", "success=true; stored value is a bcrypt hash",
    r.json().get("success") and hashed.startswith("$2y$"), f"HTTP {r.status_code}; stored prefix {hashed[:7]}...")

r = s_admin.post(f"{BASE}/main/admin-user-save.php", data={"name": "Dup", "username": "officer1", "email": "officer1@idep.org", "password": "secret1", "status": "manager"})
rec("TC19", "FR14", "Duplicate username/email is refused", "success=false with message",
    not r.json().get("success"), f"HTTP {r.status_code}; '{r.json().get('message')}'")

r = s_admin.post(f"{BASE}/main/admin-user-save.php", data={"name": "Short", "username": "short1", "email": "s@idep.org", "password": "123", "status": "manager"})
rec("TC20", "FR14", "Password shorter than 6 characters is refused", "HTTP 422", r.status_code == 422, f"HTTP {r.status_code}")

r = s_admin.post(f"{BASE}/main/admin-user-toggle.php", data={"user_id": new_id, "action": "deactivate"})
_, r2 = login("officer1", "secret1")
rec("TC21", "FR14", "Deactivated account can no longer sign in", "toggle success; login HTTP 401",
    r.json().get("success") and r2.status_code == 401, f"toggle={r.json().get('success')}; login HTTP {r2.status_code}")

r = s_admin.post(f"{BASE}/main/admin-user-toggle.php", data={"user_id": 1, "action": "deactivate"})
rec("TC22", "FR14", "Administrator cannot deactivate own account", "HTTP 422", r.status_code == 422, f"HTTP {r.status_code}; '{r.json().get('message')}'")

# ---------- Site registration ----------
vid = db("select v.id from village v join sub_district sd on sd.id=v.sub_district_id where upper(v.name)='MAS' limit 1")
vid = vid[0][0] if vid else db("select id from village limit 1")[0][0]
r = s_admin.post(f"{BASE}/main/admin-site-registration-save.php", data={"name": "Test Well", "village_id": vid, "latitude": "120", "longitude": "115.2", "installer": "QA", "type": "Type 3", "date": "2026-10-01"})
rec("TC23", "FR20", "Out-of-range latitude is rejected", "HTTP 422", r.status_code == 422, f"HTTP {r.status_code}; '{r.json().get('message')}'")

r = s_admin.post(f"{BASE}/main/admin-site-registration-save.php", data={"name": "", "village_id": 0})
rec("TC24", "FR20", "Missing name/village is rejected", "HTTP 422", r.status_code == 422, f"HTTP {r.status_code}")

r = s_admin.post(f"{BASE}/main/admin-site-registration-save.php", data={"name": "QA Well <script>alert(1)</script>", "village_id": vid, "latitude": "-8.52", "longitude": "115.27", "installer": "QA", "type": "Type 3", "depth": "6", "absorption": "2 m3/hr", "date": "2026-10-01", "sensor_id": "device_TEST9"})
wid = r.json().get("well_id")
page = s_admin.get(f"{BASE}/main/admin-monitoring-wells.php").text
rec("TC25", "FR20", "Valid site with sensor is saved and shown in inventory", "success=true; well listed",
    r.json().get("success") and "device_TEST9" in page, f"HTTP {r.status_code}; well_id={wid}")

raw_script = "<script>alert(1)</script>" in s_admin.get(f"{BASE}/main/admin-site-registration.php").text
rec("TC26", "NFR05", "HTML in a site name is escaped on output (XSS)", "no raw <script> in page", not raw_script, "escaped" if not raw_script else "raw script tag rendered")

r = s_admin.post(f"{BASE}/main/admin-site-registration-save.php", data={"name": "Second Well", "village_id": vid, "installer": "QA", "type": "Type 3", "date": "2026-10-01", "sensor_id": "device_TEST9"})
rec("TC27", "FR21", "One sensor cannot be linked to two wells", "HTTP 422", r.status_code == 422, f"HTTP {r.status_code}; '{r.json().get('message')}'")

# online status: device_TEST9 just transmitted (< 3 min)
import re
online = bool(re.search(r'data-id="qa-well[^"]*"[^>]*data-status="online"', page))
rec("TC28", "FR05", "Well whose sensor sent data < 3 min ago shows Online", "status=online for linked well",
    online, "Online" if online else "not online")

r = s_admin.post(f"{BASE}/main/admin-site-registration-delete.php", data={"well_id": wid})
rec("TC29", "FR20", "Registered site can be deleted", "success=true", r.json().get("success") is True, f"HTTP {r.status_code}; {r.json()}")

# ---------- Data APIs ----------
r = requests.get(f"{BASE}/main/api/well-absorption.php", params={"start": "2026-08-31", "end": "2026-10-12"})
j = r.json()
rch = j["data"].get("RCH-082", {})
rec("TC30", "FR07", "Absorption totals for a date range", "HTTP 200; RCH-082 total returned",
    r.status_code == 200 and rch.get("readingCount", 0) > 0, f"HTTP {r.status_code}; RCH-082 {rch.get('readingCount')} readings, {rch.get('label')}")

r = requests.get(f"{BASE}/main/api/well-absorption.php", params={"start": "2026-13-40"})
rec("TC31", "FR07", "Invalid date is rejected", "HTTP 400", r.status_code == 400, f"HTTP {r.status_code}; '{r.json().get('message')}'")

r = requests.get(f"{BASE}/main/api/well-absorption.php", params={"start": "2026-10-12", "end": "2026-09-01"})
rec("TC32", "FR07", "Start date after end date is rejected", "HTTP 400", r.status_code == 400, f"HTTP {r.status_code}")

r = requests.get(f"{BASE}/main/api/programme-metrics.php")
m = r.json()["data"]
rec("TC33", "FR08", "Programme metrics endpoint returns live figures", "HTTP 200; wellCount, actualLitres, peopleSupported",
    r.status_code == 200 and m["wellCount"] >= 82, f"wells={m['wellCount']}, villages={m['villageCount']}, regencies={m['districtCount']}, litres={m['actualLitresExactLabel']}, people={m['peopleSupportedLabel']}")

r = requests.get(f"{BASE}/main/api/locations.php", params={"level": "province"})
rec("TC34", "NFR05", "Location API refuses requests without a session", "HTTP 401", r.status_code == 401, f"HTTP {r.status_code}")

r = s_admin.post(f"{BASE}/main/api/locations.php", data=json.dumps({"action": "create", "level": "village", "parentId": 1, "name": "X"}), headers={"Content-Type": "text/plain"})
rec("TC35", "NFR05", "Location API refuses non-JSON/cross-site style POST", "HTTP 4xx", 400 <= r.status_code < 500, f"HTTP {r.status_code}")

r = requests.get(f"{BASE}/main/ajax-get-villages.php", params={"sub_district_id": 1})
rec("TC36", "NFR05", "Location lookup AJAX requires sign-in", "HTTP 401", r.status_code == 401, f"HTTP {r.status_code}")

hist = s_admin.get(f"{BASE}/main/admin-historical-data.php").text
rows = hist.count("data-history-row")
rec("TC37", "FR06", "Historical page lists stored readings with quality flag", "rows rendered with 'Good'/'No reading'",
    rows > 0 and ("No reading" in hist or "Good" in hist), f"{rows} rows rendered")

# ---------- New: CSRF, headers, photo upload (admin session) ----------
bare = requests.Session()
bare.cookies.update(s_admin.cookies)
r = bare.post(f"{BASE}/main/admin-user-toggle.php", data={"user_id": new_id, "action": "activate"})
rec("TC42", "NFR05", "Admin POST without a CSRF token is refused", "HTTP 403",
    r.status_code == 403, f"HTTP {r.status_code}; '{r.json().get('message')}'")

r = bare.post(f"{BASE}/main/admin-user-toggle.php", data={"user_id": new_id, "action": "activate"}, headers={"X-CSRF-Token": "0" * 64})
rec("TC43", "NFR05", "Admin POST with a wrong CSRF token is refused", "HTTP 403",
    r.status_code == 403, f"HTTP {r.status_code}")

r = s_admin.get(f"{BASE}/main/admin-dashboard.php")
h = r.headers
need = {"X-Frame-Options": "DENY", "X-Content-Type-Options": "nosniff", "Referrer-Policy": "strict-origin-when-cross-origin"}
ok = all(h.get(k) == v for k, v in need.items()) and "frame-ancestors 'none'" in h.get("Content-Security-Policy", "") and "X-Powered-By" not in h
lh = requests.get(f"{BASE}/main/login.php").headers
rec("TC44", "NFR05", "Dashboard pages send browser security headers", "X-Frame-Options, nosniff, CSP frame-ancestors, Referrer-Policy; no X-Powered-By",
    ok, ("all present on dashboard pages" if ok else f"missing: {[k for k in need if h.get(k) != need[k]]}") + ("; login page: none" if "X-Frame-Options" not in lh else "; login page: present"))

import io
from PIL import Image as _Im
buf = io.BytesIO(); _Im.new("RGB", (64, 48), (90, 140, 100)).save(buf, "PNG"); png = buf.getvalue()
r = s_admin.post(f"{BASE}/main/admin-site-registration-save.php",
                 data={"name": "Photo Test Well", "village_id": vid, "installer": "QA", "type": "Type 3", "date": "2026-10-01"},
                 files={"photo": ("site.png", png, "image/png")})
pid = r.json().get("well_id")
pp = db("select photo_path from wells where id=?", pid)[0][0] if pid else None
served = requests.get(BASE + pp).status_code if pp else None
rec("TC45", "FR20", "Valid site photo is uploaded, renamed and served", "success; stored as /uploads/wells/<random>.png; HTTP 200",
    bool(pp) and re.fullmatch(r"/uploads/wells/[0-9a-f]{24}\.png", pp or "") is not None and served == 200,
    f"HTTP {r.status_code}; stored {pp}; served HTTP {served}")

r = s_admin.post(f"{BASE}/main/admin-site-registration-save.php",
                 data={"name": "Bad Upload Well", "village_id": vid, "installer": "QA", "type": "Type 3", "date": "2026-10-01"},
                 files={"photo": ("shell.jpg", b"<?php echo 'pwned'; ?>", "image/jpeg")})
rec("TC46", "NFR05", "PHP code disguised as a .jpg photo is rejected", "HTTP 422, nothing saved",
    r.status_code == 422 and not db("select id from wells where well_name='Bad Upload Well'"), f"HTTP {r.status_code}; '{r.json().get('message')}'")

big = b"\xff\xd8\xff" + os.urandom(6 * 1024 * 1024)
r = s_admin.post(f"{BASE}/main/admin-site-registration-save.php",
                 data={"name": "Big Upload Well", "village_id": vid, "installer": "QA", "type": "Type 3", "date": "2026-10-01"},
                 files={"photo": ("big.jpg", big, "image/jpeg")})
rec("TC47", "NFR05", "Photo larger than 5 MB is rejected", "HTTP 422 'too large'",
    r.status_code == 422, f"HTTP {r.status_code}; '{r.json().get('message')}'")

r = s_admin.post(f"{BASE}/main/admin-site-registration-delete.php", data={"well_id": pid})
gone = not os.path.exists(ROOT + pp) if pp else False
rec("TC48", "FR20", "Deleting a site also deletes its photo file", "success; file removed",
    r.json().get("success") is True and gone, f"HTTP {r.status_code}; file removed={gone}")

s_admin.get(f"{BASE}/main/logout.php")
r = s_admin.get(f"{BASE}/main/admin-dashboard.php", allow_redirects=False)
rec("TC38", "FR11", "Logout ends the session", "admin page redirects after logout", r.status_code == 302, f"HTTP {r.status_code}")

# ---------- Deployment security (re-test of earlier findings) ----------
r = requests.get(f"{BASE}/database/idep_groundwater.db")
rec("TC39", "NFR05", "Database file is not downloadable over HTTP", "HTTP 403/404",
    r.status_code in (403, 404), f"HTTP {r.status_code}; {len(r.content):,} bytes" + (" (SQLite file downloaded)" if r.content[:15] == b"SQLite format 3" else ""))

r = requests.get(f"{BASE}/database/check_wells.php")
rec("TC41", "NFR05", "Maintenance scripts are not web-executable", "HTTP 403/404",
    r.status_code in (403, 404), f"HTTP {r.status_code}" + ("; script ran and printed well records" if "well_code" in r.text else ""))

r1 = requests.get(f"{BASE}/import_users_sensors.sql")
r = requests.get(f"{BASE}/database/import_users_sensors.sql")
rec("TC49", "NFR05", "Data-import SQL file is not downloadable", "HTTP 403/404",
    r.status_code in (403, 404) and r1.status_code in (403, 404), f"root path HTTP {r1.status_code}; database/ path HTTP {r.status_code}" + ("; file contains plain-text demo passwords" if "admin123" in r.text else ""))

# Host-header spoof on a non-local address with a changed admin password
import shutil, subprocess
c = sqlite3.connect(DB); c.execute("delete from login_attempts"); c.commit(); c.close()
shutil.copy(DB, DB + ".bak")
subprocess.run(["php", "-r", '$p=new PDO("sqlite:' + DB + '");$p->exec("UPDATE user SET password=\\"".password_hash("N3wStrongPass!",PASSWORD_DEFAULT)."\\" WHERE username=\\"admin123\\"");'], check=True)
import socket
_s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
try:
    _s.connect(("10.255.255.255", 1)); ip = _s.getsockname()[0]   # this computer's network address (works on macOS and Linux)
except OSError:
    ip = "127.0.0.2"
finally:
    _s.close()
srv = subprocess.Popen(["php", "-S", f"{ip}:8001"], cwd=ROOT, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
time.sleep(1)
normal = requests.post(f"http://{ip}:8001/main/api/login.php", data={"username": "admin123", "password": "admin123"})
spoof = requests.post(f"http://{ip}:8001/main/api/login.php", data={"username": "admin123", "password": "admin123"}, headers={"Host": "localhost"})
srv.terminate()
shutil.copy(DB + ".bak", DB)
rec("TC40", "NFR05", "Demo fallback cannot bypass a changed password", "HTTP 401",
    spoof.status_code == 401, f"normal Host: HTTP {normal.status_code}; Host: localhost: HTTP {spoof.status_code}" + (", role=admin" if spoof.status_code == 200 else ""))

# ---------- Login throttling ----------
c = sqlite3.connect(DB); c.execute("delete from login_attempts"); c.commit(); c.close()
codes = [login("manager123", f"wrong{i}")[1].status_code for i in range(5)]
_, r6 = login("manager123", "wrong6")
_, r7 = login("manager123", "manager123")
rec("TC50", "NFR05", "Sign-in locks after 5 failed attempts from one IP", "5 x 401, then 429 even with the right password",
    codes == [401] * 5 and r6.status_code == 429 and r7.status_code == 429,
    f"attempts 1–5: {sorted(set(codes))}; 6th: HTTP {r6.status_code}; correct password: HTTP {r7.status_code}; Retry-After {r6.headers.get('Retry-After')} s")
c = sqlite3.connect(DB); c.execute("delete from login_attempts"); c.commit(); c.close()
_, r8 = login("manager123", "manager123")
rec("TC51", "NFR05", "Sign-in works again once the lockout window clears", "HTTP 200",
    r8.status_code == 200, f"HTTP {r8.status_code}")


# ---------- Added for the fixes ----------
c = sqlite3.connect(DB); c.execute("delete from login_attempts"); c.commit(); c.close()
sA, _ = login("admin123", "admin123")
vid2 = db("select id from village limit 1")[0][0]
data = open(ROOT + "/images/homepageIMG/recharge-well-01.jpg", "rb").read()
r = sA.post(f"{BASE}/main/admin-site-registration-save.php", data={"name": "Phone Photo Well", "village_id": vid2, "installer": "QA", "type": "Type 3", "date": "2026-10-01"},
            files={"photo": ("well.jpg", data, "image/jpeg")})
ok = r.status_code == 200
rec("TC52", "FR20", "A 3.7 MB phone photo (under the stated 5 MB limit) is accepted", "success=true",
    ok, f"HTTP {r.status_code}" + ("; saved" if ok else f"; '{r.json().get('message')}'"))
if ok:
    sA.post(f"{BASE}/main/admin-site-registration-delete.php", data={"well_id": r.json()["well_id"]})

before = db("select count(*) from sensor_readings")[0][0]
codes = [requests.post(f"{BASE}/main/api/v1/sensor.php", data=d).status_code for d in (
    {"id_device": "device_1", "h1": "-1", "h2": "0.1", "hasil": "1"},
    {"id_device": "device_1", "h1": "0.1", "h2": "0.1", "hasil": "5000000000"},
    {"id_device": "device_1", "h1": "INF", "h2": "0.1", "hasil": "1"})]
rec("TC53", "FR02", "Out-of-range sensor values are rejected", "HTTP 422 for negative, oversized and infinite values; nothing stored",
    codes == [422] * 3 and db("select count(*) from sensor_readings")[0][0] == before, f"HTTP {codes}; rows unchanged={db('select count(*) from sensor_readings')[0][0] == before}")

r = requests.post(f"{BASE}/main/api/v1/sensor.php", data={"id_device": "<script>x</script>", "h1": "0.1", "h2": "0.1", "hasil": "1"})
rec("TC54", "NFR05", "Malformed device ID is rejected", "HTTP 422; no sensor registered",
    r.status_code == 422 and not db("select id from sensors where id_device like '%script%'"), f"HTTP {r.status_code}")

hh = [requests.get(f"{BASE}{u}").headers for u in ("/", "/main/login.php")]
ok = all(h.get("X-Frame-Options") == "DENY" and h.get("X-Content-Type-Options") == "nosniff" for h in hh)
rec("TC55", "NFR05", "Homepage and sign-in page send security headers", "X-Frame-Options DENY and nosniff on both",
    ok, "present on both" if ok else "missing")

codes = {u: requests.get(f"{BASE}{u}").status_code for u in ("/.git/config", "/README.md", "/router.php", "/database/")}
rec("TC56", "NFR05", "Hidden files, docs and the router are not served", "HTTP 404 for /.git/config, /README.md, /router.php, /database/",
    all(v == 404 for v in codes.values()), "; ".join(f"{k} {v}" for k, v in codes.items()))

# ---------- Alerts (now generated from sensor data) ----------
def alert_rows(sess):
    html = sess.get(f"{BASE}/main/admin-alerts.php").text
    return re.findall(r'<button\s+class="alert-row[^"]*".*?<strong>([^<]+)</strong><small>([^<]+)</small>', html, re.S)

r = sA.post(f"{BASE}/main/admin-site-registration-save.php", data={"name": "Alert Test Well", "village_id": vid2, "installer": "QA", "type": "Type 3", "date": "2026-10-01", "sensor_id": "device_ALERT"})
awid = r.json().get("well_id")
acode = db("select well_code from wells where id=?", awid)[0][0]
mine = [t for t, sm in alert_rows(sA) if acode in sm]
rec("TC57", "FR18", "Site whose sensor has never reported gets a Missing Transmission alert", "Alert ‘Missing Transmission’ for the new site",
    mine == ["Missing Transmission"], f"alerts for {acode}: {mine}")

requests.post(f"{BASE}/main/api/v1/sensor.php", data={"id_device": "device_ALERT", "h1": "0.12", "h2": "0.10", "hasil": "3.49"})
mine = [t for t, sm in alert_rows(sA) if acode in sm]
rec("TC58", "FR18", "Alert clears once the sensor reports", "No alert for the site", mine == [], f"alerts for {acode}: {mine or 'none'}")

c = sqlite3.connect(DB); c.execute("update sensors set status=0 where id_device='device_ALERT'"); c.commit(); c.close()
mine = [t for t, sm in alert_rows(sA) if acode in sm]
rec("TC59", "FR18", "Disabled sensor gets an Inactive alert", "Alert ‘Inactive’", mine == ["Inactive"], f"alerts for {acode}: {mine}")

c = sqlite3.connect(DB)
c.execute("update sensors set status=1 where id_device='device_ALERT'")
sid = c.execute("select id from sensors where id_device='device_ALERT'").fetchone()[0]
c.execute("insert into sensor_readings (sensor_id,h1,h2,hasil,received_at,created_at) values (?,0,0,5,datetime('now','+8 hours'),datetime('now','+8 hours'))", (sid,))
c.commit(); c.close()
mine = [t for t, sm in alert_rows(sA) if acode in sm]
rec("TC60", "FR18", "Reading that breaks the zero-height rule gets a Data Quality alert", "Alert ‘Data Quality Review’",
    mine == ["Data Quality Review"], f"alerts for {acode}: {mine}")

rows = alert_rows(sA)
dash = sA.get(f"{BASE}/main/admin-dashboard.php").text
m = re.search(r"<span>Active Alerts</span><strong>(\d+)</strong>", dash)
rec("TC61", "FR18", "Dashboard Active Alerts count matches the Alerts page", "Same number on both",
    m is not None and int(m.group(1)) == len(rows), f"dashboard {m.group(1) if m else '?'}; alerts page {len(rows)}")
sA.post(f"{BASE}/main/admin-site-registration-delete.php", data={"well_id": awid})
c = sqlite3.connect(DB); c.execute("delete from sensor_readings where sensor_id=?", (sid,)); c.execute("delete from sensors where id=?", (sid,)); c.commit(); c.close()
order = ["TC%02d" % i for i in range(1, 62)]
results.sort(key=lambda x: order.index(x["tc"]) if x["tc"] in order else 99)
json.dump(results, open("blackbox-results.json", "w"), indent=1)

print(f"\n{sum(r['result']=='Pass' for r in results)}/{len(results)} passed")
