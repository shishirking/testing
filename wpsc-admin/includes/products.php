<?php
/**
 * WP eCommerce product page functions
 *
 * These are the main WPSC Admin functions
 *
 * @package wp-e-commerce
 * @since 3.8
 *
 */

/**
 * wpsc_product_rows function, copies the functionality of the wordpress code for displaying posts and pages, but is for products
 *
 */
function wpsc_admin_product_listing($parent_product = null) {
	global $wp_query, $wpsc_products;

	add_filter('the_title','esc_html');
	$args = array_merge( $wp_query->query, array( 'posts_per_page' => '-1', 'post_status' => 'all' ) );
	$wpsc_temp_query = query_posts( $args );
	if ( empty($wpsc_products) )
		$wpsc_products = &$wpsc_temp_query;

	foreach ( (array)$wpsc_products as $product ) {
		wpsc_product_row($product, $parent_product);
	}
}
/**
 * Adds the -trash status in the product row of manage products page
 * @access public
 *
 * @since 3.8
 * @param $post_status (array) of current posts statuses
 * @return $post_status (array)
 */
function wpsc_trashed_post_status($post_status){
	$post = get_post(get_the_ID());
	if('wpsc-product' == $post->post_type && 'trash' == $post->post_status && !in_array('trash', $post_status))
		$post_status[] = 'Trash';

	return $post_status;
}
add_filter('display_post_states','wpsc_trashed_post_status');

/**
 * Spits out the current products details in a table row for manage products page and variations on edit product page.
 * @access public
 *
 * @since 3.8
 * @param $product (Object), $parent_product (Int) Note: I believe parent_product is unused
 */
