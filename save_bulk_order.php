<?php
if (isset($_POST) && !empty($_POST)) {
	$bulk_order = $_POST['bulk_order'];
	foreach ($bulk_order as $post_id => $b) {
		echo get_the_title($post_id).': '.$b;
	}
}
?>
