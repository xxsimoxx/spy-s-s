<?php
/**
 * Plugin Name: Spy Scripts and Styles
 * Plugin URI: https://software.gieffeedizioni.it
 * Description: Record all the scripts and styles enqueued.
 * Version: 1.0.0
 * License: GPL2
 * Requires CP: 1.0
 * Requires PHP: 5.6
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: Gieffe edizioni srl
 * Author URI: https://www.gieffeedizioni.it
 */

namespace XXSimoXX\Spy;

if (!defined('ABSPATH')) {
	die;
}

add_action('wp_enqueue_scripts',    '\XXSimoXX\Spy\spy_scripts', PHP_INT_MAX);
add_action('admin_enqueue_scripts', '\XXSimoXX\Spy\spy_scripts', PHP_INT_MAX);
add_action('login_enqueue_scripts', '\XXSimoXX\Spy\spy_scripts', PHP_INT_MAX);

add_action('wp_enqueue_scripts',    '\XXSimoXX\Spy\spy_styles',  PHP_INT_MAX);
add_action('admin_enqueue_scripts', '\XXSimoXX\Spy\spy_styles',  PHP_INT_MAX);
add_action('login_enqueue_scripts', '\XXSimoXX\Spy\spy_styles',  PHP_INT_MAX);

add_action('admin_menu',            '\XXSimoXX\Spy\create_menu', 100);

function spy_scripts() {
	global $wp_scripts;
	$list    = get_option('xsx-spy-scripts', []);
	$scripts = wp_print_scripts();
	foreach ($scripts as $script) {
		array_push($list, $wp_scripts->registered[$script]->src);
	}
	$list = array_unique($list);
	update_option('xsx-spy-scripts', $list);
}

function spy_styles() {
	global $wp_styles;
	$list   = get_option('xsx-spy-styles', []);
	$styles = wp_print_styles();
	foreach ($styles as $style) {
		array_push($list, $wp_styles->registered[$style]->src);
	}
	$list = array_unique($list);
	update_option('xsx-spy-styles', $list);
}

function create_menu() {
	if (!current_user_can('manage_options')) {
		return;
	}
	$page = add_menu_page(
		'Spy Script and Styles',
		'Spy S&S',
		'manage_options',
		'spy',
		'\XXSimoXX\Spy\render_page',
		'dashicons-hammer'
	);
	add_action('load-'.$page, '\XXSimoXX\Spy\empty_action');
}

function render_page() {
	foreach (['scripts', 'styles'] as $item) {
		$list = get_option('xsx-spy-'.$item, []);
		asort($list);
		$list = array_map('\XXSimoXX\Spy\purge_path', $list);
		echo '<h1>'.esc_html(ucfirst($item)).' list ('.count($list).')<a href="'.esc_url(wp_nonce_url(add_query_arg(['action' => 'empty', 'empty' => sanitize_key($item)]), 'empty', '_xsx-spy')).'"><span style="text-decoration: none" class="dashicons dashicons-trash"></span></a></h1>';
		echo wp_kses(implode('<br>', $list), ['br' => []]);
	}
}

function purge_path($path) {
	$url = site_url();
	if (stripos($path, $url) === 0) {
		return substr($path, strlen($url));
	}
	return $path;
}

function empty_action() {
	if (!isset($_GET['action'])) {
		return;
	}
	if ($_GET['action'] !== 'empty') {
		return;
	}
	if (!check_admin_referer('empty', '_xsx-spy')) {
		return;
	}
	if (!current_user_can('manage_options')) {
		return;
	}
	if (!isset($_REQUEST['empty'])) {
		return;
	}
	$item = sanitize_key(wp_unslash($_REQUEST['empty']));
	if (!in_array($item, ['scripts', 'styles'])) {
		return;
	}
	delete_option('xsx-spy-'.$item);
	$sendback = remove_query_arg(['action', 'empty', '_xsx-spy'], wp_get_referer());
	wp_safe_redirect($sendback);
	exit;
}

register_uninstall_hook(__FILE__, '\XXSimoXX\Spy\uninstall');
function uninstall() {
	if (!defined('WP_UNINSTALL_PLUGIN')) {
		die;
	}
	delete_option('xsx-spy-scripts');
	delete_option('xsx-spy-styles');
}
