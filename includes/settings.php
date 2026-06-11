<?php
/**
 * WordPress MCP Connector - Settings Management
 *
 * Provides admin settings page to control which abilities are enabled
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WMC_Settings {

	/**
	 * Initialize settings
	 */
	public static function init() {
		add_action( 'admin_menu', array( self::class, 'add_admin_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
	}

	/**
	 * Add admin menu
	 */
	public static function add_admin_menu() {
		add_menu_page(
			'WordPress MCP Connector',
			'MCP Connector',
			'manage_options',
			'wmc-settings',
			array( self::class, 'render_settings_page' ),
			'dashicons-admin-tools',
			99
		);
	}

	/**
	 * Register settings
	 */
	public static function register_settings() {
		register_setting( 'wmc_settings', 'wmc_abilities_config' );
	}

	/**
	 * Render settings page
	 */
	public static function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'wmc_settings' ); ?>

				<div class="wmc-settings-container">
					<h2>📝 Posts Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_posts_read">
									<input type="checkbox" id="wmc_posts_read" name="wmc_abilities_config[posts][read]" value="1"
										<?php checked( self::is_ability_enabled( 'posts', 'read' ), true ); ?> />
									Read Posts (Get)
								</label>
							</th>
							<td>Allow users to retrieve and read posts</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_posts_create">
									<input type="checkbox" id="wmc_posts_create" name="wmc_abilities_config[posts][create]" value="1"
										<?php checked( self::is_ability_enabled( 'posts', 'create' ), true ); ?> />
									Create Posts
								</label>
							</th>
							<td>Allow creation of new posts</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_posts_update">
									<input type="checkbox" id="wmc_posts_update" name="wmc_abilities_config[posts][update]" value="1"
										<?php checked( self::is_ability_enabled( 'posts', 'update' ), true ); ?> />
									Update Posts
								</label>
							</th>
							<td>Allow modification of existing posts</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_posts_delete">
									<input type="checkbox" id="wmc_posts_delete" name="wmc_abilities_config[posts][delete]" value="1"
										<?php checked( self::is_ability_enabled( 'posts', 'delete' ), true ); ?> />
									Delete Posts ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow permanent deletion of posts</td>
						</tr>
					</table>

					<h2>📄 Pages Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_pages_read">
									<input type="checkbox" id="wmc_pages_read" name="wmc_abilities_config[pages][read]" value="1"
										<?php checked( self::is_ability_enabled( 'pages', 'read' ), true ); ?> />
									Read Pages (Get)
								</label>
							</th>
							<td>Allow users to retrieve and read pages</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_pages_create">
									<input type="checkbox" id="wmc_pages_create" name="wmc_abilities_config[pages][create]" value="1"
										<?php checked( self::is_ability_enabled( 'pages', 'create' ), true ); ?> />
									Create Pages
								</label>
							</th>
							<td>Allow creation of new pages</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_pages_update">
									<input type="checkbox" id="wmc_pages_update" name="wmc_abilities_config[pages][update]" value="1"
										<?php checked( self::is_ability_enabled( 'pages', 'update' ), true ); ?> />
									Update Pages
								</label>
							</th>
							<td>Allow modification of existing pages</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_pages_delete">
									<input type="checkbox" id="wmc_pages_delete" name="wmc_abilities_config[pages][delete]" value="1"
										<?php checked( self::is_ability_enabled( 'pages', 'delete' ), true ); ?> />
									Delete Pages ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow permanent deletion of pages</td>
						</tr>
					</table>

					<h2>🏷️ Categories & Tags Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_categories_read">
									<input type="checkbox" id="wmc_categories_read" name="wmc_abilities_config[categories][read]" value="1"
										<?php checked( self::is_ability_enabled( 'categories', 'read' ), true ); ?> />
									Read Categories (Get)
								</label>
							</th>
							<td>Allow retrieving categories</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_categories_write">
									<input type="checkbox" id="wmc_categories_write" name="wmc_abilities_config[categories][write]" value="1"
										<?php checked( self::is_ability_enabled( 'categories', 'write' ), true ); ?> />
									Create/Update/Delete Categories ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow full management of categories</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_tags_read">
									<input type="checkbox" id="wmc_tags_read" name="wmc_abilities_config[tags][read]" value="1"
										<?php checked( self::is_ability_enabled( 'tags', 'read' ), true ); ?> />
									Read Tags (Get)
								</label>
							</th>
							<td>Allow retrieving tags</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_tags_write">
									<input type="checkbox" id="wmc_tags_write" name="wmc_abilities_config[tags][write]" value="1"
										<?php checked( self::is_ability_enabled( 'tags', 'write' ), true ); ?> />
									Create/Update/Delete Tags ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow full management of tags</td>
						</tr>
					</table>

					<h2>🖼️ Media Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_media_read">
									<input type="checkbox" id="wmc_media_read" name="wmc_abilities_config[media][read]" value="1"
										<?php checked( self::is_ability_enabled( 'media', 'read' ), true ); ?> />
									Read Media (Get)
								</label>
							</th>
							<td>Allow retrieving media files</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_media_write">
									<input type="checkbox" id="wmc_media_write" name="wmc_abilities_config[media][write]" value="1"
										<?php checked( self::is_ability_enabled( 'media', 'write' ), true ); ?> />
									Create/Update/Delete Media ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow full management of media files</td>
						</tr>
					</table>

					<h2>💬 Comments Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_comments_read">
									<input type="checkbox" id="wmc_comments_read" name="wmc_abilities_config[comments][read]" value="1"
										<?php checked( self::is_ability_enabled( 'comments', 'read' ), true ); ?> />
									Read Comments (Get)
								</label>
							</th>
							<td>Allow retrieving comments</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_comments_moderate">
									<input type="checkbox" id="wmc_comments_moderate" name="wmc_abilities_config[comments][moderate]" value="1"
										<?php checked( self::is_ability_enabled( 'comments', 'moderate' ), true ); ?> />
									Moderate Comments
								</label>
							</th>
							<td>Allow approving/unapproving comments</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_comments_delete">
									<input type="checkbox" id="wmc_comments_delete" name="wmc_abilities_config[comments][delete]" value="1"
										<?php checked( self::is_ability_enabled( 'comments', 'delete' ), true ); ?> />
									Delete Comments ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow permanent deletion of comments</td>
						</tr>
					</table>

					<h2>👤 Users Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_users_read">
									<input type="checkbox" id="wmc_users_read" name="wmc_abilities_config[users][read]" value="1"
										<?php checked( self::is_ability_enabled( 'users', 'read' ), true ); ?> />
									Read Users (Get)
								</label>
							</th>
							<td>Allow retrieving user list</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_users_write">
									<input type="checkbox" id="wmc_users_write" name="wmc_abilities_config[users][write]" value="1"
										<?php checked( self::is_ability_enabled( 'users', 'write' ), true ); ?> />
									Create/Update/Delete Users ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow full management of users</td>
						</tr>
					</table>

					<h2>⚙️ Settings Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_settings_read">
									<input type="checkbox" id="wmc_settings_read" name="wmc_abilities_config[settings][read]" value="1"
										<?php checked( self::is_ability_enabled( 'settings', 'read' ), true ); ?> />
									Read Settings (Get)
								</label>
							</th>
							<td>Allow retrieving WordPress settings</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_settings_write">
									<input type="checkbox" id="wmc_settings_write" name="wmc_abilities_config[settings][write]" value="1"
										<?php checked( self::is_ability_enabled( 'settings', 'write' ), true ); ?> />
									Update Settings ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow modification of WordPress settings (General, Reading, Writing, Discussion)</td>
						</tr>
					</table>

					<h2>📋 Menus Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_menus_read">
									<input type="checkbox" id="wmc_menus_read" name="wmc_abilities_config[menus][read]" value="1"
										<?php checked( self::is_ability_enabled( 'menus', 'read' ), true ); ?> />
									Read Menus (Get)
								</label>
							</th>
							<td>Allow retrieving menus and menu items</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_menus_write">
									<input type="checkbox" id="wmc_menus_write" name="wmc_abilities_config[menus][write]" value="1"
										<?php checked( self::is_ability_enabled( 'menus', 'write' ), true ); ?> />
									Create/Update/Delete Menus ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow full menu management</td>
						</tr>
					</table>

					<h2>🎨 Widgets Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_widgets_read">
									<input type="checkbox" id="wmc_widgets_read" name="wmc_abilities_config[widgets][read]" value="1"
										<?php checked( self::is_ability_enabled( 'widgets', 'read' ), true ); ?> />
									Read Widgets (Get)
								</label>
							</th>
							<td>Allow retrieving widgets and sidebars</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_widgets_write">
									<input type="checkbox" id="wmc_widgets_write" name="wmc_abilities_config[widgets][write]" value="1"
										<?php checked( self::is_ability_enabled( 'widgets', 'write' ), true ); ?> />
									Update/Manage Widgets ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow widget and sidebar management</td>
						</tr>
					</table>

					<h2>🎭 Themes Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_themes_read">
									<input type="checkbox" id="wmc_themes_read" name="wmc_abilities_config[themes][read]" value="1"
										<?php checked( self::is_ability_enabled( 'themes', 'read' ), true ); ?> />
									Read Themes (Get)
								</label>
							</th>
							<td>Allow retrieving installed themes</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_themes_write">
									<input type="checkbox" id="wmc_themes_write" name="wmc_abilities_config[themes][write]" value="1"
										<?php checked( self::is_ability_enabled( 'themes', 'write' ), true ); ?> />
									Activate/Manage Themes ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow theme activation and customization</td>
						</tr>
					</table>

					<h2>🔍 SEO Meta Management (Yoast, Rank Math, All in One SEO)</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_seo_read">
									<input type="checkbox" id="wmc_seo_read" name="wmc_abilities_config[seo][read]" value="1"
										<?php checked( self::is_ability_enabled( 'seo', 'read' ), true ); ?> />
									Read SEO Meta (Get)
								</label>
							</th>
							<td>Allow retrieving SEO metadata (title, description) from posts and pages</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_seo_write">
									<input type="checkbox" id="wmc_seo_write" name="wmc_abilities_config[seo][write]" value="1"
										<?php checked( self::is_ability_enabled( 'seo', 'write' ), true ); ?> />
									Update SEO Meta ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow modification of SEO metadata (meta title, description, robots)</td>
						</tr>
					</table>

					<h2>🔌 Plugin Management</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_plugins_read">
									<input type="checkbox" id="wmc_plugins_read" name="wmc_abilities_config[plugins][read]" value="1"
										<?php checked( self::is_ability_enabled( 'plugins', 'read' ), true ); ?> />
									Read Plugins (Get)
								</label>
							</th>
							<td>Allow listing all installed plugins and their status</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_plugins_write">
									<input type="checkbox" id="wmc_plugins_write" name="wmc_abilities_config[plugins][write]" value="1"
										<?php checked( self::is_ability_enabled( 'plugins', 'write' ), true ); ?> />
									Activate / Deactivate Plugins ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow activating and deactivating installed plugins</td>
						</tr>
					</table>

					<h2>👥 User Roles &amp; Permissions</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_roles_read">
									<input type="checkbox" id="wmc_roles_read" name="wmc_abilities_config[roles][read]" value="1"
										<?php checked( self::is_ability_enabled( 'roles', 'read' ), true ); ?> />
									Read Roles (Get)
								</label>
							</th>
							<td>Allow listing all WordPress user roles and their capabilities</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_roles_write">
									<input type="checkbox" id="wmc_roles_write" name="wmc_abilities_config[roles][write]" value="1"
										<?php checked( self::is_ability_enabled( 'roles', 'write' ), true ); ?> />
									Assign Roles ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow assigning roles to users</td>
						</tr>
					</table>

					<h2>🛒 WooCommerce Management</h2>
					<p style="color: #666; margin: 0 0 10px;">Requires WooCommerce to be installed and active. Abilities return an error if WooCommerce is missing.</p>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_woocommerce_read">
									<input type="checkbox" id="wmc_woocommerce_read" name="wmc_abilities_config[woocommerce][read]" value="1"
										<?php checked( self::is_ability_enabled( 'woocommerce', 'read' ), true ); ?> />
									Read WooCommerce (Products, Orders, Customers)
								</label>
							</th>
							<td>Allow reading products, orders, and customers from WooCommerce</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_woocommerce_write">
									<input type="checkbox" id="wmc_woocommerce_write" name="wmc_abilities_config[woocommerce][write]" value="1"
										<?php checked( self::is_ability_enabled( 'woocommerce', 'write' ), true ); ?> />
									Write WooCommerce (Create/Update Products, Update Order Status) ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow creating/updating products and changing order statuses</td>
						</tr>
					</table>

					<h2>⚙️ System / Maintenance</h2>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="wmc_system_read">
									<input type="checkbox" id="wmc_system_read" name="wmc_abilities_config[system][read]" value="1"
										<?php checked( self::is_ability_enabled( 'system', 'read' ), true ); ?> />
									Read System Info (Site Health, Cron Jobs)
								</label>
							</th>
							<td>Allow retrieving site health information and scheduled cron jobs</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wmc_system_write">
									<input type="checkbox" id="wmc_system_write" name="wmc_abilities_config[system][write]" value="1"
										<?php checked( self::is_ability_enabled( 'system', 'write' ), true ); ?> />
									Write System (Clear Cache) ⚠️
								</label>
							</th>
							<td style="color: #d63638;">Allow clearing WordPress object cache and transients</td>
						</tr>
					</table>
				</div>

				<?php submit_button(); ?>
			</form>

			<style>
				.wmc-settings-container {
					background: #fff;
					padding: 20px;
					border: 1px solid #ccc;
					margin-top: 20px;
					border-radius: 5px;
				}
				.wmc-settings-container h2 {
					margin-top: 30px;
					padding-top: 20px;
					border-top: 2px solid #eee;
					color: #1f2937;
				}
				.wmc-settings-container input[type="checkbox"] {
					margin-right: 8px;
				}
				.form-table tr th label {
					font-weight: 600;
					cursor: pointer;
				}
			</style>
		</div>
		<?php
	}

	/**
	 * Check if ability is enabled
	 */
	public static function is_ability_enabled( $resource, $operation ) {
		$config = get_option( 'wmc_abilities_config', array() );

		// Default: all enabled
		if ( empty( $config ) ) {
			return true;
		}

		return isset( $config[ $resource ][ $operation ] ) && $config[ $resource ][ $operation ];
	}

	/**
	 * Get default config (all enabled)
	 */
	public static function get_default_config() {
		return array(
			'posts'       => array( 'read' => 1, 'create' => 1, 'update' => 1, 'delete' => 1 ),
			'pages'       => array( 'read' => 1, 'create' => 1, 'update' => 1, 'delete' => 1 ),
			'categories'  => array( 'read' => 1, 'write' => 1 ),
			'tags'        => array( 'read' => 1, 'write' => 1 ),
			'media'       => array( 'read' => 1, 'write' => 1 ),
			'comments'    => array( 'read' => 1, 'moderate' => 1, 'delete' => 1 ),
			'users'       => array( 'read' => 1, 'write' => 1 ),
			'settings'    => array( 'read' => 1, 'write' => 1 ),
			'menus'       => array( 'read' => 1, 'write' => 1 ),
			'widgets'     => array( 'read' => 1, 'write' => 1 ),
			'themes'      => array( 'read' => 1, 'write' => 1 ),
			'seo'         => array( 'read' => 1, 'write' => 1 ),
			'plugins'     => array( 'read' => 1, 'write' => 1 ),
			'roles'       => array( 'read' => 1, 'write' => 1 ),
			'woocommerce' => array( 'read' => 1, 'write' => 1 ),
			'system'      => array( 'read' => 1, 'write' => 1 ),
		);
	}
}

// Initialize settings
WMC_Settings::init();
