<?php

// Function to update the categories supplied from the remote server
function add_category_remotely() {
	$json_server = 'https://my-json-server.typicode.com/kcssm/bluetest/categories'; // Replace with your json server url
	$categories_response = wp_remote_get( esc_url_raw( $json_server ) );
	
	$parentarray = array();
	

	$categories_response_array = json_decode( wp_remote_retrieve_body( $categories_response ), true );
	foreach( $categories_response_array as $key => $value ) {
	
			$mycategories = get_terms(array(
			    'hide_empty' => false,
			));
			
			foreach( $mycategories as $key1 => $value1) {
				$parentarray[$value1->term_id] = $value1->name;
			}

		if( in_array( $value[ 'name' ], $parentarray ) ) {
			$term_to_update = array_search($value[ 'name' ], $parentarray);
			$catarray = array(
				'term_id' => $term_to_update,
				'name' => $value['name'],
				'description' => $value['name'],
				'slug' => sanitize_title($value['name']),
				'parent' => $value['parent_id'],
				'taxonomy' => 'category' 
			);
			wp_update_term( $term_to_update, 'category', $catarray );
		} else {
			$catarray = array(
				'name' => $value['name'],
				'description' => $value['name'],
				'slug' => sanitize_title($value['name']),
				'parent' => $value['parent_id'],
				'taxonomy' => 'category' 
			);
			wp_insert_term( $value['name'], 'category', $catarray );
		}

	}

}

add_action('add_category_remotely_hook', 'add_category_remotely');

// Add update button on Settings Page under General 
add_filter('admin_init', 'my_general_settings_register_fields');
 
function my_general_settings_register_fields()
{
    register_setting('general', 'my_field', 'esc_attr');
    add_settings_field('my_field', '<label for="my_field">'.__('Update Categories' , 'my_field' ).'</label>' , 'my_general_settings_fields_html', 'general');
}
 
function my_general_settings_fields_html()
{
    echo '<input type="button" class="button" id="update_categories" name="update_categories" value="Click to Update" />';
    
}

// Ajax call to update the category
add_action( 'wp_ajax_nopriv_my_update_category', 'my_update_category' );
add_action( 'wp_ajax_my_update_category', 'my_update_category' );

function my_update_category() {
	
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { 
		add_category_remotely();
		echo "Categories has been updated successfully!";
	}
	die();
}

// Ajax request
add_filter('admin_footer', 'my_general_settings_script');
 function my_general_settings_script() {
 	?>
 	<script>
 		jQuery(document).ready( function($) {

 	jQuery( document ).on( 'click', '#update_categories', function() {
			jQuery.ajax({
				url : ajaxurl,
				type : 'post',
				data : {
					action : 'my_update_category'
				},
				success : function( response ) {
					alert(response)
				}
			});
		})
 });
</script>
 <?php
 }

/**
 * Adds a custom cron schedule for every 30 minutes.
 */
function sanam_custom_cron_schedule( $schedules ) {
    $schedules[ 'every-30-minutes' ] = array( 'interval' => 30 * MINUTE_IN_SECONDS, 'display' => __( 'Every 30 minutes', 'twentysixteen' ) );
    return $schedules;
}
add_filter( 'cron_schedules', 'sanam_custom_cron_schedule' );


wp_schedule_event(time(), 'every-30-minutes', 'add_category_remotely_hook');