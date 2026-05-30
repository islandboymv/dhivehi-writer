<?php
/**
 * Dhivehi Writer — uninstall cleanup.
 * Runs only when the plugin is deleted from the Plugins screen.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

delete_option( 'dhw_font' );
delete_option( 'dhw_font_size' );
delete_option( 'dhw_line_height' );
