<?php

/**
 * Class Mautic_Segments_Metabox
 */
class Mautic_Segments_Metabox {

	private $post_type_with_segments;

	public function __construct() {

		$this->post_type_with_segments = array( 'post' ); // TODO get it from option
	}

	public function init(){

		foreach ( $this->post_type_with_segments as $post_type ){

			register_meta( $post_type, 'wpmautic_segments', array(
				'type'              => 'integer',
				'description'       => 'Id of the Mautic Segments attache to this post',
				'single'            => false,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => true,
			));

			add_action( "save_post_{$post_type}", array( $this, 'save_post' ) );
		}


		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}


	public function add_meta_box(){

		add_meta_box( 'wpmautic_segments', __('Mautic segments', 'wp-mautic'), array( $this, 'segments_metabox_content' ), $this->post_type_with_segments );
	}

	public function segments_metabox_content( $post ){

		$mautic_api = new Mautic_Api();

		$segments = $mautic_api->call( 'segments', array( 'searchFilter' => 'afg' ), 'GET', false );
		$post_segment = get_post_meta( $post->ID, 'wpmautic_segments' );

		if( $segments && ! is_wp_error( $segments ) ){

			wp_nonce_field( 'add_mautic_post_segment', 'mautic_post_segment_nonce' );
			?>
			<table class="form-table">
				<tr>
					<th><label for="wpmautic_segments"><?php _e('Segments', 'wp-mautic') ?></label></th>
					<td>
						<select multiple="multiple" name="wpmautic_segments[]" id="wpmautic_segments" class="widefat">
							<?php
							foreach ( $segments->lists as $segment ){
								$selected = selected( in_array( $segment->id,$post_segment), true, false );
								echo "<option value='{$segment->id}' {$selected}>{$segment->name}</option>";
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<?php
		} else {
			echo $segments->get_error_message();
		}
	}

	public function save_post( $post_id ){

		if( isset( $_POST['mautic_post_segment_nonce'] ) &&
			check_admin_referer( 'add_mautic_post_segment', 'mautic_post_segment_nonce' ) &&
			current_user_can( 'edit_post', $post_id ) ){


			delete_post_meta( $post_id,'wpmautic_segments' );

			if( isset( $_POST['wpmautic_segments'] ) && ! empty( $_POST['wpmautic_segments'] ) ) {

				$segment_id = array_map( 'intval', $_POST['wpmautic_segments'] );

				foreach ( $segment_id as $id ){
					if( $id !== 0 ){
						add_post_meta( $post_id,'wpmautic_segments', $id);
					}
				}

			}

		}
	}

}

add_action( 'init', 'wpmautic_init_metabox' );

function wpmautic_init_metabox(){

	$mautic_metabox = new Mautic_Segments_Metabox();
	$mautic_metabox->init();
}