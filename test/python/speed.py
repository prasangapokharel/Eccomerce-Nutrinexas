"""Selenium page speed tester for multiple URLs.

This script measures page load times using a headless Chrome browser for
the specified URLs. It performs an HTTP status check first, then loads
each page in Selenium and records timing metrics.

Usage: `python test/python/speed.py`
"""

import time
import requests
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException


# URLs to test
TEST_URLS = [
    "http://192.168.1.77:8000/",
    "http://192.168.1.77:8000/cart",
    "http://192.168.1.77:8000/checkout",
]


def setup_driver() -> webdriver.Chrome:
    """Configure and return a headless Chrome WebDriver.

    Uses Selenium Manager to resolve the appropriate driver.
    """
    options = Options()
    # Use new headless mode for modern Chrome versions
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--window-size=1366,768")
    # Ensure standard page load strategy
    options.page_load_strategy = "normal"

    driver = webdriver.Chrome(options=options)
    driver.set_page_load_timeout(30)
    return driver


def navigation_duration_ms(driver: webdriver.Chrome) -> float:
    """Return navigation duration in milliseconds from the Performance API.

    Falls back to deprecated performance.timing if navigation entries are
    unavailable.
    """
    try:
        duration = driver.execute_script(
            """
            const nav = performance.getEntriesByType('navigation')[0];
            if (nav && nav.duration) { return nav.duration; }
            const t = performance.timing;
            return (t.loadEventEnd && t.navigationStart)
                ? (t.loadEventEnd - t.navigationStart)
                : 0;
            """
        )
        return float(duration) if duration else 0.0
    except Exception:
        return 0.0


def measure_url(driver: webdriver.Chrome, url: str, timeout: int = 30) -> dict:
    """Measure page load time for a single URL using Selenium.

    Returns a dict with status_code, selenium_ms, navigation_ms, and error.
    """
    result = {
        "url": url,
        "status_code": None,
        "selenium_ms": None,
        "navigation_ms": None,
        "error": None,
    }

    # Preflight HTTP check
    try:
        resp = requests.get(url, timeout=10)
        result["status_code"] = resp.status_code
    except requests.RequestException as exc:
        result["error"] = f"HTTP error: {exc}"
        return result

    start = time.perf_counter()
    try:
        driver.get(url)
        # Wait for document to be fully loaded
        WebDriverWait(driver, timeout).until(
            lambda d: d.execute_script("return document.readyState") == "complete"
        )
        # Ensure <body> is present
        WebDriverWait(driver, timeout).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        elapsed_ms = (time.perf_counter() - start) * 1000.0
        nav_ms = navigation_duration_ms(driver)
        result["selenium_ms"] = elapsed_ms
        result["navigation_ms"] = nav_ms
    except TimeoutException as exc:
        result["error"] = f"Timeout waiting for page load: {exc}"
    except NoSuchElementException as exc:
        result["error"] = f"Element not found: {exc}"
    except Exception as exc:
        result["error"] = f"Unexpected error: {exc}"

    return result


def print_result(result: dict) -> None:
    """Pretty-print a single measurement result."""
    url = result.get("url")
    status = result.get("status_code")
    sel_ms = result.get("selenium_ms")
    nav_ms = result.get("navigation_ms")
    error = result.get("error")

    if error:
        print(f"- {url} -> ERROR: {error}")
    else:
        sel_txt = f"{sel_ms:.0f} ms" if sel_ms is not None else "n/a"
        nav_txt = f"{nav_ms:.0f} ms" if nav_ms is not None else "n/a"
        print(
            f"- {url} -> status: {status}, selenium load: {sel_txt}, navigation: {nav_txt}"
        )


def main() -> None:
    """Entry point for running measurements across all URLs."""
    driver = None
    try:
        driver = setup_driver()
        print("Starting Selenium page speed measurements...\n")
        for url in TEST_URLS:
            result = measure_url(driver, url)
            print_result(result)
    finally:
        if driver is not None:
            driver.quit()
        print("\nCompleted measurements. Resources cleaned up.")


if __name__ == "__main__":
    main()