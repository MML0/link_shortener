import csv
import string
import itertools
import requests
import pandas as pd
import json
import os
import sys
import datetime

sys.stdout.reconfigure(encoding='utf-8')

CSV_PATH = 'backend/Birth_10-17.csv'
BASE_URL = 'https://banoo.khas.shop/'
BASE_URL = 'https://banoo.khas.shop/tgtg17/'
SHORT_LINK_BASE = 'ac8.ir/'  # <-- short link base
BATCH_SIZE = 50

SHORT_CHARS = string.ascii_letters + string.digits  # a-zA-Z0-9
USED_CODES_FILE = 'backend/used_codes.json'  # local tracking of all codes
EXCEL_FILE = f"backend/output_map_{datetime.datetime.now().strftime('%y%m%d_%H%M%S')}.xlsx"

def normalize_persian(text: str) -> str:
    """
    Normalize Persian text:
    - Arabic ي -> Persian ی
    - Arabic ك -> Persian ک
    - Remove extra spaces
    - Trim text
    """
    if not text:
        return ''

    replacements = {
        'ي': 'ی',
        'ك': 'ک',
        '‌': ' ',  # replace ZWNJ (zero-width non-joiner) with space
        '‏': '',   # remove RTL mark
    }

    for old, new in replacements.items():
        text = text.replace(old, new)

    # Normalize multiple spaces to single space
    text = ' '.join(text.split())

    return text.strip()

# -------------------------------
# Generate short codes dynamically
# -------------------------------
def generate_short_codes(length=3):
    return ("".join(p) for p in itertools.product(SHORT_CHARS, repeat=length))


# -------------------------------
# Read CSV
# -------------------------------
def read_csv(path):
    with open(path, encoding='utf-8-sig') as f:
        reader = csv.DictReader(f)
        return list(reader)


# -------------------------------
# Send batch to server
# -------------------------------
def send_batch_to_server(batch):
    url = "https://ac8.ir/put.php"
    data = {
        "pass": "CHANGE_ME_1234",
        "links": {entry['short_code']: {
            "url": entry['link'],
            "name": entry['user']['FIRST_NAME'] + ' ' + entry['user']['LAST_NAME'],
            "phone": entry['user']['MOBILE']
        } for entry in batch}
    }
    response = requests.post(url, json=data)
    try:
        return response.json()
    except ValueError:
        return None


# -------------------------------
# Load / save used codes
# -------------------------------
def load_used_codes():
    if os.path.exists(USED_CODES_FILE):
        with open(USED_CODES_FILE, 'r', encoding='utf-8') as f:
            return set(json.load(f))
    return set()


def save_used_codes(codes):
    with open(USED_CODES_FILE, 'w', encoding='utf-8') as f:
        json.dump(list(codes), f, ensure_ascii=False, indent=2)


# -------------------------------
# Main
# -------------------------------
def main():
    users = read_csv(CSV_PATH)
    used_codes = load_used_codes()

    code_length = 3
    short_code_gen = generate_short_codes(code_length)
    all_rows = []  # Collect all rows for a single Excel sheet

    for user in users:
        # Skip if phone number is missing
        if not user.get('MOBILE'):
            # print(f"Skipping {user.get('FIRST_NAME', '')} {user.get('LAST_NAME', '')} – no phone number")
            continue
        # Get next unique code
        try:
            code = next(short_code_gen)
        except StopIteration:
            code_length += 1
            print(f"All {code_length-1}-char codes exhausted. Switching to {code_length}-char codes.")
            short_code_gen = generate_short_codes(code_length)
            code = next(short_code_gen)

        while code in used_codes:
            try:
                code = next(short_code_gen)
            except StopIteration:
                code_length += 1
                print(f"All {code_length-1}-char codes exhausted. Switching to {code_length}-char codes.")
                short_code_gen = generate_short_codes(code_length)
                code = next(short_code_gen)

        used_codes.add(code)
        user_link = {
            'short_code': code,
            'link': BASE_URL,
            'user': user
        }

        # Send single or batch to server
        result_map = send_batch_to_server([user_link]) or {}

        details = result_map.get(code, {})
        all_rows.append({
            'Short_link': f"{SHORT_LINK_BASE}{code}",  
            'First_name': normalize_persian(user.get('FIRST_NAME', '')),
            'Phone': details.get('phone', user['MOBILE']),
            'Short Code': code,
            'URL': details.get('url', BASE_URL),
            'Name': normalize_persian(details.get('name', user['FIRST_NAME'] + ' ' + user['LAST_NAME'])),
            'User Last Name': normalize_persian(user.get('LAST_NAME', '')),
            'User Mobile': user.get('MOBILE', '')
        })

    # Save used codes locally
    save_used_codes(used_codes)

    # Write all rows to **one Excel sheet**
    df = pd.DataFrame(all_rows)
    df.to_excel(EXCEL_FILE, sheet_name='All_Links', index=False)

    print(f"Excel saved: {EXCEL_FILE}")
    print("All done ✅")


if __name__ == '__main__':
    main()
