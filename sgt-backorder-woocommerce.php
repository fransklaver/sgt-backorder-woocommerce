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
require('storebackorderpage.php');
require('resetstorepage.php');

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

	woocommerce_wp_select(
		array(
			'id'          => '_sgt_product_unit',
			'label'       => __('Unit', 'sgt-backorder-woocommerce'),
			'desc_tip'    => true,
			'description' => __('Select the unit for this item', 'sgt-backorder-woocommerce'),
			'options'     => array('pieces', 'grams')
		)
	);

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

	$sale_amount = get_post_meta($post->ID, '_sgt_sale_amount', true);
	if (empty($sale_amount))
		$sale_amount = '1';

	woocommerce_wp_text_input(
		array(
			'id'          => '_sgt_sale_amount',
			'label'       => __('Sale amount', 'sgt-backorder-woocommerce'),
			'placeholder' => $sale_amount,
			'desc_tip'    => 'true',
			'description' => __('Fill in how many items are sold in one go', 'sgt-backorder-woocommerce'),
			'type'        => 'number',
			'custom_attributes' => array('step' => 'any', 'min' => '0')
		)
	);

	$division_amount = get_post_meta($post->ID, '_sgt_division_amount', true);
	if (empty($division_amount))
		$division_amount = '1';

	woocommerce_wp_text_input(
		array(
			'id'          => '_sgt_division_amount',
			'label'       => __('Division amount', 'sgt-backorder-woocommerce'),
			'placeholder' => $division_amount,
			'desc_tip'    => 'true',
			'description' => __('Fill in how many items are used to split the difference', 'sgt-backorder-woocommerce'),
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
	$sale_amount = $_POST['_sgt_sale_amount'];
	if (!empty($sale_amount))
		update_post_meta($post_id, '_sgt_sale_amount', esc_attr($sale_amount));
	$division_amount = $_POST['_sgt_division_amount'];
	if (!empty($division_amount))
		update_post_meta($post_id, '_sgt_division_amount', esc_attr($division_amount));
	$product_unit = $_POST['_sgt_product_unit'];
	if (!empty($product_unit))
		update_post_meta($post_id, '_sgt_product_unit', esc_attr($product_unit));
}
?>
