<?php
/**
 * Register settings for the plugins.
 *
 * @since 0.1
 */
add_action( 'admin_init', 'tokokoo_instagram_setting_init' );

function tokokoo_instagram_setting_init() {
	$settings = get_option( 'tokokoo-instagram' );
	
	register_setting( 'tokokoo_instagram_options', 'tokokoo-instagram' );
	
	/* Application setting section */
	add_settings_section( 'app-section', __( 'Application Setting', 'koo-instagram' ), 'tokokoo_instagram_app_section_callback', 'tokokoo-instagram' );
	add_settings_field( 'client_id', __( 'Client ID', 'koo-instagram' ), 'tokokoo_instagram_field_text', 'tokokoo-instagram', 'app-section', array(
		'name' => 'client_id',
		'value' => $settings['client_id'],
	) );
	add_settings_field( 'client_secret', __( 'Client Secret', 'koo-instagram' ), 'tokokoo_instagram_field_text', 'tokokoo-instagram', 'app-section', array(
		'name' => 'client_secret',
		'value' => $settings['client_secret'],
	) );
	
	/* Misc setting section */
	add_settings_section( 'misc-section', __( 'Misc Setting', 'koo-instagram' ), 'tokokoo_instagram_misc_section_callback', 'tokokoo-instagram' );
	add_settings_field( 'no_result_text', __( 'No result text', 'koo-instagram' ), 'tokokoo_instagram_field_text', 'tokokoo-instagram', 'misc-section', array(
		'name' => 'no_result_text',
		'value' => $settings['no_result_text'],
	) );
	
	/* Secret section */
	add_settings_section( 'secret-section', __( 'Your Access Token', 'koo-instagram' ), 'tokokoo_instagram_secret_section_callback', 'tokokoo-instagram' );
	add_settings_field( 'access_token', __( 'Access Token', 'koo-instagram' ), 'tokokoo_instagram_field_text', 'tokokoo-instagram', 'secret-section', array(
		'name' => 'access_token',
		'value' => $settings['access_token'],
		'opt' => 'readonly',
	) );
	add_settings_field( 'user_id', __( 'User ID', 'koo-instagram' ), 'tokokoo_instagram_field_text', 'tokokoo-instagram', 'secret-section', array(
		'name' => 'user_id',
		'value' => $settings['user_id'],
		'opt' => 'readonly',
	) );
}

/**
 * Callback function for text input setting field.
 *
 * @since 0.1
 */
function tokokoo_instagram_field_text( $args ) {
	$name = esc_attr( $args['name'] );
	$value = esc_attr( $args['value'] );
	$type = isset( $args['type'] ) ? $args['type'] : 'text';
	$opt = isset( $args['opt'] ) ? $args['opt'] : '';
	
	echo '<input type="' . $type . '" class="regular-text" id="tokokoo-instagram-' . $name . '" name="tokokoo-instagram[' . $name . ']" value="' . $value . '" ' . $opt . '>';
}

/**
 * Callback function for application section setting.
 *
 * @since 0.1
 */
function tokokoo_instagram_app_section_callback() {
	echo '<p>'. sprintf( __( 'Before you can use this plugin, you have to <a href="%1$s">Register your Application</a> to get Cliend ID and Client Secret. When registering your application, use the following callback URI: <code>%2$s</code>', 'koo-instagram' ), 'http://instagram.com/developer/clients/manage/', admin_url( 'options-general.php?page=tokokoo-instagram' ) ) . '</p>';
}

/**
 * Callback function for misc section.
 *
 * @since 0.1
 */
function tokokoo_instagram_misc_section_callback() {
	echo '<p>' . __( 'Misc. settings for the widget.', 'koo-instagram' ) . '</p>';
}

/**
 * Callback function for secret section that holds the secret access token.
 *
 * @since 0.1
 */
function tokokoo_instagram_secret_section_callback() {
	echo '<p>' . __( 'Here is your access token. Keep it yours and use with care.', 'koo-instagram' ) . '</p>';
}

/**
 * Render the setting page.
 *
 * @since 0.1
 */
function tokokoo_instagram_setting_page(){
	if( $_GET['code'] ) {
		$get_access_token = Tokokoo_Instagram::get_access_token( $_GET['code'] );
	}
	
	$settings = get_option( 'tokokoo-instagram' );
	$user_profile = Tokokoo_Instagram::get_user_profile();
	?>
	
	<div class="wrap">
		<div id="icon-options-general" class="icon32"><br/></div><h2><?php _e( 'Tokokoo Instagram Settings', 'koo-instagram' ); ?></h2>
			
		<?php if( isset( $get_access_token->code ) && $get_access_token->code != 200 ) : ?>
			
			<div class="error settings-error">
				<p><strong><?php echo $get_access_token->code . ' : ' . $get_access_token->error_type; ?></strong></p>
				<p><?php echo $get_access_token->error_message; ?></p>
			</div>
			
		<?php endif; ?>
			
		<?php if( $user_profile->meta->code == 200 ): ?>
			
			<div class="updated settings-error" style="overflow:hidden">
				<p><img src="<?php echo $user_profile->data->profile_picture; ?>" class="alignleft" width="48" height="48" alt="<?php $user_profile->data->full_name; ?>" style="margin-right:10px"> <?php _e( 'You are currently signed in as', 'koo-instagram' ); ?> <strong><?php echo $user_profile->data->full_name; ?> <em>( <?php echo $user_profile->data->username; ?> )</em></strong>.</p>
				<p><a href="http://instagram.com/<?php echo $user_profile->data->username; ?>"><em><?php _e( 'Instagram Profile', 'koo-instagram' ); ?> &rarr;</em></a></p>
			</div>
			
		<?php elseif( !$_GET['code'] && $settings['client_id'] != '' || ( isset( $get_access_token->code ) && $get_access_token->code != 200 ) ) :
			$callback_url = urlencode( admin_url( 'options-general.php?page=tokokoo-instagram' ) );
			$authorization_url = 'https://api.instagram.com/oauth/authorize/?client_id=' . $settings['client_id'] . '&amp;redirect_uri=' . $callback_url . '&amp;response_type=code';
		?>
			
			<div class="updated settings-error">
				<p><?php _e( 'You have not connect your Instagram Account or your access token is expired. Make sure the <strong>Client ID</strong> and <strong>Client Secret</strong> is correct to your application and click the following button.', 'koo-instagram' ); ?></p>
				<p><a href="<?php echo $authorization_url; ?>" target="_blank"><img src="<?php echo TOKOKOO_INSTAGRAM_URI . 'assets/images/instagram-signin.png'; ?>" alt="<?php esc_attr_e( 'Connect with Instagram', 'koo-instagram' ); ?>"></a></p>
			</div>
		   
		<?php endif; ?>
			
		<form method="post" action="options.php">

			<?php settings_fields('tokokoo_instagram_options'); ?>
			<?php do_settings_sections( 'tokokoo-instagram' ); ?>
			<?php submit_button(); ?>
			
		</form>
	</div>
<?php
}
?>