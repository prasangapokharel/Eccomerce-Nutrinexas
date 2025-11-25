import requests

BASE_URL = "http://localhost:8000"
HEADERS = {
    "Content-Type": "application/json",
    # Add authentication headers here if required, e.g.:
    # "Authorization": "Bearer <token>"
}
TIMEOUT = 30

def test_update_product():
    # Sample product payload for creation
    product_create_payload = {
        "name": "Test Product Update",
        "price": 49.99,
        "description": "Initial description for update test",
        "category": "Test Category"
    }
    # Updated product data
    product_update_payload = {
        "name": "Test Product Updated",
        "price": 59.99,
        "description": "Updated description after PUT",
        "category": "Updated Category"
    }

    product_id = None
    try:
        # Create a product first to ensure we have a product to update
        create_response = requests.post(
            f"{BASE_URL}/admin/products",
            headers=HEADERS,
            json=product_create_payload,
            timeout=TIMEOUT
        )
        assert create_response.status_code == 201, f"Product creation failed: {create_response.text}"
        product_data = create_response.json()
        # Assuming the created product ID is returned under 'id'
        product_id = product_data.get("id")
        assert product_id is not None, "Created product ID is missing in response"

        # Update the product using PUT
        update_response = requests.put(
            f"{BASE_URL}/admin/products/{product_id}",
            headers=HEADERS,
            json=product_update_payload,
            timeout=TIMEOUT
        )
        assert update_response.status_code == 200, f"Product update failed: {update_response.text}"

        # Retrieve the product to verify the update
        get_response = requests.get(
            f"{BASE_URL}/admin/products/{product_id}",
            headers=HEADERS,
            timeout=TIMEOUT
        )
        assert get_response.status_code == 200, f"Failed to retrieve product after update: {get_response.text}"
        updated_product = get_response.json()
        # Validate the updated fields
        assert updated_product.get("name") == product_update_payload["name"], "Product name not updated correctly"
        assert float(updated_product.get("price", 0)) == product_update_payload["price"], "Product price not updated correctly"
        assert updated_product.get("description") == product_update_payload["description"], "Product description not updated correctly"
        assert updated_product.get("category") == product_update_payload["category"], "Product category not updated correctly"

    finally:
        # Clean up by deleting the created product if it exists
        if product_id is not None:
            try:
                delete_response = requests.delete(
                    f"{BASE_URL}/admin/products/{product_id}",
                    headers=HEADERS,
                    timeout=TIMEOUT
                )
                assert delete_response.status_code == 200, f"Failed to delete product in cleanup: {delete_response.text}"
            except Exception as e:
                # Log or pass because cleanup should not raise exceptions
                pass

test_update_product()
