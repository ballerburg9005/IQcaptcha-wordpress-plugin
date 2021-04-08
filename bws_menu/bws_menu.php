<?php
/*
* Function for displaying ballerburg9005 menu
* Version: 2.3.7
*/

if ( ! function_exists ( 'bws_admin_enqueue_scripts' ) )
	require_once( dirname( __FILE__ ) . '/bws_functions.php' );

if ( ! function_exists( 'bws_add_menu_render' ) ) {
	function bws_add_menu_render() {
		global $wpdb, $wp_version, $bstwbsftwppdtplgns_options;
		$error = $message = '';

		/**
		 * @deprecated 1.9.8 (15.12.2016)
		 */
		$is_main_page = in_array( $_GET['page'], array( 'bws_panel', 'bws_themes', 'bws_system_status' ) );
		$page = wp_unslash( $_GET['page'] );
		$tab = isset( $_GET['tab'] ) ? wp_unslash( $_GET['tab'] ) : '';

		if ( $is_main_page )
			$current_page = 'admin.php?page=' . $page;
		else
			$current_page = isset( $_GET['tab'] ) ? 'admin.php?page=' . $page . '&tab=' . $tab : 'admin.php?page=' . $page;

		if ( 'bws_panel' == $page || ( ! $is_main_page && '' == $tab ) ) {

			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			/* get $bws_plugins */
			require( dirname( __FILE__ ) . '/product_list.php' );

			$all_plugins = get_plugins();
			$active_plugins = get_option( 'active_plugins' );
			$sitewide_active_plugins = ( function_exists( 'is_multisite' ) && is_multisite() ) ? get_site_option( 'active_sitewide_plugins' ) : array();
			$update_availible_all = get_site_transient( 'update_plugins' );

			$plugin_category = isset( $_GET['category'] ) ? esc_attr( $_GET['category'] ) : 'all';

			if ( ( isset( $_GET['sub'] ) && 'installed' == $_GET['sub'] ) || ! isset( $_GET['sub'] ) ) {
				$bws_plugins_update_availible = $bws_plugins_expired = array();
				foreach ( $bws_plugins as $key_plugin => $value_plugin ) {

					foreach ( $value_plugin['category'] as $category_key ) {
						$bws_plugins_category[ $category_key ]['count'] = isset( $bws_plugins_category[ $category_key ]['count'] ) ? $bws_plugins_category[ $category_key ]['count'] + 1 : 1;
					}

					$is_installed = array_key_exists( $key_plugin, $all_plugins );
					$is_pro_installed = false;
					if ( isset( $value_plugin['pro_version'] ) ) {
						$is_pro_installed = array_key_exists( $value_plugin['pro_version'], $all_plugins );
					}
					/* check update_availible */
					if ( ! empty( $update_availible_all ) && ! empty( $update_availible_all->response ) ) {
						if ( $is_pro_installed && array_key_exists( $value_plugin['pro_version'], $update_availible_all->response ) ) {
							unset( $bws_plugins[ $key_plugin ] );
							$value_plugin['update_availible'] = $value_plugin['pro_version'];
							$bws_plugins_update_availible[ $key_plugin ] = $value_plugin;
						} else if ( $is_installed && array_key_exists( $key_plugin, $update_availible_all->response ) ) {
							unset( $bws_plugins[ $key_plugin ] );
							$value_plugin['update_availible'] = $key_plugin;
							$bws_plugins_update_availible[ $key_plugin ] = $value_plugin;
						}
					}
					/* check expired */
					if ( $is_pro_installed && isset( $bstwbsftwppdtplgns_options['time_out'][ $value_plugin['pro_version'] ] ) &&
					     strtotime( $bstwbsftwppdtplgns_options['time_out'][ $value_plugin['pro_version'] ] ) < strtotime( date( "m/d/Y" ) ) ) {
						unset( $bws_plugins[ $key_plugin ] );
						$value_plugin['expired'] = $bstwbsftwppdtplgns_options['time_out'][ $value_plugin['pro_version'] ];
						$bws_plugins_expired[ $key_plugin ] = $value_plugin;
					}
				}
				$bws_plugins = $bws_plugins_update_availible + $bws_plugins_expired + $bws_plugins;
			} else {
				foreach ( $bws_plugins as $key_plugin => $value_plugin ) {
					foreach ( $value_plugin['category'] as $category_key ) {
						$bws_plugins_category[ $category_key ]['count'] = isset( $bws_plugins_category[ $category_key ]['count'] ) ? $bws_plugins_category[ $category_key ]['count'] + 1 : 1;
					}
				}
			}

			/*** membership ***/
			$bws_license_plugin = 'bws_get_list_for_membership';
			$bws_license_key = isset( $bstwbsftwppdtplgns_options[ $bws_license_plugin ] ) ? $bstwbsftwppdtplgns_options[ $bws_license_plugin ] : '';
			$update_membership_list = true;

			if ( isset( $_POST['bws_license_key'] ) )
				$bws_license_key = sanitize_text_field( $_POST['bws_license_key'] );

			if ( isset( $_SESSION['bws_membership_time_check'] ) && isset( $_SESSION['bws_membership_list'] ) && $_SESSION['bws_membership_time_check'] < strtotime( '+12 hours' ) ) {
				$update_membership_list = false;
				$plugins_array = $_SESSION['bws_membership_list'];
			}

			if ( ( $update_membership_list && ! empty( $bws_license_key ) ) || ( isset( $_POST['bws_license_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'bws_license_nonce_name' ) ) ) {

				if ( '' != $bws_license_key ) {
					if ( strlen( $bws_license_key ) != 18 ) {
						$error = __( 'Wrong license key', 'ballerburg9005' );
					} else {

						if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] ) && $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] > ( time() - (24 * 60 * 60) ) ) {
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] = $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] + 1;
						} else {
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] = 1;
							$bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] = time();
						}

						/* get Pro list */
						$to_send = array();
						$to_send["plugins"][ $bws_license_plugin ] = array();
						$to_send["plugins"][ $bws_license_plugin ]["bws_license_key"] = $bws_license_key;
						$options = array(
							'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3 ),
							'body' => array( 'plugins' => serialize( $to_send ) ),
							'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) );
						$raw_response = wp_remote_post( 'https://ballerburg9005.com/wp-content/plugins/paid-products/plugins/pro-license-check/1.0/', $options );

						if ( is_wp_error( $raw_response ) || 200 != wp_remote_retrieve_response_code( $raw_response ) ) {
							$error = __( "Something went wrong. Please try again later. If the error appears again, please contact us", 'ballerburg9005' ) . ' <a href="https://support.ballerburg9005.com">ballerburg9005</a>. ' . __( "We are sorry for inconvenience.", 'ballerburg9005' );
						} else {
							$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );

							if ( is_array( $response ) && !empty( $response ) ) {
								foreach ( $response as $key => $value ) {
									if ( "wrong_license_key" == $value->package ) {
										$error = __( "Wrong license key.", 'ballerburg9005' );
									} elseif ( "wrong_domain" == $value->package ) {
										$error = __( 'This license key is bound to another site. Change it via personal Client Area.', 'ballerburg9005' ) . '<a target="_blank" href="https://ballerburg9005.com/client-area">' . __( 'Log in', 'ballerburg9005' ) . '</a>';
									} elseif ( "you_are_banned" == $value->package ) {
										$error = __( "Unfortunately, you have exceeded the number of available tries per day.", 'ballerburg9005' );
									} elseif ( "time_out" == $value->package ) {
										$error = sprintf( __( "Unfortunately, Your license has expired. To continue getting top-priority support and plugin updates, you should extend it in your %s", 'ballerburg9005' ), ' <a href="https://ballerburg9005.com/client-area">Client Area</a>' );
									} elseif ( "duplicate_domen_for_trial" == $value->package ) {
										$error = __( "Unfortunately, the Pro licence was already installed to this domain. The Pro Trial license can be installed only once.", 'ballerburg9005' );
									} elseif ( is_array( $value->package ) && ! empty( $value->package ) ) {
										$plugins_array = $_SESSION['bws_membership_list'] = $value->package;
										$_SESSION['bws_membership_time_check'] = strtotime( 'now' );

										if ( isset( $bstwbsftwppdtplgns_options[ $bws_license_plugin ] ) && $bws_license_key == $bstwbsftwppdtplgns_options[ $bws_license_plugin ] ) {
											$message = __( 'The license key is valid.', 'ballerburg9005' );
											if ( isset( $value->time_out ) && $value->time_out != '' )
												$message .= ' ' . __( 'Your license will expire on', 'ballerburg9005' ) . ' ' . $value->time_out . '.';
										} else {
											$message = __( 'Congratulations! Pro Membership license is activated successfully.', 'ballerburg9005' );
										}

										$bstwbsftwppdtplgns_options[ $bws_license_plugin ] = $bws_license_key;
									}
								}
							} else {
								$error = __( "Something went wrong. Try again later or upload the plugin manually. We are sorry for inconvenience.", 'ballerburg9005' );
							}
						}

						if ( is_multisite() )
							update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
						else
							update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
					}
				} else {
					$error = __( "Please enter your license key.", 'ballerburg9005' );
				}
			}
		} elseif ( 'bws_system_status' == $page || 'system-status' == $tab ) {

			$all_plugins = get_plugins();
			$active_plugins = get_option( 'active_plugins' );
			$mysql_info = $wpdb->get_results( "SHOW VARIABLES LIKE 'sql_mode'" );
			if ( is_array( $mysql_info ) )
				$sql_mode = $mysql_info[0]->Value;
			if ( empty( $sql_mode ) )
				$sql_mode = __( 'Not set', 'ballerburg9005' );

			$allow_url_fopen = ( ini_get( 'allow_url_fopen' ) ) ? __( 'On', 'ballerburg9005' ) : __( 'Off', 'ballerburg9005' );
			$upload_max_filesize = ( ini_get( 'upload_max_filesize' ) )? ini_get( 'upload_max_filesize' ) : __( 'N/A', 'ballerburg9005' );
			$post_max_size = ( ini_get( 'post_max_size' ) ) ? ini_get( 'post_max_size' ) : __( 'N/A', 'ballerburg9005' );
			$max_execution_time = ( ini_get( 'max_execution_time' ) ) ? ini_get( 'max_execution_time' ) : __( 'N/A', 'ballerburg9005' );
			$memory_limit = ( ini_get( 'memory_limit' ) ) ? ini_get( 'memory_limit' ) : __( 'N/A', 'ballerburg9005' );
			$wp_memory_limit = ( defined( 'WP_MEMORY_LIMIT' ) ) ? WP_MEMORY_LIMIT : __( 'N/A', 'ballerburg9005' );
			$memory_usage = ( function_exists( 'memory_get_usage' ) ) ? round( memory_get_usage() / 1024 / 1024, 2 ) . ' ' . __( 'Mb', 'ballerburg9005' ) : __( 'N/A', 'ballerburg9005' );
			$exif_read_data = ( is_callable( 'exif_read_data' ) ) ? __( 'Yes', 'ballerburg9005' ) . " ( V" . substr( phpversion( 'exif' ), 0,4 ) . ")" : __( 'No', 'ballerburg9005' );
			$iptcparse = ( is_callable( 'iptcparse' ) ) ? __( 'Yes', 'ballerburg9005' ) : __( 'No', 'ballerburg9005' );
			$xml_parser_create = ( is_callable( 'xml_parser_create' ) ) ? __( 'Yes', 'ballerburg9005' ) : __( 'No', 'ballerburg9005' );
			$theme = ( function_exists( 'wp_get_theme' ) ) ? wp_get_theme() : get_theme( get_current_theme() );

			if ( function_exists( 'is_multisite' ) ) {
				$multisite = is_multisite() ? __( 'Yes', 'ballerburg9005' ) : __( 'No', 'ballerburg9005' );
			} else {
				$multisite = __( 'N/A', 'ballerburg9005' );
			}

			$system_info = array(
				'wp_environment' => array(
					'name' => __( 'WordPress Environment', 'ballerburg9005' ),
					'data' => array(
						__( 'Home URL', 'ballerburg9005' )						=> home_url(),
						__( 'Website URL', 'ballerburg9005' )					=> get_option( 'siteurl' ),
						__( 'WP Version', 'ballerburg9005' )					=> $wp_version,
						__( 'WP Multisite', 'ballerburg9005' )					=> $multisite,
						__( 'WP Memory Limit', 'ballerburg9005' )				=> $wp_memory_limit,
						__( 'Active Theme', 'ballerburg9005' )					=> $theme['Name'] . ' ' . $theme['Version'] . ' (' . sprintf( __( 'by %s', 'ballerburg9005' ), $theme['Author'] ) . ')'
					),
				),
				'server_environment' => array(
					'name' => __( 'Server Environment', 'ballerburg9005' ),
					'data' => array(
						__( 'Operating System', 'ballerburg9005' )				=> PHP_OS,
						__( 'Server', 'ballerburg9005' )						=> $_SERVER["SERVER_SOFTWARE"],
						__( 'PHP Version', 'ballerburg9005' )					=> PHP_VERSION,
						__( 'PHP Allow URL fopen', 'ballerburg9005' )			=> $allow_url_fopen,
						__( 'PHP Memory Limit', 'ballerburg9005' )				=> $memory_limit,
						__( 'Memory Usage', 'ballerburg9005' )					=> $memory_usage,
						__( 'PHP Max Upload Size', 'ballerburg9005' )			=> $upload_max_filesize,
						__( 'PHP Max Post Size', 'ballerburg9005' )			=> $post_max_size,
						__( 'PHP Max Script Execute Time', 'ballerburg9005' )	=> $max_execution_time,
						__( 'PHP Exif support', 'ballerburg9005' )				=> $exif_read_data,
						__( 'PHP IPTC support', 'ballerburg9005' )				=> $iptcparse,
						__( 'PHP XML support', 'ballerburg9005' )				=> $xml_parser_create,
						'$_SERVER[HTTP_HOST]'								=> $_SERVER['HTTP_HOST'],
						'$_SERVER[SERVER_NAME]'								=> $_SERVER['SERVER_NAME'],
					),
				),
				'db'	=> array(
					'name' => __( 'Database', 'ballerburg9005' ),
					'data' => array(
						__( 'WP DB version', 'ballerburg9005' )	=> get_option( 'db_version' ),
						__( 'MySQL version', 'ballerburg9005' )	=> $wpdb->get_var( "SELECT VERSION() AS version" ),
						__( 'SQL Mode', 'ballerburg9005' )			=> $sql_mode,
					),
				),
				'active_plugins'	=> array(
					'name' 	=> __( 'Active Plugins', 'ballerburg9005' ),
					'data' 	=> array(),
					'count'	=> 0
				),
				'inactive_plugins'	=> array(
					'name' 	=> __( 'Inactive Plugins', 'ballerburg9005' ),
					'data' 	=> array(),
					'count'	=> 0
				)
			);

			foreach ( $all_plugins as $path => $plugin ) {
				$name = str_replace( 'by ballerburg9005', '', $plugin['Name'] );
				if ( is_plugin_active( $path ) ) {
					$system_info['active_plugins']['data'][ $name ] = sprintf( __( 'by %s', 'ballerburg9005' ), $plugin['Author'] ) . ' - ' . $plugin['Version'];
					$system_info['active_plugins']['count'] = $system_info['active_plugins']['count'] + 1;
				} else {
					$system_info['inactive_plugins']['data'][ $name ] = sprintf( __( 'by %s', 'ballerburg9005' ), $plugin['Author'] ) . ' - ' . $plugin['Version'];
					$system_info['inactive_plugins']['count'] = $system_info['inactive_plugins']['count'] + 1;
				}
			}

			if ( ( isset( $_REQUEST['bwsmn_form_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'bwsmn_nonce_submit' ) ) || ( isset( $_REQUEST['bwsmn_form_submit_custom_email'] ) && check_admin_referer( plugin_basename(__FILE__), 'bwsmn_nonce_submit_custom_email' ) ) ) {
				if ( isset( $_REQUEST['bwsmn_form_email'] ) ) {
					$email = sanitize_email( $_REQUEST['bwsmn_form_email'] );
					if ( '' == $email ) {
						$error = __( 'Please enter a valid email address.', 'ballerburg9005' );
					} else {
						$message = sprintf( __( 'Email with system info is sent to %s.', 'ballerburg9005' ), $email );
					}
				} else {
					$email = 'plugin_system_status@ballerburg9005.com';
					$message = __( 'Thank you for contacting us.', 'ballerburg9005' );
				}

				if ( $error == '' ) {
					$headers  = 'MIME-Version: 1.0' . "\n";
					$headers .= 'Content-type: text/html; charset=utf-8' . "\n";
					$headers .= 'From: ' . get_option( 'admin_email' );
					$message_text = '<html><head><title>System Info From ' . home_url() . '</title></head><body>';
					foreach ( $system_info as $info ) {
						if ( ! empty( $info['data'] ) ) {
							$message_text .= '<h4>' . $info['name'];
							if ( isset( $info['count'] ) )
								$message_text .= ' (' . $info['count'] . ')';
							$message_text .= '</h4><table>';
							foreach ( $info['data'] as $key => $value ) {
								$message_text .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
							}
							$message_text .= '</table>';
						}
					}
					$message_text .= '</body></html>';
					$result = wp_mail( $email, 'System Info From ' . home_url(), $message_text, $headers );
					if ( $result != true )
						$error = __( "Sorry, email message could not be delivered.", 'ballerburg9005' );
				}
			}
		} ?>
        <div class="bws-wrap">
            <div class="bws-header">
                <div class="bws-title">
                    <a href="<?php echo ( $is_main_page ) ? self_admin_url( 'admin.php?page=bws_panel' ) : esc_url( self_admin_url( 'admin.php?page=' . $page ) ); ?>">
                        <span class="bws-logo bwsicons bwsicons-bws-logo"></span>
                        ballerburg9005
                        <span>panel</span>
                    </a>
                </div>
                <div class="bws-menu-item-icon">&#8226;&#8226;&#8226;</div>
                <div class="bws-nav-tab-wrapper">
					<?php if ( $is_main_page ) { ?>
                        <a class="bws-nav-tab<?php if ( 'bws_panel' == $page ) echo ' bws-nav-tab-active'; ?>" href="<?php echo self_admin_url( 'admin.php?page=bws_panel' ); ?>"><?php _e( 'Plugins', 'ballerburg9005' ); ?></a>
                        <a class="bws-nav-tab<?php if ( 'bws_themes' == $page ) echo ' bws-nav-tab-active'; ?>" href="<?php echo self_admin_url( 'admin.php?page=bws_themes' ); ?>"><?php _e( 'Themes', 'ballerburg9005' ); ?></a>
                        <a class="bws-nav-tab<?php if ( 'bws_system_status' == $page ) echo ' bws-nav-tab-active'; ?>" href="<?php echo self_admin_url( 'admin.php?page=bws_system_status' ); ?>"><?php _e( 'System status', 'ballerburg9005' ); ?></a>
					<?php } else { ?>
                        <a class="bws-nav-tab<?php if ( ! isset( $_GET['tab'] ) ) echo ' bws-nav-tab-active'; ?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=' . $page ) ); ?>"><?php _e( 'Plugins', 'ballerburg9005' ); ?></a>
                        <a class="bws-nav-tab<?php if ( 'themes' == $tab ) echo ' bws-nav-tab-active'; ?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=' . $page . '&tab=themes' ) ); ?>"><?php _e( 'Themes', 'ballerburg9005' ); ?></a>
                        <a class="bws-nav-tab<?php if ( 'system-status' == $tab ) echo ' bws-nav-tab-active'; ?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=' . $page . '&tab=system-status' ) ); ?>"><?php _e( 'System status', 'ballerburg9005' ); ?></a>
					<?php } ?>
                </div>
                <div class="bws-help-links-wrapper">
                    <a href="https://support.ballerburg9005.com" target="_blank"><?php _e( 'Support', 'ballerburg9005' ); ?></a>
                    <a href="https://ballerburg9005.com/client-area" target="_blank" title="<?php _e( 'Manage purchased licenses & subscriptions', 'ballerburg9005' ); ?>">Client Area</a>
                </div>
                <div class="clear"></div>
            </div>
			<?php if ( ( 'bws_panel' == $page || ( ! isset( $_GET['tab'] ) && ! $is_main_page ) ) && ! isset( $_POST['bws_plugin_action_submit'] ) ) { ?>
                <div class="bws-membership-wrap">
                    <div class="bws-membership-backround"></div>
                    <div class="bws-membership">
                        <div class="bws-membership-title"><?php printf( __( 'Get Access to %s+ Premium Plugins', 'ballerburg9005' ), '30' ); ?></div>
                        <form class="bws-membership-form" method="post" action="">
                            <span class="bws-membership-link"><a target="_blank" href="https://ballerburg9005.com/membership/"><?php _e( 'Subscribe to Pro Membership', 'ballerburg9005' ); ?></a> <?php _e( 'or', 'ballerburg9005' ); ?></span>
							<?php if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] ) &&
							           '5' < $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['count'] &&
							           $bstwbsftwppdtplgns_options['go_pro'][ $bws_license_plugin ]['time'] > ( time() - ( 24 * 60 * 60 ) ) ) { ?>
                                <div class="bws_form_input_wrap">
                                    <input disabled="disabled" type="text" name="bws_license_key" value="<?php echo esc_attr( $bws_license_key); ?>" />
                                    <div class="bws_error"><?php _e( "Unfortunately, you have exceeded the number of available tries per day.", 'ballerburg9005' ); ?></div>
                                </div>
                                <input disabled="disabled" type="submit" class="bws-button" value="<?php _e( 'Check license key', 'ballerburg9005' ); ?>" />
							<?php } else { ?>
                                <div class="bws_form_input_wrap">
                                    <input <?php if ( "" != $error ) echo 'class="bws_input_error"'; ?> type="text" placeholder="<?php _e( 'Enter your license key', 'ballerburg9005' ); ?>" maxlength="100" name="bws_license_key" value="<?php echo esc_attr( $bws_license_key ); ?>" />
                                    <div class="bws_error" <?php if ( "" == $error ) echo 'style="display:none"'; ?>><?php echo $error; ?></div>
                                </div>
                                <input type="hidden" name="bws_license_plugin" value="<?php echo esc_attr( $bws_license_plugin ); ?>" />
                                <input type="hidden" name="bws_license_submit" value="submit" />
								<?php if ( empty( $plugins_array ) ) { ?>
                                    <input type="submit" class="bws-button" value="<?php _e( 'Activate', 'ballerburg9005' ); ?>" />
								<?php } else { ?>
                                    <input type="submit" class="bws-button" value="<?php _e( 'Check license key', 'ballerburg9005' ); ?>" />
								<?php } ?>
								<?php wp_nonce_field( plugin_basename(__FILE__), 'bws_license_nonce_name' ); ?>
							<?php } ?>
                        </form>
                        <div class="clear"></div>
                    </div>
                </div>
			<?php } ?>
            <div class="bws-wrap-content wrap">
				<?php if ( 'bws_panel' == $page || ( ! isset( $_GET['tab'] ) && ! $is_main_page ) ) { ?>
                    <div class="updated notice is-dismissible inline" <?php if ( '' == $message || '' != $error ) echo 'style="display:none"'; ?>><p><?php echo $message; ?></p></div>
                    <h1>
						<?php _e( 'Plugins', 'ballerburg9005' ); ?>
                        <a href="<?php echo self_admin_url( 'plugin-install.php?tab=upload' ); ?>" class="upload page-title-action add-new-h2"><?php _e( 'Upload Plugin', 'ballerburg9005' ); ?></a>
                    </h1>
					<?php if ( isset( $_GET['error'] ) ) {
						if ( isset( $_GET['charsout'] ) )
							$errmsg = sprintf( __( 'The plugin generated %d characters of <strong>unexpected output</strong> during activation. If you notice &#8220;headers already sent&#8221; messages, problems with syndication feeds or other issues, try deactivating or removing this plugin.' ), $_GET['charsout'] );
						else
							$errmsg = __( 'Plugin could not be activated because it triggered a <strong>fatal error</strong>.' ); ?>
                        <div id="message" class="error is-dismissible"><p><?php echo $errmsg; ?></p></div>
					<?php } elseif ( isset( $_GET['activate'] ) ) { ?>
                        <div id="message" class="updated notice is-dismissible"><p><?php _e( 'Plugin <strong>activated</strong>.' ) ?></p></div>
					<?php }

					if ( isset( $_POST['bws_plugin_action_submit'] ) && isset( $_POST['bws_install_plugin'] ) && check_admin_referer( plugin_basename(__FILE__), 'bws_license_install_nonce_name' ) ) {

						$bws_license_plugin = sanitize_text_field( $_POST['bws_install_plugin'] );

						$bstwbsftwppdtplgns_options[ $bws_license_plugin ] = $bws_license_key;
						if ( is_multisite() )
							update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
						else
							update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );

						$url = $plugins_array[ $bws_license_plugin ]['link'] . '&download_from=5'; ?>
						<h2><?php _e( 'Download Pro Plugin', 'ballerburg9005' ); ?></h2>		
						<p>
							<strong><?php _e( 'Your Pro plugin is ready', 'ballerburg9005' ); ?></strong>
							<br>
							<?php _e( 'Your plugin has been zipped, and now is ready to download.', 'ballerburg9005' ); ?>
						</p>
						<p>
							<a class="button button-secondary" target="_parent" href="<?php echo esc_url( $url ); ?>"><?php _e( 'Download Now', 'ballerburg9005' ); ?></a>
						</p>
						<br>
						<p>
							<strong><?php _e( 'Need help installing the plugin?', 'ballerburg9005' ); ?></strong>
							<br>
							<a target="_blank" href="https://docs.google.com/document/d/1-hvn6WRvWnOqj5v5pLUk7Awyu87lq5B_dO-Tv-MC9JQ/"><?php _e( 'How to install WordPress plugin from your admin Dashboard (ZIP archive)', 'ballerburg9005' ); ?></a>
						</p>						
						<p>
							<strong><?php _e( 'Get Started', 'ballerburg9005' ); ?></strong>
							<br>
							<a target="_blank" href="https://drive.google.com/drive/u/0/folders/0B5l8lO-CaKt9VGh0a09vUjNFNjA"><?php _e( 'Documentation', 'ballerburg9005' ); ?></a>
							<br>
							<a target="_blank" href="https://www.youtube.com/user/ballerburg9005"><?php _e( 'Video Instructions', 'ballerburg9005' ); ?></a>
							<br>
							<a target="_blank" href="https://support.ballerburg9005.com"><?php _e( 'Knowledge Base', 'ballerburg9005' ); ?></a>
						</p>
						<p>
							<strong><?php _e( 'Licenses & Domains', 'ballerburg9005' ); ?></strong>
							<br>
							<?php printf( 'Manage your license(-s) and change domain names using the %s at ballerburg9005.',
							'<a target="_blank" href="https://ballerburg9005.com/client-area">' . __( 'Client Area', 'ballerburg9005' ) . '</a>' ); ?>
						</p>
						<p><a href="<?php echo esc_url( self_admin_url( $current_page ) ); ?>" target="_parent"><?php _e( 'Return to ballerburg9005 Panel', 'ballerburg9005' ); ?></a></p>
					<?php } else {
						$category_href = $current_page;
						if ( 'all' != $plugin_category )
							$category_href .= '&category=' . $plugin_category; ?>
                        <ul class="subsubsub">
                            <li>
                                <a <?php if ( ! isset( $_GET['sub'] ) ) echo 'class="current" '; ?>href="<?php echo esc_url( self_admin_url( $category_href ) ); ?>"><?php _e( 'All', 'ballerburg9005' ); ?></a>
                            </li> |
                            <li>
                                <a <?php if ( isset( $_GET['sub'] ) && 'installed' == $_GET['sub'] ) echo 'class="current" '; ?>href="<?php echo esc_url( self_admin_url( $category_href . '&sub=installed' ) ); ?>"><?php _e( 'Installed', 'ballerburg9005' ); ?></a>
                            </li> |
                            <li>
                                <a <?php if ( isset( $_GET['sub'] ) && 'not_installed' == $_GET['sub'] ) echo 'class="current" '; ?>href="<?php echo esc_url( self_admin_url( $category_href . '&sub=not_installed' ) ); ?>"><?php _e( 'Not Installed', 'ballerburg9005' ); ?></a>
                            </li>
                        </ul>
                        <div class="clear"></div>
                        <div class="bws-filter-top">
                            <h2>
                                <span class="bws-toggle-indicator"></span>
								<?php _e( 'Filter results', 'ballerburg9005' ); ?>
                            </h2>
                            <div class="bws-filter-top-inside">
                                <div class="bws-filter-title"><?php _e( 'Category', 'ballerburg9005' ); ?></div>
                                <ul class="bws-category">
                                    <li>
										<?php $sub_in_url = ( isset( $_GET['sub'] ) && in_array( $_GET['sub'], array( 'installed', 'not_installed' ) ) ) ? '&sub=' . $_GET['sub'] : ''; ?>
                                        <a <?php if ( 'all' == $plugin_category ) echo ' class="bws-active"'; ?> href="<?php echo esc_url(self_admin_url( $current_page . $sub_in_url ) ); ?>"><?php _e( 'All', 'ballerburg9005' ); ?>
                                            <span>(<?php echo count( $bws_plugins ); ?>)</span>
                                        </a>
                                    </li>
									<?php foreach ( $bws_plugins_category as $category_key => $category_value ) { ?>
                                        <li>
                                            <a <?php if ( $category_key == $plugin_category ) echo ' class="bws-active"'; ?> href="<?php echo esc_url( self_admin_url( $current_page . $sub_in_url . '&category=' . $category_key ) ); ?>"><?php echo $category_value['name']; ?>
                                                <span>(<?php echo $category_value['count']; ?>)</span>
                                            </a>
                                        </li>
									<?php } ?>
                                </ul>
                            </div>
                        </div>
                        <div class="bws-products">
							<?php $nothing_found = true;
							foreach ( $bws_plugins as $key_plugin => $value_plugin ) {

								if ( 'all' != $plugin_category && isset( $bws_plugins_category[ $plugin_category ] ) && ! in_array( $plugin_category, $value_plugin['category'] ) )
									continue;

								$key_plugin_explode = explode( '/', $key_plugin );

								$icon = isset( $value_plugin['icon'] ) ? $value_plugin['icon'] : '//ps.w.org/' . $key_plugin_explode[0] . '/assets/icon-256x256.png';
								$is_pro_isset = isset( $value_plugin['pro_version'] );
								$is_installed = array_key_exists( $key_plugin, $all_plugins );
								$is_active = in_array( $key_plugin, $active_plugins ) || isset( $sitewide_active_plugins[ $key_plugin ] );

								$is_pro_installed = $is_pro_active = false;
								if ( $is_pro_isset ) {
									$is_pro_installed = array_key_exists( $value_plugin['pro_version'], $all_plugins );
									$is_pro_active = in_array( $value_plugin['pro_version'], $active_plugins ) || isset( $sitewide_active_plugins[ $value_plugin['pro_version'] ] );
								}

								if ( ( isset( $_GET['sub'] ) && 'installed' == $_GET['sub'] && ! $is_pro_installed && ! $is_installed ) ||
								     ( isset( $_GET['sub'] ) && 'not_installed' == $_GET['sub'] && ( $is_pro_installed || $is_installed ) ) )
									continue;

								$link_attr = isset( $value_plugin['install_url'] ) ? 'href="' . esc_url( $value_plugin['install_url'] ) . '" target="_blank"' : 'href="' . esc_url( self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $key_plugin_explode[0] . '&from=import&TB_iframe=true&width=600&height=550' ) ) . '" class="thickbox open-plugin-details-modal"';

								$nothing_found = false; ?>
                                <div class="bws_product_box<?php if ( $is_active || $is_pro_active ) echo ' bws_product_active'; ?>">
                                    <div class="bws_product_image">
                                        <a <?php echo $link_attr; ?>><img src="<?php echo $icon; ?>"/></a>
                                    </div>
                                    <div class="bws_product_content">
                                        <div class="bws_product_title"><a <?php echo $link_attr; ?>><?php echo $value_plugin['name']; ?></a></div>
                                        <div class="bws-version">
											<?php
											if ( $is_pro_installed ) {
												echo '<span';
												if ( ! empty( $value_plugin['expired'] ) || ! empty( $value_plugin['update_availible'] ) )
													echo ' class="bws-update-available"';
												echo '>v ' . $all_plugins[ $value_plugin['pro_version'] ]['Version'] . '</span>';
											} elseif ( $is_installed ) {
												echo '<span';
												if ( ! empty( $value_plugin['expired'] ) || ! empty( $value_plugin['update_availible'] ) )
													echo ' class="bws-update-available"';
												echo '>v ' . $all_plugins[ $key_plugin ]['Version'] . '</span>';
											} else {
												echo '<span>' . __( 'Not installed', 'ballerburg9005' ) . '</span>';
											}

											if ( ! empty( $value_plugin['expired'] ) ) {
												echo ' - <a class="bws-update-now" href="https://support.ballerburg9005.com/hc/en-us/articles/202356359" target="_blank">' . __( 'Renew to get updates', 'ballerburg9005' ) . '</a>';
											} elseif ( ! empty( $value_plugin['update_availible'] ) ) {
												$r = $update_availible_all->response[ $value_plugin['update_availible'] ];
												echo ' - <a class="bws-update-now" href="' . esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $value_plugin['update_availible'] ), 'upgrade-plugin_' . $value_plugin['update_availible'] ) ) . '" class="update-link" aria-label="' . sprintf( __( 'Update to v %s', 'ballerburg9005' ), $r->new_version ) . '">' . sprintf( __( 'Update to v %s', 'ballerburg9005' ), $r->new_version ) . '</a>';
											} ?>
                                        </div>
                                        <div class="bws_product_description">
											<?php echo ( strlen( $value_plugin['description'] ) > 100 ) ? mb_substr( $value_plugin['description'], 0, 100 ) . '...' : $value_plugin['description']; ?>
                                        </div>
                                        <div class="bws_product_links">
											<?php if ( $is_active || $is_pro_active ) {
												if ( $is_pro_isset ) {
													if ( ! $is_pro_installed ) {
														if ( ! empty( $plugins_array ) && array_key_exists( $value_plugin['pro_version'], $plugins_array ) ) { ?>
                                                            <form method="post" action="">
                                                                <input type="submit" class="button button-secondary" value="<?php _e( 'Get Pro', 'ballerburg9005' ); ?>" />
                                                                <input type="hidden" name="bws_plugin_action_submit" value="submit" />
                                                                <input type="hidden" name="bws_install_plugin" value="<?php echo $value_plugin['pro_version']; ?>" />
																<?php wp_nonce_field( plugin_basename(__FILE__), 'bws_license_install_nonce_name' ); ?>
                                                            </form>
														<?php } else { ?>
                                                            <a class="button button-secondary bws_upgrade_button" href="<?php echo esc_url( $bws_plugins[ $key_plugin ]['purchase'] ); ?>" target="_blank"><?php _e( 'Upgrade to Pro', 'ballerburg9005' ); ?></a>
														<?php }
													}
												} else { ?>
                                                    <a class="bws_donate" href="https://ballerburg9005.com/donate/" target="_blank"><?php _e( 'Donate', 'ballerburg9005' ); ?></a> <span>|</span>
												<?php }

												if ( $is_pro_active ) { ?>
                                                    <a class="bws_settings" href="<?php echo esc_url( self_admin_url( $bws_plugins[ $key_plugin ]["pro_settings"] ) ); ?>"><?php _e( 'Settings', 'ballerburg9005' ); ?></a>
												<?php } else { ?>
                                                    <a class="bws_settings" href="<?php echo esc_url( self_admin_url( $bws_plugins[ $key_plugin ]["settings"] ) ); ?>"><?php _e( 'Settings', 'ballerburg9005' ); ?></a>
												<?php }
											} else {
												if ( $is_pro_installed ) { ?>
                                                    <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( self_admin_url( $current_page . '&bws_activate_plugin=' . $value_plugin['pro_version'] ), 'bws_activate_plugin' . $value_plugin['pro_version'] ) ); ?>" title="<?php _e( 'Activate this plugin', 'ballerburg9005' ); ?>"><?php _e( 'Activate', 'ballerburg9005' ); ?></a>
												<?php } elseif ( ! empty( $plugins_array ) && isset( $value_plugin['pro_version'] ) && array_key_exists( $value_plugin['pro_version'], $plugins_array ) ) { ?>
                                                    <form method="post" action="">
                                                        <input type="submit" class="button button-secondary" value="<?php _e( 'Get Pro', 'ballerburg9005' ); ?>" />
                                                        <input type="hidden" name="bws_plugin_action_submit" value="submit" />
                                                        <input type="hidden" name="bws_install_plugin" value="<?php echo $value_plugin['pro_version']; ?>" />
														<?php wp_nonce_field( plugin_basename(__FILE__), 'bws_license_install_nonce_name' ); ?>
                                                    </form>
												<?php } elseif ( $is_installed ) { ?>
                                                    <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( self_admin_url( $current_page . '&bws_activate_plugin=' . $key_plugin ), 'bws_activate_plugin' . $key_plugin ) ); ?>" title="<?php _e( 'Activate this plugin', 'ballerburg9005' ); ?>"><?php _e( 'Activate', 'ballerburg9005' ); ?></a>
												<?php } else {
													$install_url = isset( $value_plugin['install_url'] ) ? $value_plugin['install_url'] : network_admin_url( 'plugin-install.php?tab=search&type=term&s=' . str_replace( array( ' ', '-' ), '+', str_replace( '&', '', $value_plugin['name'] ) ) . '+ballerburg9005&plugin-search-input=Search+Plugins' ); ?>
                                                    <a class="button button-secondary" href="<?php echo esc_url( $install_url ); ?>" title="<?php _e( 'Install this plugin', 'ballerburg9005' ); ?>" target="_blank"><?php _e( 'Install Now', 'ballerburg9005' ); ?></a>
												<?php }
											} ?>
                                        </div>
                                    </div>
                                    <div class="clear"></div>
                                </div>
							<?php }
							if ( $nothing_found ) { ?>
                                <p class="description"><?php _e( 'Nothing found. Try another criteria.', 'ballerburg9005' ); ?></p>
							<?php } ?>
                        </div>
                        <div id="bws-filter-wrapper">
                            <div class="bws-filter">
                                <div class="bws-filter-title"><?php _e( 'Category', 'ballerburg9005' ); ?></div>
                                <ul class="bws-category">
                                    <li>
										<?php $sub_in_url = ( isset( $_GET['sub'] ) && in_array( $_GET['sub'], array( 'installed', 'not_installed' ) ) ) ? '&sub=' . $_GET['sub'] : ''; ?>
                                        <a <?php if ( 'all' == $plugin_category ) echo ' class="bws-active"'; ?> href="<?php echo esc_url( self_admin_url( $current_page . $sub_in_url ) ); ?>"><?php _e( 'All', 'ballerburg9005' ); ?>
                                            <span>(<?php echo count( $bws_plugins ); ?>)</span>
                                        </a>
                                    </li>
									<?php foreach ( $bws_plugins_category as $category_key => $category_value ) { ?>
                                        <li>
                                            <a <?php if ( $category_key == $plugin_category ) echo ' class="bws-active"'; ?> href="<?php echo esc_url( self_admin_url( $current_page . $sub_in_url . '&category=' . $category_key ) ); ?>"><?php echo $category_value['name']; ?>
                                                <span>(<?php echo $category_value['count']; ?>)</span>
                                            </a>
                                        </li>
									<?php } ?>
                                </ul>
                            </div>
                        </div><!-- #bws-filter-wrapper -->
                        <div class="clear"></div>
					<?php }
				} elseif ( 'bws_themes' == $page || 'themes' == $tab ) {
					require( dirname( __FILE__ ) . '/product_list.php' ); ?>
                    <h1><?php _e( 'Themes', 'ballerburg9005' ); ?></h1>
                    <div id="availablethemes" class="bws-availablethemes">
                        <div class="theme-browser content-filterable rendered">
                            <div class="themes wp-clearfix">
								<?php foreach ( $themes as $key => $theme ) {
									$installed_theme = wp_get_theme( $theme->slug ); ?>
                                    <div class="theme" tabindex="0">
                                        <div class="theme-screenshot">
                                            <img src="<?php echo bws_menu_url( "icons/themes/" ) . $theme->slug . '.png'; ?>" alt="" />
                                        </div>
                                        <div class="theme-author"><?php printf( __( 'By %s', 'ballerburg9005' ), 'ballerburg9005' ); ?></div>
                                        <h3 class="theme-name"><?php echo $theme->name; ?></h3>
                                        <div class="theme-actions">
                                            <a class="button button-secondary preview install-theme-preview" href="<?php echo esc_url( $theme->href ); ?>" target="_blank"><?php _e( 'Learn More', 'ballerburg9005' ); ?></a>
                                        </div>
										<?php if ( $installed_theme->exists() ) {
											if ( $wp_version < '4.6' ) { ?>
                                                <div class="theme-installed"><?php _e( 'Already Installed', 'ballerburg9005' ); ?></div>
											<?php } else { ?>
                                                <div class="notice notice-success notice-alt inline"><p><?php _e( 'Installed', 'ballerburg9005' ); ?></p></div>
											<?php }
										} ?>
                                    </div>
								<?php } ?>
                                <br class="clear" />
                            </div>
                        </div>
                        <p><a class="bws_browse_link" href="https://ballerburg9005.com/products/wordpress/themes/" target="_blank"><?php _e( 'Browse More WordPress Themes', 'ballerburg9005' ); ?> <span class="dashicons dashicons-arrow-right-alt2"></span></a></p>
                    </div>
				<?php } elseif ( 'bws_system_status' == $page || 'system-status' == $tab ) { ?>
                    <h1><?php _e( 'System status', 'ballerburg9005' ); ?></h1>
                    <div class="updated fade notice is-dismissible inline" <?php if ( ! ( isset( $_REQUEST['bwsmn_form_submit'] ) || isset( $_REQUEST['bwsmn_form_submit_custom_email'] ) ) || $error != "" ) echo 'style="display:none"'; ?>><p><strong><?php echo $message; ?></strong></p></div>
                    <div class="error" <?php if ( "" == $error ) echo 'style="display:none"'; ?>><p><strong><?php echo $error; ?></strong></p></div>
                    <form method="post" action="">
                        <p>
                            <input type="hidden" name="bwsmn_form_submit" value="submit" />
                            <input type="submit" class="button-primary" value="<?php _e( 'Send to support', 'ballerburg9005' ) ?>" />
							<?php wp_nonce_field( plugin_basename(__FILE__), 'bwsmn_nonce_submit' ); ?>
                        </p>
                    </form>
                    <form method="post" action="">
                        <p>
                            <input type="hidden" name="bwsmn_form_submit_custom_email" value="submit" />
                            <input type="submit" class="button" value="<?php _e( 'Send to custom email &#187;', 'ballerburg9005' ) ?>" />
                            <input type="text" maxlength="250" value="" name="bwsmn_form_email" />
							<?php wp_nonce_field( plugin_basename(__FILE__), 'bwsmn_nonce_submit_custom_email' ); ?>
                        </p>
                    </form>
					<?php foreach ( $system_info as $info ) { ?>
                        <table class="widefat bws-system-info" cellspacing="0">
                            <thead>
                            <tr>
                                <th colspan="2">
                                    <strong>
										<?php echo $info['name'];
										if ( isset( $info['count'] ) )
											echo ' (' . $info['count'] . ')'; ?>
                                    </strong>
                                </th>
                            </tr>
                            </thead>
                            <tbody>
							<?php foreach ( $info['data'] as $key => $value ) { ?>
                                <tr>
                                    <td scope="row"><?php echo $key; ?></td>
                                    <td scope="row"><?php echo $value; ?></td>
                                </tr>
							<?php } ?>
                            </tbody>
                        </table>
					<?php }
				} ?>
            </div>
        </div>
	<?php }
}

