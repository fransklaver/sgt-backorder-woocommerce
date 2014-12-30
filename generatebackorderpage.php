<?php
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
	$sale_amount = get_post_meta($post->ID, '_sgt_sale_amount', true);
	$bulk_amount = $bulk_amount / $sale_amount;

	$stock = get_post_meta($post->ID, '_stock', true);
	$back_order = -$stock;

	$something_wrong = sgt_something_wrong($post);
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
			value="<?php echo $post->ID; ?>" />
	<td><a class="row-title" href="<?php echo get_edit_post_link($post->ID, '&'); ?>"><?php echo $title; ?></a></td>
	<td><?php echo $sku; ?></td>
	<td><?php echo $back_order; ?></td>
	<td><?php echo $bulk_amount; ?></td>
	<td><input type="number" min="0" name="back_order[<?php echo $line_id; ?>][amount]"value="<?php echo $bulk_order; ?>" /></td>
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
			$post = $loop->next_post();
			if ($post->post_status != 'publish')
				continue;
			$something_wrong |= sgt_add_back_order_page_line($post, $line_id++);
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
