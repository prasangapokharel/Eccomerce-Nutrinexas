import time
import requests

BASE_URL = "http://127.0.0.1:8000"

ROUTES = [
    "/admin",
    "/admin/products",
    "/admin/users",
    "/admin/blog",
    "/admin/coupons",
]

def measure_route(url: str, timeout: float = 10.0):
    start = time.perf_counter()
    try:
        r = requests.get(url, timeout=timeout)
        status = r.status_code
    except requests.RequestException as e:
        status = "ERR"
    ms = (time.perf_counter() - start) * 1000.0
    return {"url": url, "status": status, "ms": round(ms, 2)}

def main():
    results = []
    for route in ROUTES:
        url = BASE_URL + route
        results.append(measure_route(url))

    results.sort(key=lambda x: x["ms"])  # fastest first

    print("Admin route performance (ms):")
    for item in results:
        print(f"{item['ms']:8.2f} ms  {item['status']:>3}  {item['url']}")

if __name__ == "__main__":
    main()