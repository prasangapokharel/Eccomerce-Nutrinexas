import requests

BASE_URL = "http://localhost:8000"
TIMEOUT = 30
HEADERS = {
    'Accept': 'application/json',
}


def test_get_all_products():
    url = f"{BASE_URL}/admin/products"
    try:
        response = requests.get(url, headers=HEADERS, timeout=TIMEOUT)
        assert response.status_code == 200, f"Expected status code 200 but got {response.status_code}"
        content_type = response.headers.get('Content-Type', '')
        assert 'application/json' in content_type.lower(), f"Expected Content-Type application/json but got {content_type}"
        assert response.text.strip() != '', "Response body is empty"
        data = response.json()
        assert isinstance(data, list), "Response data should be a list of products"
        expected_keys = {"name", "price", "description", "category"}
        for product in data:
            assert isinstance(product, dict), "Each product should be a dictionary"
            assert expected_keys.issubset(product.keys()), f"Product keys missing. Expected keys: {expected_keys}"
    except requests.RequestException as e:
        assert False, f"Request failed: {e}"
    except ValueError as e:
        assert False, f"Invalid JSON response: {e}"


test_get_all_products()