if ( ! function_exists( 'bws_get_banner_array' ) ) {
	function bws_get_banner_array() {
		global $bstwbsftwppdtplgns_banner_array;
		$bstwbsftwppdtplgns_banner_array = array(
			array( 'gglstpvrfctn_hide_banner_on_plugin_page', 'bws-google-2-step-verification/bws-google-2-step-verification.php', '1.0.0' ),
			array( 'sclbttns_hide_banner_on_plugin_page', 'social-buttons-pack/social-buttons-pack.php', '1.1.0' ),
			array( 'tmsht_hide_banner_on_plugin_page', 'timesheet/timesheet.php', '0.1.3' ),
			array( 'pgntn_hide_banner_on_plugin_page', 'pagination/pagination.php', '1.0.6' ),
			array( 'crrntl_hide_banner_on_plugin_page', 'car-rental/car-rental.php', '1.0.0' ),
			array( 'lnkdn_hide_banner_on_plugin_page', 'bws-linkedin/bws-linkedin.php', '1.0.1' ),
			array( 'pntrst_hide_banner_on_plugin_page', 'bws-pinterest/bws-pinterest.php', '1.0.1' ),
			array( 'zndskhc_hide_banner_on_plugin_page', 'zendesk-help-center/zendesk-help-center.php', '1.0.0' ),
			array( 'gglcptch_hide_banner_on_plugin_page', 'google-captcha/google-captcha.php', '1.18' ),
			array( 'mltlngg_hide_banner_on_plugin_page', 'multilanguage/multilanguage.php', '1.1.1' ),
			array( 'adsns_hide_banner_on_plugin_page', 'adsense-plugin/adsense-plugin.php', '1.36' ),
			array( 'vstrsnln_hide_banner_on_plugin_page', 'visitors-online/visitors-online.php', '0.2' ),
			array( 'cstmsrch_hide_banner_on_plugin_page', 'custom-search-plugin/custom-search-plugin.php', '1.28' ),
			array( 'prtfl_hide_banner_on_plugin_page', 'portfolio/portfolio.php', '2.33' ),
			array( 'rlt_hide_banner_on_plugin_page', 'realty/realty.php', '1.0.0' ),
			array( 'prmbr_hide_banner_on_plugin_page', 'promobar/promobar.php', '1.0.0' ),
			array( 'gglnltcs_hide_banner_on_plugin_page', 'bws-google-analytics/bws-google-analytics.php', '1.6.2' ),
			array( 'htccss_hide_banner_on_plugin_page', 'htaccess/htaccess.php', '1.6.3' ),
			array( 'sbscrbr_hide_banner_on_plugin_page', 'subscriber/subscriber.php', '1.1.8' ),
			array( 'lmtttmpts_hide_banner_on_plugin_page', 'limit-attempts/limit-attempts.php', '1.0.2' ),
			array( 'sndr_hide_banner_on_plugin_page', 'sender/sender.php', '0.5' ),
			array( 'srrl_hide_banner_on_plugin_page', 'user-role/user-role.php', '1.4' ),
			array( 'pdtr_hide_banner_on_plugin_page', 'updater/updater.php', '1.12' ),
			array( 'cntctfrmtdb_hide_banner_on_plugin_page', 'contact-form-to-db/contact_form_to_db.php', '1.2' ),
			array( 'cntctfrmmlt_hide_banner_on_plugin_page', 'contact-form-multi/contact-form-multi.php', '1.0.7' ),
			array( 'gglmps_hide_banner_on_plugin_page', 'bws-google-maps/bws-google-maps.php', '1.2' ),
			array( 'fcbkbttn_hide_banner_on_plugin_page', 'facebook-button-plugin/facebook-button-plugin.php', '2.29' ),
			array( 'twttr_hide_banner_on_plugin_page', 'twitter-plugin/twitter.php', '2.34' ),
			array( 'pdfprnt_hide_banner_on_plugin_page', 'pdf-print/pdf-print.php', '1.7.1' ),
			array( 'gglstmp_hide_banner_on_plugin_page', 'google-sitemap-plugin/google-sitemap-plugin.php', '2.8.4' ),
			array( 'cntctfrmpr_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-pro/contact_form_pro.php', '1.14' ),
			array( 'cntctfrm_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.47' ),
			array( 'cptch_hide_banner_on_plugin_page', 'captcha-bws/captcha-bws.php', '3.8.4' ),
			array( 'gllr_hide_banner_on_plugin_page', 'gallery-plugin/gallery-plugin.php', '3.9.1' ),
			array( 'cntctfrm_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.62' ),
            array( 'bwscrrntl_hide_banner_on_plugin_page', 'bws-car-rental/bws-car-rental.php', '0.0.1' ),
			array( 'rtng_hide_banner_on_plugin_page', 'rating-bws/rating-bws.php', '1.0.0' ),
			array( 'prflxtrflds_hide_banner_on_plugin_page', 'profile-extra-fields/profile-extra-fields.php', '1.1.3' ),
			array( 'psttcsv_hide_banner_on_plugin_page', 'post-to-csv/post-to-csv.php', '1.3.4' ),
			array( 'cstmdmnpg_hide_banner_on_plugin_page', 'custom-admin-page/custom-admin-page.php', '1.0.0' )
		);
	}
}
