import requests
from bs4 import BeautifulSoup
from pathlib import Path
import time
import re


BASE_URL = "http://192.168.1.77:8000"
ADMIN_LOGIN_URL = f"{BASE_URL}/auth/login"
ADD_PRODUCT_URL = f"{BASE_URL}/admin/addProduct"
ADMIN_PRODUCTS_URL = f"{BASE_URL}/admin/products"

OUTPUT_DIR = Path("testsprite_tests/tmp/schedule_test")
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)


def save_html(name: str, content: str):
    ts = time.strftime("%Y%m%d_%H%M%S")
    p = OUTPUT_DIR / f"{ts}_{name}.html"
    p.write_text(content, encoding="utf-8", errors="ignore")
    return p


def gen_png_bytes():
    # Minimal 1x1 red-ish PNG
    return (
        b"\x89PNG\r\n\x1a\n"
        b"\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x02\x00\x00\x00\x90wS\xde"
        b"\x00\x00\x00\x0cIDAT\x08\xd7c\xf8\xcf\xc0\x00\x00\x03\x01\x01\x00\x18\xdd\x8d\x9b"
        b"\x00\x00\x00\x00IEND\xaeB`\x82"
    )


def parse_hidden_inputs(html: str) -> dict:
    soup = BeautifulSoup(html, "html.parser")
    hidden = {}
    for inp in soup.find_all("input", {"type": "hidden"}):
        name = inp.get("name")
        val = inp.get("value", "")
        if name:
            hidden[name] = val
    return hidden


def pick_category_value(html: str) -> str:
    soup = BeautifulSoup(html, "html.parser")
    # Try common select names
    for sel_name in ["category", "category_id", "product_category"]:
        sel = soup.select_one(f'select[name="{sel_name}"]')
        if sel:
            for opt in sel.find_all("option"):
                val = (opt.get("value") or "").strip()
                if val:
                    return val
    # Fallback to a reasonable default if not present
    return "Protein"


def login_admin(session: requests.Session, phone: str, password: str) -> bool:
    r = session.get(ADMIN_LOGIN_URL, timeout=25)
    save_html("login_page", r.text)
    payload = {
        "phone": phone,
        "password": password,
    }
    r = session.post(ADMIN_LOGIN_URL, data=payload, timeout=35, allow_redirects=True)
    save_html("login_submit", r.text)
    # Validate by accessing admin/products
    check = session.get(ADMIN_PRODUCTS_URL, timeout=30)
    save_html("admin_products_after_login", check.text)
    return check.status_code == 200 and ("/admin/addProduct" in check.text or "Manage Products" in check.text)


def create_scheduled_product(session: requests.Session, name: str) -> bool:
    r = session.get(ADD_PRODUCT_URL, timeout=30)
    save_html("add_product_page", r.text)

    hidden = parse_hidden_inputs(r.text)
    category_val = pick_category_value(r.text)

    # Scheduled date a little in the future (datetime-local format)
    scheduled_date = time.strftime("%Y-%m-%dT%H:%M")

    form = {
        "product_name": name,
        "category": category_val,
        "price": "1999",
        "sale_price": "1799",
        "stock_quantity": "12",
        "weight": "1kg",
        "serving": "30",
        "flavor": "Chocolate",
        "product_type_main": "Supplement",
        "product_type": "Protein",
        "short_description": "Automated scheduled product test",
        "description": "**Automated** scheduled product created via Testsprite.",
        "is_featured": "0",
        # Scheduling
        "is_scheduled": "1",
        "scheduled_date": scheduled_date,
        "scheduled_duration": "2",
        "scheduled_message": "Coming Soon! Get ready for this automated test product.",
        # Include any hidden inputs like CSRF
        **hidden,
    }

    png_bytes = gen_png_bytes()
    # Provide both common field names for file uploads
    files = [
        ("images[]", ("testsprite.png", png_bytes, "image/png")),
        ("images", ("testsprite.png", png_bytes, "image/png")),
    ]

    r = session.post(ADD_PRODUCT_URL, data=form, files=files, timeout=60, allow_redirects=True)
    save_html("add_product_submit", r.text)

    list_page = session.get(ADMIN_PRODUCTS_URL, timeout=40)
    save_html("admin_products", list_page.text)
    return name in list_page.text


def main():
    phone = "9765470926"
    password = "R@man741"
    session = requests.Session()
    session.headers.update({
        "User-Agent": "Testsprite/1.0 (+automation)",
        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    })

    ok = login_admin(session, phone, password)
    assert ok, "Admin login failed"

    name = f"Scheduled Auto Test {time.strftime('%Y%m%d_%H%M%S')}"
    ok = create_scheduled_product(session, name)
    assert ok, "Newly created product not found in admin products list"

    print("SUCCESS: Scheduled product created and listed:", name)


if __name__ == "__main__":
    main()