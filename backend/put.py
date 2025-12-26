import requests

url = "https://ac8.ir/put.php"

data = {
    "pass": "CHANGE_ME_1234",
    "links": {
        "mml": "https://www.mml-dev.ir/",
        "b": "https://banoo.khas.shop/",
        "py1": "https://python.org"
    }
}

r = requests.post(url, json=data)
print(r.json())
