# Dhivehi Writer

Write **Dhivehi (Thaana)** inside an otherwise English/LTR WordPress site — with proper right‑to‑left layout and a **bundled Thaana web font**, so Dhivehi renders on every visitor's device (not just machines that happen to have a Thaana font installed).

Works in both the **Block Editor (Gutenberg)** and the **Classic Editor (TinyMCE)**.

## Features

- **Dhivehi Section** block (Gutenberg) — an RTL container for paragraphs, headings, and lists
- **Inline Dhivehi** format button — mark any selected text as Dhivehi
- Classic Editor toolbar buttons + format variants
- **Bundled web fonts:** Faruma (default) and Noto Sans Thaana (fallback)
- Configurable font, size, and line‑height from **Settings → Dhivehi Writer**, applied live on the frontend
- Correct `dir="rtl"` and `lang="dv"` attributes for accessibility and SEO

## Installation

1. Download `dhivehi-writer.zip` from the [latest release](https://github.com/islandboymv/dhivehi-writer/releases/latest).
2. In WordPress: **Plugins → Add New → Upload Plugin** → choose the zip → **Install** → **Activate**.
3. (Optional) Configure fonts at **Settings → Dhivehi Writer**.

## Automatic updates

The plugin checks this repository's **GitHub Releases** and offers updates from the WordPress **Plugins** screen (and via WordPress auto‑updates).

### Publishing an update (maintainers)

1. Bump the version in the `dhivehi-writer.php` header **and** the `Stable tag` in `readme.txt`.
2. Commit, then tag and push:
   ```bash
   git commit -am "Release vX.Y.Z"
   git tag vX.Y.Z
   git push origin main --tags
   ```
3. The release workflow builds `dhivehi-writer.zip` and publishes a GitHub Release. Installed sites pick up the update automatically.

## Fonts & licensing

- **Noto Sans Thaana** — © The Noto Project, [SIL Open Font License 1.1](assets/fonts/OFL.txt).
- **Faruma** — the classic Maldivian Thaana font, bundled as a web font.

## License

GPL‑2.0+
