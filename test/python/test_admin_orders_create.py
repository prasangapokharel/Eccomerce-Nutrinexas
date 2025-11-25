import os
import sys
import requests


def base_url():
    return os.environ.get("BASE_URL", "http://localhost:8000")


def login_admin(session: requests.Session) -> bool:
    phone = os.environ.get("ADMIN_PHONE")
    password = os.environ.get("ADMIN_PASSWORD")
    if not phone or not password:
        return False

    resp = session.get(f"{base_url()}/auth/login", allow_redirects=True)
    if resp.status_code >= 400:
        print("Failed to load login page:", resp.status_code)
        return False

    resp = session.post(
        f"{base_url()}/auth/login",
        data={"phone": phone, "password": password},
        allow_redirects=True,
    )

    # Consider login successful if we can access admin dashboard
    dash = session.get(f"{base_url()}/admin", allow_redirects=True)
    return dash.status_code == 200 and "Admin Dashboard" in dash.text


def test_admin_orders_create_route():
    session = requests.Session()

    # Try to login if credentials provided
    logged_in = login_admin(session)
    if logged_in:
        print("Logged in as admin.")
    else:
        print("Proceeding without admin login.")

    # Hit the create order route
    resp = session.get(f"{base_url()}/admin/orders/create", allow_redirects=True)

    # The route should exist (not 404/500)
    assert resp.status_code in (200, 302), f"Unexpected status: {resp.status_code}"

    # If accessible and logged in, the page should contain the expected header
    if resp.status_code == 200 and logged_in:
        assert "Create Order" in resp.text, "Page content does not include 'Create Order'"


if __name__ == "__main__":
    try:
        test_admin_orders_create_route()
        print("PASS: /admin/orders/create route is reachable and renders as expected.")
    except AssertionError as e:
        print("FAIL:", e)
        sys.exit(1)
    except Exception as e:
        print("ERROR:", e)
        sys.exit(2)