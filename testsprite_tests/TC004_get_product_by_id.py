import requests

BASE_URL = "http://localhost:8000"
HEADERS = {
    "Content-Type": "application/json",
    # Add authentication headers here if required, e.g. "Authorization": "Bearer <token>"
}
TIMEOUT = 30


def test_get_product_by_id():
    product_data = {
        "name": "Test Product TC004",
        "price": 19.99,
        "description": "Description for test product TC004",
        "category": "Test Category"
    }

    product_id = None
    try:
        # Create a new product first to get a valid product ID
        create_resp = requests.post(
            f"{BASE_URL}/admin/products",
            json=product_data,
            headers=HEADERS,
            timeout=TIMEOUT,
        )
        assert create_resp.status_code == 201, f"Product creation failed: {create_resp.text}"
        created_product = create_resp.json()
        # The response should include at least the product ID; adapt as needed
        product_id = created_product.get("id")
        assert product_id is not None, "Created product ID not found in response"

        # GET the product by ID
        get_resp = requests.get(
            f"{BASE_URL}/admin/products/{product_id}",
            headers=HEADERS,
            timeout=TIMEOUT,
        )
        assert get_resp.status_code == 200, f"Get product by ID failed: {get_resp.text}"

        product = get_resp.json()
        # Validate returned product details match what was created
        assert product.get("id") == product_id, "Product ID does not match"
        assert product.get("name") == product_data["name"], "Product name mismatch"
        assert float(product.get("price", 0)) == product_data["price"], "Product price mismatch"
        assert product.get("description") == product_data["description"], "Product description mismatch"
        assert product.get("category") == product_data["category"], "Product category mismatch"

    finally:
        # Cleanup: Delete the created product if it exists
        if product_id is not None:
            try:
                delete_resp = requests.delete(
                    f"{BASE_URL}/admin/products/{product_id}",
                    headers=HEADERS,
                    timeout=TIMEOUT,
                )
                assert delete_resp.status_code == 200, f"Cleanup delete failed: {delete_resp.text}"
            except Exception:
                pass


test_get_product_by_id()