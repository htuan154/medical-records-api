import requests

# CouchDB config
BASE_URL = "https://couchdb-public.onrender.com"
USER = "admin"
PASSWORD = "0986480752A"

session = requests.Session()
session.auth = (USER, PASSWORD)

# List all databases
print("📦 All databases:")
dbs_response = session.get(f"{BASE_URL}/_all_dbs")
databases = dbs_response.json()
print(databases)

# Check document count for each database
print("\n📊 Document counts:")
for db in databases:
    if not db.startswith('_'):  # Skip system databases
        info = session.get(f"{BASE_URL}/{db}").json()
        doc_count = info.get('doc_count', 0)
        print(f"  {db}: {doc_count} documents")

# Test specific collections mentioned in the issue
collections_to_check = [
    'medical_records',
    'medical_tests', 
    'medications',
    'doctors',
    'staffs',
    'invoices',
    'roles',
    'patients',
    'appointments'
]

print("\n🔍 Checking specific collections:")
for collection in collections_to_check:
    try:
        info = session.get(f"{BASE_URL}/{collection}").json()
        doc_count = info.get('doc_count', 0)
        
        # Get sample docs
        docs_response = session.get(f"{BASE_URL}/{collection}/_all_docs?limit=3&include_docs=true")
        docs_data = docs_response.json()
        
        print(f"\n{collection}:")
        print(f"  Total docs: {doc_count}")
        if docs_data.get('rows'):
            print(f"  Sample IDs: {[r['id'] for r in docs_data['rows'][:3]]}")
    except Exception as e:
        print(f"\n{collection}: ERROR - {e}")
