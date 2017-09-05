<?php
/*
Plugin Name: LH Copy Media File
Plugin URI: https://lhero.org/plugins/lh-copy-media-file/
Description: Allows a admin users to create a copy of any media file without the need to download and upload the original so that they can edit the new copy without changing the original.
Author: Peter Shaw
Author URI: https://shawfactor.com/
Version: 1.01
License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/


class LH_copy_media_file_plugin {

var $namespace = 'lh_copy_media_file';


public function media_row_action($actions, $post){
 
$actions['lh_copy_media_file_link'] = '<a href="'.add_query_arg( 'lh-copy-media-file-hander-postid', $post->ID ).'" title="'.__('create a new copy of this file', $this->namespace).'" class="lh_copy_media_file_link">' . __('Copy File', $this->namespace) . '</a>';
 
   return $actions;
}


//Perform the duplicating action
public function duplicate_file() {
	global $pagenow;
	
	
	//Check to make sure we're on the right page and performing the right action

if( 'upload.php' != $pagenow ){
	
	return false;

} elseif ( empty( $_GET[ 'lh-copy-media-file-hander-postid' ] ) ){

 return false;
		
} else {


$post_id = (int) $_GET[ 'lh-copy-media-file-hander-postid' ];
	
if ( empty( $post_id ) ){

		return false;

} else {

$url = wp_get_attachment_url($post_id);

	$tmp = download_url( $url );

$post_data = get_post($post_id); 

	$desc = "Copy of ".$post_data->post_title;;
	$file_array = array();

	// Set variables for storage
	// fix file filename for query strings
	preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png|xls)/i', $url, $matches);
	$file_array['name'] = time().basename($matches[0]);
	$file_array['tmp_name'] = $tmp;

	// If error storing temporarily, unlink
	if ( is_wp_error( $tmp ) ) {


		@unlink($file_array['tmp_name']);
		$file_array['tmp_name'] = '';
	}

	// do the validation and storage stuff
	$attachment_id = media_handle_sideload( $file_array, "0", $desc );

	// If error storing permanently, unlink
	if ( is_wp_error($attachment_id) ) {
		@unlink($file_array['tmp_name']);
		return $id;
	} else {

//$upload_dir = wp_upload_dir();

//$path = $upload_dir['path'];


//$attach_data = wp_generate_attachment_metadata( $attachment_id, $path );

//wp_update_attachment_metadata( $attachment_id,  $attach_data );	
	


		
		
	}
	
	//Redirect to the edit page for that file
	wp_safe_redirect( admin_url( 'post.php?post='.$attachment_id.'&action=edit') );
	exit();

}

}

}





function __construct() {

add_filter('media_row_actions', array($this,"media_row_action"), 10, 2);
add_action( 'admin_init', array($this,"duplicate_file") );

}

}

$lh_copy_media_file_instance = new LH_copy_media_file_plugin();


?>