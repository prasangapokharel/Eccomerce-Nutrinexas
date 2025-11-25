import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30
HEADERS = {
    "Accept": "application/json",
    "Content-Type": "application/json"
}

def test_get_order_by_id():
    order_id = None
    # Create an order first to get a valid order_id
    create_order_url = f"{BASE_URL}/admin/orders"
    try:
        create_resp = requests.post(create_order_url, headers=HEADERS, json={}, timeout=TIMEOUT)
        assert create_resp.status_code == 201, f"Failed to create order: {create_resp.text}"
        order_data = create_resp.json()
        order_id = order_data.get("id")
        assert order_id is not None, "Created order ID missing in response."

        # Now get the order by ID
        get_order_url = f"{BASE_URL}/admin/orders/{order_id}"
        get_resp = requests.get(get_order_url, headers=HEADERS, timeout=TIMEOUT)
        assert get_resp.status_code == 200, f"Get order by ID failed: {get_resp.text}"
        retrieved_order = get_resp.json()

        # Validate the retrieved order ID matches the created order ID
        assert isinstance(retrieved_order, dict), "Response is not a JSON object."
        assert retrieved_order.get("id") == order_id, "Retrieved order ID does not match requested ID."

    finally:
        # Clean up: delete the created order if possible (assuming an endpoint exists)
        if order_id is not None:
            delete_order_url = f"{BASE_URL}/admin/orders/{order_id}"
            try:
                # Using DELETE method with a timeout and ignoring response validation here
                requests.delete(delete_order_url, headers=HEADERS, timeout=TIMEOUT)
            except Exception:
                pass

test_get_order_by_id()