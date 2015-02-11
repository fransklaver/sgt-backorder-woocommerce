<?php
function reset_store()
{
	if (!isset($_POST["reset_store"]) || $_POST["reset_store"] != 1)
		return false;

	$products = get_posts(array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'post_status' => 'publish'
		));
	foreach ($products as $post) {
		$product = wc_get_product($post);
		$product->set_stock(0);
		wp_update_post(array(
			'ID' => $post->ID,
			'post_status' => 'private',
		));
	}

	$orders = get_posts(array(
			'post_type' => 'shop_order',
			'posts_per_page' => -1,
			'post_status' => array('wc-on-hold', 'wc-processing')
		));
	foreach ($orders as $post) {
		wp_update_post(array(
			'ID' => $post->ID,
			'post_status' => 'wc-completed'
		));
	}
	_e('Store has been reset', 'sgt-backorder-woocommerce');
	echo '<br/>';
	return true;
}
?>
