=== Dhivehi Writer ===
Contributors: islandboymv
Tags: dhivehi, thaana, rtl, maldives, multilingual
Requires at least: 5.8
Requires PHP: 7.2
Tested up to: 6.7
Stable tag: 2.1.0
License: GPLv2 or later

Write Dhivehi (Thaana script) inside English WordPress posts — with proper RTL layout and a bundled font that renders on every device.

== Description ==

Dhivehi Writer adds seamless Dhivehi language support to the WordPress editor without switching your entire site to RTL.

**Features:**
* Works in both Gutenberg (Block Editor) and Classic Editor (TinyMCE)
* **Dhivehi Section** — a dedicated RTL container block in Gutenberg holding paragraphs, headings, and lists
* **Inline Dhivehi format** — highlight any text and mark it as Dhivehi via the toolbar
* Classic Editor toolbar buttons for paragraph and inline Dhivehi
* **Bundled web fonts (Faruma + Noto Sans Thaana)** — Dhivehi renders for every visitor, not only those whose device has a Thaana font installed
* Configurable font, font size, and line-height from Settings — applied live on the frontend
* Correct `dir="rtl"` and `lang="dv"` attributes for accessibility and SEO

The chosen font is tried first, then it falls back to the bundled Faruma and Noto Sans Thaana, so Dhivehi never breaks. Faruma (the classic Maldivian Thaana look) is the default.

== Installation ==

1. Upload the `dhivehi-writer` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins → Installed Plugins**
3. Optionally configure font settings at **Settings → Dhivehi Writer**

== Usage ==

**Gutenberg:**
- Search for "Dhivehi Section" in the block inserter (+) for a full RTL writing area
- Select any text in a paragraph block and click the **ދިވެހި** (translation icon) button in the toolbar for inline Dhivehi

**Classic Editor:**
- Use **ދިވެހި ޕެރެ** button to insert a Dhivehi paragraph
- Select text and use **ދިވެހި ޓެކްސްޓް** to wrap it inline

**Manual HTML:**
- `<p class="dhivehi-block" dir="rtl" lang="dv">...</p>` for a paragraph
- `<span class="dhivehi-text" dir="rtl" lang="dv">...</span>` for inline

== Credits ==

Bundles the **Noto Sans Thaana** font by Google, licensed under the SIL Open Font License 1.1 (see assets/fonts/OFL.txt), and the **Faruma** Thaana font.

== Changelog ==

= 2.1.0 =
* Bundled Faruma (default) and Noto Sans Thaana web fonts so Dhivehi renders on every device (previously relied on the visitor having a Thaana font installed)
* Settings (font, size, line-height) now drive the frontend live via CSS custom properties
* Fixed the Gutenberg block missing `apiVersion: 2` (caused editor wrapper warnings)
* Fixed settings-page assets (styling + live preview) not loading on the settings page
* Fixed `style_formats_merge` so theme TinyMCE formats are preserved
* Frontend styles now load site-wide (not only on singular views)
* Added uninstall cleanup of plugin options

= 2.0.0 =
* Rewrote the Gutenberg block as an InnerBlocks "Dhivehi Section" container
* Added inline Dhivehi format button and Classic Editor format variants

= 1.0.0 =
* Initial release
