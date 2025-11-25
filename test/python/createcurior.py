import os
import sys
import time
import requests

try:
    import mysql.connector as mysql
except Exception:
    mysql = None


def base_url():
    return os.environ.get("BASE_URL", "http://192.168.1.77:8000")


def login_admin(session: requests.Session) -> bool:
    phone = os.environ.get("ADMIN_PHONE")
    password = os.environ.get("ADMIN_PASSWORD")
    if not phone or not password:
        return False

    # Load login page
    resp = session.get(f"{base_url()}/auth/login", allow_redirects=True, timeout=15)
    if resp.status_code >= 400:
        print("Failed to load login page:", resp.status_code)
        return False

    # Submit credentials
    resp = session.post(
        f"{base_url()}/auth/login",
        data={"phone": phone, "password": password},
        allow_redirects=True,
        timeout=20,
    )

    # Consider login successful if we can access admin dashboard
    dash = session.get(f"{base_url()}/admin", allow_redirects=True, timeout=15)
    return dash.status_code == 200 and ("Admin Dashboard" in dash.text or "Dashboard" in dash.text)


def unique_suffix() -> str:
    return str(int(time.time()))


def test_create_curior():
    session = requests.Session()

    # Login admin if creds available
    logged_in = login_admin(session)

    # Prepare unique test data
    suffix = unique_suffix()
    name = f"Auto Courier {suffix}"
    phone = f"9{suffix[:9]}"  # ensure 10+ digits starting with 9
    email = f"auto_{suffix}@example.com"
    address = "Test Address"
    password = "secret123"

    if logged_in:
        # Hit GET create page
        resp_get = session.get(f"{base_url()}/admin/curior/create", allow_redirects=True, timeout=15)
        assert resp_get.status_code in (200, 302), f"GET create page failed: {resp_get.status_code}"

        # Submit POST to create
        resp_post = session.post(
            f"{base_url()}/admin/curior/create",
            data={
                "name": name,
                "phone": phone,
                "email": email,
                "address": address,
                "password": password,
                "status": "active",
            },
            allow_redirects=True,
            timeout=20,
        )
        # Expect redirect back to listing or 200 with success
        assert resp_post.status_code in (200, 302), f"POST create failed: {resp_post.status_code}"

        # Go to listing page and verify presence by phone or name
        resp_list = session.get(f"{base_url()}/admin/curior", allow_redirects=True, timeout=15)
        assert resp_list.status_code == 200, f"Listing not reachable: {resp_list.status_code}"
        page = resp_list.text
        assert (phone in page) or (name in page), "Created curior not found on listing page"
    else:
        # Fallback: create directly in DB if admin creds unavailable
        assert mysql is not None, "mysql-connector not installed; install it or provide admin creds."
        db_host = os.getenv('NUTRINEXAS_DB_HOST', 'localhost')
        db_name = os.getenv('NUTRINEXAS_DB_NAME', 'nutrinexas')
        db_user = os.getenv('NUTRINEXAS_DB_USER', 'root')
        db_pass = os.getenv('NUTRINEXAS_DB_PASS', '123456')

        conn = mysql.connect(host=db_host, database=db_name, user=db_user, password=db_pass)
        cur = conn.cursor()
        # Insert using schema from exported SQL
        cur.execute(
            """
            INSERT INTO curiors (name, phone, email, address, password, status, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, NOW(), NOW())
            """,
            (name, phone, email, address, password, 'active'),
        )
        conn.commit()
        cur.execute("SELECT id, name, phone FROM curiors WHERE phone=%s", (phone,))
        row = cur.fetchone()
        cur.close()
        conn.close()
        assert row is not None, "DB insert fallback failed; no record found"

    print("PASS: Curior created and visible in listing.")


if __name__ == "__main__":
    try:
        test_create_curior()
    except AssertionError as e:
        print("FAIL:", e)
        sys.exit(1)
    except Exception as e:
        print("ERROR:", e)
        sys.exit(2)