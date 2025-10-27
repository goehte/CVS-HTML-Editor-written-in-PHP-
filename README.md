```markdown
# CVS HTML Editor (written in PHP)

This project is a lightweight CSV editor written in PHP. It edits existing CSV files stored in a local `tables/` folder, creates server-side version snapshots, and provides a client-side "Trash" with undo.

This repository contains:
- `csv_html_editor.php` — Main application (PHP + embedded JS).
- `translations.json` — Configurable translations for server and client UI strings.
- `.gitignore` — Useful ignores for development.

Important notes
- The app only opens CSV files that already exist in `tables/`.
- Versions are stored in `tables/versions/<csv_basename>_versions/`.
- The UI supports English and German out of the box (detects Accept-Language and client locale).

Quick start (local)
1. Create project folder and copy files into it.
2. Create `tables/` folder next to `csv_html_editor.php` and add at least one CSV file:
   - mkdir tables
   - echo "col1,col2" > tables/data.csv
3. Serve with PHP built-in server for testing:
   - php -S 0.0.0.0:8000
   - Open in browser: http://localhost:8000/csv_html_editor.php?csv_filename=data.csv

Create a GitHub repository and push (example using `git` CLI)
1. Create repo on GitHub via the website titled:
   `CVS HTML Editor (written in PHP)`
   or use the GitHub CLI / API of your choice.

2. Locally, initialize and push:
   - git init
   - git add .
   - git commit -m "Initial commit: CVS HTML Editor (PHP) — v16 cleaned"
   - git remote add origin git@github.com:<your-username>/CVS-HTML-Editor-written-in-PHP.git
   - git branch -M main
   - git push -u origin main

If you prefer the HTTPS remote:
   - git remote add origin https://github.com/<your-username>/CVS-HTML-Editor-written-in-PHP.git

Security and deployment notes
- This editor is intended for trusted environments (intranet, personal server). If you expose it publicly add authentication and restrict write access.
- Validate your webserver PHP user has write permission to `tables/` and the versions directory.

If you want, I can:
- Prepare the exact repo content and provide the series of `git` commands with exact filenames and content for a single copy-paste commit, or
- Draft the GitHub repository (I can't create it for you without repository access), or
- Add an admin UI to edit translations.json from the browser.

```
