## PHP URL Shortener

A simple and lightweight URL shortener written in PHP.
Easy to set up, easy to use, and easy to extend.

---

## Features

- Short URLs with redirects
- Hit counter for every link
- Count hits for base route (/)
- JSON stats API
- Admin API for bulk insert/update
- MySQL / MariaDB support

---

## Easy Setup

1. Upload files to your server
2. Create a MySQL database
3. Edit `db.php` with your database info
4. Open `migration.php` in your browser

Done ✅

---

## Usage

- `/abc` → redirects to long URL
- `/` → redirects to home and counts hits

---

## Admin API

Use `put.php` to add or update links using JSON.
Works with Python, curl, or any HTTP client.

---

## Stats

- `stat.php` → all links (JSON)
- `stat.php?code=abc` → single link stats

---

## Contributing

Contributions are welcome!
Feel free to open issues or pull requests.

---

## Bug Reports

If you find a bug or have a suggestion,
please open an issue and describe it clearly.

---

## License

MIT License  
Free to use, modify, and distribute.

---

## Collaboration

Looking for collaborators!
If you like this project, feel free to contribute ⭐
