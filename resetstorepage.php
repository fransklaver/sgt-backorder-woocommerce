<?php
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
?>
