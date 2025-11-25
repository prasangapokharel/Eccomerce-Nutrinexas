import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30
HEADERS = {
    "Accept": "application/json",
    # Add authentication headers here if required, e.g. "Authorization": "Bearer <token>"
}

def test_get_all_orders():
    url = f"{BASE_URL}/admin/orders"
    try:
        response = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
        response.raise_for_status()
    except requests.RequestException as e:
        assert False, f"Request to get all orders failed: {e}"

    assert response.status_code == 200, f"Expected status code 200, got {response.status_code}"

    try:
        orders = response.json()
    except ValueError:
        assert False, "Response is not valid JSON"

    assert isinstance(orders, list), f"Expected response to be a list of orders, got {type(orders)}"

    # Further validation could include checking structure of each order but not specified in test case
    for order in orders:
        assert isinstance(order, dict), f"Each order should be a dictionary, got {type(order)}"
        assert "id" in order, "Order dictionary missing 'id' field"
        assert "status" in order or "order_status" in order, "Order dictionary missing status field"

test_get_all_orders()