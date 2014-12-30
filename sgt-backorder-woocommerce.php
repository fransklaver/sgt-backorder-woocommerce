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

function sgt_something_wrong($post)
{
	$bulk_amount = get_post_meta($post->ID, '_sgt_bulk_amount', true);
	$stock = get_post_meta($post->ID, '_stock', true);
	$sku = get_sku($post->ID);

	return empty($sku) || empty($bulk_amount) || $bulk_amount == '0' || $stock > 0;
}

function sgt_add_back_order_page_line($post, $line_id)
{
	$title = get_the_title($post->ID);
	$sku = get_sku($post->ID);
	$bulk_amount = get_post_meta($post->ID, '_sgt_bulk_amount', true);
	$stock = get_post_meta($post->ID, '_stock', true);
	$back_order = -$stock;

	$something_wrong = sgt_something_wrong($post);
	?><tr <?php
	if ($something_wrong) {
		?> style="background: red" <?php
	}
	?>><?php

	if ($something_wrong) {
		$back_order = 0;
	} else {
		$back_order = ceil($back_order / $bulk_amount);
	}
?>
	<input type="hidden" name="back_order[<?php echo $line_id; ?>][post_id]"
			value="<?php echo $post->ID; ?>" />
	<td><a class="row-title" href="<?php echo get_edit_post_link($post->ID, '&'); ?>"><?php echo $title; ?></a></td>
	<td><?php echo $sku; ?></td>
	<td><?php echo $back_order; ?></td>
	<td><?php echo $bulk_amount; ?></td>
	<td><input type="number" min="0" name="back_order[<?php echo $line_id; ?>][amount]"value="<?php echo $back_order; ?>" /></td>
	</tr><?php
	return $something_wrong;
}

function show_backorder_page()
{
	$args = array(
		'post_type' => 'product',
		'posts_per_page' => -1
	);
	$loop = new WP_Query($args);
	$product_count = $loop->post_count;
	$line_id = 0;
	if ($loop->have_posts()) {
		$something_wrong = false;
?><form method="post" action=""><table class="form-table">
	<input type="hidden" name="store_backorder" value="1" />
<thead>
	<th><?php _e('Product', 'sgt-backorder-woocommerce'); ?></th>
	<th><?php _e('SKU'); ?></th>
	<th><?php _e('Back order', 'sgt-backorder-woocommerce');?></th>
	<th><?php _e('Bulk amount', 'sgt-backorder-woocommerce');?></th>
	<th><?php _e('Total bulk', 'sgt-backorder-woocommerce'); ?></th>
</thead>
<tbody id="the-list"><?php
		while ($loop->have_posts()) {
			$something_wrong |= sgt_add_back_order_page_line($loop->next_post(), $line_id++);
		}
?></tbody>
</table>
	<?php
		if ($something_wrong)
			_e('Errors were detected, you may want to fix them first', 'sgt-backorder-woocommerce');
		submit_button(__('Create back order', 'sgt-backorder-woocommerce'), 'primary', 'generate_back_order_button');
	?>
</form>
<?php
	} else {
		_e('No orders this week', 'sgt-backorder-woocommerce');
	}
}

function add_back_order_page_fill_div()
{
	if (store_backorder())
		return;
	show_backorder_page();
}

function add_back_order_page()
{
	echo '<div class="wrap">';
	add_back_order_page_fill_div();
	echo '</div>';
}
?>
