import requests

BASE_URL = "http://localhost:8000"
ADMIN_ORDERS_ENDPOINT = "/admin/orders"
TIMEOUT = 30
HEADERS = {
    "Content-Type": "application/json",
    # Add authentication header here if required, e.g. "Authorization": "Bearer <token>"
}

def test_create_new_order():
    try:
        json_payload = {}
        response = requests.post(
            BASE_URL + ADMIN_ORDERS_ENDPOINT,
            headers=HEADERS,
            json=json_payload,
            timeout=TIMEOUT
        )
        assert response.status_code == 201, f"Expected status code 201, got {response.status_code}"
        # Response schema details are missing; minimal validation done
        response_json = response.json()
        assert isinstance(response_json, dict), "Response is not a JSON object"

    except requests.RequestException as e:
        assert False, f"Request failed: {e}"


test_create_new_order()
