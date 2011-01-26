<?php

/**
 * This file used for adding fields to the products category taxonomy page and saving those values correctly :)
 *
 * @package wp-e-commerce
 * @since 3.8
 * @todo UI needs a lot of loving - lots of padding issues, if we have these boxes, they should be sortable, closable, hidable, etc.
 */

/**
 * WP eCommerce edit and add product category page functions
 *
 * These are the main WPSC Admin functions
 *
 * @package wp-e-commerce
 * @since 3.7
 */

function wpsc_ajax_set_category_order(){
  global $wpdb;
  $sort_order = $_POST['sort_order'];
  $parent_id  = $_POST['parent_id'];

  $result = true;
  foreach( $sort_order as $key=>$value ){
    if( !wpsc_update_meta( $value, 'sort_order', $key, 'wpsc_category' ) )
      $result = false;
  }
  
}

/**
 * wpsc_display_categories_page, assembles the category page
 * @param nothing
 * @return nothing
 */

add_filter( 'manage_edit-wpsc_product_category_columns', 'wpsc_custom_category_columns' );
add_filter( 'manage_wpsc_product_category_custom_column', 'wpsc_custom_category_column_data', 10, 2);
add_action( 'wpsc_product_category_add_form_fields', 'wpsc_admin_category_forms_add' ); // After left-col
add_action( 'wpsc_product_category_edit_form_fields', 'wpsc_admin_category_forms_edit' ); // After left-col

//add_action("created_wpsc_product_category", $term_id, $tt_id); //After created
//add_action("edited_wpsc_product_category", $term_id, $tt_id); //After Saved

/**
 * wpsc_custom_category_columns
 * Adds images column to category column.
 * @internal Don't feel handle column is necessary, but you would add it here if you wanted to
 * @param (array) columns | Array of columns assigned to this taxonomy
 * @return (array) columns | Modified array of columns
 */

function wpsc_custom_category_columns( $columns ) {
    // Doing it this funny way to ensure that image stays in far left, even if other items are added via plugin.

    $custom_array = array(
        'image' => __( 'Image' )
    );
    
    $columns = array_merge( $custom_array, $columns );
    
    return $columns;
}
/**
 * wpsc_custom_category_column_data
 * Adds images to the custom category column
 * @param (array) column_name | column name
 * @return nada
 */

function wpsc_custom_category_column_data( $column_name, $term_id ) {
   global $current_screen;

   echo '<img title="Drag to a new position" src="http://wpec.zaowebdesign.com/wp-content/plugins/wp-e-commerce/wpsc-core/images/no-image-uploaded.gif" alt="" width="38" height="38">';

}
/**
 *  Create the actual drag and drop list used for the admin category view
 *
 * @param array $categories
 * @param int $level
 * @return string $output
 */
function wpsc_admin_list_category_array($categories, $level = 0){
  $output = '';
  foreach($categories as $cat){

    $output .= "<li id='".$cat['id']."'>";
    $output .= "<div id='category-".$cat['id']."-container'>";

    $output .= "<div class='category_admin_list_img' id='category-".$cat['id']."-imgs'>";
    $output .= "<span title='click and drag to move' class='handle'>↕</span>";
    if($level > 0){
      $output .= "<img class='category_indenter' src='".WPSC_CORE_IMAGES_URL."/indenter.gif' alt='' title='' />";
    }
    $output .= "<a class='row-title' href='".add_query_arg('category_id', $cat['id'])."'>";
    if(isset($cat['image'])){
      $output .= "<img src=\"".WPSC_CATEGORY_URL.stripslashes($cat['image'])."\" title='".$cat['name']."' alt='".$cat['name']."' width='30' height='30' />";
    }else{
      $output .= "<img src='".WPSC_CORE_IMAGES_URL."/no-image-uploaded.gif' title='".$cat['name']."' alt='".$cat['name']."' width='30' height='30' />";
    }
    $output .= stripslashes($cat['name'])."</a>";

    $output .= "<div class='row-actions'><span class='edit'><a class='edit-product' style='cursor:pointer;' title='Edit This Category' href='".add_query_arg('category_id', $cat['id'])."'>". __('Edit', 'wpsc')."</a>";
    $output .= "</span> | <span class='edit'>";
    $nonced_url = wp_nonce_url("admin.php?wpsc_admin_action=wpsc-delete-category&amp;deleteid={$cat['id']}", 'delete-category');
    $output .=  "<a class='delete_button' style='text-decoration:none;' href='".$nonced_url."' onclick=\"return conf();\" >". __('Delete', 'wpsc')."</a>";
    $output .=  "</span></div>";
    $output .= "</div>";
    if(is_array($cat['children'])){
      $newhandle = "category-".$cat['id']."-children";
      $output .= <<<EOT
  <script type="text/javascript">
    jQuery(document).ready(function(){
      jQuery('#{$newhandle}').sortable({
        axis: 'y',
        containment: 'parent',
        handle: '.handle',
        tolerance: 'pointer',
        update: function(event, ui){
          categorySort(jQuery('#{$newhandle}').sortable('toArray'), 0);
        }
      });
    });
  </script>
EOT;
      $output .= "<ul id='{$newhandle}' class='ui-sortable'>";
      $output .= wpsc_admin_list_category_array($cat['children'], ($level + 1));
      $output .= "</ul>";
    }
    $output .= "</div></li>";

  }
  return $output;
}

