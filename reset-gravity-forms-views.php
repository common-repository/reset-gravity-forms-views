<?php
/*
Plugin Name: Reset Gravity Forms Views
Description: Reset Gravity Forms Views  hourly, twicedaily, daily or weekly
Plugin URI: https://wpsos.io/
Author: Morgan Friedman
Version: 1.1
*/


function wh_gf_views_add_intervals( $schedules ) {


	// add a 'weekly' interval
	$schedules['weekly'] = array(
		'interval' => 604800,
		'display' => __( 'Once Weekly' )
	);
	$schedules['monthly'] = array(
		'interval' => 2635200,
		'display' => __( 'Once a month' )
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'wh_gf_views_add_intervals' );


register_activation_hook( __FILE__, 'wh_gf_views_activation' );

function wh_gf_views_activation() {
	if ( ! wp_next_scheduled ( 'wh_gf_views_cron_jobs' ) ) {
		wp_schedule_event( time(), 'monthly', 'wh_gf_views_cron_jobs' );
	}
}


add_action( 'wh_gf_views_cron_jobs', 'wh_gf_views_cron_func' );
// a function to add a option in the options table
function wh_gf_views_cron_func() {
	if ( class_exists( 'GFFormsModel' ) ) {
		$options = get_option( 'wh_gf_views_cron_settings' );
		if ( !empty( $options['forms'] ) ) {
			foreach ( $options['forms'] as $formid ) {
				GFFormsModel::delete_views( $formid );
			}
		}
	}
}



//for clearing the sceduled tasks
register_deactivation_hook( __FILE__, 'wh_gf_deactivate_cron_hook' );

function wh_gf_deactivate_cron_hook() {
	wp_clear_scheduled_hook( 'wh_gf_views_cron_jobs' );
}


class wh_gf_views_cron_settings {

	function __construct() {

		// Add GetResponse Settings submenu
		add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
		// add fields using settings api
		add_action( 'admin_init', array( $this, 'initialize_options' ) );

		add_action( "update_option_wh_gf_views_cron_settings",  array( $this, 'update_cron_interval' ), 10, 3 );
	}

	function register_menu_page() {
		add_options_page( 'Reset GF Views', 'Reset GF Views', 'manage_options', 'wh_gf_views_cron_settings', array( $this, 'wh_gf_views_cron_settings_callback' ) );
	}

	function wh_gf_views_cron_settings_callback() {
?>
		<!-- Create a header in the default WordPress 'wrap' container -->
    <div class="wrap">

        <!-- Create the form that will be used to render our options -->
        <form method="post" action="options.php">
            <?php settings_fields( 'wh_gf_views_cron_settings' ); ?>
            <?php do_settings_sections( 'wh_gf_views_cron_settings' ); ?>
            <?php submit_button(); ?>
        </form>

    </div><!-- /.wrap -->
<?php
	}



	/**
	 * Register settings and fields
	 *
	 * @author Aman Saini
	 * @since  1.0
	 * @return [type] [description]
	 */
	function initialize_options() {

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wh_gf_views_cron_settings' ) {
			if ( isset( $_GET['action'] ) && $_GET['action'] == 'wh-reset-gf-views' ) {
				wh_gf_views_cron_func();
			}
		}

		// If settings don't exist, create them.
		if ( false == get_option( 'wh_gf_views_cron_settings' ) ) {
			add_option( 'wh_gf_views_cron_settings' );
		} // end if

		add_settings_section(
			'wh_gf_views_cron_settings_section',
			'Reset Views Cron Settings',
			array( $this, 'wh_gf_views_cron_settings_section_callback' ),
			'wh_gf_views_cron_settings'
		);

		// API KEY
		add_settings_field(
			'frequency',
			'Reset Views Cron Frequency',
			array( $this, 'frequeny' ),
			'wh_gf_views_cron_settings',
			'wh_gf_views_cron_settings_section'

		);
		// API KEY
		add_settings_field(
			'forms',
			'Select Forms',
			array( $this, 'forms' ),
			'wh_gf_views_cron_settings',
			'wh_gf_views_cron_settings_section'

		);

		//register settings
		register_setting( 'wh_gf_views_cron_settings', 'wh_gf_views_cron_settings' );

	}

	function wh_gf_views_cron_settings_section_callback() {

		echo '';
	}

	function frequeny() {
		$options = get_option( 'wh_gf_views_cron_settings' );

		$frequeny = !empty( $options['frequeny'] )?$options['frequeny']:'';

		// Render the output
		echo '<select  id="frequeny" name="wh_gf_views_cron_settings[frequeny]">';
		echo "<option value='monthly' ".selected( 'monthly', $frequeny, true )." > Monthly (1 Month)</option>";
		echo "<option value='weekly' ".selected( 'weekly', $frequeny, true )." > Weekly (1 Week)</option>";
		echo "<option value='daily' ".selected( 'daily', $frequeny, true )." > Daily (1 Day)</option>";
		echo "<option value='twicedaily' ".selected( 'twicedaily', $frequeny, true )." > Twice Daily (12 hour)</option>";
		echo "<option value='hourly' ".selected( 'hourly', $frequeny, true )." > Hourly (1 hour)</option>";

		echo "</select>";



	}

	function forms() {

		if ( class_exists( 'GFAPI' ) ) {
			$forms = GFAPI::get_forms();

			$options = get_option( 'wh_gf_views_cron_settings' );

			$frequeny = !empty( $options['forms'] )?$options['forms']:'';
			echo '<select multiple="multiple"  id="forms" name="wh_gf_views_cron_settings[forms][]">';

			foreach ( $forms as $form ) {
				//var_dump($form);
				$selected = in_array( $form['id'], $frequeny )? 'selected=selected': '';
				echo "<option value='".$form['id']."' ".$selected." >".$form['title']."</option>";
			}
			echo "</select>";
		}

		echo "<br/><br/><a class='button' href='".admin_url( '/options-general.php?page=wh_gf_views_cron_settings&action=wh-reset-gf-views' )."'> Reset Views Now! </a>";

	}

	function update_cron_interval( $oldvalue, $value, $option ) {
		// var_dump( $value ); die;
		wp_clear_scheduled_hook( 'wh_gf_views_cron_jobs' );
		wp_schedule_event( time(), $value['frequeny'], 'wh_gf_views_cron_jobs' );

	}


}

new wh_gf_views_cron_settings();

//call_me();
