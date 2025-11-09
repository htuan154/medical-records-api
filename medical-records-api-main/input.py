import requests, json, os, math

# ====== Cấu hình CouchDB (Render) ======
USER = "admin"
PASSWORD = "0986480752A"
# PASSWORD = "123"
BASE_URL = "https://couchdb-public.onrender.com"   # ✅ KHÔNG dùng /_utils/#
# BASE_URL = "http://127.0.0.1:5984"   # ✅ KHÔNG dùng /_utils/#

BACKUP_FOLDER = "backup"
BATCH_SIZE = 1000  # import theo lô để tránh request quá lớn

# Session chung (giữ auth/headers)
session = requests.Session()
session.auth = (USER, PASSWORD)
session.headers.update({"Content-Type": "application/json; charset=utf-8"})

def chunked(iterable, size):
    for i in range(0, len(iterable), size):
        yield iterable[i:i+size]

for file in os.listdir(BACKUP_FOLDER):
    if not file.endswith(".json"):
        continue

    dbname = file[:-5]  # bỏ .json
    # 1) Tạo DB nếu chưa có
    session.put(f"{BASE_URL}/{dbname}")

    # 2) Đọc dữ liệu
    with open(os.path.join(BACKUP_FOLDER, file), "r", encoding="utf-8") as f:
        data = json.load(f)

    if "rows" in data:         # export dạng _all_docs
        docs = [r["doc"] for r in data["rows"] if "doc" in r]
    elif "docs" in data:       # export dạng replication
        docs = data["docs"]
    else:
        docs = data if isinstance(data, list) else []

    # 3) Xoá _rev để tránh conflict
    for d in docs:
        d.pop("_rev", None)

    print(f"📥 {file}: {len(docs)} docs sẽ import vào '{dbname}'")

    # 4) Import theo lô
    imported = 0
    for pack in chunked(docs, BATCH_SIZE):
        res = session.post(f"{BASE_URL}/{dbname}/_bulk_docs",
                           data=json.dumps({"docs": pack}, ensure_ascii=False).encode("utf-8"))
        if res.status_code not in (200, 201, 202):
            print("⚠️ Lỗi:", res.status_code, res.text[:300])
            break
        imported += len(pack)

    # 5) Kiểm tra lại số lượng
    info = session.get(f"{BASE_URL}/{dbname}").json()
    print(f"✅ Đã import {imported} docs. Hiện DB có {info.get('doc_count', 0)} documents\n")
