<?php
function sgt_something_wrong($product_id)
{
	$bulk_amount = get_post_meta($product_id, '_sgt_bulk_amount', true);
	$stock = get_post_meta($product_id, '_stock', true);
	$sku = get_sku($product_id);

	return empty($sku) || empty($bulk_amount) || $bulk_amount == '0' || $stock > 0;
}

function sgt_add_back_order_page_line($product_id, $back_order, $line_id)
{
	$title = get_the_title($product_id);
	$sku = get_sku($product_id);
	$bulk_amount = get_post_meta($product_id, '_sgt_bulk_amount', true);
	$sale_amount = get_post_meta($product_id, '_sgt_sale_amount', true);
	if ($sale_amount)
		$bulk_amount = $bulk_amount / $sale_amount;

	$something_wrong = sgt_something_wrong($product_id);
	?><tr <?php
	if ($something_wrong) {
		?> style="background: red" <?php
	}
	?>><?php

	if ($something_wrong) {
		$bulk_order = 0;
	} else {
		$bulk_order = ceil($back_order / $bulk_amount);
	}
?>
	<input type="hidden" name="back_order[<?php echo $line_id; ?>][post_id]"
			value="<?php echo $product_id; ?>" />
	<td><a class="row-title" href="<?php echo get_edit_post_link($product_id, '&'); ?>"><?php echo $title; ?></a></td>
	<td><?php echo $sku; ?></td>
	<td><?php echo $back_order; ?></td>
	<td><?php echo $bulk_amount; ?></td>
	<td><input type="number" min="0" name="back_order[<?php echo $line_id; ?>][amount]"value="<?php echo $bulk_order; ?>" /></td>
	</tr><?php
	return $something_wrong;
}

function show_backorder_page()
{
	$orders = get_posts(array(
			'post_type' => 'shop_order',
			'posts_per_page' => -1,
			'post_status' => array('wc-on-hold', 'wc-processing')
			));

	$products = array();
	foreach ($orders as $order_id) {
		$order = new WC_Order($order_id);
		$items = $order->get_items();
		foreach ($items as $v) {
			$product_id = $v['item_meta']['_product_id'][0];
			$quantity = $v['item_meta']['_qty'][0];

			$pid = (string)$product_id;
			if (array_key_exists($pid, $products))
				$products[$pid] += $quantity;
			else
				$products[$pid] = $quantity;
		}
	}

	$product_count = count($products);
	$line_id = 0;

	if ($product_count) {
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
		foreach ($products as $product_id => $v) {
			$something_wrong |= sgt_add_back_order_page_line($product_id, $v, $line_id++);
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
	if (reset_store())
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
