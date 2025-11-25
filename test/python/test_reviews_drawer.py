"""UI and gating test for My Review drawer on product view.

This script verifies:
- Product view renders the My Review section and drawer markup.
- Server-side gating rejects review submission for guests (not logged in).

Run: python test/python/test_reviews_drawer.py
"""

import time
import json
import requests
from typing import Optional

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException


BASE = "http://192.168.1.77:8000"


def setup_driver() -> webdriver.Chrome:
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--window-size=1366,768")
    driver = webdriver.Chrome(options=options)
    driver.set_page_load_timeout(30)
    return driver


def find_any_product_view_url(driver: webdriver.Chrome) -> Optional[str]:
    """Navigate to products page and return a first product view URL."""
    products_url = f"{BASE}/products"
    driver.get(products_url)
    WebDriverWait(driver, 30).until(
        lambda d: d.execute_script("return document.readyState") == "complete"
    )
    # Try anchors that link to product view
    links = driver.find_elements(By.CSS_SELECTOR, "a[href*='/products/view/']")
    if links:
        return links[0].get_attribute("href")
    # Fallback to id=1 if no links found
    return f"{BASE}/products/view/1"


def test_ui_and_gating() -> None:
    driver = None
    try:
        driver = setup_driver()
        view_url = find_any_product_view_url(driver)

        # Load product view
        driver.get(view_url)
        WebDriverWait(driver, 30).until(
            EC.presence_of_element_located((By.ID, "my-review-section"))
        )

        # Assert drawer markup exists
        drawer = driver.find_element(By.ID, "myReviewDrawer")
        assert drawer is not None, "Review drawer not found"

        # Extract product_id from hidden input
        hidden_inputs = driver.find_elements(By.CSS_SELECTOR, "#myReviewForm input[name='product_id']")
        assert hidden_inputs, "Hidden product_id input not found in review form"
        product_id = hidden_inputs[0].get_attribute("value")
        assert product_id and product_id.isdigit(), "Invalid product_id value"

        # Attempt guest submission (no session)
        resp = requests.post(
            f"{BASE}/reviews/submitAjax",
            data={
                "product_id": product_id,
                "rating": 5,
                "review_text": "Automated test review as guest",
            },
            headers={"X-Requested-With": "XMLHttpRequest"},
            timeout=15,
        )

        # Validate JSON response structure and gating message
        assert resp.status_code == 200, f"Unexpected status: {resp.status_code}"
        data = resp.json()
        assert isinstance(data, dict), "Response is not JSON object"
        assert data.get("success") is False, "Guest review should be rejected"
        msg = (data.get("message") or "").lower()
        assert ("login" in msg) or ("ordered" in msg), f"Unexpected message: {data.get('message')}"

        print("UI present and gating works for guest submissions.")

    except TimeoutException as exc:
        raise AssertionError(f"Timeout while loading page: {exc}")
    except NoSuchElementException as exc:
        raise AssertionError(f"Missing expected UI element: {exc}")
    finally:
        if driver is not None:
            driver.quit()


if __name__ == "__main__":
    test_ui_and_gating()