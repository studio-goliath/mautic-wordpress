<?php

/**
 * Class Mautic_Post_By_Segement_Shortcode
 */
class Mautic_Post_By_Segment_Shortcode {

	public function get_pots_by_segment( $contact_id, $post_type, $order, $posts_number, $exclude = '' ){

		$segment = $this->get_contact_segment( $contact_id );

		$latest_post_query_args = array(
			'post_type'         => $post_type,
			'posts_per_page'    => $posts_number,
			'no_found_rows'     => true,
			'orderby'           => $order,
			'post_status'       => 'publish',
		);

		if ( $exclude ) {
            $latest_post_query_args['post__not_in'] = explode( ',', $exclude );
        }

		if( ! is_wp_error( $segment ) ){
			$latest_post_query_args['meta_query'][] = array(
				'key'     => 'wpmautic_segments',
				'value'   => $segment,
				'compare' => 'IN',
			);
		}

		/**
		 * Update / add data to the WP_Query call to get post by segment
		 *
		 * @since 3.0.0
		 *
		 * @param array $attrs Attributes to be filters, default ['language' => get_locale()]
		 */
		$latest_post_query_args = apply_filters( 'wpmautic_post_by_segment_query_args', $latest_post_query_args );

		$latest_post_query = new WP_Query( $latest_post_query_args );

		$fill_post_query = false;
		$filled_post = get_option('mautic_filled_post_by_segment', true );
		if( $filled_post && $latest_post_query->post_count < $posts_number && ! is_wp_error( $segment ) ){

			$exclude_post = wp_list_pluck( $latest_post_query->posts, 'ID' );
			$fill_post_query_args = $latest_post_query_args;
			$fill_post_query_args['posts_per_page'] = (int) $posts_number - (int) $latest_post_query->post_count;
			$fill_post_query_args['post__not_in'] = $exclude_post;
			unset( $fill_post_query_args['meta_query'] );
			$fill_post_query = new WP_Query( $fill_post_query_args );
		}

		$response = '<div class="wpmautic-post-list">';

		while ( $latest_post_query->have_posts() ){

			$latest_post_query->the_post();
			$response .= $this->get_post_content();
		}

		if( $fill_post_query && $fill_post_query->have_posts() ){

			while ( $fill_post_query->have_posts()){
				$fill_post_query->the_post();
				$response .= $this->get_post_content();
			}
		}
		$response .= '</div>';

		wp_reset_postdata();

		return $response;
	}


	/**
	 * @param int $contact_id ID of the current contact
	 *
	 * @return array|\WP_Error return the ids of the contact's segments
	 */
	private function get_contact_segment( $contact_id ){

		if( $contact_id == 0 ){
			$segment = new WP_Error( 400, 'Anonymous contact don\'t have segment' );
		} else {
			$mautic_api = new Mautic_Api();
			$segment_call = $mautic_api->call( "contacts/{$contact_id}/segments" );

			if( is_wp_error( $segment_call ) ){

				$segment = $segment_call;
			} else {

				if( $segment_call->total > 0 ){

					$segment_array = get_object_vars( $segment_call->lists );
					$segment = array_keys( $segment_array );
				} else {
					$segment = new WP_Error( 400, 'Contact don\'t have segment' );
				}

			}
		}

		return $segment;
	}


	public function do_shortcode( $atts ){

		$post_type = isset( $atts['post-type'] ) ? esc_attr( $atts['post-type'] ): 'post';
		$order = isset( $atts['order'] ) && $atts['order'] === 'date' ? 'date' : 'rand';
		$posts_number = isset( $atts['number'] ) ? intval( $atts['number'] ) : 3;
		$exclude = isset( $atts['exclude'] ) ? $atts['exclude'] : '';
		$loader = apply_filters( 'wpmautic_segment_loader', '<p>Loading...</p>' );

		return "<div class='wpmautic-posts-segment' data-post-type='{$post_type}' data-order='{$order}' data-posts-number='{$posts_number}' data-exclude='{$exclude}'>{$loader}</div>";
	}

    public function get_post_content(){


		$post_content = '';
		$post_content = apply_filters( 'wpmautic_pre_post_markup', $post_content );

		if( $post_content ){
			return $post_content;
		}

		$post_class = implode( ' ', get_post_class() );
		$post_content = "<article class='{$post_class}'>";
		$post_content .= '<header class="entry-header">';
		$post_content .= '<h2 class="entry-title">';
		$post_content .= get_the_title();
		$post_content .= '</h2>';
		$post_content .= '</header>';
		$post_content .= '<div class="entry-content">';
		$post_content .= '<p>';
		$post_content .= get_the_excerpt();
		$post_content .= '</p>';
		$post_content .= '</div>';
		$post_content .= "</article>";

		return apply_filters( 'wpmautic_post_markup', $post_content );
	}
}


add_shortcode( 'mautic-segment', 'wpmautic_mautic_segment_shorcode_callback' );

function wpmautic_mautic_segment_shorcode_callback( $atts ){

	$segment_shortcode = new Mautic_Post_By_Segment_Shortcode();
	return $segment_shortcode->do_shortcode( $atts );
}


add_action( 'wp_ajax_nopriv_mautic-get-segment-post', 'wpmautic_get_segment_post' );
add_action( 'wp_ajax_mautic-get-segment-post', 'wpmautic_get_segment_post' );

function wpmautic_get_segment_post(){

	$contact_id = intval( $_POST['contactId'] );
	$post_type = esc_attr( $_POST['postType'] );
	$order = esc_attr( $_POST['order'] );
	$posts_number = esc_attr( $_POST['postsNumber'] );
	$exclude = esc_attr( $_POST['exclude'] );

	$segment_shortcode = new Mautic_Post_By_Segment_Shortcode();
	$html_response = $segment_shortcode->get_pots_by_segment( $contact_id, $post_type, $order, $posts_number, $exclude );

	wp_send_json( $html_response );
}
