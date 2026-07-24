<?php
/**
 * Header Navigation login/profile widget class
 * Registers shortcode, Elementor widget, and Gutenberg block with complete custom styles.
 *
 * @since      1.7.0
 * @package    DLM
 * @subpackage DLM/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DLM_Header_Nav {

	/**
	 * Register actions & shortcodes
	 */
	public function init() {
		// Register shortcode
		add_shortcode( 'dlm_header_nav', array( $this, 'render_shortcode' ) );

		// Register Elementor Widget
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );

		// Register Gutenberg Block
		add_action( 'init', array( $this, 'register_gutenberg_block' ) );

		// Enqueue script for notification dropdowns
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_nav_script' ) );
	}

	/**
	 * Enqueue inline JS helper for toggle transitions
	 */
	public function enqueue_nav_script() {
		$js = "
			jQuery(document).ready(function($) {
				$('body').on('click', '.dlm-notif-bell-btn', function(e) {
					e.stopPropagation();
					var \$dropdown = $(this).siblings('.dlm-notif-dropdown');
					$('.dlm-notif-dropdown').not(\$dropdown).removeClass('show');
					\$dropdown.toggleClass('show');
				});
				$('body').on('click', function(e) {
					if (!$(e.target).closest('.dlm-notif-bell-container').length) {
						$('.dlm-notif-dropdown').removeClass('show');
					}
				});
			});
		";
		wp_add_inline_script( 'jquery', $js );
	}

	/**
	 * Get dashboard link permalink
	 */
	public static function get_dashboard_url() {
		$page_id = get_option( 'dlm_account_page_id' );
		if ( $page_id ) {
			return get_permalink( $page_id );
		}
		return home_url( '/library-account/' );
	}

	/**
	 * Compile dynamic alerts list from user achievements state and active status
	 */
	public static function get_user_notifications( $user_id ) {
		$notifications = array();
		if ( ! $user_id ) {
			return $notifications;
		}

		$ach_raw = get_user_meta( $user_id, 'dlm_achievements_state', true );
		if ( $ach_raw ) {
			$ach = json_decode( $ach_raw, true );
			if ( is_array( $ach ) && ! empty( $ach['badges'] ) ) {
				foreach ( $ach['badges'] as $b ) {
					$notifications[] = array(
						'title' => sprintf( __( 'Earned Badge: %s', 'digital-library-membership' ), $b['label'] ),
						'time'  => isset( $b['earned'] ) ? $b['earned'] : '',
						'type'  => 'badge',
					);
				}
			}
			if ( is_array( $ach ) && isset( $ach['streak'] ) && $ach['streak'] > 0 ) {
				$notifications[] = array(
					'title' => sprintf( __( 'Reading Streak: %d Days', 'digital-library-membership' ), $ach['streak'] ),
					'time'  => gmdate( 'Y-m-d' ),
					'type'  => 'streak',
				);
			}
		}

		$db = new DLM_DB();
		$sub = $db->get_subscription_by_user( $user_id );
		if ( $sub ) {
			$notifications[] = array(
				'title' => sprintf( __( 'Subscription status is %s', 'digital-library-membership' ), strtoupper( $sub->status ) ),
				'time'  => isset( $sub->updated_at ) ? $sub->updated_at : '',
				'type'  => 'membership',
			);
		}

		return array_slice( $notifications, 0, 5 );
	}

	/**
	 * Render shortcode
	 */
	public function render_shortcode( $atts ) {
		$a = shortcode_atts( array(
			'text_color'                 => '',
			'bg_color'                   => '',
			'avatar_size'                => '36px',
			'spacing'                    => '16px',
			'margin'                     => '0',
			'padding'                    => '0',
			'avatar_padding'             => '',
			'avatar_margin'              => '',
			'avatar_border_radius'       => '',
			'avatar_border_width'        => '',
			'avatar_border_color'        => '',
			'avatar_border_style'        => '',
			'bell_color'                 => '',
			'bell_hover_color'           => '',
			'bell_size'                  => '',
			'badge_bg_color'             => '',
			'badge_size'                 => '',
			'badge_horizontal_position'  => '',
			'badge_vertical_position'    => '',
			'dropdown_bg_color'          => '',
			'dropdown_border_radius'     => '',
			'dropdown_header_text_color' => '',
			'dropdown_item_title_color'  => '',
			'dropdown_item_time_color'   => '',
			'dropdown_item_hover_bg'     => '',
		), $atts );

		$style_container = '';
		if ( ! empty( $a['bg_color'] ) ) {
			$style_container .= 'background-color: ' . esc_attr( $a['bg_color'] ) . '; ';
		}
		if ( ! empty( $a['margin'] ) ) {
			$style_container .= 'margin: ' . esc_attr( $a['margin'] ) . '; ';
		}
		if ( ! empty( $a['padding'] ) ) {
			$style_container .= 'padding: ' . esc_attr( $a['padding'] ) . '; ';
		}

		$uniq_id = uniqid( 'dlm-nav-' );

		ob_start();
		?>
		<!-- External Dependencies to guarantee same-to-same display in theme headers -->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
		
		<style>
			/* Base layout structure and defaults (without !important to allow page builders to override) */
			.dlm-header-nav-container {
				display: inline-flex;
				align-items: center;
				font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
				box-sizing: border-box;
			}
			.dlm-header-nav-container *, 
			.dlm-header-nav-container *::before, 
			.dlm-header-nav-container *::after {
				box-sizing: border-box;
			}
			.dlm-header-user-wrapper {
				display: flex;
				align-items: center;
				position: relative;
			}
			.dlm-notif-bell-container {
				position: relative;
				display: inline-block;
			}
			.dlm-notif-bell-btn {
				border: none;
				outline: none;
				background: transparent;
				cursor: pointer;
				padding: 8px;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				color: #5f5e60;
				transition: all 0.2s ease-in-out;
				margin: 0;
				width: 38px;
				height: 38px;
			}
			.dlm-notif-bell-btn:hover {
				background-color: #f2f2f3;
				color: #855300;
			}
			.dlm-notif-bell-btn .material-symbols-outlined {
				font-family: 'Material Symbols Outlined' !important;
				font-size: 22px;
				font-style: normal;
				line-height: 1;
				display: inline-block;
			}
			.dlm-notif-badge {
				position: absolute;
				top: 6px;
				right: 6px;
				width: 10px;
				height: 10px;
				background-color: #855300;
				border-radius: 50%;
				border: 2px solid #ffffff;
			}
			.dlm-notif-dropdown {
				display: none;
				position: absolute;
				right: 0;
				top: 46px;
				width: 320px;
				background-color: #ffffff;
				border: 1px solid rgba(0, 0, 0, 0.08);
				border-radius: 16px;
				box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
				z-index: 999999;
				padding: 12px 0;
				font-family: inherit;
			}
			.dlm-notif-dropdown.show {
				display: block !important;
			}
			.dlm-notif-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 0 16px 8px 16px;
				border-bottom: 1px solid #f2f2f3;
			}
			.dlm-notif-header-title {
				font-size: 11px;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: 0.05em;
				color: #1a1c1c;
				margin: 0;
			}
			.dlm-notif-header-count {
				font-size: 10px;
				font-weight: 600;
				background-color: #f2f2f3;
				color: #5f5e60;
				padding: 2px 8px;
				border-radius: 99px;
			}
			.dlm-notif-list {
				max-height: 240px;
				overflow-y: auto;
				padding: 8px;
				display: flex;
				flex-direction: column;
				gap: 4px;
			}
			.dlm-notif-item {
				display: flex;
				align-items: flex-start;
				gap: 12px;
				padding: 10px 12px;
				border-radius: 10px;
				transition: background-color 0.2s ease;
				cursor: pointer;
				text-decoration: none;
			}
			.dlm-notif-item:hover {
				background-color: #f5f5f7;
			}
			.dlm-notif-icon-box {
				color: #855300;
				margin-top: 2px;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.dlm-notif-icon-box .material-symbols-outlined {
				font-family: 'Material Symbols Outlined' !important;
				font-size: 18px;
			}
			.dlm-notif-info {
				flex: 1;
			}
			.dlm-notif-title {
				font-size: 12px;
				font-weight: 600;
				color: #1a1c1c;
				line-height: 1.4;
				margin: 0 0 2px 0;
				text-align: left;
			}
			.dlm-notif-time {
				font-size: 10px;
				color: #9ca3af;
				display: block;
				text-align: left;
			}
			.dlm-notif-empty {
				padding: 24px 16px;
				text-align: center;
				color: #9ca3af;
			}
			.dlm-notif-empty-icon {
				font-family: 'Material Symbols Outlined' !important;
				font-size: 32px;
				margin-bottom: 4px;
				opacity: 0.5;
				display: block;
			}
			.dlm-notif-empty p {
				font-size: 12px;
				margin: 0;
			}
			.dlm-profile-link {
				display: flex;
				align-items: center;
				cursor: pointer;
				transition: transform 0.2s ease;
				text-decoration: none !important;
			}
			.dlm-profile-link:hover {
				transform: translateY(-1px);
			}
			.dlm-user-first-name {
				font-size: 14px;
				font-weight: 700;
				color: #653e00;
				margin: 0;
				transition: color 0.2s ease;
			}
			.dlm-profile-link:hover .dlm-user-first-name {
				color: #855300;
			}
			.dlm-user-avatar {
				border-radius: 50%;
				object-fit: cover;
				border: 1px solid rgba(0, 0, 0, 0.08);
				display: block;
			}
			.dlm-header-signin-btn {
				background-color: #855300;
				color: #ffffff;
				font-weight: 700;
				font-size: 12px;
				padding: 10px 20px;
				border-radius: 99px;
				transition: all 0.2s ease;
				text-decoration: none;
				display: inline-block;
				box-shadow: 0 2px 8px rgba(133, 83, 0, 0.15);
				border: none;
			}
			.dlm-header-signin-btn:hover {
				background-color: #613b00;
				transform: translateY(-1px);
			}

			/* Scoped Overrides (Applied with !important only if set by page builder / attributes) */
			<?php if ( ! empty( $a['text_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-first-name { color: <?php echo esc_attr( $a['text_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['bg_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?>.dlm-header-nav-container { background-color: <?php echo esc_attr( $a['bg_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['avatar_size'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-avatar { 
					width: <?php echo esc_attr( $a['avatar_size'] ); ?> !important; 
					height: <?php echo esc_attr( $a['avatar_size'] ); ?> !important; 
				}
			<?php endif; ?>
			<?php if ( ! empty( $a['avatar_padding'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-avatar { padding: <?php echo esc_attr( $a['avatar_padding'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['avatar_margin'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-avatar { margin: <?php echo esc_attr( $a['avatar_margin'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['avatar_border_radius'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-avatar { border-radius: <?php echo esc_attr( $a['avatar_border_radius'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['avatar_border_width'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-avatar { border-width: <?php echo esc_attr( $a['avatar_border_width'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['avatar_border_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-avatar { border-color: <?php echo esc_attr( $a['avatar_border_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['avatar_border_style'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-avatar { border-style: <?php echo esc_attr( $a['avatar_border_style'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['bell_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-bell-btn { color: <?php echo esc_attr( $a['bell_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['bell_hover_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-bell-btn:hover { color: <?php echo esc_attr( $a['bell_hover_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['bell_size'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-bell-btn .material-symbols-outlined { font-size: <?php echo esc_attr( $a['bell_size'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['badge_bg_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-badge { background-color: <?php echo esc_attr( $a['badge_bg_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['badge_size'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-badge { width: <?php echo esc_attr( $a['badge_size'] ); ?> !important; height: <?php echo esc_attr( $a['badge_size'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['badge_vertical_position'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-badge { top: <?php echo esc_attr( $a['badge_vertical_position'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['badge_horizontal_position'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-badge { right: <?php echo esc_attr( $a['badge_horizontal_position'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['dropdown_bg_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-dropdown { background-color: <?php echo esc_attr( $a['dropdown_bg_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['dropdown_border_radius'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-dropdown { border-radius: <?php echo esc_attr( $a['dropdown_border_radius'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['dropdown_header_text_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-header-title { color: <?php echo esc_attr( $a['dropdown_header_text_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['dropdown_item_title_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-title { color: <?php echo esc_attr( $a['dropdown_item_title_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['dropdown_item_time_color'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-time { color: <?php echo esc_attr( $a['dropdown_item_time_color'] ); ?> !important; }
			<?php endif; ?>
			<?php if ( ! empty( $a['dropdown_item_hover_bg'] ) ) : ?>
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-item:hover { background-color: <?php echo esc_attr( $a['dropdown_item_hover_bg'] ); ?> !important; }
			<?php endif; ?>
			
			/* Responsive adjustments */
			@media (max-width: 640px) {
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-user-first-name {
					display: none; /* Hide name text on smaller viewports */
				}
				#<?php echo esc_attr( $uniq_id ); ?> .dlm-notif-dropdown {
					right: -80px; /* Shift dropdown box so it doesn't clip off mobile borders */
				}
			}
		</style>

		<div id="<?php echo esc_attr( $uniq_id ); ?>" class="dlm-header-nav-container" style="<?php echo esc_attr( $style_container ); ?>">
			<?php if ( is_user_logged_in() ) : ?>
				<?php 
				$user_id = get_current_user_id();
				$wp_user = wp_get_current_user();
				$first_name = ! empty( $wp_user->first_name ) ? $wp_user->first_name : $wp_user->display_name;
				
				$avatar_url = get_user_meta( $user_id, 'dlm_avatar_url', true );
				if ( ! $avatar_url ) {
					$avatar_url = get_avatar_url( $user_id, array( 'size' => 64 ) );
				}

				$notifications = self::get_user_notifications( $user_id );
				$notif_count = count( $notifications );
				?>
				<div class="dlm-header-user-wrapper" style="gap: <?php echo esc_attr( $a['spacing'] ); ?>;">
					<!-- Notifications Bell -->
					<div class="dlm-notif-bell-container">
						<button type="button" class="dlm-notif-bell-btn">
							<span class="material-symbols-outlined">notifications</span>
							<?php if ( $notif_count > 0 ) : ?>
								<span class="dlm-notif-badge"></span>
							<?php endif; ?>
						</button>
						
						<!-- Dropdown container -->
						<div class="dlm-notif-dropdown">
							<div class="dlm-notif-header">
								<h5 class="dlm-notif-header-title"><?php esc_html_e( 'Alerts & Activity', 'digital-library-membership' ); ?></h5>
								<span class="dlm-notif-header-count"><?php echo intval( $notif_count ); ?></span>
							</div>
							<div class="dlm-notif-list">
								<?php if ( empty( $notifications ) ) : ?>
									<div class="dlm-notif-empty">
										<span class="dlm-notif-empty-icon">notifications_off</span>
										<p><?php esc_html_e( 'No new alerts', 'digital-library-membership' ); ?></p>
									</div>
								<?php else : ?>
									<?php foreach ( $notifications as $notif ) : ?>
										<a href="<?php echo esc_url( self::get_dashboard_url() ); ?>" class="dlm-notif-item">
											<div class="dlm-notif-icon-box">
												<span class="material-symbols-outlined">
													<?php 
													if ( $notif['type'] === 'badge' ) echo 'military_tech';
													elseif ( $notif['type'] === 'streak' ) echo 'local_fire_department';
													else echo 'loyalty';
													?>
												</span>
											</div>
											<div class="dlm-notif-info">
												<h6 class="dlm-notif-title"><?php echo esc_html( $notif['title'] ); ?></h6>
												<span class="dlm-notif-time"><?php echo esc_html( $notif['time'] ); ?></span>
											</div>
										</a>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<!-- Profile Link -->
					<a href="<?php echo esc_url( self::get_dashboard_url() ); ?>" class="dlm-profile-link" style="gap: 10px;">
						<span class="dlm-user-first-name" style="<?php if(!empty($a['text_color'])) echo 'color: '.esc_attr($a['text_color']).' !important;'; ?>">
							<?php echo esc_html( $first_name ); ?>
						</span>
						<img class="dlm-user-avatar" src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $first_name ); ?>" style="width: <?php echo esc_attr( $a['avatar_size'] ); ?>; height: <?php echo esc_attr( $a['avatar_size'] ); ?>;">
					</a>
				</div>
			<?php else : ?>
				<a href="<?php echo esc_url( self::get_dashboard_url() ); ?>" class="dlm-header-signin-btn" style="<?php if(!empty($a['text_color'])) echo 'color: '.esc_attr($a['text_color']).' !important;'; ?> <?php if(!empty($a['bg_color'])) echo 'background-color: '.esc_attr($a['bg_color']).' !important;'; ?>">
					<?php esc_html_e( 'Sign In', 'digital-library-membership' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Register Elementor widget
	 */
	public function register_elementor_widget( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		require_once DLM_PATH . 'includes/class-dlm-elementor-header-nav.php';
		$widgets_manager->register( new DLM_Elementor_Header_Nav() );
	}

	/**
	 * Register Gutenberg Server-Rendered Block
	 */
	public function register_gutenberg_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type( 'dlm/header-nav', array(
			'render_callback' => array( $this, 'render_gutenberg_block' ),
			'attributes'      => array(
				'text_color'   => array( 'type' => 'string', 'default' => '' ),
				'bg_color'     => array( 'type' => 'string', 'default' => '' ),
				'avatar_size'  => array( 'type' => 'string', 'default' => '36px' ),
				'spacing'      => array( 'type' => 'string', 'default' => '16px' ),
				'margin'       => array( 'type' => 'string', 'default' => '0' ),
				'padding'      => array( 'type' => 'string', 'default' => '0' ),
			),
		) );
	}

	/**
	 * Gutenberg render callback
	 */
	public function render_gutenberg_block( $attributes ) {
		return $this->render_shortcode( $attributes );
	}
}
