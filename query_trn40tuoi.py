#!/usr/bin/env python3
import requests
from datetime import date

BASE_URL = "https://couchdb-public.onrender.com"
USERNAME = "admin"
PASSWORD = "0986480752A"

START = "2025-11-01T00:00:00Z"
END = "2025-11-30T23:59:59Z"
MIN_AGE = 40  # đặt 0 nếu muốn lấy toàn bộ hóa đơn

session = requests.Session()
session.auth = (USERNAME, PASSWORD)
session.headers.update({"Content-Type": "application/json"})

def mango_find(db, payload):
    res = session.post(f"{BASE_URL}/{db}/_find", json=payload, timeout=30)
    res.raise_for_status()
    return res.json().get("docs", [])

def fetch_invoices(start_iso, end_iso):
    payload = {
        "selector": {
            "type": "invoice",
            "invoice_info.invoice_date": {"$gte": start_iso, "$lte": end_iso}
        },
        "fields": ["_id", "patient_id", "invoice_info", "payment_info"],
        "limit": 1000
    }
    return mango_find("invoices", payload)

def fetch_patients():
    payload = {
        "selector": {"type": "patient"},
        "fields": ["_id", "personal_info.birth_date"],
        "limit": 5000
    }
    return mango_find("patients", payload)

def calculate_age(birth_date):
    if not birth_date:
        return None
    born = date.fromisoformat(birth_date[:10])
    today = date.today()
    return today.year - born.year - ((today.month, today.day) < (born.month, born.day))

def main():
    invoices = fetch_invoices(START, END)
    print(f"Tổng hóa đơn tìm thấy: {len(invoices)}")

    if MIN_AGE <= 0:
        total = sum(float(inv["payment_info"].get("total_amount", 0)) for inv in invoices)
        print(f"Doanh thu tất cả hóa đơn: {total:,.0f} VND")
        return

    patients = fetch_patients()
    birth_map = {p["_id"]: p["personal_info"].get("birth_date") for p in patients if p.get("personal_info")}

    filtered = []
    total = 0.0

    for inv in invoices:
        patient_id = inv.get("patient_id")
        birth = birth_map.get(patient_id)
        age = calculate_age(birth)
        if age is None or age < MIN_AGE:
            continue
        amount = float(inv["payment_info"].get("total_amount", 0))
        total += amount
        filtered.append((inv["_id"], patient_id, age, amount))

    print(f"Hóa đơn với bệnh nhân ≥ {MIN_AGE} tuổi: {len(filtered)}")
    print(f"Tổng doanh thu bộ lọc: {total:,.0f} VND")
    if filtered:
        print("Ví dụ:", filtered[0])

if __name__ == "__main__":
    main()
