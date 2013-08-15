<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * The widget class.
 *
 * @since 0.1
 */
class Tokokoo_Instagram_Widget extends WP_Widget {

	/**
	 * Widget setup
	 */
	function __construct() {
		$widget_ops = array(
			'classname' => 'tokokoo-instagram-widget',
			'description' => __('Show Instagram photos in your sidebar easily.', 'koo-instagram')
		);

		$control_ops = array(
			'width' => 300,
			'height' => 350,
			'id_base' => 'tokokoo_instagram_widget'
		);

		parent::__construct('tokokoo_instagram_widget', __('&raquo; Tokokoo Instagram', 'koo-instagram'), $widget_ops, $control_ops);

	}

	/**
	 * Display widget
	 */
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$show = $instance['show'];
		$count = (int)( $instance['count'] );
		
		$settings = get_option( 'tokokoo-instagram' );
		$no_result = ( !empty( $settings['no_result_text'] ) ) ? $settings['no_result_text'] : __( 'There is no images found.', 'koo-instagram' );
		
		$data = $this->get_instagram_data( $show, $count );

		echo $before_widget;

		if ( !empty( $title ) ) echo $before_title . $title . $after_title;

		?>
		
		<div class="tokokoo-instagram-wrapper">
		
		<?php if( isset( $data['code'] ) && $data['code'] != 0 ) : ?>

			<div class="error">
				<p><code><?php echo $data['code'] . ' : ' . $data['error_type']; ?></code></p>
				<p><?php echo $data['error_message']; ?></p>
			</div>

		<?php elseif ( isset( $data['code'] ) && $data['code'] == 0 ) : ?>

			<p><?php echo $no_result; ?></p>

		<?php else :
			$i = 0;

			foreach( $data as $image ) :
		?>

				<div class="tokokoo-instagram-image" id="tokokoo-instagram-image-<?php echo $i; ?>">
					<a href="<?php echo $image['link']; ?>" target="_blank">
						<img src="<?php echo $image['image']; ?>" alt="<?php echo $post['caption']; ?>" title="<?php echo $post['caption']; ?>">
					</a>
				</div>

		<?php
				$i++;

			endforeach;
		endif;
		?>

		</div>

		<?php

		echo $after_widget;
	}

	/**
	 * Update widget
	 */
	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['show'] = $new_instance['show'];
		$instance['count'] = (int)( $new_instance['count'] );

		return $instance;
	}

	/**
	 * Widget setting
	 */
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array(
			'title' => '',
			'show' => 'recent',
			'count' => 8
		);

		$instance = wp_parse_args( (array) $instance, $defaults);
		$title = strip_tags( $instance['title'] );
		$show = $instance['show'];
		$count = (int)( $instance['count'] );
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'koo-instagram' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo $title; ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'show') ); ?>"><?php _e( 'Show Feed:', 'koo-instagram' ); ?></label>
			<select class="widefat" name="<?php echo $this->get_field_name( 'show' ); ?>" id="<?php echo $this->get_field_id( 'show' ); ?>">
				<option value="recent" <?php selected( $show, 'recent' ); ?>><?php _e( 'Recent Uploads', 'koo-instagram' ); ?></option>
				<option value="feed" <?php selected( $show, 'feed' ); ?>><?php _e( 'Following', 'koo-instagram' ); ?></option>
				<option value="liked" <?php selected( $show, 'liked' ); ?>><?php _e( 'Liked Images', 'koo-instagram' ); ?></option>
				<option value="popular" <?php selected( $show, 'popular' ); ?>><?php _e( 'Popular Images', 'koo-instagram' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php _e( 'Counts:', 'koo-instagram' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'count') ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="text" value="<?php echo $count; ?>">
		</p>
	<?php
	}
	
	function get_instagram_data( $type, $count ) {
		$settings = get_option( 'tokokoo-instagram' );
		
		switch( $type ) {
			case 'recent' :
				$endpoint_uri = add_query_arg( array(
						'count'			=> $count,
						'access_token'	=> $settings['access_token']
					), 'https://api.instagram.com/v1/users/' . $settings['user_id'] . '/media/recent' );
			break;
			case 'feed' :
				$endpoint_uri = add_query_arg( array(
						'count'			=> $count,
						'access_token'	=> $settings['access_token']
					), 'https://api.instagram.com/v1/users/self/feed' );
			break;
			case 'liked' :
				$endpoint_uri = add_query_arg( array(
						'count'			=> $count,
						'access_token'	=> $settings['access_token']
					), 'https://api.instagram.com/v1/users/self/media/liked' );
			break;
			case 'popular' :
				$endpoint_uri = add_query_arg( array(
						'access_token'	=> $settings['access_token']
					), 'https://api.instagram.com/v1/media/popular' ) ;
			break;
		}
		
		$response = wp_remote_get( $endpoint_uri );
		
		$results = json_decode( $response['body'], true );
		
		$data = array();
		
		if ( isset( $results['meta']['error_type'] ) && 200 != $response['meta']['code'] ){	
			$data = $results['meta'];
		} elseif ( isset( $results['data'] ) && !empty( $results['data'] ) ) {
			foreach( $results['data'] as $result ){
				$data[] = array(
						'caption' => $result['caption'] == 'null' ? '' : preg_replace('/[^(\x20-\x7F)]*/','', $result['caption']['text']),
						'link' => $result['link'],
						'image' => $result['images']['thumbnail']['url']
					);
			}
		} else {
			$data['code'] = 0;
			$data['error_type'] = 'not found';
		}
		
		return $data;
	}
}
?>