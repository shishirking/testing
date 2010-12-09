<?php
/**
 * WP eCommerce database updating page functions
 *
 * These are the main WPSC Admin functions
 *
 * @package wp-e-commerce
 * @since 3.8
 */

global $show_update_page, $wpdb;

$show_update_page = 1;

// if there's nothing in the children variation cache, refresh it, just to make sure.
if ( 0 == count( get_option( 'wpsc-variation_children' ) ) ) {
	delete_option( 'wpsc-variation_children' );
	_get_term_hierarchy( 'wpsc-variation' );
}

// if there's nothing in the children variation cache, refresh it, just to make sure.
if ( 0 == count( get_option( 'wpsc_product_category_children' ) ) ) {
	delete_option( 'wpsc_product_category_children' );
	_get_term_hierarchy( 'wpsc_product_category_children' );
}

// If database is already updated, then no need to update
if ( get_option( 'wpsc_version' ) >= 3.8 ) {
	$show_update_page = 0;
}

// Check to see if there are any products.
// if they don't have any, they don't need to update
if ( get_option( 'wpsc_version' ) < 3.8 || !get_option( 'wpsc_version' ) ) {

	$product_count = $wpdb->get_var( "SELECT COUNT(*) FROM " . WPSC_TABLE_PRODUCT_LIST );

	if ( $product_count > 0 ) {

		$show_update_page = 0;

		function wpsc_display_update_notice() {
			echo "<div id='wpsc-warning' class='error fade'><p><strong>" . __( 'WP e-Commerce is almost ready.' ) . "</strong> " . sprintf( __( 'You must <a href="%1$s">update your database</a> to import all of your products.' ), "admin.php?page=wpsc-update") . "</p></div>";
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] != 'wpsc-update' )
			add_action( 'admin_notices', 'wpsc_display_update_notice' );

	// There weren't any products, so mark the update as complete
	} else {	
		update_option( 'wpsc_version', '3.8' );
	}
}

function wpsc_display_update_page() { ?>

	<div class="wrap">
		<h2><?php echo esc_html( __('Update WP e-Commerce', 'wpsc') ); ?> </h2>
		<br />

	<?php
		if ( isset( $_POST['run_updates'] ) ) :
			echo 'Updating Categories...';
			wpsc_convert_category_groups();
			echo '<br />Updating Variations...';
			wpsc_convert_variation_sets();
			echo '<br />Updating Products...';
			wpsc_convert_products_to_posts();
			echo '<br />Updating Child Products...';
			wpsc_convert_variation_combinations();
			echo '<br />Updating Product Files...';
			wpsc_update_files();
			echo '<br />Updating Database...';
			wpsc_update_database();
			echo '<br /><br /><strong>WP e-Commerce updated successfully!</strong>';
			update_option('wpsc_version', 3.8);
		else:


		_e('Your WP e-Commerce database needs to be updated for WP e-Commerce 3.8.  To perform this update, press the button below.  It is highly recommended that you back up your database before performing this update.','wpsc'); 
?>		<br />
		<br />
		<em><?php _e('Note: If the server times out or runs out of memory, just reload this page, the server will pick up where it left off.','wpsc'); ?></em>
		<br />
		
		<form action="" method="post" id="setup">
			<input type="hidden" name="run_updates" value="true" id="run_updates">
			<p class="step"><input type="submit" class="button" value="Update WP e-Commerce" name="Submit"></p>
		</form>
	<?php
		endif;
	?>
	</div>

<?php 
}

?>
