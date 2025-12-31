import requests

url = "https://ac8.ir/put.php"

data = {
    "pass": "CHANGE_ME_1234",
    "links": {
        "mml": {
            "url": "https://www.mml-dev.ir/",
            "name": "John Doe",
            "phone": "+9876543210"
        },
        "b": {
            "url": "https://banoo.khas.shop/",
            "name": "Jane Smith",
            "phone": "+1234567890"
        }
    }
}

r = requests.post(url, json=data)

# Check response and print
print(f"Response Status Code: {r.status_code}")
print(f"Response Content: {r.text}")  # Raw response to inspect
try:
    print(r.json())  # This will try to parse the response as JSON
except ValueError as e:
    print(f"Error decoding JSON: {e}")
