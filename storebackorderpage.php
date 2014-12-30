<?php
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

?>
