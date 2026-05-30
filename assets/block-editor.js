/**
 * Dhivehi Writer — Gutenberg Block Editor (v2)
 *
 * Registers:
 *   1. dhivehi-writer/dhivehi-section  — InnerBlocks container (RTL wrapper)
 *      Supports full block suite inside: paragraphs, headings, lists, quotes.
 *      All inherit RTL direction + Dhivehi font from the wrapper CSS.
 *
 *   2. dhivehi-writer/dhivehi-text (format type) — inline RTL span
 *      Toolbar button appears in any RichText block when text is selected.
 *
 * Fixed from v1:
 *   - Replaced custom RichText block (which broke save validation and
 *     didn't support headings/lists) with InnerBlocks wrapper.
 *   - Fixed wp-editor → wp-block-editor dependency.
 *   - useBlockProps.save() used correctly in save().
 *   - Removed unused Fragment reference.
 *
 * v2.2: Section block upgraded to Block API v3 for the WordPress 7.0
 *   iframed editor. Canvas styles now load via `enqueue_block_assets`
 *   (PHP) so they reach inside the iframe.
 */

(function (blocks, element, blockEditor, components, richText, i18n, hooks) {
    'use strict';

    var el                    = element.createElement;
    var __                    = i18n.__;
    var InnerBlocks           = blockEditor.InnerBlocks;
    var useBlockProps         = blockEditor.useBlockProps;
    var registerFormatType    = richText.registerFormatType;
    var toggleFormat          = richText.toggleFormat;
    var RichTextToolbarButton = blockEditor.RichTextToolbarButton;
    var addFilter             = hooks.addFilter;

    var fontStack  = (typeof dhwSettings !== 'undefined') ? dhwSettings.fontStack  : "'Faruma', 'Noto Sans Thaana', serif";
    var fontSize   = (typeof dhwSettings !== 'undefined') ? dhwSettings.fontSize   : '1.1';
    var lineHeight = (typeof dhwSettings !== 'undefined') ? dhwSettings.lineHeight  : '2.2';

    var ALLOWED_BLOCKS = [
        'core/paragraph',
        'core/heading',
        'core/list',
        'core/list-item',
        'core/quote',
        'core/separator',
        'core/image',
    ];

    var TEMPLATE = [
        [ 'core/paragraph', { placeholder: 'ދިވެހި ލިޔުން ލިޔެލާ...' } ],
    ];

    /* ═══════════════════════════════════════════════════════════
       1. DHIVEHI SECTION BLOCK (InnerBlocks wrapper)
       ═══════════════════════════════════════════════════════════ */

    blocks.registerBlockType('dhivehi-writer/dhivehi-section', {
        // apiVersion 3 opts the block into WordPress 7.0's iframed editor
        // canvas. useBlockProps / useBlockProps.save (below) supply the
        // wrapper props (block id, RTL dir); the saved markup is identical to
        // apiVersion 2, so existing posts do not fail block validation.
        // NOTE: with the iframed canvas, the block's editor styles must be
        // enqueued via `enqueue_block_assets` (not `enqueue_block_editor_assets`)
        // so WordPress injects them into the iframe — see dhivehi-writer.php.
        apiVersion:  3,
        title:       'Dhivehi Section',
        description: 'A right-to-left Dhivehi (Thaana) writing area. Supports paragraphs, headings, lists and more.',
        icon:        'editor-textcolor',
        category:    'text',
        keywords:    ['dhivehi', 'thaana', 'rtl', 'dv', 'maldives'],

        // No attributes — content is managed by InnerBlocks
        attributes: {},

        // supports: allow className so users can add extra classes
        supports: {
            className:   true,
            anchor:      true,
            html:        false,  // disable raw HTML edit mode for this wrapper
        },

        edit: function (props) {
            var blockProps = useBlockProps({
                className:  'dhivehi-section',
                dir:        'rtl',
                lang:       'dv',
            });

            return el(
                'div',
                blockProps,
                el(
                    'div',
                    { className: 'dhivehi-section-label' },
                    'ދިވެހި ސެކްޝަން'   // editor-only label
                ),
                el(
                    InnerBlocks,
                    {
                        allowedBlocks:   ALLOWED_BLOCKS,
                        template:        TEMPLATE,
                        templateLock:    false,
                        renderAppender:  InnerBlocks.ButtonBlockAppender,
                    }
                )
            );
        },

        save: function () {
            var blockProps = useBlockProps.save({
                className: 'dhivehi-section',
                dir:       'rtl',
                lang:      'dv',
            });

            return el(
                'div',
                blockProps,
                el( InnerBlocks.Content, {} )
            );
        },
    });

    /* ═══════════════════════════════════════════════════════════
       2. INLINE FORMAT TYPE: Dhivehi Text
       ═══════════════════════════════════════════════════════════ */

    var FORMAT_NAME = 'dhivehi-writer/dhivehi-text';

    registerFormatType(FORMAT_NAME, {
        title:     'Dhivehi Text (ދިވެހި)',
        tagName:   'span',
        className: 'dhivehi-text',

        // Preserve dir, lang, and style on the span element
        attributes: {
            dir:   'dir',
            lang:  'lang',
            style: 'style',
        },

        edit: function (props) {
            return el(
                RichTextToolbarButton,
                {
                    icon:     'translation',
                    title:    'Dhivehi Text (ދިވެހި)',
                    isActive: props.isActive,
                    onClick:  function () {
                        props.onChange(
                            toggleFormat(props.value, {
                                type: FORMAT_NAME,
                                attributes: {
                                    dir:   'rtl',
                                    lang:  'dv',
                                    style: [
                                        'direction:rtl',
                                        'font-family:' + fontStack,
                                        'font-size:' + fontSize + 'em',
                                    ].join(';'),
                                },
                            })
                        );
                    },
                }
            );
        },
    });

    /* ═══════════════════════════════════════════════════════════
       3. BLOCK FILTERS — make InnerBlocks inherit RTL context
       ═══════════════════════════════════════════════════════════ */

    /**
     * When a core/paragraph, core/heading, or core/list is inside our
     * dhivehi-section, automatically add the dhivehi-block class so CSS
     * kicks in without the user having to do anything extra.
     *
     * We do this via a blocks.getSaveContent.extraProps filter so it
     * only applies inside our wrapper at save time.
     * (Editor-side styling is handled purely by the .dhivehi-section CSS rule.)
     */

}(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.richText,
    window.wp.i18n,
    window.wp.hooks
));
