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

CSV_PATH = 'backend/Birth_10-16.csv'
BASE_URL = 'https://banoo.khas.shop/'
BATCH_SIZE = 100

SHORT_CHARS = string.ascii_letters + string.digits  # a-zA-Z0-9
USED_CODES_FILE = 'backend/used_codes.json'  # local tracking of all codes
EXCEL_FILE = f"backend/output_map_{datetime.datetime.now().strftime('%y%m%d')}.xlsx"


# -------------------------------
# Generate short codes dynamically
# -------------------------------
def generate_short_codes(length=2):
    if length < 1:
        raise ValueError("Code length must be >= 1")
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
# Load used codes from file
# -------------------------------
def load_used_codes():
    if os.path.exists(USED_CODES_FILE):
        with open(USED_CODES_FILE, 'r', encoding='utf-8') as f:
            return set(json.load(f))
    return set()


# -------------------------------
# Save used codes to file
# -------------------------------
def save_used_codes(codes):
    with open(USED_CODES_FILE, 'w', encoding='utf-8') as f:
        json.dump(list(codes), f, ensure_ascii=False, indent=2)


# -------------------------------
# Main function
# -------------------------------
def main():
    users = read_csv(CSV_PATH)
    used_codes = load_used_codes()

    # Start with 2-character codes
    code_length = 2
    short_code_gen = generate_short_codes(code_length)

    user_links = []

    for user in users:
        # Get next unique code
        try:
            code = next(short_code_gen)
        except StopIteration:
            # If 2-char codes exhausted, increment to 3 chars
            code_length += 1
            print(f"All {code_length-1}-char codes exhausted. Switching to {code_length}-char codes.")
            short_code_gen = generate_short_codes(code_length)
            code = next(short_code_gen)

        # Ensure globally unique
        while code in used_codes:
            try:
                code = next(short_code_gen)
            except StopIteration:
                code_length += 1
                print(f"All {code_length-1}-char codes exhausted. Switching to {code_length}-char codes.")
                short_code_gen = generate_short_codes(code_length)
                code = next(short_code_gen)

        used_codes.add(code)
        user_links.append({
            'short_code': code,
            'link': BASE_URL,
            'user': user
        })

    # Save codes locally
    save_used_codes(used_codes)

    # Split into batches
    batches = [user_links[i:i + BATCH_SIZE] for i in range(0, len(user_links), BATCH_SIZE)]

    # Prepare Excel writer
    writer = pd.ExcelWriter(EXCEL_FILE, engine='xlsxwriter')

    for i, batch in enumerate(batches):
        print(f'Batch {i+1}')

        # Send batch to server
        result_map = send_batch_to_server(batch)
        if result_map is None:
            print(f"Failed to get valid response for batch {i+1}")
            continue

        # Prepare data for Excel
        rows = []
        for short_code, details in result_map.items():
            user_info = next((entry['user'] for entry in batch if entry['short_code'] == short_code), None)
            if user_info:
                rows.append({
                    'Short Code': short_code,
                    'URL': details.get('url', ''),
                    'Name': details.get('name', ''),
                    'Phone': details.get('phone', ''),
                    'User First Name': user_info.get('FIRST_NAME', ''),
                    'User Last Name': user_info.get('LAST_NAME', ''),
                    'User Mobile': user_info.get('MOBILE', '')
                })

        df = pd.DataFrame(rows)
        df.to_excel(writer, sheet_name=f'Batch_{i+1}', index=False)

    writer.close()
    print("All done ✅")


if __name__ == '__main__':
    main()
