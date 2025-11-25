import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30

def test_get_admin_dashboard():
    url = f"{BASE_URL}/admin"
    headers = {
        "Accept": "application/json",
    }
    try:
        response = requests.get(url, headers=headers, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request to {url} failed: {e}"

    assert response.status_code == 200, f"Expected status code 200, got {response.status_code}"

    try:
        data = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    # Validate dashboard keys expected to represent real-time statistics and overview
    expected_keys = [
        "statistics",
        "overview",
        "products_count",
        "orders_count",
        "users_count",
        "staff_count",
        "coupons_count",
        "inventory_status",
        "delivery_status",
        "customers_count",
        "referrals",
        "withdrawals"
    ]

    # Basic validation that at least these keys exist in the dashboard response
    missing_keys = [key for key in expected_keys if key not in data]
    assert not missing_keys, f"Response JSON missing expected keys: {missing_keys}"

test_get_admin_dashboard()