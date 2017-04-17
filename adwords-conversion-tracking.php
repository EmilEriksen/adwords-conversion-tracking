<?php
/**
 * Plugin Name: AdWords Conversion Tracking
 * Version: 1.0.0
 * Description: Track AdWords conversions on the WooCommerce thank you page.
 * Author: Emil Kjær Eriksen <hello@emileriksen.me>
 * Text Domain: act
 * Domain Path: /languages/
 * License: GPL v3
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

add_filter( 'woocommerce_integrations', function( $integrations ) {
    $integrations[] = '\\ACT\\Integration';

    return $integrations;
} );
