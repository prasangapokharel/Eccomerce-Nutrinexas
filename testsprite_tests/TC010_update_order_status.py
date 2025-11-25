import requests
import random
import string

BASE_URL = "http://localhost:8000"
TIMEOUT = 30
HEADERS = {
    "Content-Type": "application/json",
    # Include auth headers here if required, e.g.:
    # "Authorization": "Bearer <token>"
}

def create_order():
    url = f"{BASE_URL}/admin/orders"
    # Minimal payload assumed as API spec does not define request body for order creation
    response = requests.post(url, headers=HEADERS, timeout=TIMEOUT)
    response.raise_for_status()
    assert response.status_code == 201
    order = response.json()
    order_id = order.get("id")
    assert order_id is not None
    return order_id

def get_order(order_id):
    url = f"{BASE_URL}/admin/orders/{order_id}"
    response = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
    response.raise_for_status()
    assert response.status_code == 200
    return response.json()

def update_order_status():
    order_id = None
    try:
        # Step 1: Create a new order to update
        order_id = create_order()

        # Step 2: Update the order status
        url = f"{BASE_URL}/admin/orders/{order_id}"
        new_status = random.choice([
            "pending",
            "processing",
            "shipped",
            "delivered",
            "cancelled",
            "returned"
        ])

        payload = {
            "status": new_status
        }

        response = requests.put(url, headers=HEADERS, json=payload, timeout=TIMEOUT)
        assert response.status_code == 200

        # Step 3: Verify the order status is updated
        order_data = get_order(order_id)
        # The structure of order_data is unknown; assume status is under 'status' key
        assert "status" in order_data
        assert order_data["status"] == new_status

    except requests.HTTPError as http_err:
        assert False, f"HTTP error occurred: {http_err}"
    except requests.RequestException as req_err:
        assert False, f"Request error occurred: {req_err}"
    except AssertionError:
        raise
    finally:
        if order_id:
            try:
                del_response = requests.delete(f"{BASE_URL}/admin/orders/{order_id}", headers=HEADERS, timeout=TIMEOUT)
                if del_response.status_code not in (200, 204):
                    # May log failure to clean up resource if logging was permitted
                    pass
            except Exception:
                pass

update_order_status()