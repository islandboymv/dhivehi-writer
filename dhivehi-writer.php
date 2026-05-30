<?php
/**
 * Plugin Name: Dhivehi Writer
 * Plugin URI:  https://github.com/islandboymv/dhivehi-writer
 * Description: Adds full Dhivehi (Thaana) writing support to the WordPress post editor — RTL paragraphs, inline text, bullets, font sizing, bold, italic, and more. Ships a bundled Thaana web font so Dhivehi renders on every visitor's device.
 * Version:     2.2.1
 * Author:      Mifzaal Abdul Bari
 * Author URI:  https://islandboy.xyz
 * License:     GPL-2.0+
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * Text Domain: dhivehi-writer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'DHW_VERSION', '2.2.1' );
define( 'DHW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DHW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Primary Dhivehi font and the bundled fallback chain. The WordPress.org
// build (bin/build-wporg.sh) flips these to Noto Sans Thaana, since it
// cannot bundle the Faruma font.
define( 'DHW_DEFAULT_FONT', 'Faruma' );
define( 'DHW_FONT_FALLBACK', "'Faruma', 'Noto Sans Thaana', 'MV Boli', serif" );

class Dhivehi_Writer {

    public function __construct() {
        add_action( 'admin_enqueue_scripts',    [ $this, 'enqueue_assets' ] );

        // Block editor JS goes in the OUTER editor frame, where wp.blocks
        // registration runs.
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_scripts' ] );

        // Block editor STYLES must go through enqueue_block_assets so WordPress
        // injects them INTO the editor's content iframe (WP 6.3+/7.0). The
        // Dhivehi Section block is apiVersion 3, so the canvas is iframed;
        // styles enqueued on enqueue_block_editor_assets only reach the outer
        // frame and would no longer style the content.
        add_action( 'enqueue_block_assets',     [ $this, 'enqueue_block_canvas_styles' ] );

        add_action( 'wp_enqueue_scripts',       [ $this, 'enqueue_frontend_styles' ] );

        // Classic Editor (TinyMCE)
        add_filter( 'mce_external_plugins',     [ $this, 'add_tinymce_plugin' ] );
        add_filter( 'mce_buttons',              [ $this, 'register_tinymce_buttons_row1' ] );
        add_filter( 'mce_buttons_2',            [ $this, 'register_tinymce_buttons_row2' ] );
        add_filter( 'tiny_mce_before_init',     [ $this, 'tinymce_settings' ] );

        // Settings
        add_action( 'admin_menu',  [ $this, 'add_settings_page' ] );
        add_action( 'admin_init',  [ $this, 'register_settings' ] );
    }

    /* ─── Assets ──────────────────────────────────────────────── */

    public function enqueue_assets( $hook ) {
        // Load on the post editor AND on our own settings page
        // (Settings → Dhivehi Writer = "settings_page_dhivehi-writer"),
        // otherwise the settings page styling and live preview never load.
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php', 'settings_page_dhivehi-writer' ] ) ) return;

        wp_enqueue_style(
            'dhivehi-writer-admin',
            DHW_PLUGIN_URL . 'assets/admin.css',
            [], DHW_VERSION
        );

        wp_enqueue_script(
            'dhivehi-writer-admin',
            DHW_PLUGIN_URL . 'assets/admin.js',
            [ 'jquery' ], DHW_VERSION, true
        );

        wp_localize_script( 'dhivehi-writer-admin', 'dhwSettings', $this->js_settings() );
    }

    public function enqueue_block_editor_scripts() {
        wp_enqueue_script(
            'dhivehi-writer-block',
            DHW_PLUGIN_URL . 'assets/block-editor.js',
            // wp-block-editor replaces deprecated wp-editor; wp-rich-text for the inline format.
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-rich-text', 'wp-hooks' ],
            DHW_VERSION, true
        );

        wp_localize_script( 'dhivehi-writer-block', 'dhwSettings', $this->js_settings() );
    }

    public function enqueue_block_canvas_styles() {
        // enqueue_block_assets fires on BOTH the front end and the editor.
        // On the front end the same fonts + RTL styling come from frontend.css
        // (wp_enqueue_scripts), and block-editor.css carries editor-only chrome
        // (the section label, canvas background) that must not leak to visitors —
        // so restrict this to the admin/editor context, where WordPress injects
        // it into the iframed canvas.
        if ( ! is_admin() ) return;

        wp_enqueue_style(
            'dhivehi-writer-block',
            DHW_PLUGIN_URL . 'assets/block-editor.css',
            [], DHW_VERSION
        );
    }

    public function enqueue_frontend_styles() {
        // Dhivehi content can appear anywhere (posts, archives, widgets),
        // so load the stylesheet site-wide — it is tiny.
        wp_enqueue_style(
            'dhivehi-writer-frontend',
            DHW_PLUGIN_URL . 'assets/frontend.css',
            [], DHW_VERSION
        );

        // Drive font / size / line-height from the saved settings via CSS
        // custom properties, so the settings page actually controls the
        // frontend without needing the font baked into every element.
        $inline = sprintf(
            ':root{--dhw-font:%s;--dhw-font-size:%sem;--dhw-line-height:%s;}',
            $this->font_stack(),
            floatval( get_option( 'dhw_font_size', '1.1' ) ),
            floatval( get_option( 'dhw_line_height', '2.2' ) )
        );
        wp_add_inline_style( 'dhivehi-writer-frontend', $inline );
    }

    private function js_settings() {
        return [
            'font'        => get_option( 'dhw_font', DHW_DEFAULT_FONT ),
            'fontStack'   => $this->font_stack(),
            'fontSize'    => get_option( 'dhw_font_size', '1.1' ),
            'lineHeight'  => get_option( 'dhw_line_height', '2.2' ),
            'pluginUrl'   => DHW_PLUGIN_URL,
        ];
    }

    /**
     * Build the Dhivehi font stack. The chosen font is tried first, then the
     * bundled web font(s) as a safety net, so Dhivehi renders on every device.
     * The primary/default font is set by DHW_DEFAULT_FONT.
     */
    private function font_stack() {
        $font = get_option( 'dhw_font', DHW_DEFAULT_FONT );
        return ( $font === DHW_DEFAULT_FONT )
            ? DHW_FONT_FALLBACK
            : "'{$font}', " . DHW_FONT_FALLBACK;
    }

    /* ─── TinyMCE (Classic Editor) ────────────────────────────── */

    public function add_tinymce_plugin( $plugins ) {
        $plugins['dhivehi_writer'] = DHW_PLUGIN_URL . 'assets/tinymce-plugin.js';
        return $plugins;
    }

    /**
     * Row 1: Add our custom buttons after the main toolbar buttons
     */
    public function register_tinymce_buttons_row1( $buttons ) {
        // Ensure row 2 (formatting toolbar) is visible
        if ( ! in_array( 'wp_adv', $buttons ) ) {
            array_push( $buttons, 'wp_adv' );
        }
        array_push( $buttons, 'separator', 'dhivehi_block', 'dhivehi_inline' );
        return $buttons;
    }

    /**
     * Row 2: Full formatting toolbar — bold, italic, lists, etc. are already here.
     * Add our Dhivehi heading variants.
     */
    public function register_tinymce_buttons_row2( $buttons ) {
        // Row 2 already has: formatselect, bold, italic, bullist, numlist, blockquote,
        // alignleft, aligncenter, alignright, link, unlink, wp_more, fullscreen.
        // Append Dhivehi-specific format selector.
        array_push( $buttons, 'separator', 'dhivehi_formats' );
        return $buttons;
    }

    /**
     * TinyMCE settings — add Dhivehi style formats to the Formats dropdown
     * and enable extended valid elements so dir/lang survive.
     */
    public function tinymce_settings( $init ) {
        $font_size   = esc_js( get_option( 'dhw_font_size', '1.1' ) );
        $line_height = esc_js( get_option( 'dhw_line_height', '2.2' ) );
        $font_family = $this->font_stack();

        $style_formats = [
            [
                'title'      => 'ދިވެހި — Dhivehi',
                'items'      => [
                    [
                        'title'      => 'ދިވެހި ޕެރެގްރާފް',
                        'block'      => 'p',
                        'classes'    => 'dhivehi-block',
                        'attributes' => [ 'dir' => 'rtl', 'lang' => 'dv' ],
                        'styles'     => [
                            'direction'   => 'rtl',
                            'text-align'  => 'right',
                            'font-family' => $font_family,
                            'font-size'   => "{$font_size}em",
                            'line-height' => $line_height,
                        ],
                        'wrapper' => false,
                    ],
                    [
                        'title'      => 'ދިވެހި ސުރުހީ 2',
                        'block'      => 'h2',
                        'classes'    => 'dhivehi-block',
                        'attributes' => [ 'dir' => 'rtl', 'lang' => 'dv' ],
                        'styles'     => [
                            'direction'   => 'rtl',
                            'text-align'  => 'right',
                            'font-family' => $font_family,
                        ],
                        'wrapper' => false,
                    ],
                    [
                        'title'      => 'ދިވެހި ސުރުހީ 3',
                        'block'      => 'h3',
                        'classes'    => 'dhivehi-block',
                        'attributes' => [ 'dir' => 'rtl', 'lang' => 'dv' ],
                        'styles'     => [
                            'direction'   => 'rtl',
                            'text-align'  => 'right',
                            'font-family' => $font_family,
                        ],
                        'wrapper' => false,
                    ],
                    [
                        'title'      => 'ދިވެހި ލިސްޓް (RTL)',
                        'block'      => 'ul',
                        'classes'    => 'dhivehi-list',
                        'attributes' => [ 'dir' => 'rtl', 'lang' => 'dv' ],
                        'styles'     => [
                            'direction'   => 'rtl',
                            'text-align'  => 'right',
                            'font-family' => $font_family,
                            'font-size'   => "{$font_size}em",
                            'line-height' => $line_height,
                        ],
                        'wrapper' => true,
                    ],
                    [
                        'title'   => 'ދިވެހި ޓެކްސްޓް (inline)',
                        'inline'  => 'span',
                        'classes' => 'dhivehi-text',
                        'attributes' => [ 'dir' => 'rtl', 'lang' => 'dv' ],
                        'styles'  => [
                            'direction'   => 'rtl',
                            'font-family' => $font_family,
                            'font-size'   => "{$font_size}em",
                        ],
                    ],
                ],
            ],
        ];

        // Merge with any existing style formats
        if ( ! empty( $init['style_formats'] ) ) {
            $existing = json_decode( $init['style_formats'], true ) ?: [];
            array_unshift( $existing, $style_formats[0] );
            $init['style_formats'] = wp_json_encode( $existing );
        } else {
            $init['style_formats'] = wp_json_encode( $style_formats );
        }

        // Keep style_formats_merge true so theme formats aren't blown away
        $init['style_formats_merge'] = true;

        // Allow dir and lang attributes to survive TinyMCE's HTML cleanup
        $init['extended_valid_elements'] = 'span[*],p[*],h1[*],h2[*],h3[*],h4[*],ul[*],ol[*],li[*],div[*]';

        // Allow TinyMCE to keep inline styles
        $init['verify_html'] = false;

        return $init;
    }

    /* ─── Settings ────────────────────────────────────────────── */

    public function add_settings_page() {
        add_options_page(
            'Dhivehi Writer Settings',
            'Dhivehi Writer',
            'manage_options',
            'dhivehi-writer',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'dhw_settings_group', 'dhw_font',        [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'dhw_settings_group', 'dhw_font_size',   [ 'sanitize_callback' => 'floatval' ] );
        register_setting( 'dhw_settings_group', 'dhw_line_height', [ 'sanitize_callback' => 'floatval' ] );
    }

    public function render_settings_page() {
        $font        = get_option( 'dhw_font', DHW_DEFAULT_FONT );
        $font_size   = get_option( 'dhw_font_size', '1.1' );
        $line_height = get_option( 'dhw_line_height', '2.2' );
        ?>
        <div class="wrap dhw-settings-wrap">
            <h1>🌊 Dhivehi Writer <span class="dhw-version">v2.2</span></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'dhw_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="dhw_font">Dhivehi Font</label></th>
                        <td>
                            <select name="dhw_font" id="dhw_font">
                                <?php
                                $fonts = [
                                    'Faruma'           => 'Faruma (bundled web font — classic Thaana look)',
                                    'Noto Sans Thaana' => 'Noto Sans Thaana (bundled web font — modern & clean)',
                                    'MV Boli'          => 'MV Boli (only renders if installed on the visitor\'s device)',
                                    'A_Faru'           => 'A_Faru (only if installed on the visitor\'s device)',
                                    'MV Iyyu'          => 'MV Iyyu (only if installed on the visitor\'s device)',
                                ];
                                foreach ( $fonts as $value => $label ) {
                                    if ( $value === DHW_DEFAULT_FONT ) $label .= ' ✓ recommended';
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr( $value ),
                                        selected( $font, $value, false ),
                                        esc_html( $label )
                                    );
                                }
                                ?>
                            </select>
                            <p class="description">
                                The recommended font is bundled as a web font and loaded for every
                                visitor automatically — so Dhivehi renders even on devices that have no
                                Thaana font installed. Whatever you pick here is tried first, then it
                                falls back to the bundled font, so Dhivehi never breaks.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="dhw_font_size">Font Size Multiplier</label></th>
                        <td>
                            <input type="number" step="0.05" min="0.8" max="2.5"
                                   name="dhw_font_size" id="dhw_font_size"
                                   value="<?php echo esc_attr( $font_size ); ?>" style="width:80px;" />
                            <span class="description"> × body size &nbsp;(e.g. 1.1 = 10% larger — Thaana reads better slightly larger)</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="dhw_line_height">Line Height</label></th>
                        <td>
                            <input type="number" step="0.1" min="1.5" max="4.0"
                                   name="dhw_line_height" id="dhw_line_height"
                                   value="<?php echo esc_attr( $line_height ); ?>" style="width:80px;" />
                            <span class="description"> (2.2 recommended for Thaana)</span>
                        </td>
                    </tr>
                </table>

                <?php submit_button( 'Save Settings' ); ?>
            </form>

            <hr>
            <h2>How to Use</h2>

            <h3>Gutenberg (Block Editor)</h3>
            <ul>
                <li>Click <strong>+</strong> and search <strong>"Dhivehi"</strong> → insert a <strong>Dhivehi Section</strong> block.</li>
                <li>Inside the block you get a full editing area: add paragraphs, headings, bullet lists, numbered lists — all RTL with Thaana font.</li>
                <li>Each inner block's toolbar works normally (bold, italic, font size, link, etc.).</li>
                <li>For a single inline word/phrase in an English paragraph: select text → click the <strong>ދިވެހި</strong> button in the format toolbar.</li>
            </ul>

            <h3>Classic Editor (TinyMCE)</h3>
            <ul>
                <li>Click <strong>"ދިވެހި ޕެރެ"</strong> to insert a Dhivehi paragraph at the cursor.</li>
                <li>Click <strong>"ދިވެހި ޓެކްސްޓް"</strong> to wrap selected text as inline Dhivehi.</li>
                <li>Use the <strong>Formats</strong> dropdown → <em>ދިވެހި — Dhivehi</em> for headings, lists, and more.</li>
                <li>The standard toolbar (bold, italic, bullets, font size) all work inside Dhivehi paragraphs.</li>
            </ul>

            <h3>Manual CSS Classes</h3>
            <code>.dhivehi-block</code> — RTL paragraph/heading &nbsp;|&nbsp;
            <code>.dhivehi-text</code> — inline span &nbsp;|&nbsp;
            <code>.dhivehi-list</code> — RTL list
        </div>
        <?php
    }
}

new Dhivehi_Writer();

/* DHW_WPORG_STRIP_START — removed in the WordPress.org build (WP.org provides updates) */
/* ─── GitHub auto-updates ─────────────────────────────────────────
 * Checks this plugin's GitHub Releases and lets WordPress update it
 * in-place (Plugins screen / auto-updates). Publish a new release with
 * a bumped version header + an attached "dhivehi-writer.zip" asset and
 * installed sites will offer the update.
 */
$dhw_puc_loader = DHW_PLUGIN_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $dhw_puc_loader ) ) {
    require_once $dhw_puc_loader;

    $dhw_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/islandboymv/dhivehi-writer/',
        __FILE__,
        'dhivehi-writer'
    );

    // Use published GitHub Releases (stable), not raw commits.
    $dhw_update_checker->getVcsApi()->enableReleaseAssets();
}
/* DHW_WPORG_STRIP_END */
