<?php
function store_backorder()
{
	if (!isset($_POST["store_backorder"]) || $_POST["store_backorder"] != 1)
		return false;

	$bulk_order = $_POST['back_order'];
	usort($bulk_order, 'sort_orders');
?>
	<table>
	<tr>
	 <th><?php _e('SKU', 'sgt-backorder-woocommerce'); ?></th>
	 <th><?php _e('Product', 'sgt-backorder-woocommerce'); ?></th>
	 <th><?php _e('Quantity', 'sgt-backorder-woocommerce'); ?></th>
	</tr>
<?php
	foreach ($bulk_order as $b) {
		$post_id = $b['post_id'];
		$amount = $b['amount'];
		$sku = get_sku($post_id);
		?>
		<tr>
		 <td><?php echo $sku; ?></td>
		 <td><?php echo get_the_title($post_id); ?></td>
		 <td><?php echo $amount; ?></td>
		</tr>
		<?php
	}
	echo '</table>';
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

?>
