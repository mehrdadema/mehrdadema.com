<?php
/**
 * Plugin Name: Cube Portfolio Rebuilt
 * Plugin URI:  https://github.com/
 * Description: A clean, modern rebuild of Cube Portfolio – Responsive WordPress Grid Plugin. Fully compatible with existing data.
 * Author:      Rebuilt for Mehrdad
 * Version:     2.0.0
 * Text Domain: cubeportfolio
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Constants ────────────────────────────────────────────────────────────────
define( 'CBP_VERSION',    '2.0.0' );
define( 'CBP_TEXTDOMAIN', 'cubeportfolio' );
define( 'CBP_DIRNAME',    dirname( plugin_basename( __FILE__ ) ) );
define( 'CBP_PATH',       trailingslashit( __DIR__ ) );
define( 'CBP_URL',        trailingslashit( plugins_url( '', __FILE__ ) ) );

// ── Bootstrap ────────────────────────────────────────────────────────────────
require_once CBP_PATH . 'php/class-main.php';

$cbp = new CBP_Main( __FILE__ );
