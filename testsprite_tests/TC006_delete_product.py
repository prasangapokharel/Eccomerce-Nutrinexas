import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30
HEADERS = {
    "Content-Type": "application/json",
    # Add authentication headers here if required, e.g. "Authorization": "Bearer <token>"
    "Authorization": "Bearer YOUR_ADMIN_TOKEN_HERE"
}


def test_delete_product():
    product_data = {
        "name": "Test Product Delete",
        "price": 19.99,
        "description": "Temporary product for delete test",
        "category": "Test Category"
    }
    product_id = None

    try:
        # Create a new product to delete
        response_create = requests.post(
            f"{BASE_URL}/admin/products",
            json=product_data,
            headers=HEADERS,
            timeout=TIMEOUT
        )
        assert response_create.status_code == 201, f"Product creation failed: {response_create.text}"
        # Check response content-type before json parsing
        content_type = response_create.headers.get('Content-Type', '')
        assert 'application/json' in content_type, f"Unexpected content-type: {content_type}"
        product_info = response_create.json()
        # Assuming the response returns the created product with an 'id' field
        product_id = product_info.get("id")
        assert product_id is not None, "Created product ID not found in response"

        # Delete the created product
        response_delete = requests.delete(
            f"{BASE_URL}/admin/products/{product_id}",
            headers=HEADERS,
            timeout=TIMEOUT
        )
        assert response_delete.status_code == 200, f"Product deletion failed: {response_delete.text}"

        # Verify the product no longer appears in product listings
        response_list = requests.get(
            f"{BASE_URL}/admin/products",
            headers=HEADERS,
            timeout=TIMEOUT
        )
        assert response_list.status_code == 200, f"Failed to retrieve products list: {response_list.text}"
        products_list = response_list.json()
        # products_list expected to be a list of products
        assert isinstance(products_list, list), f"Product list response is not a list: {products_list}"
        product_ids = [product.get("id") for product in products_list if "id" in product]
        assert product_id not in product_ids, "Deleted product still found in product listings"

    finally:
        if product_id is not None:
            # Cleanup in case the product was not deleted
            requests.delete(
                f"{BASE_URL}/admin/products/{product_id}",
                headers=HEADERS,
                timeout=TIMEOUT
            )


test_delete_product()
