/**
 * Dhivehi Writer — TinyMCE Plugin (Classic Editor)
 *
 * Adds two toolbar buttons:
 *   dhivehi_block  — insert / convert current block to a Dhivehi paragraph
 *   dhivehi_inline — wrap selection as inline Dhivehi span
 *   dhivehi_formats — dropdown for Dhivehi heading/list variants
 *
 * NOTE: bold, italic, font-size, bullets, numbering, etc. are provided by
 * the standard TinyMCE row-2 toolbar (wp_adv). Our style_formats are merged
 * via tiny_mce_before_init in PHP so they appear in the Formats dropdown.
 */
(function () {
    'use strict';

    tinymce.PluginManager.add('dhivehi_writer', function (editor) {

        var fontStack   = (typeof dhwSettings !== 'undefined') ? dhwSettings.fontStack  : "'Faruma', 'Noto Sans Thaana', serif";
        var fontSize    = (typeof dhwSettings !== 'undefined') ? dhwSettings.fontSize    : '1.1';
        var lineHeight  = (typeof dhwSettings !== 'undefined') ? dhwSettings.lineHeight  : '2.2';
        var pluginUrl   = (typeof dhwSettings !== 'undefined') ? dhwSettings.pluginUrl   : '';

        function dhivehiParaHtml(content) {
            return '<p class="dhivehi-block" dir="rtl" lang="dv" style="'
                + 'direction:rtl;text-align:right;'
                + 'font-family:' + fontStack + ';'
                + 'font-size:' + fontSize + 'em;'
                + 'line-height:' + lineHeight + ';">'
                + (content || 'ދިވެހި ލިޔުން')
                + '</p>';
        }

        /* ── Button: Insert Dhivehi Paragraph ─────────────────── */
        editor.addButton('dhivehi_block', {
            text: 'ދިވެހި ޕެރެ',
            tooltip: 'Insert Dhivehi Paragraph (RTL)',
            icon: false,
            onclick: function () {
                var selected = editor.selection.getContent({ format: 'text' }).trim();
                editor.insertContent(dhivehiParaHtml(selected));
            }
        });

        /* ── Button: Wrap selection as inline Dhivehi ──────────── */
        editor.addButton('dhivehi_inline', {
            text: 'ދިވެހި ޓެކްސްޓް',
            tooltip: 'Wrap selected text as inline Dhivehi',
            icon: false,
            onclick: function () {
                var selected = editor.selection.getContent({ format: 'html' });
                if (!selected || selected.trim() === '') {
                    editor.windowManager.alert(
                        'Please select some text first, then click this button.'
                    );
                    return;
                }
                var html = '<span class="dhivehi-text" dir="rtl" lang="dv" style="'
                    + 'direction:rtl;'
                    + 'font-family:' + fontStack + ';'
                    + 'font-size:' + fontSize + 'em;">'
                    + selected
                    + '</span>';
                editor.selection.setContent(html);
            }
        });

        /* ── Register formatters on init (used by Formats dropdown) ── */
        editor.on('init', function () {
            // These are also registered via tiny_mce_before_init style_formats in PHP.
            // Registering here too ensures they work via editor.execCommand if needed.
            editor.formatter.register('dhivehi_paragraph', {
                block: 'p',
                attributes: { 'class': 'dhivehi-block', 'dir': 'rtl', 'lang': 'dv' },
                styles: {
                    direction:    'rtl',
                    'text-align': 'right',
                    'font-family': fontStack,
                    'font-size':  fontSize + 'em',
                    'line-height': lineHeight,
                },
                wrapper: false,
            });

            editor.formatter.register('dhivehi_inline', {
                inline: 'span',
                attributes: { 'class': 'dhivehi-text', 'dir': 'rtl', 'lang': 'dv' },
                styles: {
                    direction:     'rtl',
                    'font-family': fontStack,
                    'font-size':   fontSize + 'em',
                },
            });

            // Load the bundled Thaana web fonts inside the editor iframe so the
            // Dhivehi font is visible while writing (not only on the frontend).
            if (pluginUrl) {
                editor.dom.addStyle(
                    '@font-face{font-family:\'Faruma\';font-style:normal;'
                    + 'font-weight:400;font-display:swap;'
                    + 'src:url(\'' + pluginUrl + 'assets/fonts/faruma.woff\') format(\'woff\');}'
                    + '@font-face{font-family:\'Noto Sans Thaana\';font-style:normal;'
                    + 'font-weight:400 700;font-display:swap;'
                    + 'src:url(\'' + pluginUrl + 'assets/fonts/noto-sans-thaana.woff2\') format(\'woff2\');}'
                );
            }

            // Apply Dhivehi font inside .dhivehi-block when editor is active
            editor.dom.addStyle(
                '.dhivehi-block, .dhivehi-list {'
                + '  direction: rtl !important;'
                + '  text-align: right !important;'
                + '  font-family: ' + fontStack + ' !important;'
                + '  font-size: ' + fontSize + 'em;'
                + '  line-height: ' + lineHeight + ';'
                + '}'
                + '.dhivehi-text {'
                + '  direction: rtl;'
                + '  font-family: ' + fontStack + ';'
                + '  font-size: ' + fontSize + 'em;'
                + '}'
                + '.dhivehi-list {'
                + '  padding-right: 2em;'
                + '  padding-left: 0;'
                + '}'
            );
        });

    });
}());