function wpsc_product_row(&$product, $parent_product = null) {
	global $mode, $current_user;
	
	//is this good practice? <v.bakaitis@gmail.com>
	static $rowclass;
	
	$global_product = $product;
	setup_postdata($product);

	$rowclass = 'alternate' == $rowclass ? '' : 'alternate';
	$post_owner = ( $current_user->ID == $product->post_author ? 'self' : 'other' );
	$edit_link = get_edit_post_link( $product->ID );

        $title = get_the_title( $product->ID );

	if ( empty( $title ) )
		$title = __('(no title)');

	?>

	<tr id='post-<?php echo $product->ID; ?>' class='<?php echo trim( $rowclass . ' author-' . $post_owner . ' status-' . $product->post_status ); ?> iedit <?php if ( get_option ( 'wpsc_sort_by' ) == 'dragndrop') { echo 'product-edit'; } ?>' valign="top">
	<?php
	$posts_columns = get_column_headers( 'wpsc-product_variants' );
	
	if(empty($posts_columns))
		$posts_columns = array('image' => '', 'title' => __('Name', 'wpsc') , 'weight' => __('Weight', 'wpsc'), 'stock' => __('Stock', 'wpsc'), 'price' => __('Price', 'wpsc'), 'sale_price' => __('Sale Price', 'wpsc'), 'SKU' => __('SKU', 'wpsc'), 'hidden_alerts' => '');

	foreach ( $posts_columns as $column_name=>$column_display_name ) {
		$class = "class=\"$column_name column-$column_name\"";

		$attributes = "$class$style";

		switch ($column_name) {

                    case 'date': /* !date case */
			if ( '0000-00-00 00:00:00' == $product->post_date && 'date' == $column_name ) {
				$t_time = $h_time = __('Unpublished');
				$time_diff = 0;
			} else {
				$t_time = get_the_time(__('Y/m/d g:i:s A'));
				$m_time = $product->post_date;
				$time = get_post_time('G', true, $post);

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < 24*60*60 )
					$h_time = sprintf( __('%s ago'), human_time_diff( $time ) );
				else
					$h_time = mysql2date(__('Y/m/d'), $m_time);
			}

			echo '<td ' . $attributes . '>';
			if ( 'excerpt' == $mode )
				echo apply_filters('post_date_column_time', $t_time, $post, $column_name, $mode);
			else
				echo '<abbr title="' . $t_time . '">' . apply_filters('post_date_column_time', $h_time, $post, $column_name, $mode) . '</abbr>';
			echo '<br />';
			if ( 'publish' == $product->post_status ) {
				_e('Published');
			} elseif ( 'future' == $product->post_status ) {
				if ( $time_diff > 0 )
					echo '<strong class="attention">' . __('Missed schedule') . '</strong>';
				else
					_e('Scheduled');
			} else {
				_e('Last Modified');
			}
			echo '</td>';
		break;

		case 'title': /* !title case */
			$attributes = 'class="post-title column-title"' . $style;
			
			$edit_link = wp_nonce_url( $edit_link, 'edit-product_'.$product->ID );
		?>
		<td <?php echo $attributes ?>>
			<strong>
			<?php if ( current_user_can('edit_post', $product->ID) && $product->post_status != 'trash' ) { ?>
				<a class="row-title" href="<?php echo $edit_link; ?>" title="<?php echo esc_attr(sprintf(__('Edit &#8220;%s&#8221;'), $title)); ?>"><?php echo $title ?></a>
				<?php if($parent_product): ?>
					<input type="hidden" class="wpsc_ie_id wpsc_ie_field" value="<?php echo $product->ID ?>">
					<input type="text" class="wpsc_ie_title wpsc_ie_field" value="<?php echo $title ?>">
					<div class="wpsc_inline_actions"><input type="button" class="button-primary wpsc_ie_save" value="Save"><img src="<?php bloginfo('url') ?>/wp-admin/images/wpspin_light.gif" class="loading_indicator"><br/><input type="button" class="button-secondary cancel wpsc_ie_cancel" value="Cancel"></div>
				<?php endif; ?>
			<?php } else {
				echo $title;
			};

			 _post_states($product);
			$product_alert = apply_filters('wpsc_product_alert', array(false, ''), $product);
			if(!empty($product_alert['messages']))
				$product_alert['messages'] = implode("\n",(array)$product_alert['messages']);

			if($product_alert['state'] === true) {
				?>
				<img alt='<?php echo $product_alert['messages'];?>' title='<?php echo $product_alert['messages'];?>' class='product-alert-image' src='<?php echo  WPSC_CORE_IMAGES_URL;?>/product-alert.jpg' alt='' />
				<?php
			}

			// If a product alert has stuff to display, show it.
			// Can be used to add extra icons etc
			if ( !empty( $product_alert['display'] ) ) {
				echo $product_alert['display'];
			}

			 ?>
			</strong>
			<?php
 			$has_var = '';
 			if(wpsc_product_has_children($product->ID))
 				$has_var = 'wpsc_has_variation';
			$actions = array();
			if ( current_user_can('edit_post', $product->ID) && 'trash' != $product->post_status ) {
				$actions['edit'] = '<a class="edit-product" href="'.$edit_link.'" title="' . esc_attr(__('Edit this product', 'wpsc')) . '">'. __('Edit', 'wpsc') . '</a>';
				$actions['quick_edit'] = "<a class='wpsc_editinline ".$has_var."' title='".esc_attr(__('Quick Edit', 'wpsc'))."' href='#'>".__('Quick Edit')."</a>";
			}

			if ( in_array($product->post_status, array('pending', 'draft')) ) {
				if ( current_user_can('edit_product', $product->ID) ) {
					$actions['view'] = '<a href="'.get_permalink($product->ID).'" title="'.esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), $title)) . '" rel="permalink">'.__('Preview').'</a>';
				}
			} else if ( 'trash' != $product->post_status ) {
				$actions['view'] = '<a href="'.get_permalink($product->ID).'" title="'.esc_attr(sprintf(__('View &#8220;%s&#8221;'), $title)).'" rel="permalink">'.__('View').'</a>';
			}

			$actions = apply_filters('post_row_actions', $actions, $product);
			$action_count = count($actions);
			$i = 0;
			echo '<div class="row-actions">';

			foreach ( $actions as $action => $link ) {
				++$i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				echo "<span class='$action'>$link$sep</span>";
			}

			echo '</div>';
			get_inline_data($product);
		?>
		</td>
		<?php
		break;



		case 'image':  /* !image case */
			?>
			<td class="product-image ">
			<?php
		   $attached_images = (array)get_posts(array(
	          'post_type' => 'attachment',
	          'numberposts' => 1,
	          'post_status' => null,
	          'post_parent' => $product->ID,
	          'orderby' => 'menu_order',
	          'order' => 'ASC'
		    ));



		 	 if(isset($product->ID) && has_post_thumbnail($product->ID)){
				echo get_the_post_thumbnail($product->ID, 'admin-product-thumbnails');
		     }elseif(!empty($attached_images)){
			    $attached_image = $attached_images[0];

				$src =wp_get_attachment_url($attached_image->ID);
		     ?>
		     	<div style='width:38px;height:38px;overflow:hidden;'>
					<img title='Drag to a new position' src='<?php echo $src; ?>' alt='<?php echo $title; ?>' width='38' height='38' />
				</div>
				<?php


		     }else{
		      	$image_url = WPSC_CORE_IMAGES_URL . "/no-image-uploaded.gif";
				?>
					<img title='Drag to a new position' src='<?php echo $image_url; ?>' alt='<?php echo $title; ?>' width='38' height='38' />
				<?php


		      }

		?>
			</td>
			<?php
		break;



		case 'price':  /* !price case */

			$price = get_post_meta($product->ID, '_wpsc_price', true);
			?>
				<td  <?php echo $attributes ?>>
					<?php echo wpsc_currency_display( $price ); ?>
					<input type="text" class="wpsc_ie_field wpsc_ie_price" value="<?php echo $price; ?>">
				</td>
			<?php
		break;

		case 'weight' :

			$product_data['meta'] = array();
			$product_data['meta'] = get_post_meta($product->ID, '');
				foreach($product_data['meta'] as $meta_name => $meta_value) {
					$product_data['meta'][$meta_name] = maybe_unserialize(array_pop($meta_value));
				}
		$product_data['transformed'] = array();
		if(!isset($product_data['meta']['_wpsc_product_metadata']['weight'])) $product_data['meta']['_wpsc_product_metadata']['weight'] = "";
		if(!isset($product_data['meta']['_wpsc_product_metadata']['weight_unit'])) $product_data['meta']['_wpsc_product_metadata']['weight_unit'] = "";

		$product_data['transformed']['weight'] = wpsc_convert_weight($product_data['meta']['_wpsc_product_metadata']['weight'], "pound", $product_data['meta']['_wpsc_product_metadata']['weight_unit']);
			$weight = $product_data['transformed']['weight'];
			if($weight == ''){
				$weight = '0';
			}

			$unit = $product_data['meta']['_wpsc_product_metadata']['weight_unit'];
			switch($unit) {
				case "pound":
					$unit = " lbs.";
					break;
				case "ounce":
					$unit = " oz.";
					break;
				case "kilograms":
				case "kilogram":
					$unit = " kgs.";
					break;
				case "gram":
				default:
					$unit = " g";
			}
			?>
				<td  <?php echo $attributes ?>>
					<span><?php echo $weight; ?></span>
					<input type="text" class="wpsc_ie_field wpsc_ie_weight" value="<?php echo $weight; ?>">
				</td>
			<?php

		break;

		case 'stock' :
			$stock = get_post_meta($product->ID, '_wpsc_stock', true);
			?>
				<td  <?php echo $attributes ?>>
					<span><?php echo $stock ? $stock : __('N/A', 'wpsc') ; ?></span>
					<input type="text" class="wpsc_ie_field wpsc_ie_stock" value="<?php echo $stock; ?>">
				</td>
	<?php
		break;

		case 'categories':  /* !categories case */
		?>
		<td <?php echo $attributes ?>><?php
			$categories = get_the_product_category($product->ID);
			if ( !empty( $categories ) ) {
				$out = array();
				foreach ( $categories as $c )
					$out[] = "<a href='admin.php?page=wpsc-edit-products&amp;category={$c->slug}'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'category', 'display')) . "</a>";
					echo join( ', ', $out );
			} else {
				_e('Uncategorized');
			}
		?></td>
		<?php
		break;



		case 'tags':  /* !tags case */
		?>
		<td <?php echo $attributes ?>><?php
			$tags = get_the_tags($product->ID);
			if ( !empty( $tags ) ) {
				$out = array();
				foreach ( $tags as $c )
					$out[] = "<a href='edit.php?tag=$c->slug'> " . esc_html(sanitize_term_field('name', $c->name, $c->term_id, 'post_tag', 'display')) . "</a>";
				echo join( ', ', $out );
			} else {
				_e('No Tags');
			}
		?></td>
		<?php
		break;
		case 'SKU':
			$sku = get_post_meta($product->ID, '_wpsc_sku', true);
			?>
				<td  <?php echo $attributes ?>>
					<span><?php echo $sku ? $sku : __('N/A', 'wpsc'); ?></span>
					<input type="text" class="wpsc_ie_field wpsc_ie_sku" value="<?php echo $sku; ?>">
				</td>
			<?php
		break;
		case 'sale_price':

			$price = get_post_meta($product->ID, '_wpsc_special_price', true);
			?>
				<td  <?php echo $attributes ?>>
					<span><?php echo wpsc_currency_display( $price ); ?></span>
					<input type="text" class="wpsc_ie_field wpsc_ie_special_price" value="<?php echo $price; ?>">
				</td>
			<?php

		break;


		case 'comments':  /* !comments case */
		?>
		<td <?php echo $attributes ?>><div class="post-com-count-wrapper">
		<?php
			$pending_phrase = sprintf( __('%s pending'), number_format( $pending_comments ) );
			if ( $pending_comments )
				echo '<strong>';
				comments_number("<a href='edit-comments.php?p=$product->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link */ _x('0', 'comment count') . '</span></a>', "<a href='edit-comments.php?p=$product->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link */ _x('1', 'comment count') . '</span></a>', "<a href='edit-comments.php?p=$product->ID' title='$pending_phrase' class='post-com-count'><span class='comment-count'>" . /* translators: comment count link: % will be substituted by comment count */ _x('%', 'comment count') . '</span></a>');
				if ( $pending_comments )
				echo '</strong>';
		?>
		</div></td>
		<?php
		break;



		case 'author':  /* !author case */
		?>
		<td <?php echo $attributes ?>><a href="edit.php?author=<?php the_author_meta('ID'); ?>"><?php the_author() ?></a></td>
		<?php
		break;


		case 'control_view':  /* !control view case */
		?>
		<td><a href="<?php the_permalink(); ?>" rel="permalink" class="view"><?php _e('View'); ?></a></td>
		<?php
		break;



		case 'control_edit':  /* !control edit case */
		?>
		<td><?php if ( current_user_can('edit_post', $product->ID) ) { echo "<a href='$edit_link' class='edit'>" . __('Edit') . "</a>"; } ?></td>
		<?php
		break;



		case 'control_delete':  /* !control delete case */
		?>
		<td><?php if ( current_user_can('delete_post', $product->ID) ) { echo "<a href='" . wp_nonce_url("post.php?action=delete&amp;post=$id", 'delete-post_' . $product->ID) . "' class='delete'>" . __('Delete') . "</a>"; } ?></td>
		<?php
		break;

		case 'featured': /* !control featured case */
		?>
			<td><?php do_action('manage_posts_featured_column', $product->ID); ?></td>
		<?php
		break;
		default:   /* !default case */
		?>
		<td <?php echo $attributes ?>><?php do_action('manage_posts_custom_column', $column_name, $product->ID); ?></td>
		<?php
		break;
	}
}
?>
	</tr>
<?php
	$product = $global_product;
}