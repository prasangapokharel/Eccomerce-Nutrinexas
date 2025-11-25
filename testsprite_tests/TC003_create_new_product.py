import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30
HEADERS = {
    "Content-Type": "application/json"
}

def test_create_new_product():
    product_data = {
        "name": "Test Product TC003",
        "price": 99.99,
        "description": "A test product created for test case TC003",
        "category": "Test Category"
    }
    created_product_id = None
    try:
        # Create new product
        response = requests.post(
            f"{BASE_URL}/admin/products",
            json=product_data,
            headers=HEADERS,
            timeout=TIMEOUT
        )
        # Assert status code 201 Created
        assert response.status_code == 201, f"Expected status code 201, got {response.status_code}"
        resp_json = response.json()
        # Validate response content has at least the sent fields and an id
        assert "id" in resp_json, "Response JSON should contain 'id'"
        created_product_id = resp_json["id"]
        assert resp_json.get("name") == product_data["name"], "Product name in response does not match"
        assert float(resp_json.get("price")) == product_data["price"], "Product price in response does not match"
        assert resp_json.get("description") == product_data["description"], "Product description in response does not match"
        assert resp_json.get("category") == product_data["category"], "Product category in response does not match"

        # Get the product by id to verify it is saved properly
        get_response = requests.get(
            f"{BASE_URL}/admin/products/{created_product_id}",
            headers=HEADERS,
            timeout=TIMEOUT
        )
        assert get_response.status_code == 200, f"Expected status code 200 when retrieving product, got {get_response.status_code}"
        get_json = get_response.json()
        # Confirm the retrieved product details match
        assert get_json.get("id") == created_product_id, "Retrieved product id does not match created product id"
        assert get_json.get("name") == product_data["name"], "Retrieved product name does not match"
        assert float(get_json.get("price")) == product_data["price"], "Retrieved product price does not match"
        assert get_json.get("description") == product_data["description"], "Retrieved product description does not match"
        assert get_json.get("category") == product_data["category"], "Retrieved product category does not match"

    finally:
        # Cleanup: delete the created product if created
        if created_product_id:
            try:
                delete_response = requests.delete(
                    f"{BASE_URL}/admin/products/{created_product_id}",
                    headers=HEADERS,
                    timeout=TIMEOUT
                )
                assert delete_response.status_code == 200, f"Expected status code 200 on delete, got {delete_response.status_code}"
            except Exception:
                pass

test_create_new_product()