"""Analyze website request load using Selenium Resource Timing API.

This script loads pages in a headless Chrome instance, then extracts
Resource Timing entries to report which requests are heavy and what
types of resources dominate the network load.

Pages analyzed:
- http://192.168.1.77:8000/
- http://192.168.1.77:8000/cart
- http://192.168.1.77:8000/checkout

Run: python test/python/test.py
"""

import time
import requests
from collections import defaultdict
from typing import Dict, Any, List

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, NoSuchElementException


TEST_URLS = [
    "http://192.168.1.77:8000/",
    "http://192.168.1.77:8000/cart",
    "http://192.168.1.77:8000/checkout",
]


def setup_driver() -> webdriver.Chrome:
    options = Options()
    options.add_argument("--headless=new")
    options.add_argument("--disable-gpu")
    options.add_argument("--no-sandbox")
    options.add_argument("--window-size=1366,768")
    options.page_load_strategy = "normal"
    driver = webdriver.Chrome(options=options)
    driver.set_page_load_timeout(30)
    return driver


def get_resource_entries(driver: webdriver.Chrome) -> List[Dict[str, Any]]:
    """Return a simplified list of PerformanceResourceTiming entries."""
    try:
        entries = driver.execute_script(
            """
            return performance.getEntriesByType('resource').map(e => ({
                name: e.name,
                initiatorType: e.initiatorType,
                startTime: e.startTime,
                duration: e.duration,
                transferSize: (typeof e.transferSize === 'number') ? e.transferSize : 0,
                encodedBodySize: (typeof e.encodedBodySize === 'number') ? e.encodedBodySize : 0,
                decodedBodySize: (typeof e.decodedBodySize === 'number') ? e.decodedBodySize : 0
            }));
            """
        )
        return entries or []
    except Exception:
        return []


def measure_and_analyze(driver: webdriver.Chrome, url: str) -> Dict[str, Any]:
    result: Dict[str, Any] = {
        "url": url,
        "status_code": None,
        "selenium_ms": None,
        "resources": [],
        "error": None,
        "summary": {},
        "top_requests": [],
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
        WebDriverWait(driver, 30).until(
            lambda d: d.execute_script("return document.readyState") == "complete"
        )
        WebDriverWait(driver, 30).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        elapsed_ms = (time.perf_counter() - start) * 1000.0
        result["selenium_ms"] = elapsed_ms

        # Gather resource timing
        resources = get_resource_entries(driver)
        result["resources"] = resources

        # Build summary by initiatorType
        by_type_counts = defaultdict(int)
        by_type_bytes = defaultdict(int)
        total_bytes = 0
        for r in resources:
            itype = r.get("initiatorType") or "other"
            size = int(r.get("transferSize") or r.get("encodedBodySize") or 0)
            by_type_counts[itype] += 1
            by_type_bytes[itype] += size
            total_bytes += size

        # Top heavy requests by transfer size
        top = sorted(
            resources,
            key=lambda x: int(x.get("transferSize") or x.get("encodedBodySize") or 0),
            reverse=True,
        )[:10]

        result["summary"] = {
            "total_requests": len(resources),
            "total_bytes": total_bytes,
            "by_type": {
                k: {"count": by_type_counts[k], "bytes": by_type_bytes[k]}
                for k in sorted(by_type_counts.keys())
            },
        }
        result["top_requests"] = top
    except TimeoutException as exc:
        result["error"] = f"Timeout: {exc}"
    except NoSuchElementException as exc:
        result["error"] = f"Element not found: {exc}"
    except Exception as exc:
        result["error"] = f"Unexpected error: {exc}"

    return result


def format_bytes(n: int) -> str:
    units = ["B", "KB", "MB", "GB"]
    size = float(n)
    for unit in units:
        if size < 1024 or unit == units[-1]:
            return f"{size:.1f} {unit}"
        size /= 1024


def print_analysis(result: Dict[str, Any]) -> None:
    url = result.get("url")
    status = result.get("status_code")
    load_ms = result.get("selenium_ms")
    error = result.get("error")
    summary = result.get("summary", {})

    print(f"\nURL: {url}")
    if error:
        print(f"  ERROR: {error}")
        return

    print(f"  Status: {status}")
    if load_ms is not None:
        print(f"  Page Load (Selenium): {load_ms:.0f} ms")

    total_requests = summary.get("total_requests", 0)
    total_bytes = int(summary.get("total_bytes", 0))
    print(f"  Total Requests: {total_requests}")
    print(f"  Total Transfer: {format_bytes(total_bytes)}")

    # By type
    by_type: Dict[str, Dict[str, int]] = summary.get("by_type", {})
    if by_type:
        print("  By Resource Type:")
        for k, v in sorted(by_type.items(), key=lambda kv: kv[1]["bytes"], reverse=True):
            print(f"    - {k}: {v['count']} reqs, {format_bytes(int(v['bytes']))}")

    # Top heavy requests
    top: List[Dict[str, Any]] = result.get("top_requests", [])
    if top:
        print("  Top Heavy Requests:")
        for i, r in enumerate(top, 1):
            size = int(r.get("transferSize") or r.get("encodedBodySize") or 0)
            print(
                f"    {i}. {format_bytes(size)} | {r.get('initiatorType','other')} | {r.get('name','').split('?')[0]}"
            )


def main() -> None:
    driver = None
    try:
        driver = setup_driver()
        print("Starting request load analysis...")
        for url in TEST_URLS:
            res = measure_and_analyze(driver, url)
            print_analysis(res)
    finally:
        if driver is not None:
            driver.quit()
        print("\nAnalysis complete. Resources cleaned up.")


if __name__ == "__main__":
    main()