/**
 * wpsc_admin_get_category_array
 * Recursively step through the categories and return it in a clean multi demensional array
 * for use in other list functions
 * @param int $parent_id
 */
function wpsc_admin_get_category_array($parent_id = null){
  global $wpdb;

  $orderedList = array();
  if(!isset($parent_id)) $parent_id = 0;
  $category_list = get_terms('wpsc_product_category','hide_empty=0&parent='.$parent_id);
  if(!is_array($category_list)){
    return false;
  }
  foreach($category_list as $category){
    $category_order = wpsc_get_categorymeta($category->term_id, 'order');
    $category_image = wpsc_get_categorymeta($category->term_id, 'image');
    if(!isset($category_order) || $category_order == 0) $category_order = (count($orderedList) +1);
    print "<!-- setting category Order number to ".$category_order."-->";
    $orderedList[$category_order]['id'] = $category->term_id;
    $orderedList[$category_order]['name'] = $category->name;
    $orderedList[$category_order]['image'] = $category_image;
    $orderedList[$category_order]['parent_id'] = $parent_id;
    $orderedList[$category_order]['children'] = wpsc_admin_get_category_array($category->term_id);
  }

  ksort($orderedList);
  return($orderedList);
}

/**
 * wpsc_admin_category_group_list, prints the left hand side of the add categories page
 * nothing returned
 */
