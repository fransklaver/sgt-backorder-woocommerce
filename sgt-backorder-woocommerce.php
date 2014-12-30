<?php
/**
* Plugin Name: Backorder for WooCommerce
* Plugin URI: https://github.com/fransklaver/sgt-backorder-woocommerce
* Description: A plugin generating backorders from WooCommerce orders
* Version: 0.1.0
* Author: Frans Klaver
* Author URI: https://github.com/fransklaver
* License: GPL2
*/

/*  Copyright 2014  Frans Klaver  (email : fransklaver@gmail.com)
*
*   This program is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License, version 2, as
*   published by the Free Software Foundation.
*
*   This program is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this program; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
	exit;

require('generatebackorderpage.php');

load_plugin_textdomain('sgt-backorder-woocommerce', false, basename(dirname(__FILE__)).'/languages/');

if (is_admin())
{
	add_action('admin_menu', 'add_generate_back_order_menu');

	add_action( 'woocommerce_product_options_general_product_data', 'sgt_add_custom_general_fields' );
	add_action( 'woocommerce_process_product_meta', 'sgt_add_custom_general_fields_save' );
}

function get_sku($post_id)
{
	return get_post_meta($post_id, '_sku', true);
}

function sort_orders($lhs, $rhs)
{
	return strcasecmp(get_sku($lhs['post_id']), get_sku($rhs['post_id']));
}

function store_backorder()
{
	if (!isset($_POST["store_backorder"]) || $_POST["store_backorder"] != 1)
		return false;

	$bulk_order = $_POST['back_order'];
	usort($bulk_order, 'sort_orders');
	foreach ($bulk_order as $b) {
		$post_id = $b['post_id'];
		$amount = $b['amount'];
		$sku = get_sku($post_id);
		echo get_the_title($post_id).' ('.$sku.'): ' . $amount . '<br/>';
	}
?>
<form method="post" action="">
	<input type="hidden" name="reset_store" value="1" />
<?php
	submit_button(__('Reset store', 'sgt-backorder-woocommerce'), 'primary', 'clear_store_button');
?>
</form>
<?php
	return true;
}

function reset_store()
{
	if (!isset($_POST["reset_store"]) || $_POST["reset_store"] != 1)
		return false;

	$args = array(
		'post_type' => 'product',
		'posts_per_page' => -1
	);
	$loop = new WP_Query($args);
	while ($loop->have_posts()) {
		$post = $loop->next_post();
		$product = wc_get_product($post->ID);
		$product->set_stock(0);
		wp_update_post(array(
			'ID' => $post->ID,
			'post_status' => 'private',
		));
	}
	return true;
}

function add_generate_back_order_menu()
{
	add_menu_page(__('Generate back order', 'sgt-backorder-woocommerce'), __('Generate back order', 'sgt-backorder-woocommerce'), 'export', 'sgt_back_order', 'add_back_order_page', null, '60.5');

}

function sgt_add_custom_general_fields()
{
	global $post;
	$bulk_amount = get_post_meta($post->ID, '_sgt_bulk_amount', true);
	if (empty($bulk_amount))
		$bulk_amount = '0';

	woocommerce_wp_text_input(
		array(
			'id'          => '_sgt_bulk_amount',
			'label'       => __( 'Bulk amount', 'sgt-backorder-woocommerce' ),
			'placeholder' => $bulk_amount,
			'desc_tip'    => 'true',
			'description' => __( 'Fill in how many items make up one back order item', 'sgt-backorder-woocommerce' ),
			'type'        => 'number',
			'custom_attributes' => array('step' => 'any', 'min' => '0')
		)
	);
}

function sgt_add_custom_general_fields_save($post_id)
{
	$bulk_amount = $_POST['_sgt_bulk_amount'];
	if (!empty($bulk_amount))
		update_post_meta($post_id, '_sgt_bulk_amount', esc_attr($bulk_amount));
}
?>
