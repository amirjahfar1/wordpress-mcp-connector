<?php
/**
 * WordPress MCP Connector - Settings Page
 * Modern card-based dashboard with toggle controls per ability.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WMC_Settings {

	public static function init() {
		add_action( 'admin_menu', array( self::class, 'add_admin_menu' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
	}

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

	public static function register_settings() {
		register_setting( 'wmc_settings', 'wmc_abilities_config' );
	}

	/**
	 * All sections + abilities definition.
	 * 'key' => [ resource, operation ] must match what is_enabled() checks in abilities.php.
	 */
	private static function get_sections() {
		return array(
			array(
				'id'    => 'posts',
				'title' => 'Posts',
				'icon'  => '📝',
				'abilities' => array(
					array( 'label' => 'Get Posts',   'slug' => 'wmc/get-posts',   'desc' => 'Retrieve posts with pagination, search, category/tag filtering and status filter.', 'badge' => 'read',  'key' => array( 'posts', 'read' ) ),
					array( 'label' => 'Create Post', 'slug' => 'wmc/create-post', 'desc' => 'Create new posts. Supports future scheduling via the date field.',                  'badge' => 'write', 'key' => array( 'posts', 'create' ) ),
					array( 'label' => 'Update Post', 'slug' => 'wmc/update-post', 'desc' => 'Modify existing posts including title, content, status, and scheduled date.',        'badge' => 'write', 'key' => array( 'posts', 'update' ) ),
					array( 'label' => 'Delete Post', 'slug' => 'wmc/delete-post', 'desc' => 'Permanently delete a post and its metadata.',                                        'badge' => 'danger', 'key' => array( 'posts', 'delete' ) ),
				),
			),
			array(
				'id'    => 'pages',
				'title' => 'Pages',
				'icon'  => '📄',
				'abilities' => array(
					array( 'label' => 'Get Pages',   'slug' => 'wmc/get-pages',   'desc' => 'List pages with search and status filtering.',                          'badge' => 'read',  'key' => array( 'pages', 'read' ) ),
					array( 'label' => 'Create Page', 'slug' => 'wmc/create-page', 'desc' => 'Create new pages. Supports parent pages and future scheduling.',        'badge' => 'write', 'key' => array( 'pages', 'create' ) ),
					array( 'label' => 'Update Page', 'slug' => 'wmc/update-page', 'desc' => 'Modify existing pages including content, status, and scheduled date.', 'badge' => 'write', 'key' => array( 'pages', 'update' ) ),
					array( 'label' => 'Delete Page', 'slug' => 'wmc/delete-page', 'desc' => 'Permanently delete a page.',                                            'badge' => 'danger', 'key' => array( 'pages', 'delete' ) ),
				),
			),
			array(
				'id'    => 'categories',
				'title' => 'Categories & Tags',
				'icon'  => '🏷️',
				'abilities' => array(
					array( 'label' => 'Get Categories',                        'slug' => 'wmc/get-categories',                          'desc' => 'List all categories with optional search and empty filter.',   'badge' => 'read',  'key' => array( 'categories', 'read' ) ),
					array( 'label' => 'Create / Update / Delete Categories',   'slug' => 'wmc/create-category · update · delete',       'desc' => 'Full category management — create, rename, and delete.',      'badge' => 'write', 'key' => array( 'categories', 'write' ) ),
					array( 'label' => 'Get Tags',                              'slug' => 'wmc/get-tags',                                'desc' => 'List all tags with optional search.',                         'badge' => 'read',  'key' => array( 'tags', 'read' ) ),
					array( 'label' => 'Create / Update / Delete Tags',         'slug' => 'wmc/create-tag · update · delete',           'desc' => 'Full tag management — create, rename, and delete.',           'badge' => 'write', 'key' => array( 'tags', 'write' ) ),
				),
			),
			array(
				'id'    => 'media',
				'title' => 'Media',
				'icon'  => '🖼️',
				'abilities' => array(
					array( 'label' => 'Get Media / Details / Search / Missing Alt', 'slug' => 'wmc/get-media · details · search · without-alt', 'desc' => 'List media library, get full details of one item, advanced search by MIME/date/attached status, and find images missing alt text (SEO audit).', 'badge' => 'read', 'key' => array( 'media', 'read' ) ),
					array( 'label' => 'Create / Update / Bulk Update / Delete / Bulk Delete', 'slug' => 'wmc/create · update · bulk-update · delete · bulk-delete', 'desc' => 'Upload media, update alt text / title / caption / description one-by-one or in bulk, and delete single or multiple files at once.', 'badge' => 'write', 'key' => array( 'media', 'write' ) ),
				),
			),
			array(
				'id'    => 'comments',
				'title' => 'Comments',
				'icon'  => '💬',
				'abilities' => array(
					array( 'label' => 'Get Comments',      'slug' => 'wmc/get-comments',      'desc' => 'List comments filtered by post ID or status (approved, pending, spam).', 'badge' => 'read',     'key' => array( 'comments', 'read' ) ),
					array( 'label' => 'Moderate Comment',  'slug' => 'wmc/moderate-comment',  'desc' => 'Approve, unapprove, or mark a comment as spam.',                         'badge' => 'moderate', 'key' => array( 'comments', 'moderate' ) ),
					array( 'label' => 'Delete Comment',    'slug' => 'wmc/delete-comment',    'desc' => 'Permanently delete a comment.',                                          'badge' => 'danger',   'key' => array( 'comments', 'delete' ) ),
				),
			),
			array(
				'id'    => 'users',
				'title' => 'Users',
				'icon'  => '👤',
				'abilities' => array(
					array( 'label' => 'Get Users',                      'slug' => 'wmc/get-users',              'desc' => 'List WordPress users with role filtering and search.',       'badge' => 'read',  'key' => array( 'users', 'read' ) ),
					array( 'label' => 'Create / Update / Delete Users', 'slug' => 'wmc/create · update · delete', 'desc' => 'Full user account management including password and role.', 'badge' => 'write', 'key' => array( 'users', 'write' ) ),
				),
			),
			array(
				'id'    => 'settings',
				'title' => 'WordPress Settings',
				'icon'  => '⚙️',
				'abilities' => array(
					array( 'label' => 'Get Options',    'slug' => 'wmc/get-options',    'desc' => 'Retrieve WordPress settings: blogname, admin_email, siteurl, posts_per_page, and more.', 'badge' => 'read',  'key' => array( 'settings', 'read' ) ),
					array( 'label' => 'Update Option',  'slug' => 'wmc/update-option',  'desc' => 'Modify whitelisted WordPress options. Dangerous options are blocked.',                   'badge' => 'write', 'key' => array( 'settings', 'write' ) ),
				),
			),
			array(
				'id'    => 'menus',
				'title' => 'Menus',
				'icon'  => '📋',
				'abilities' => array(
					array( 'label' => 'Get Menus',              'slug' => 'wmc/get-menus',              'desc' => 'List all navigation menus and their items.',       'badge' => 'read',  'key' => array( 'menus', 'read' ) ),
					array( 'label' => 'Create / Delete Menus',  'slug' => 'wmc/create-menu · delete',   'desc' => 'Create new menus and delete existing ones.',       'badge' => 'write', 'key' => array( 'menus', 'write' ) ),
				),
			),
			array(
				'id'    => 'widgets',
				'title' => 'Widgets',
				'icon'  => '🎨',
				'abilities' => array(
					array( 'label' => 'Get Sidebars',         'slug' => 'wmc/get-sidebars',         'desc' => 'List all registered widget areas and their widgets.',     'badge' => 'read',  'key' => array( 'widgets', 'read' ) ),
					array( 'label' => 'Update Widget Option', 'slug' => 'wmc/update-widget-option', 'desc' => 'Modify widget settings and configuration per widget ID.', 'badge' => 'write', 'key' => array( 'widgets', 'write' ) ),
				),
			),
			array(
				'id'    => 'themes',
				'title' => 'Themes',
				'icon'  => '🎭',
				'abilities' => array(
					array( 'label' => 'Get Themes / Theme Mods',          'slug' => 'wmc/get-themes · get-theme-mods',    'desc' => 'List installed themes and retrieve current theme customization settings.', 'badge' => 'read',  'key' => array( 'themes', 'read' ) ),
					array( 'label' => 'Activate Theme / Update Theme Mod', 'slug' => 'wmc/activate-theme · update-mod',   'desc' => 'Switch the active theme and update theme customizer values.',            'badge' => 'write', 'key' => array( 'themes', 'write' ) ),
				),
			),
			array(
				'id'    => 'seo',
				'title' => 'SEO Meta',
				'icon'  => '🔍',
				'abilities' => array(
					array( 'label' => 'Get Post / Page SEO Meta',     'slug' => 'wmc/get-post-seo-meta · get-page-seo-meta',       'desc' => 'Read SEO title, description, robots, focus keyword. Auto-detects Yoast SEO, Rank Math, or All in One SEO.', 'badge' => 'read',  'key' => array( 'seo', 'read' ) ),
					array( 'label' => 'Update Post / Page SEO Meta',  'slug' => 'wmc/update-post-seo-meta · update-page-seo-meta', 'desc' => 'Write SEO metadata to posts and pages via the detected SEO plugin.',                                        'badge' => 'write', 'key' => array( 'seo', 'write' ) ),
				),
			),
			array(
				'id'    => 'plugins',
				'title' => 'Plugin Management',
				'icon'  => '🔌',
				'abilities' => array(
					array( 'label' => 'Get Plugins',                     'slug' => 'wmc/get-plugins',                       'desc' => 'List all installed plugins with name, version, status (active/inactive).',   'badge' => 'read',   'key' => array( 'plugins', 'read' ) ),
					array( 'label' => 'Activate / Deactivate Plugin',    'slug' => 'wmc/activate-plugin · deactivate',      'desc' => 'Enable or disable installed WordPress plugins by plugin file path.',          'badge' => 'danger', 'key' => array( 'plugins', 'write' ) ),
				),
			),
			array(
				'id'    => 'roles',
				'title' => 'User Roles & Permissions',
				'icon'  => '👥',
				'abilities' => array(
					array( 'label' => 'Get Roles',   'slug' => 'wmc/get-roles',   'desc' => 'List all WordPress user roles. Optionally include full capabilities list per role.', 'badge' => 'read',  'key' => array( 'roles', 'read' ) ),
					array( 'label' => 'Assign Role', 'slug' => 'wmc/assign-role', 'desc' => 'Assign any registered role to a WordPress user by user ID.',                         'badge' => 'write', 'key' => array( 'roles', 'write' ) ),
				),
			),
			array(
				'id'    => 'woocommerce',
				'title' => 'WooCommerce',
				'icon'  => '🛒',
				'abilities' => array(
					array( 'label' => 'Get Products / Orders / Customers',              'slug' => 'wmc/get-woo-products · orders · customers',      'desc' => 'Read WooCommerce products, orders, and customer data. Returns error if WooCommerce is not active.',       'badge' => 'read',  'key' => array( 'woocommerce', 'read' ) ),
					array( 'label' => 'Create / Update Products · Update Order Status', 'slug' => 'wmc/create-woo-product · update · order-status', 'desc' => 'Create & update products (price, SKU, stock) and change order status. Requires WooCommerce to be active.', 'badge' => 'write', 'key' => array( 'woocommerce', 'write' ) ),
				),
			),
			array(
				'id'    => 'system',
				'title' => 'System / Maintenance',
				'icon'  => '🖥️',
				'abilities' => array(
					array( 'label' => 'Get Site Health / Cron Jobs', 'slug' => 'wmc/get-site-health · get-cron-jobs', 'desc' => 'View site health report (WP, PHP, DB, server info) and list all scheduled WP cron tasks.', 'badge' => 'read',  'key' => array( 'system', 'read' ) ),
					array( 'label' => 'Clear Cache',                  'slug' => 'wmc/clear-cache',                    'desc' => 'Clear WP object cache, transients, W3 Total Cache, WP Rocket, WP Super Cache, LiteSpeed.', 'badge' => 'write', 'key' => array( 'system', 'write' ) ),
				),
			),
		);
	}

	public static function render_settings_page() {
		$sections = self::get_sections();

		$total   = 0;
		$enabled = 0;
		foreach ( $sections as $section ) {
			foreach ( $section['abilities'] as $ab ) {
				$total++;
				if ( self::is_ability_enabled( $ab['key'][0], $ab['key'][1] ) ) {
					$enabled++;
				}
			}
		}

		$badge_labels = array(
			'read'     => 'READ-ONLY',
			'write'    => 'WRITE',
			'danger'   => 'DESTRUCTIVE',
			'moderate' => 'MODERATE',
		);
		?>
		<div class="wmc-wrap">

			<!-- HEADER -->
			<div class="wmc-header">
				<div class="wmc-header-left">
					<div class="wmc-logo"><span class="dashicons dashicons-admin-tools"></span></div>
					<div>
						<h1 class="wmc-title">WordPress MCP Connector</h1>
						<span class="wmc-version">v<?php echo esc_html( WMC_VERSION ); ?></span>
					</div>
				</div>
				<div class="wmc-header-right">
					<a href="https://github.com/amirjahfar1/wordpress-mcp-connector" target="_blank" class="wmc-btn-ghost">📖 GitHub</a>
					<a href="<?php echo esc_url( rest_url( 'wmc/v1/diagnose' ) ); ?>" target="_blank" class="wmc-btn-ghost">🔎 Diagnose</a>
				</div>
			</div>

			<!-- APPLICATION PASSWORD CARD -->
			<?php
			$site_url   = get_site_url();
			$admin_users = get_users( array( 'role' => 'administrator', 'fields' => array( 'ID', 'user_login', 'display_name' ) ) );
			$gen_nonce   = wp_create_nonce( 'wmc_gen_app_password' );
			?>
			<div class="wmc-connect-card" id="wmc-connect-card">
				<div class="wmc-connect-header">
					<div class="wmc-connect-title">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="flex-shrink:0"><rect width="24" height="24" rx="6" fill="rgba(255,255,255,0.2)"/><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" fill="white"/></svg>
						<div>
							<strong>Connect Claude Code to Your Site</strong>
							<div class="wmc-connect-sub">Generate credentials below and give the JSON to Claude — one-time setup, works forever</div>
						</div>
					</div>
					<div class="wmc-connect-status">
						<span class="wmc-status-dot"></span> WordPress <?php echo esc_html( get_bloginfo('version') ); ?>
					</div>
				</div>

				<!-- Quick how-to banner -->
				<div class="wmc-howto-bar">
					<div class="wmc-howto-item">
						<div class="wmc-howto-num">1</div>
						<div class="wmc-howto-text">Generate password below</div>
					</div>
					<div class="wmc-howto-arrow">&#8594;</div>
					<div class="wmc-howto-item">
						<div class="wmc-howto-num">2</div>
						<div class="wmc-howto-text">Copy the JSON config</div>
					</div>
					<div class="wmc-howto-arrow">&#8594;</div>
					<div class="wmc-howto-item">
						<div class="wmc-howto-num">3</div>
						<div class="wmc-howto-text">Give to Claude &amp; done!</div>
					</div>
				</div>

				<div class="wmc-connect-body">

					<!-- Step 1: Generate -->
					<div class="wmc-step">
						<div class="wmc-step-num">1</div>
						<div class="wmc-step-content">
							<div class="wmc-step-title">Generate Application Password</div>
							<div class="wmc-step-desc">Select the admin account you want Claude to use, then click <strong>Generate</strong>.</div>
							<div class="wmc-gen-row">
								<select id="wmc-user-select" class="wmc-select">
									<?php foreach ( $admin_users as $u ) : ?>
										<option value="<?php echo esc_attr( $u->ID ); ?>">
											<?php echo esc_html( $u->display_name . ' (@' . $u->user_login . ')' ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<button type="button" class="wmc-copy-btn" id="wmc-gen-btn" onclick="wmcGenPassword()">
									&#9889; Generate
								</button>
							</div>
						</div>
					</div>

					<!-- Step 2: Copy credentials -->
					<div class="wmc-step" id="wmc-creds-step" style="display:none">
						<div class="wmc-step-num">2</div>
						<div class="wmc-step-content">
							<div class="wmc-step-title">Your Credentials <span class="wmc-once-badge">Shown only once — save now!</span></div>
							<div class="wmc-creds-box" id="wmc-creds-box">
								<div class="wmc-cred-row">
									<span class="wmc-cred-label">Site URL</span>
									<code id="wmc-cred-url"><?php echo esc_html( $site_url ); ?></code>
								</div>
								<div class="wmc-cred-row">
									<span class="wmc-cred-label">Username</span>
									<code id="wmc-cred-user">—</code>
								</div>
								<div class="wmc-cred-row">
									<span class="wmc-cred-label">App Password</span>
									<code id="wmc-cred-pass" style="color:#6366f1;font-weight:700;letter-spacing:.05em">—</code>
									<button type="button" class="wmc-copy-inline" onclick="wmcCopyPass()">Copy</button>
								</div>
							</div>
						</div>
					</div>

					<!-- Step 3: MCP config JSON -->
					<div class="wmc-step" id="wmc-config-step" style="display:none">
						<div class="wmc-step-num">3</div>
						<div class="wmc-step-content">
							<div class="wmc-step-title">Copy &amp; Give to Claude</div>
							<div class="wmc-step-desc">
								Copy this JSON and tell Claude: <em style="color:#6366f1">"Update my MCP config with this"</em> — Claude will connect automatically.
							</div>
							<div class="wmc-token-wrap" style="margin-top:10px">
								<textarea id="wmc-config-json" class="wmc-token-input wmc-config-textarea" readonly rows="9"></textarea>
								<button type="button" class="wmc-copy-btn" onclick="wmcCopyConfig()" style="align-self:flex-start">
									&#128203; Copy JSON
								</button>
							</div>
							<div class="wmc-restart-note">
								&#9432; After Claude updates the config, <strong>restart Claude Code</strong> for the connection to take effect.
							</div>
						</div>
					</div>

					<div class="wmc-token-hint">
						&#128274; Application Passwords can be revoked anytime from <a href="<?php echo esc_url( admin_url('users.php') ); ?>">Users &rarr; Profile</a>.
					</div>
				</div>
			</div>

			<!-- STATS BAR -->
			<div class="wmc-stats">
				<div class="wmc-stat">
					<span class="wmc-stat-icon" style="background:#6366f1">⚡</span>
					<div>
						<div class="wmc-stat-num">142</div>
						<div class="wmc-stat-label">Total Abilities</div>
					</div>
				</div>
				<div class="wmc-stat">
					<span class="wmc-stat-icon" style="background:#10b981">✓</span>
					<div>
						<div class="wmc-stat-num" id="wmc-active-count"><?php echo $enabled; ?></div>
						<div class="wmc-stat-label">Active</div>
					</div>
				</div>
				<div class="wmc-stat">
					<span class="wmc-stat-icon" style="background:#f59e0b">◈</span>
					<div>
						<div class="wmc-stat-num">16</div>
						<div class="wmc-stat-label">Categories</div>
					</div>
				</div>
				<div class="wmc-stat">
					<span class="wmc-stat-icon" style="background:#ef4444">⊘</span>
					<div>
						<div class="wmc-stat-num" id="wmc-disabled-count"><?php echo ( $total - $enabled ); ?></div>
						<div class="wmc-stat-label">Disabled</div>
					</div>
				</div>
			</div>

			<!-- FORM -->
			<form method="post" action="options.php" id="wmc-form">
				<?php settings_fields( 'wmc_settings' ); ?>

				<!-- TOOLBAR -->
				<div class="wmc-toolbar">
					<span id="wmc-enabled-text">
						<?php echo $enabled; ?> of <?php echo $total; ?> controls enabled
					</span>
					<div class="wmc-toolbar-right">
						<button type="button" class="wmc-btn wmc-btn-outline" onclick="wmcToggleAll(true)">Enable All</button>
						<button type="button" class="wmc-btn wmc-btn-outline" onclick="wmcToggleAll(false)">Disable All</button>
						<?php submit_button( 'Save Changes', 'primary', 'submit', false, array( 'class' => 'wmc-btn wmc-btn-primary' ) ); ?>
					</div>
				</div>

				<!-- SECTIONS -->
				<div class="wmc-sections">
				<?php foreach ( $sections as $section ) :
					$sec_total   = count( $section['abilities'] );
					$sec_enabled = 0;
					foreach ( $section['abilities'] as $ab ) {
						if ( self::is_ability_enabled( $ab['key'][0], $ab['key'][1] ) ) {
							$sec_enabled++;
						}
					}
					$sec_id = esc_attr( $section['id'] );
				?>
					<div class="wmc-section" id="section-<?php echo $sec_id; ?>">
						<div class="wmc-section-header" onclick="wmcToggleSection('<?php echo esc_js( $section['id'] ); ?>')">
							<div class="wmc-section-title">
								<span class="wmc-arrow" id="arrow-<?php echo $sec_id; ?>">▼</span>
								<span class="wmc-section-icon"><?php echo $section['icon']; ?></span>
								<span><?php echo esc_html( $section['title'] ); ?></span>
								<span class="wmc-section-badge" id="count-<?php echo $sec_id; ?>">
									<?php echo "$sec_enabled/$sec_total"; ?>
								</span>
							</div>
							<div class="wmc-section-actions" onclick="event.stopPropagation()">
								<button type="button" class="wmc-mini-btn" onclick="wmcSectionToggle('<?php echo esc_js( $section['id'] ); ?>', true)">All</button>
								<button type="button" class="wmc-mini-btn" onclick="wmcSectionToggle('<?php echo esc_js( $section['id'] ); ?>', false)">None</button>
							</div>
						</div>

						<div class="wmc-section-body" id="body-<?php echo $sec_id; ?>">
							<div class="wmc-cards">
							<?php foreach ( $section['abilities'] as $ab ) :
								$is_on      = self::is_ability_enabled( $ab['key'][0], $ab['key'][1] );
								$fname      = 'wmc_abilities_config[' . $ab['key'][0] . '][' . $ab['key'][1] . ']';
								$fid        = 'wmc_' . $ab['key'][0] . '_' . $ab['key'][1];
								$blabel     = $badge_labels[ $ab['badge'] ] ?? strtoupper( $ab['badge'] );
							?>
								<div class="wmc-card <?php echo $is_on ? 'wmc-card-on' : 'wmc-card-off'; ?>"
								     id="card-<?php echo esc_attr( $fid ); ?>"
								     data-section="<?php echo $sec_id; ?>">
									<div class="wmc-card-top">
										<label class="wmc-toggle" for="<?php echo esc_attr( $fid ); ?>" title="<?php echo $is_on ? 'Click to disable' : 'Click to enable'; ?>">
											<input type="checkbox"
											       id="<?php echo esc_attr( $fid ); ?>"
											       name="<?php echo esc_attr( $fname ); ?>"
											       value="1"
											       class="wmc-checkbox"
											       data-section="<?php echo $sec_id; ?>"
											       <?php checked( $is_on, true ); ?>
											       onchange="wmcOnChange(this)">
											<span class="wmc-slider"></span>
										</label>
										<div class="wmc-card-name"><?php echo esc_html( $ab['label'] ); ?></div>
										<span class="wmc-badge wmc-badge-<?php echo esc_attr( $ab['badge'] ); ?>"><?php echo esc_html( $blabel ); ?></span>
									</div>
									<div class="wmc-card-desc"><?php echo esc_html( $ab['desc'] ); ?></div>
									<div class="wmc-card-slug"><?php echo esc_html( $ab['slug'] ); ?></div>
								</div>
							<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
				</div>

				<!-- BOTTOM SAVE -->
				<div class="wmc-bottom-bar">
					<?php submit_button( 'Save Changes', 'primary', 'submit', false, array( 'class' => 'wmc-btn wmc-btn-primary wmc-btn-lg' ) ); ?>
				</div>

			</form>
		</div><!-- .wmc-wrap -->

		<style>
		.wmc-wrap *, .wmc-wrap *::before, .wmc-wrap *::after { box-sizing: border-box; }

		/* ---- Connect Card ---- */
		.wmc-connect-card {
			background:#fff; border:1.5px solid #6366f1; border-radius:14px;
			margin-bottom:14px; overflow:hidden;
		}
		.wmc-connect-header {
			display:flex; align-items:center; justify-content:space-between;
			padding:18px 24px; background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);
			flex-wrap:wrap; gap:10px;
		}
		.wmc-connect-title { display:flex; align-items:center; gap:12px; color:#fff; }
		.wmc-connect-icon { font-size:24px; }
		.wmc-connect-title strong { font-size:15px; font-weight:700; }
		.wmc-connect-sub { font-size:12px; opacity:.85; margin-top:2px; }
		.wmc-connect-status {
			display:flex; align-items:center; gap:7px;
			background:rgba(255,255,255,.18); color:#fff;
			font-size:12px; font-weight:600; padding:6px 14px; border-radius:20px;
		}
		.wmc-status-dot {
			width:8px; height:8px; border-radius:50%; background:#4ade80;
			box-shadow:0 0 0 2px rgba(74,222,128,.3); animation:wmcPulse 2s infinite;
		}
		@keyframes wmcPulse { 0%,100%{opacity:1} 50%{opacity:.5} }

		.wmc-connect-body { padding:20px 24px; display:flex; flex-direction:column; gap:14px; }

		/* Steps */
		.wmc-step { display:flex; gap:14px; align-items:flex-start; }
		.wmc-step-num {
			width:28px; height:28px; border-radius:50%; flex-shrink:0;
			background:linear-gradient(135deg,#6366f1,#8b5cf6);
			color:#fff; font-size:13px; font-weight:700;
			display:flex; align-items:center; justify-content:center;
			margin-top:2px;
		}
		.wmc-step-content { flex:1; }
		.wmc-step-title { font-size:14px; font-weight:700; color:#0f172a; margin-bottom:3px; }
		.wmc-step-desc { font-size:12.5px; color:#64748b; margin-bottom:10px; }

		.wmc-gen-row { display:flex; gap:8px; flex-wrap:wrap; }
		.wmc-select {
			flex:1; min-width:180px; padding:9px 12px;
			border:1.5px solid #e2e8f0; border-radius:8px;
			font-size:13px; color:#334155; background:#f8fafc; outline:none;
		}
		.wmc-select:focus { border-color:#6366f1; background:#fff; }

		/* Creds box */
		.wmc-creds-box {
			background:#f8fafc; border:1.5px solid #c7d2fe;
			border-radius:10px; overflow:hidden;
		}
		.wmc-cred-row {
			display:flex; align-items:center; gap:10px; padding:10px 14px;
			border-bottom:1px solid #e2e8f0; flex-wrap:wrap;
		}
		.wmc-cred-row:last-child { border:none; }
		.wmc-cred-label { font-size:11.5px; font-weight:700; color:#94a3b8; width:90px; flex-shrink:0; }
		.wmc-cred-row code { font-size:13px; color:#334155; flex:1; word-break:break-all; background:transparent; }
		.wmc-copy-inline {
			padding:4px 12px; border-radius:6px; font-size:11px; font-weight:700;
			cursor:pointer; border:1px solid #c7d2fe; background:#eef2ff; color:#6366f1;
			transition:all .15s; white-space:nowrap;
		}
		.wmc-copy-inline:hover { background:#6366f1; color:#fff; border-color:#6366f1; }

		/* Config textarea */
		.wmc-config-textarea {
			font-family:'SFMono-Regular',Consolas,Menlo,monospace;
			font-size:12px; line-height:1.6; resize:vertical;
			width:100%; min-height:160px;
		}

		/* Shared token/input */
		.wmc-token-wrap { display:flex; gap:8px; align-items:flex-start; flex-wrap:wrap; }
		.wmc-token-input {
			flex:1; min-width:200px; font-family:monospace; font-size:13px;
			padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:8px;
			background:#f8fafc; color:#334155; outline:none;
		}
		.wmc-token-input:focus { border-color:#6366f1; background:#fff; }
		.wmc-copy-btn {
			padding:10px 18px; border-radius:8px; font-size:13px; font-weight:600;
			cursor:pointer; border:none; transition:all .15s; white-space:nowrap;
			background:#6366f1; color:#fff;
		}
		.wmc-copy-btn:hover { background:#4f46e5; }
		.wmc-token-hint { font-size:12px; color:#94a3b8; }
		.wmc-token-hint a { color:#6366f1; }

		/* How-to banner */
		.wmc-howto-bar {
			display:flex; align-items:center; justify-content:center; gap:10px;
			background:#f8f7ff; border-bottom:1px solid #e9d5ff;
			padding:12px 24px; flex-wrap:wrap;
		}
		.wmc-howto-item { display:flex; align-items:center; gap:8px; }
		.wmc-howto-num {
			width:24px; height:24px; border-radius:50%;
			background:#6366f1; color:#fff;
			font-size:12px; font-weight:700;
			display:flex; align-items:center; justify-content:center; flex-shrink:0;
		}
		.wmc-howto-text { font-size:12.5px; font-weight:600; color:#4c1d95; }
		.wmc-howto-arrow { color:#c4b5fd; font-size:16px; }

		/* Once badge */
		.wmc-once-badge {
			display:inline-block; background:#fef3c7; color:#92400e;
			font-size:10.5px; font-weight:700; padding:2px 8px;
			border-radius:4px; margin-left:8px; vertical-align:middle;
			border:1px solid #fde68a;
		}

		/* Restart note */
		.wmc-restart-note {
			margin-top:10px; font-size:12px; color:#64748b;
			background:#f8fafc; border:1px solid #e2e8f0;
			border-radius:8px; padding:10px 14px; line-height:1.6;
		}
		.wmc-restart-note strong { color:#334155; }

		/* Generate button pulse on load */
		#wmc-gen-btn { animation:wmcGenPulse 2s ease-in-out 1s 3; }
		@keyframes wmcGenPulse {
			0%,100%{ box-shadow:0 0 0 0 rgba(99,102,241,.4); }
			50%{ box-shadow:0 0 0 8px rgba(99,102,241,0); }
		}

		/* ---- Rest of styles ---- */
		.wmc-wrap {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
			max-width: 1200px;
			margin: 20px 20px 60px;
			color: #1e293b;
		}

		/* ---- Header ---- */
		.wmc-header {
			display: flex; align-items: center; justify-content: space-between;
			background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
			padding: 20px 24px; margin-bottom: 14px;
		}
		.wmc-header-left { display: flex; align-items: center; gap: 16px; }
		.wmc-logo {
			width: 48px; height: 48px; border-radius: 12px;
			background: linear-gradient(135deg,#6366f1,#8b5cf6);
			display: flex; align-items: center; justify-content: center; flex-shrink: 0;
		}
		.wmc-logo .dashicons { color:#fff; font-size:24px; width:24px; height:24px; }
		.wmc-title { margin:0; font-size:20px; font-weight:700; color:#0f172a; line-height:1.2; }
		.wmc-version {
			display:inline-block; background:#f1f5f9; color:#64748b;
			font-size:12px; font-weight:600; padding:2px 10px; border-radius:20px; margin-top:5px;
		}
		.wmc-header-right { display:flex; gap:8px; }
		.wmc-btn-ghost {
			display:inline-block; padding:8px 14px;
			border:1px solid #e2e8f0; border-radius:8px;
			color:#64748b; text-decoration:none; font-size:12px; font-weight:600;
			transition:all .15s;
		}
		.wmc-btn-ghost:hover { border-color:#6366f1; color:#6366f1; text-decoration:none; }

		/* ---- Stats ---- */
		.wmc-stats {
			display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:14px;
		}
		.wmc-stat {
			background:#fff; border:1px solid #e2e8f0; border-radius:12px;
			padding:16px 18px; display:flex; align-items:center; gap:14px;
		}
		.wmc-stat-icon {
			width:44px; height:44px; border-radius:10px; flex-shrink:0;
			display:flex; align-items:center; justify-content:center;
			color:#fff; font-size:18px; font-style:normal;
		}
		.wmc-stat-num  { font-size:26px; font-weight:700; color:#0f172a; line-height:1; }
		.wmc-stat-label{ font-size:12px; color:#94a3b8; margin-top:4px; font-weight:500; }

		/* ---- Toolbar ---- */
		.wmc-toolbar {
			display:flex; align-items:center; justify-content:space-between;
			background:#fff; border:1px solid #e2e8f0; border-radius:12px;
			padding:14px 20px; margin-bottom:14px; flex-wrap:wrap; gap:10px;
		}
		#wmc-enabled-text { font-size:13px; color:#64748b; font-weight:500; }
		.wmc-toolbar-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

		/* ---- Buttons ---- */
		.wmc-btn {
			padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600;
			cursor:pointer; transition:all .15s; text-decoration:none; line-height:1.4;
			display:inline-block;
		}
		.wmc-btn-outline {
			background:#fff; border:1px solid #e2e8f0; color:#475569;
		}
		.wmc-btn-outline:hover { border-color:#6366f1; color:#6366f1; background:#f8f7ff; }
		#wmc-form .wmc-btn-primary,
		#wmc-form .wmc-btn-primary.button-primary {
			background: linear-gradient(135deg,#6366f1,#8b5cf6) !important;
			color:#fff !important; border:none !important;
			box-shadow:none; height:auto; padding:8px 20px;
		}
		#wmc-form .wmc-btn-primary:hover { opacity:.9; box-shadow:0 4px 14px rgba(99,102,241,.4); transform:translateY(-1px); }
		#wmc-form .wmc-btn-lg { padding:12px 36px !important; font-size:15px !important; }

		/* ---- Sections ---- */
		.wmc-sections { display:flex; flex-direction:column; gap:10px; }
		.wmc-section  { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }

		.wmc-section-header {
			display:flex; align-items:center; justify-content:space-between;
			padding:15px 20px; cursor:pointer; user-select:none; transition:background .15s;
		}
		.wmc-section-header:hover { background:#f8fafc; }

		.wmc-section-title {
			display:flex; align-items:center; gap:10px;
			font-size:14.5px; font-weight:600; color:#0f172a;
		}
		.wmc-arrow { font-size:10px; color:#94a3b8; transition:transform .2s; display:inline-block; }
		.wmc-arrow.wmc-collapsed { transform:rotate(-90deg); }
		.wmc-section-icon { font-size:17px; }
		.wmc-section-badge {
			background:#f1f5f9; color:#64748b;
			font-size:11px; font-weight:700; padding:2px 10px; border-radius:20px;
		}
		.wmc-section-actions { display:flex; gap:6px; }
		.wmc-mini-btn {
			padding:4px 12px; border:1px solid #e2e8f0; border-radius:6px;
			font-size:11px; font-weight:600; color:#64748b; background:#fff; cursor:pointer;
			transition:all .15s;
		}
		.wmc-mini-btn:hover { border-color:#6366f1; color:#6366f1; }

		.wmc-section-body { padding:0 14px 14px; }
		.wmc-section-body.wmc-collapsed { display:none; }

		/* ---- Cards ---- */
		.wmc-cards {
			display:grid;
			grid-template-columns:repeat(auto-fill, minmax(300px,1fr));
			gap:10px;
		}
		.wmc-card {
			border:1.5px solid #e2e8f0; border-radius:10px;
			padding:14px 15px; transition:border-color .15s, opacity .15s;
		}
		.wmc-card-on  { border-color:#c7d2fe; background:#fafbff; }
		.wmc-card-off { border-color:#e2e8f0; background:#f8fafc; opacity:.65; }
		.wmc-card:hover { border-color:#818cf8; }

		.wmc-card-top {
			display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:8px;
		}
		.wmc-card-name { font-size:13px; font-weight:600; color:#1e293b; flex:1; min-width:0; }

		/* Toggle switch */
		.wmc-toggle { position:relative; display:inline-flex; align-items:center; cursor:pointer; flex-shrink:0; }
		.wmc-toggle input { opacity:0; width:0; height:0; position:absolute; }
		.wmc-slider {
			width:38px; height:21px; background:#cbd5e1; border-radius:21px;
			transition:background .2s; position:relative; display:block;
		}
		.wmc-slider::after {
			content:''; position:absolute; top:2.5px; left:2.5px;
			width:16px; height:16px; background:#fff; border-radius:50%;
			transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.2);
		}
		.wmc-toggle input:checked ~ .wmc-slider { background:#6366f1; }
		.wmc-toggle input:checked ~ .wmc-slider::after { transform:translateX(17px); }

		/* Badges */
		.wmc-badge {
			font-size:10px; font-weight:700; padding:2px 7px;
			border-radius:4px; letter-spacing:.3px; white-space:nowrap; flex-shrink:0;
		}
		.wmc-badge-read     { background:#dbeafe; color:#1d4ed8; }
		.wmc-badge-write    { background:#dcfce7; color:#15803d; }
		.wmc-badge-danger   { background:#fee2e2; color:#b91c1c; }
		.wmc-badge-moderate { background:#ffedd5; color:#c2410c; }

		.wmc-card-desc {
			font-size:12px; color:#64748b; line-height:1.55; margin-bottom:9px;
		}
		.wmc-card-slug {
			font-size:10.5px; color:#94a3b8; background:#f1f5f9;
			padding:3px 8px; border-radius:5px; display:block;
			font-family:'SFMono-Regular',Consolas,Menlo,monospace; word-break:break-all;
		}

		/* Bottom save bar */
		.wmc-bottom-bar {
			text-align:center; margin-top:20px; padding:20px;
			background:#fff; border:1px solid #e2e8f0; border-radius:12px;
		}

		@media (max-width:900px) {
			.wmc-stats        { grid-template-columns:repeat(2,1fr); }
			.wmc-cards        { grid-template-columns:1fr; }
			.wmc-toolbar      { flex-direction:column; align-items:flex-start; }
			.wmc-header       { flex-direction:column; gap:12px; align-items:flex-start; }
		}
		</style>

		<script>
		(function() {
			var TOTAL = <?php echo (int) $total; ?>;

			function countEnabled() {
				return document.querySelectorAll('.wmc-checkbox:checked').length;
			}

			function updateGlobalStats() {
				var e = countEnabled();
				document.getElementById('wmc-active-count').textContent   = e;
				document.getElementById('wmc-disabled-count').textContent = TOTAL - e;
				document.getElementById('wmc-enabled-text').textContent   = e + ' of ' + TOTAL + ' controls enabled';
			}

			function updateSectionCount(sectionId) {
				var all     = document.querySelectorAll('.wmc-checkbox[data-section="' + sectionId + '"]');
				var checked = document.querySelectorAll('.wmc-checkbox[data-section="' + sectionId + '"]:checked');
				var el = document.getElementById('count-' + sectionId);
				if (el) el.textContent = checked.length + '/' + all.length;
			}

			window.wmcOnChange = function(cb) {
				var card = cb.closest('.wmc-card');
				if (cb.checked) {
					card.classList.add('wmc-card-on');
					card.classList.remove('wmc-card-off');
				} else {
					card.classList.remove('wmc-card-on');
					card.classList.add('wmc-card-off');
				}
				updateSectionCount(cb.dataset.section);
				updateGlobalStats();
			};

			window.wmcToggleAll = function(state) {
				document.querySelectorAll('.wmc-checkbox').forEach(function(cb) {
					if (cb.checked !== state) { cb.checked = state; wmcOnChange(cb); }
				});
			};

			window.wmcSectionToggle = function(sectionId, state) {
				document.querySelectorAll('.wmc-checkbox[data-section="' + sectionId + '"]').forEach(function(cb) {
					if (cb.checked !== state) { cb.checked = state; wmcOnChange(cb); }
				});
			};

			window.wmcToggleSection = function(sectionId) {
				var body  = document.getElementById('body-'  + sectionId);
				var arrow = document.getElementById('arrow-' + sectionId);
				var c = body.classList.toggle('wmc-collapsed');
				arrow.classList.toggle('wmc-collapsed', c);
			};

			// ---- Generate Application Password ----
			window.wmcGenPassword = function() {
				var userId = document.getElementById('wmc-user-select').value;
				var btn = document.getElementById('wmc-gen-btn');
				btn.textContent = 'Generating...'; btn.disabled = true;

				fetch('<?php echo esc_js( admin_url('admin-ajax.php') ); ?>', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: 'action=wmc_gen_app_password&nonce=<?php echo esc_js( $gen_nonce ); ?>&user_id=' + encodeURIComponent(userId)
				})
				.then(function(r){ return r.json(); })
				.then(function(data) {
					btn.disabled = false;
					if (!data.success) { btn.innerHTML = '&#9889; Generate'; alert('Error: ' + data.data); return; }

					var d = data.data;
					document.getElementById('wmc-cred-user').textContent = d.username;
					document.getElementById('wmc-cred-pass').textContent = d.password;

					// Build MCP config JSON — matches @automattic/mcp-wordpress-remote format
					var serverKey = 'wordpress-' + d.site_url.replace(/^https?:\/\//, '').replace(/[^a-z0-9]/gi, '-').replace(/-+$/, '');
					var config = {};
					config[serverKey] = {
						"command": "npx",
						"args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
						"env": {
							"WP_API_URL": d.site_url + "/wp-json/mcp/mcp-adapter-default-server",
							"WP_API_USERNAME": d.username,
							"WP_API_PASSWORD": d.password
						}
					};
					var fullConfig = { "mcpServers": config };
					document.getElementById('wmc-config-json').value = JSON.stringify(fullConfig, null, 2);

					document.getElementById('wmc-creds-step').style.display = 'flex';
					document.getElementById('wmc-config-step').style.display = 'flex';
					btn.innerHTML = '&#10003; Done! Regenerate'; btn.style.background = '#10b981';
					setTimeout(function(){ btn.style.background = ''; btn.innerHTML = '&#9889; Generate'; }, 3000);
				})
				.catch(function(){ btn.textContent = 'Generate'; btn.disabled = false; alert('Request failed.'); });
			};

			// ---- Copy application password ----
			window.wmcCopyPass = function() {
				var val = document.getElementById('wmc-cred-pass').textContent;
				navigator.clipboard.writeText(val).catch(function() {
					var ta = document.createElement('textarea');
					ta.value = val; document.body.appendChild(ta); ta.select();
					document.execCommand('copy'); document.body.removeChild(ta);
				});
				var btn = document.querySelector('.wmc-copy-inline');
				btn.textContent = 'Copied!'; btn.style.background = '#10b981'; btn.style.color = '#fff';
				setTimeout(function(){ btn.textContent = 'Copy'; btn.style.background = ''; btn.style.color = ''; }, 2000);
			};

			// ---- Copy config JSON ----
			window.wmcCopyConfig = function() {
				var val = document.getElementById('wmc-config-json').value;
				navigator.clipboard.writeText(val).catch(function() {
					var ta = document.getElementById('wmc-config-json');
					ta.select(); document.execCommand('copy');
				});
				var btn = document.querySelector('#wmc-config-step .wmc-copy-btn');
				btn.textContent = 'Copied!'; btn.style.background = '#10b981';
				setTimeout(function(){ btn.textContent = 'Copy JSON'; btn.style.background = ''; }, 2000);
			};
		})();
		</script>
		<?php
	}

	public static function is_ability_enabled( $resource, $operation ) {
		$config = get_option( 'wmc_abilities_config', array() );
		if ( empty( $config ) ) {
			return true;
		}
		return isset( $config[ $resource ][ $operation ] ) && $config[ $resource ][ $operation ];
	}

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

WMC_Settings::init();