function wpsc_admin_category_forms_add() {
	global $wpdb;
	$category_value_count = 0;
	?>

	<h3><?php _e('Advanced Settings', 'wpsc'); ?></h3>

	<div id="poststuff" class="postbox">
		<h3 class="hndle"><?php _e('Presentation Settings'); ?></h3>

		<div class="inside">
			<input type='file' name='image' value='' /><br /><br />

				<tr>
					<td>
						<?php _e('Catalog View', 'wpsc'); ?>
					</td>
					<td>
						<?php
					if (!isset($category['display_type'])) $category['display_type'] = '';

						if ($category['display_type'] == 'grid') {
							$display_type1="selected='selected'";
						} else if ($category['display_type'] == 'default') {
							$display_type2="selected='selected'";
						}

						switch($category['display_type']) {
							case "default":
								$category_view1 = "selected ='selected'";
							break;

							case "grid":
							if(function_exists('product_display_grid')) {
								$category_view3 = "selected ='selected'";
								break;
							}

							case "list":
							if(function_exists('product_display_list')) {
								$category_view2 = "selected ='selected'";
								break;
							}

							default:
								$category_view0 = "selected ='selected'";
							break;
						}?>
							<span class='small'><?php _e('To over-ride the presentation settings for this group you can enter in your prefered settings here', 'wpsc'); ?></span><br /><br />

						<select name='display_type'>
							<option value=''<?php echo $category_view0; ?> ><?php _e('Please select', 'wpsc'); ?></option>
							<option value='default' <?php if (isset($category_view1)) echo $category_view1; ?> ><?php _e('Default View', 'wpsc'); ?></option>

							<?php	if(function_exists('product_display_list')) {?>
										<option value='list' <?php echo  $category_view2; ?>><?php _e('List View', 'wpsc'); ?></option>
							<?php	} else { ?>
										<option value='list' disabled='disabled' <?php if (isset($category_view2)) echo $category_view2; ?>><?php _e('List View', 'wpsc'); ?></option>
							<?php	} ?>
							<?php if(function_exists('product_display_grid')) { ?>
										<option value='grid' <?php if (isset($category_view3)) echo  $category_view3; ?>><?php _e('Grid View', 'wpsc'); ?></option>
							<?php	} else { ?>
										<option value='grid' disabled='disabled' <?php if (isset($category_view3)) echo  $category_view3; ?>><?php  _e('Grid View', 'wpsc'); ?></option>
							<?php	} ?>
						</select><br /><br />
					</td>
				</tr>


			<?php	if(function_exists("getimagesize")) { ?>
			<tr>
				<td>
					<?php _e('Thumbnail&nbsp;Size', 'wpsc'); ?>
				</td>
				<td>
					<?php _e('Width', 'wpsc'); ?> <input type='text' value='<?php if (isset($category['image_width'])) echo $category['image_width']; ?>' name='image_width' size='6'/>
                                        <?php _e('Height', 'wpsc'); ?> <input type='text' value='<?php if (isset($category['image_height'])) echo $category['image_height']; ?>' name='image_height' size='6'/><br/>
                                </td>
			</tr>
			<?php	}
			 _e('Delete Image', 'wpsc'); ?><input type='checkbox' name='deleteimage' value='1' /><br/><br/>
		</div>
	</div>

<!--  SHORT CODE META BOX only display if product has been created -->

<?php if ( isset( $_GET["tag_ID"] ) ) {?>

		<div id="poststuff" class="postbox">
			<h3 class="hndle"><?php _e('Shortcodes and Template Tags'); ?></h3>
			<div class="inside">
				<?php
				$output = '';
				$product = get_term($_GET["category_id"], "wpsc_product_category" );
				$output .= " <span class='wpscsmall description'>Template tags and Shortcodes are used to display a particular category or group within your theme / template or any wordpress page or post.</span>\n\r";
				$output .="<div class='inside'>\n\r";
				$output .="<div class='editing_this_group form_table'>";
				$output .="<dl>\n\r";
				$output .="<dt>Display Category Shortcode: </dt>\n\r";
				$output .="<dd> [wpsc_products category_url_name='{$product->slug}']</dd>\n\r";
				$output .="<dt>Display Category Template Tag: </dt>\n\r";
				$output .="<dd> &lt;?php echo wpsc_display_products_page(array('category_url_name'=>'{$product->slug}')); ?&gt;</dd>\n\r";
				$output .="</dl>\n\r";
				$output .= "</div></div>";
			$output .= "</div>";
		$output .= "</div>";
		echo $output;
}?>

<!-- START OF TARGET MARKET SELECTION -->
<div id="poststuff" class="postbox">
	<h3 class="hndle"><?php _e('Target Market Restrictions'); ?></h3>
	<div class="inside"><?php
		$category_id = '';
		if (isset($_GET["tag_ID"])) $category_id = $_GET["tag_ID"];
		$countrylist = $wpdb->get_results("SELECT id,country,visible FROM `".WPSC_TABLE_CURRENCY_LIST."` ORDER BY country ASC ",ARRAY_A);
		$selectedCountries = wpsc_get_meta($category_id,'target_market','wpsc_category');
		$output = '';
		$output .= " <tr>\n\r";
		$output .= " 	<td>\n\r";
		$output .= __('Target Markets', 'wpsc').":\n\r";
		$output .= " 	</td>\n\r";
		$output .= " 	<td>\n\r";

		if(@extension_loaded('suhosin')) {
			$output .= "<em>".__("The Target Markets feature has been disabled because you have the Suhosin PHP extension installed on this server. If you need to use the Target Markets feature then disable the suhosin extension, if you can not do this, you will need to contact your hosting provider.
			",'wpsc')."</em>";

		} else {
			$output .= "<span>Select: <a href='' class='wpsc_select_all'>All</a>&nbsp; <a href='' class='wpsc_select_none'>None</a></span><br />";
			$output .= " 	<div id='resizeable' class='ui-widget-content multiple-select'>\n\r";
			foreach($countrylist as $country){
				if(in_array($country['id'], (array)$selectedCountries)){
					$output .= " <input type='checkbox' name='countrylist2[]' value='".$country['id']."'  checked='".$country['visible']."' />".$country['country']."<br />\n\r";
				} else {
					$output .= " <input type='checkbox' name='countrylist2[]' value='".$country['id']."'  />".$country['country']."<br />\n\r";
				}
			}
			$output .= " </div><br /><br />";
			$output .= " <span class='wpscsmall description'>Select the markets you are selling this category to.<span>\n\r";
		}
		$output .= "   </td>\n\r";
		$output .= " </tr>\n\r";

		echo $output;
		?>
	</div>
</div>

<!-- Checkout settings -->
<div id="poststuff" class="postbox">
	<h3 class="hndle"><?php _e('Checkout Settings', 'wpsc'); ?></h3>
	<div class="inside">
		<table class='category_forms'>
			<?php
			if (!isset($category['term_id'])) $category['term_id'] = '';
				$used_additonal_form_set = wpsc_get_categorymeta($category['term_id'], 'use_additonal_form_set'); ?>
				<tr>
					<td>
						<?php _e("This category requires additional checkout form fields",'wpsc'); ?>
					</td>
					<td>
						<select name='use_additonal_form_set'>
							<option value=''><?php _e("None",'wpsc'); ?></option>
							<?php
							$checkout_sets = get_option('wpsc_checkout_form_sets');
							unset($checkout_sets[0]);

							foreach((array)$checkout_sets as $key => $value) {
								$selected_state = "";
							if($used_additonal_form_set == $key)
								$selected_state = "selected='selected'";
							 ?>
								<option <?php echo $selected_state; ?> value='<?php echo $key; ?>'><?php echo stripslashes($value); ?></option>
							<?php
							}
							?>
						</select>
					</td>
				</tr>
			<?php $uses_billing_address = (bool)wpsc_get_categorymeta($category['term_id'], 'uses_billing_address'); ?>
				<tr>
					<td>
						<?php _e("Products in this category use the billing address to calculate shipping",'wpsc'); ?>
					</td>
					<td>
						<input type='radio' value='1' name='uses_billing_address' <?php echo (($uses_billing_address == true) ? "checked='checked'" : ""); ?> /><?php _e("Yes",'wpsc'); ?>
						<input type='radio' value='0' name='uses_billing_address' <?php echo (($uses_billing_address != true) ? "checked='checked'" : ""); ?> /><?php _e("No",'wpsc'); ?>
					</td>
				</tr>
		</table>
	</div>
</div>

<table class="category_forms">
	<tr>
		<td>
			<?php wp_nonce_field('edit-category', 'wpsc-edit-category'); ?>
			<input type='hidden' name='wpsc_admin_action' value='wpsc-category-set' />
		</td>
	</tr>
</table>
  <?php
}

function wpsc_admin_category_forms_edit() {
	global $wpdb;

	$category_value_count = 0;
	$category_name = '';
	$category = array();
           
        $category_id = absint( $_REQUEST["tag_ID"] );
        $category = get_term($category_id, 'wpsc_product_category', ARRAY_A);
        $category['nice-name'] = wpsc_get_categorymeta($category['term_id'], 'nice-name');
        $category['description'] = wpsc_get_categorymeta($category['term_id'], 'description');
        $category['image'] = wpsc_get_categorymeta($category['term_id'], 'image');
        $category['fee'] = wpsc_get_categorymeta($category['term_id'], 'fee');
        $category['active'] = wpsc_get_categorymeta($category['term_id'], 'active');
        $category['order'] = wpsc_get_categorymeta($category['term_id'], 'order');
        $category['display_type'] = wpsc_get_categorymeta($category['term_id'], 'display_type');
        $category['image_height'] = wpsc_get_categorymeta($category['term_id'], 'image_height');
        $category['image_width'] = wpsc_get_categorymeta($category['term_id'], 'image_width');
	

	?>

        <tr>
            <td colspan="2">
                <h3><?php _e( 'Advanced Settings', 'wpsc' ); ?></h3>
            </td>
        </tr>

	<tr class="form-field">
            <th scope="row" valign="top">
		<label for="image"><?php _e( 'Category Image', 'wpsc' ); ?></label>
            </th>
            <td>
		<input type='file' name='image' value='' /><br />
		<span class="description"><?php _e( 'You can set an image for the category here.', 'wpsc' ); ?></span>
            </td>
	</tr>

	<tr class="form-field">
            <th scope="row" valign="top">
		<label for="display_type"><?php _e( 'Catalog View', 'wpsc' ); ?></label>
            </th>
            <td>
		<?php
                    //Could probably be *heavily* refactored later just to use do_action here and in GoldCart.  Baby steps.
					if (!isset($category['display_type'])) $category['display_type'] = '';

						if ($category['display_type'] == 'grid') {
							$display_type1="selected='selected'";
						} else if ($category['display_type'] == 'default') {
							$display_type2="selected='selected'";
						}

						switch($category['display_type']) {
							case "default":
								$category_view1 = "selected ='selected'";
							break;

							case "grid":
							if(function_exists('product_display_grid')) {
								$category_view3 = "selected ='selected'";
								break;
							}

							case "list":
							if(function_exists('product_display_list')) {
								$category_view2 = "selected ='selected'";
								break;
							}

							default:
								$category_view0 = "selected ='selected'";
							break;
						}
                                                ?>
                        <select name='display_type'>
                                <option value=''<?php echo $category_view0; ?> ><?php _e('Please select', 'wpsc'); ?></option>
                                <option value='default' <?php if (isset($category_view1)) echo $category_view1; ?> ><?php _e( 'Default View', 'wpsc' ); ?></option>

                                <?php	if(function_exists('product_display_list')) {?>
                                                        <option value='list' <?php echo  $category_view2; ?>><?php _e('List View', 'wpsc'); ?></option>
                                <?php	} else { ?>
                                                        <option value='list' disabled='disabled' <?php if (isset($category_view2)) echo $category_view2; ?>><?php _e( 'List View', 'wpsc' ); ?></option>
                                <?php	} ?>
                                <?php if(function_exists('product_display_grid')) { ?>
                                                        <option value='grid' <?php if (isset($category_view3)) echo  $category_view3; ?>><?php _e( 'Grid View', 'wpsc' ); ?></option>
                                <?php	} else { ?>
                                                        <option value='grid' disabled='disabled' <?php if (isset($category_view3)) echo  $category_view3; ?>><?php  _e( 'Grid View', 'wpsc' ); ?></option>
                                <?php	} ?>
                        </select><br />
		<span class="description"><?php _e( 'To over-ride the presentation settings for this group you can enter in your prefered settings here', 'wpsc' ); ?></span>
            </td>
	</tr>
        <tr class="form-field">
            <th scope="row" valign="top">
		<label for="image"><?php _e( 'Category Image', 'wpsc' ); ?></label>
            </th>
            <td>
		<input type='file' name='image' value='' /><br />
                <label><input type='checkbox' name='deleteimage' class="wpsc_cat_box" value='1' /><?php _e( 'Delete Image', 'wpsc' ); ?></label><br/>
		<span class="description"><?php _e( 'You can set an image for the category here.  If one exists, check the box to delete.', 'wpsc' ); ?></span>
            </td>
	</tr>
        <?php if( function_exists( "getimagesize" ) ) : ?>
        <tr class="form-field">
            <th scope="row" valign="top">
		<label for="image"><?php _e( 'Thumbnail Size', 'wpsc' ); ?></label>
            </th>
            <td>
                <?php _e( 'Width', 'wpsc' ); ?> <input type='text' class="wpsc_cat_image_size" value='<?php if (isset($category['image_width'])) echo $category['image_width']; ?>' name='image_width' size='6' />
                <?php _e( 'Height', 'wpsc' ); ?> <input type='text' class="wpsc_cat_image_size" value='<?php if (isset($category['image_height'])) echo $category['image_height']; ?>' name='image_height' size='6' /><br/>
           </td>
	</tr>
        <?php endif; // 'getimagesize' condition ?>
	<tr>
            <td colspan="2"><h3><?php _e( 'Shortcodes and Template Tags', 'wpsc' ); ?></h3></td>
        </tr>

        <tr class="form-field">
            <th scope="row" valign="top">
		<label for="image"><?php _e( 'Display Category Shortcode', 'wpsc' ); ?>:</label>
            </th>
            <td>
                <span>[wpsc_products category_url_name='<?php echo $category["slug"]; ?>']</span><br />
		<span class="description"><?php _e( 'Shortcodes are used to display a particular category or group within any WordPress page or post.', 'wpsc' ); ?></span>
            </td>
	</tr>
        <tr class="form-field">
            <th scope="row" valign="top">
		<label for="image"><?php _e( 'Display Category Template Tag', 'wpsc' ); ?>:</label>
            </th>
            <td>
                <span>&lt;?php echo wpsc_display_products_page( array( 'category_url_name'=>'<?php echo $category["slug"]; ?>' ) ); ?&gt;</span><br />
		<span class="description"><?php _e( 'Template tags are used to display a particular category or group within your theme / template.', 'wpsc' ); ?></span>
            </td>
	</tr>

<!-- START OF TARGET MARKET SELECTION -->

        <tr>
            <td colspan="2">
                <h3><?php _e( 'Target Market Restrictions', 'wpsc' ); ?></h3>
            </td>
        </tr>
        <?php
            $countrylist = $wpdb->get_results( "SELECT id,country,visible FROM `".WPSC_TABLE_CURRENCY_LIST."` ORDER BY country ASC ",ARRAY_A );
            $selectedCountries = wpsc_get_meta( $category_id,'target_market','wpsc_category' );
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
		<label for="image"><?php _e( 'Target Markets', 'wpsc' ); ?>:</label>
            </th>
            <td>
                <?php
                    if ( @extension_loaded( 'suhosin' ) ) :
                 ?>
                <em><?php _e( 'The Target Markets feature has been disabled because you have the Suhosin PHP extension installed on this server. If you need to use the Target Markets feature, then disable the suhosin extension. If you can not do this, you will need to contact your hosting provider.','wpsc'); ?></em>

                <?php
                    else :
                ?>
		<span><?php _e( 'Select', 'wpsc' ); ?>: <a href='' class='wpsc_select_all'><?php _e( 'All', 'wpsc' ); ?></a>&nbsp; <a href='' class='wpsc_select_none'><?php _e( 'None', 'wpsc' ); ?></a></span><br />
		<div id='resizeable' class='ui-widget-content multiple-select'>
                    <?php
			foreach( $countrylist as $country ) {
                            if( in_array( $country['id'], (array)$selectedCountries ) )
                                echo " <input type='checkbox' class='wpsc_cat_box' name='countrylist2[]' value='".$country['id']."'  checked='".$country['visible']."' />".$country['country']."<br />";
                            else
                                echo " <input type='checkbox' class='wpsc_cat_box' name='countrylist2[]' value='".$country['id']."'  />".$country['country']."<br />";
                        }
                    ?>
		</div>
                <?
                    endif;
                ?><br />
		<span class="description"><?php _e( 'Select the markets you are selling this category to.', 'wpsc' ); ?></span>
            </td>
	</tr>
<!-- Checkout settings -->

        <tr>
            <td colspan="2">
                <h3><?php _e( 'Checkout Settings', 'wpsc' ); ?></h3>
            </td>
        </tr>
        <?php
            if ( !isset( $category['term_id'] ) )
                $category['term_id'] = '';
            $used_additonal_form_set = wpsc_get_categorymeta( $category['term_id'], 'use_additonal_form_set' );
            $checkout_sets = get_option('wpsc_checkout_form_sets');
            unset($checkout_sets[0]);
            $uses_billing_address = (bool)wpsc_get_categorymeta( $category['term_id'], 'uses_billing_address' );
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
		<label for="image"><?php _e( 'Category requires additional checkout form fields', 'wpsc' ); ?></label>
            </th>
            <td>
                <select name='use_additonal_form_set'>
                    <option value=''><?php _e( 'None', 'wpsc' ); ?></option>
                    <?php
                        foreach( (array) $checkout_sets as $key => $value ) {
                            $selected_state = "";
                            if( $used_additonal_form_set == $key )
                                $selected_state = "selected='selected'";
                     ?>
                    <option <?php echo $selected_state; ?> value='<?php echo $key; ?>'><?php echo stripslashes( $value ); ?></option>
                    <?php
                        }
                    ?>
                </select><br />
              </td>
	</tr>

        <tr class="form-field">
            <th scope="row" valign="top">
		<label for="image"><?php _e( 'Products in this category use the billing address to calculate shipping', 'wpsc' ); ?></label>
            </th>
            <td>
                <input type='radio' class="wpsc_cat_box" value='1' name='uses_billing_address' <?php echo ( ( $uses_billing_address == true ) ? "checked='checked'" : "" ); ?> /><?php _e( 'Yes','wpsc' ); ?>
                <input type='radio' class="wpsc_cat_box" value='0' name='uses_billing_address' <?php echo (($uses_billing_address != true) ? "checked='checked'" : ""); ?> /><?php _e( 'No','wpsc' ); ?>
                <br />
	  </td>
	</tr>
	<tr>
		<td>
			<?php wp_nonce_field('edit-category', 'wpsc-edit-category'); ?>
			<input type='hidden' name='wpsc_admin_action' value='wpsc-category-set' />
		</td>
	</tr>
  <?php
} 

/**
 * wpsc_save_category_set, Saves the category set data
 * @param nothing
 * @return nothing
 */
function wpsc_save_category_set() {
	global $wpdb;
	
	if(($_POST['submit_action'] == "add") || ($_POST['submit_action'] == "edit")) {
		check_admin_referer('edit-category', 'wpsc-edit-category');
		/* Image Processing Code*/
		if(($_FILES['image'] != null) && preg_match("/\.(gif|jp(e)*g|png){1}$/i",$_FILES['image']['name'])) {
			if(function_exists("getimagesize")) {
				if(((int)$_POST['width'] > 10 && (int)$_POST['width'] < 512) && ((int)$_POST['height'] > 10 && (int)$_POST['height'] < 512) ) {
					$width = (int)$_POST['width'];
					$height = (int)$_POST['height'];
					image_processing($_FILES['image']['tmp_name'], (WPSC_CATEGORY_DIR.$_FILES['image']['name']), $width, $height);
				} else {
					image_processing($_FILES['image']['tmp_name'], (WPSC_CATEGORY_DIR.$_FILES['image']['name']));
				}	
				$image = $wpdb->escape($_FILES['image']['name']);
			} else {
				$new_image_path = (WPSC_CATEGORY_DIR.basename($_FILES['image']['name']));
				move_uploaded_file($_FILES['image']['tmp_name'], $new_image_path);
				$stat = stat( dirname( $new_image_path ));
				$perms = $stat['mode'] & 0000666;
				@ chmod( $new_image_path, $perms );	
				$image = $wpdb->escape($_FILES['image']['name']);
			}
		} else {
			$image = '';
		}
		
		/* Set the parent category ID variable*/
		if(is_numeric($_POST['category_parent']) && absint($_POST['category_parent']) > 0) {
			$parent_category = (int)$_POST['category_parent'];
		} else {
			$parent_category = 0;
		}
		
		  
		/* add category code */
		if($_POST['submit_action'] == "add") {
			$name = $_POST['name'];			
			$term = get_term_by('name', $name, 'wpsc_product_category', ARRAY_A);
						
			$term = wp_insert_term( $name, 'wpsc_product_category',array('parent' => $parent_category));

			if (is_wp_error($term)) {
				$sendback = add_query_arg('message',$term->get_error_code());
				wp_redirect($sendback);
				return;
			}
			
			$category_id= $term['term_id'];
			
			$category = get_term_by('id', $category_id, 'wpsc_product_category');
			$url_name=$category->slug;
			
			if($category_id > 0) {
				wpsc_update_categorymeta($category_id, 'nice-name', $url_name);
				wpsc_update_categorymeta($category_id, 'description', $wpdb->escape(stripslashes($_POST['description'])));
				if($image != '') {
					wpsc_update_categorymeta($category_id, 'image', $image);
				}
				wpsc_update_categorymeta($category_id, 'fee', '0');
				wpsc_update_categorymeta($category_id, 'active', '1');
				wpsc_update_categorymeta($category_id, 'order', '0');
				wpsc_update_categorymeta($category_id, 'display_type',$wpdb->escape(stripslashes($_POST['display_type'])));
				wpsc_update_categorymeta($category_id, 'image_height', $wpdb->escape(stripslashes($_POST['image_height'])));
				wpsc_update_categorymeta($category_id, 'image_width', $wpdb->escape(stripslashes($_POST['image_width'])));
				if($_POST['use_additonal_form_set'] != '') {
					wpsc_update_categorymeta($category_id, 'use_additonal_form_set', $_POST['use_additonal_form_set']);
				} else {
					wpsc_delete_categorymeta($category_id, 'use_additonal_form_set');
				}
	
				if((bool)(int)$_POST['uses_billing_address'] == true) {
					wpsc_update_categorymeta($category_id, 'uses_billing_address', 1);
					$uses_additional_forms = true;
				} else {
					wpsc_update_categorymeta($category_id, 'uses_billing_address', 0);
					$uses_additional_forms = false;
				}
			}

			if(isset($_POST['countrylist2']) && ($_POST['countrylist2'] != null ) && ($category_id > 0)){
		    	$AllSelected = false;
				$countryList = $wpdb->get_col("SELECT `id` FROM  `".WPSC_TABLE_CURRENCY_LIST."`");
		    			
				if($AllSelected != true){
					$unselectedCountries = array_diff($countryList, $_POST['countrylist2']);
					//find the countries that are selected
					$selectedCountries = array_intersect($countryList, $_POST['countrylist2']);
					 wpsc_update_meta( $category_id,   'target_market',$selectedCountries, 'wpsc_category'); 
				}
			}elseif(!isset($_POST['countrylist2'])){
				wpsc_update_meta( $category_id,   'target_market','', 'wpsc_category'); 
	  			$AllSelected = true;
			
			}
		
	}
		
	    
		/* edit category code */
		if(($_POST['submit_action'] == "edit") && is_numeric($_POST['category_id'])) {
			$category_id = absint($_POST['category_id']);
			
			$name = $_POST['name'];
			
			$category = get_term_by('id', $category_id, 'wpsc_product_category');
			if($category->name != $name || $category->parent != $parent_category) {
				wp_update_term($category_id, 'wpsc_product_category', array(
					'name' => $name , 'parent' => $parent_category
				));
				$category = get_term($category_id, 'wpsc_product_category');
			}
			
			
			$url_name=$category->slug;
			wpsc_update_categorymeta($category_id, 'nice-name', $url_name);
			wpsc_update_categorymeta($category_id, 'description', $wpdb->escape(stripslashes($_POST['description'])));
			
			
			if(isset($_POST['deleteimage']) && $_POST['deleteimage'] == 1) {
				wpsc_delete_categorymeta($category_id, 'image');
			} else if($image != '') {
				wpsc_update_categorymeta($category_id, 'image', $image);
			}
			
			if(is_numeric($_POST['height']) && is_numeric($_POST['width']) && ($image == null)) {
				$imagedata = wpsc_get_categorymeta($category_id, 'image');
				if($imagedata != null) {
					$height = $_POST['height'];
					$width = $_POST['width'];
					$imagepath = WPSC_CATEGORY_DIR . $imagedata;
					$image_output = WPSC_CATEGORY_DIR . $imagedata;
					image_processing($imagepath, $image_output, $width, $height);
				}
			}
			
			
			wpsc_update_categorymeta($category_id, 'fee', '0');
			wpsc_update_categorymeta($category_id, 'active', '1');
			wpsc_update_categorymeta($category_id, 'order', '0');
			
			wpsc_update_categorymeta($category_id, 'display_type',$wpdb->escape(stripslashes($_POST['display_type'])));
			wpsc_update_categorymeta($category_id, 'image_height', $wpdb->escape(stripslashes($_POST['image_height'])));
			wpsc_update_categorymeta($category_id, 'image_width', $wpdb->escape(stripslashes($_POST['image_width'])));
			
			
			if($_POST['use_additonal_form_set'] != '') {
				wpsc_update_categorymeta($category_id, 'use_additonal_form_set', $_POST['use_additonal_form_set']);
			} else {
				wpsc_delete_categorymeta($category_id, 'use_additonal_form_set');
			}
	
			if((bool)(int)$_POST['uses_billing_address'] == true) {
				wpsc_update_categorymeta($category_id, 'uses_billing_address', 1);
				$uses_additional_forms = true;
			} else {
				wpsc_update_categorymeta($category_id, 'uses_billing_address', 0);
				$uses_additional_forms = false;
			}	
			
		  	if(($_POST['countrylist2'] != null ) && ($category_id > 0)){
		    	$AllSelected = false;
				$countryList = $wpdb->get_col("SELECT `id` FROM  `".WPSC_TABLE_CURRENCY_LIST."`");
		    			
				if($AllSelected != true){
					$unselectedCountries = array_diff($countryList, $_POST['countrylist2']);
					//find the countries that are selected
					$selectedCountries = array_intersect($countryList, $_POST['countrylist2']);
					 wpsc_update_meta( $category_id,   'target_market',$selectedCountries, 'wpsc_category'); 
				}
			}elseif(!isset($_POST['countrylist2'])){
				wpsc_update_meta( $category_id,   'target_market','', 'wpsc_category'); 
	  			$AllSelected = true;
			
			}
	}
}
	
	$sendback = remove_query_arg(array(
		'wpsc_admin_action',
		'delete_category',
		'_wpnonce',
		'category_id'
	));
	$sendback = add_query_arg('message', 1, $sendback);
	wp_redirect($sendback);
}


?>