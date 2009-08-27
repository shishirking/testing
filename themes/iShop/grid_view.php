<?php
global $wpsc_query, $wpdb;
?>
<div id='products_page_container' class="wpsc_container productdisplay example-category">

<?php if(wpsc_has_breadcrumbs()) : ?>
		<div class='breadcrumb'>
			<a href='<?php echo get_option('home'); ?>'><?php echo get_option('blogname'); ?></a> &raquo;
			<?php while (wpsc_have_breadcrumbs()) : wpsc_the_breadcrumb(); ?>
				<?php if(wpsc_breadcrumb_url()) :?> 	   
					<a href='<?php echo wpsc_breadcrumb_url(); ?>'><?php echo wpsc_breadcrumb_name(); ?></a> &raquo;
				<?php else: ?> 
					<?php echo wpsc_breadcrumb_name(); ?>
				<?php endif; ?> 
			<?php endwhile; ?>
		</div>
	<?php endif; ?>
	
	<?php do_action('wpsc_top_of_products_page'); // Plugin hook for adding things to the top of the products page, like the live search ?>
	
	<?php if(wpsc_is_in_category()) : ?>
		<div class='wpsc_category_details'>
			<?php if(get_option('show_category_thumbnails') && wpsc_category_image()) : ?>
				<img src='<?php echo wpsc_category_image(); ?>' alt='<?php echo wpsc_category_name(); ?>' title='<?php echo wpsc_category_name(); ?>' />
			<?php endif; ?>
			
			<?php if(get_option('wpsc_category_description') &&  wpsc_category_description()) : ?>
				<?php echo wpsc_category_description(); ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	
	<?php if(wpsc_has_pages() && ((get_option('wpsc_page_number_position') == 1 ) || (get_option('wpsc_page_number_position') == 3)))  : ?>
		<div class='wpsc_page_numbers'>
		  Pages: 
			<?php while (wpsc_have_pages()) : wpsc_the_page(); ?>
				<?php if(wpsc_page_is_selected()) :?> 	   
					<a href='<?php echo wpsc_page_url(); ?>' class='selected'><?php echo wpsc_page_number(); ?></a>
				<?php else: ?> 
					<a href='<?php echo wpsc_page_url(); ?>'><?php echo wpsc_page_number(); ?></a>
				<?php endif; ?> 
			<?php endwhile; ?>
		</div>
	<?php endif; ?>

	<div class="product_grid_display">
		<?php while (wpsc_have_products()) :  wpsc_the_product(); ?>
			<div class="product_grid_item product_view_<?php echo wpsc_the_product_id(); ?>">
				  
				<?php if(wpsc_the_product_thumbnail()) :?> 	   
					<div class="item_image">
						<a href="<?php echo wpsc_the_product_permalink(); ?>"><img class="product_image" id="product_image_<?php echo wpsc_the_product_id(); ?>" alt="<?php echo wpsc_the_product_title(); ?>" title="<?php echo wpsc_the_product_title(); ?>" src="<?php echo wpsc_the_product_thumbnail(); ?>"/></a>
					</div>
				<?php else: ?> 
					<div class="item_no_image">
						<a href="<?php echo wpsc_the_product_permalink(); ?>">
						<span>No Image Available</span>
						</a>
					</div>
				<?php endif; ?> 
				
					
				<?php if(get_option('show_images_only') != 1): ?>
					<div class="grid_product_info">
						<div class="product_text">
							<strong><?php echo wpsc_the_product_title(); ?></strong>
							
						<?php if((wpsc_the_product_description() != '') && (get_option('display_description') == 1)): ?>
							<p class='griddescription'><?php echo wpsc_the_product_description(); ?></p>
						<?php endif; ?>
							
							<br/>
							<span class="pricedisplay"><?php echo wpsc_product_normal_price(); ?></span>Price: 
							
						<?php if(get_option('display_moredetails') == 1) : ?>
							<br />
							<a href='<?php echo wpsc_the_product_permalink(); ?>'>More Details</a>
						<?php endif; ?> 
						</div>
					</div>
					<div class="grid_more_info">
						<form class='product_form'  enctype="multipart/form-data" action="<?php echo wpsc_this_page_url(); ?>" method="post" name="product_<?php echo wpsc_the_product_id(); ?>" id="product_<?php echo wpsc_the_product_id(); ?>" >
							<input type="hidden" value="add_to_cart" name="wpsc_ajax_action"/>
							<input type="hidden" value="<?php echo wpsc_the_product_id(); ?>" name="product_id"/>
							
							
							<?php if(get_option('display_variations') == 1) : ?>
								<?php /** the variation group HTML and loop */ ?>
								<div class="wpsc_variation_forms">
									<?php while (wpsc_have_variation_groups()) : wpsc_the_variation_group(); ?>
										<p>
											<label for="<?php echo wpsc_vargrp_form_id(); ?>"><?php echo wpsc_the_vargrp_name(); ?>:</label>
											<?php /** the variation HTML and loop */?>
											<select class='wpsc_select_variation' name="variation[<?php echo wpsc_vargrp_id(); ?>]" id="<?php echo wpsc_vargrp_form_id(); ?>">
											<?php while (wpsc_have_variations()) : wpsc_the_variation(); ?>
												<option value="<?php echo wpsc_the_variation_id(); ?>"  <?php echo wpsc_the_variation_out_of_stock(); ?> ><?php echo wpsc_the_variation_name(); ?></option>
											<?php endwhile; ?>
											</select> 
										</p>
									<?php endwhile; ?>
								</div>
								<?php /** the variation group HTML and loop ends here */?>
							<?php endif; ?>
							
							<?php if((get_option('display_addtocart') == 1) && (get_option('addtocart_or_buynow') !='1')) :?> 	   
								<?php if(wpsc_product_has_stock()) : ?>
									<input type="submit" value="<?php echo TXT_WPSC_ADDTOCART; ?>" name="Buy" class="wpsc_buy_button" id="product_<?php echo wpsc_the_product_id(); ?>_submit_button"/>
								<?php else : ?>
									<p class='soldout'><?php echo TXT_WPSC_PRODUCTSOLDOUT; ?></p>
								<?php endif ; ?>
							<?php endif; ?>
							
						</form>		
					</div>
					
					<?php if((get_option('display_addtocart') == 1) && (get_option('addtocart_or_buynow') == '1')) :?> 	  
						<?php echo wpsc_buy_now_button(wpsc_the_product_id()); ?>
					<?php endif ; ?>
					
				<?php endif; ?> 				
			</div>
			
			
			<?php if((get_option('grid_number_per_row') > 0) && ((($wpsc_query->current_product +1) % get_option('grid_number_per_row')) == 0)) :?>
			  <div class='grid_view_newline'></div>
			<?php endif ; ?>
		
			
			
		<?php endwhile; ?>
		
		<?php if(wpsc_product_count() < 1):?>
			<p><?php  echo TXT_WPSC_NOITEMSINTHISGROUP; ?></p>
		<?php endif ; ?>
		
		
	</div>
	
	<?php if(wpsc_has_pages() &&  ((get_option('wpsc_page_number_position') == 2) || (get_option('wpsc_page_number_position') == 3))) : ?>
	<div class='wpsc_page_numbers'>
		Pages: 
		<?php while ($wpsc_query->have_pages()) : $wpsc_query->the_page(); ?>
			<?php if(wpsc_page_is_selected()) :?> 	   
				<a href='<?php echo wpsc_page_url(); ?>' class='selected'><?php echo wpsc_page_number(); ?></a>
			<?php else: ?> 
				<a href='<?php echo wpsc_page_url(); ?>'><?php echo wpsc_page_number(); ?></a>
			<?php endif; ?> 
		<?php endwhile; ?>
	</div>
	<?php endif; ?>
	
	
	<?php

	if(function_exists('fancy_notifications')) {
		echo fancy_notifications();
	}
	?>
</div>