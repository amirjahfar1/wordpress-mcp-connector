<?php
/**
 * WMC OAuth Authorization Server
 *
 * Implements a lightweight OAuth 2.0 Authorization Code flow so Claude
 * can connect to WordPress with just the site URL — no token copy-paste needed.
 *
 * Flow:
 *   1. Claude opens: GET /wp-json/wmc/v1/oauth/authorize?redirect_uri=http://localhost:PORT/callback&state=RANDOM
 *   2. WordPress shows a nice "Authorize Claude?" page (user must be WP logged in)
 *   3. User clicks "Allow" → POST to same endpoint
 *   4. WordPress redirects to: http://localhost:PORT/callback?token=SECRET&state=RANDOM&site=URL
 *   5. Claude's local server catches the redirect → connected!
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function () {

	// ── Authorization endpoint ─────────────────────────────────────────────
	register_rest_route( 'wmc/v1', '/oauth/authorize', array(
		array(
			'methods'             => 'GET',
			'callback'            => 'wmc_oauth_authorize_page',
			'permission_callback' => '__return_true',
		),
		array(
			'methods'             => 'POST',
			'callback'            => 'wmc_oauth_authorize_grant',
			'permission_callback' => '__return_true',
		),
	) );

	// ── Token verify endpoint (Claude calls this to test the token) ────────
	register_rest_route( 'wmc/v1', '/oauth/verify', array(
		'methods'             => 'GET',
		'callback'            => 'wmc_oauth_verify',
		'permission_callback' => '__return_true',
	) );

	// ── Revoke endpoint ────────────────────────────────────────────────────
	register_rest_route( 'wmc/v1', '/oauth/revoke', array(
		'methods'             => 'POST',
		'callback'            => 'wmc_oauth_revoke',
		'permission_callback' => function() { return current_user_can( 'manage_options' ); },
	) );
} );


// ============================================================================
//  GET — Show Authorization Page
// ============================================================================

function wmc_oauth_authorize_page( WP_REST_Request $request ) {
	$redirect_uri = $request->get_param( 'redirect_uri' );
	$state        = $request->get_param( 'state' ) ?? '';

	// Validate redirect_uri — must be localhost (Claude's local server)
	if ( ! wmc_oauth_valid_redirect( $redirect_uri ) ) {
		wp_die( '<h2>Invalid redirect_uri</h2><p>Only localhost redirect URIs are permitted.</p>', 'WMC OAuth Error', 400 );
	}

	// User must be logged in as admin
	if ( ! is_user_logged_in() ) {
		// Redirect to WP login, then back here
		$back = add_query_arg( array(
			'redirect_uri' => urlencode( $redirect_uri ),
			'state'        => urlencode( $state ),
		), rest_url( 'wmc/v1/oauth/authorize' ) );
		wp_redirect( wp_login_url( $back ) );
		exit;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '<h2>Permission Denied</h2><p>Only administrators can authorize Claude.</p>', 'WMC OAuth Error', 403 );
	}

	// Show the authorization page (HTML — exit after output)
	wmc_oauth_render_page( $redirect_uri, $state );
	exit;
}


// ============================================================================
//  POST — Grant Authorization (user clicked Allow)
// ============================================================================

function wmc_oauth_authorize_grant( WP_REST_Request $request ) {
	$redirect_uri = $request->get_param( 'redirect_uri' );
	$state        = $request->get_param( 'state' ) ?? '';
	$nonce        = $request->get_param( 'wmc_nonce' );
	$action       = $request->get_param( 'wmc_action' ); // 'allow' or 'deny'

	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Unauthorized', 403 );
	}

	if ( ! wp_verify_nonce( $nonce, 'wmc_oauth_authorize' ) ) {
		wp_die( 'Invalid request. Please try again.', 400 );
	}

	if ( ! wmc_oauth_valid_redirect( $redirect_uri ) ) {
		wp_die( 'Invalid redirect URI.', 400 );
	}

	if ( $action === 'deny' ) {
		wp_redirect( add_query_arg( array(
			'error'             => 'access_denied',
			'error_description' => 'User denied the authorization request.',
			'state'             => $state,
		), $redirect_uri ) );
		exit;
	}

	// --- Generate / retrieve the site's secret token ---
	$token = get_option( 'wmc_secret_token', '' );
	if ( empty( $token ) ) {
		$token = bin2hex( random_bytes( 32 ) );
		update_option( 'wmc_secret_token', $token, false );
	}

	// Log the authorization
	$log   = get_option( 'wmc_oauth_log', array() );
	$log[] = array(
		'time'     => current_time( 'Y-m-d H:i:s' ),
		'user'     => wp_get_current_user()->user_login,
		'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
		'redirect' => $redirect_uri,
	);
	update_option( 'wmc_oauth_log', array_slice( $log, -20 ), false );

	// Redirect to Claude's local server with the token
	$callback = add_query_arg( array(
		'token'     => $token,
		'state'     => $state,
		'site'      => urlencode( get_site_url() ),
		'site_name' => urlencode( get_bloginfo( 'name' ) ),
		'username'  => urlencode( wp_get_current_user()->user_login ),
		'version'   => WMC_VERSION,
	), $redirect_uri );

	wp_redirect( $callback );
	exit;
}


// ============================================================================
//  GET — Verify Token
// ============================================================================

function wmc_oauth_verify( WP_REST_Request $request ) {
	$token  = '';
	$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
	if ( $header && stripos( $header, 'Bearer ' ) === 0 ) {
		$token = trim( substr( $header, 7 ) );
	}
	if ( empty( $token ) ) $token = sanitize_text_field( $request->get_param( 'token' ) ?? '' );

	$stored = get_option( 'wmc_secret_token', '' );
	if ( empty( $stored ) || ! hash_equals( $stored, $token ) ) {
		return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid token' ), 401 );
	}

	return array(
		'success'     => true,
		'connected'   => true,
		'site_url'    => get_site_url(),
		'site_name'   => get_bloginfo( 'name' ),
		'wp_version'  => get_bloginfo( 'version' ),
		'wmc_version' => WMC_VERSION,
		'message'     => 'Token valid. WordPress MCP Connector is ready.',
	);
}


// ============================================================================
//  POST — Revoke Token
// ============================================================================

function wmc_oauth_revoke( WP_REST_Request $request ) {
	$new_token = bin2hex( random_bytes( 32 ) );
	update_option( 'wmc_secret_token', $new_token, false );
	return array( 'success' => true, 'message' => 'Token revoked. Generate a new connection from Claude.' );
}


// ============================================================================
//  HELPER: Validate redirect_uri
// ============================================================================

function wmc_oauth_valid_redirect( $uri ) {
	if ( empty( $uri ) ) return false;
	$parsed = parse_url( $uri );
	$host   = $parsed['host'] ?? '';
	// Only allow localhost / 127.0.0.1 / ::1
	return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
}


// ============================================================================
//  RENDER: Authorization Page HTML
// ============================================================================

function wmc_oauth_render_page( $redirect_uri, $state ) {
	$site_name    = get_bloginfo( 'name' );
	$site_url     = get_site_url();
	$site_icon    = get_site_icon_url( 64 );
	$user         = wp_get_current_user();
	$nonce        = wp_create_nonce( 'wmc_oauth_authorize' );
	$abilities_url = rest_url( 'wp-abilities/v1/abilities' );

	// POST endpoint (same REST route)
	$post_url = rest_url( 'wmc/v1/oauth/authorize' );
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Authorize Claude — <?php echo esc_html( $site_name ); ?></title>
		<style>
			*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
			body {
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				min-height: 100vh;
				display: flex; align-items: center; justify-content: center;
				padding: 20px;
			}
			.card {
				background: #fff;
				border-radius: 20px;
				box-shadow: 0 25px 60px rgba(0,0,0,0.2);
				max-width: 440px;
				width: 100%;
				overflow: hidden;
			}
			.card-header {
				background: linear-gradient(135deg, #6366f1, #8b5cf6);
				padding: 32px 32px 28px;
				text-align: center;
				color: #fff;
			}
			.logos {
				display: flex; align-items: center; justify-content: center;
				gap: 16px; margin-bottom: 20px;
			}
			.logo-box {
				width: 56px; height: 56px; border-radius: 14px;
				background: rgba(255,255,255,0.2);
				display: flex; align-items: center; justify-content: center;
				font-size: 28px; border: 2px solid rgba(255,255,255,0.3);
			}
			.logo-box img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }
			.connector { font-size: 22px; opacity: 0.7; }
			.card-header h1 { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
			.card-header p  { font-size: 14px; opacity: 0.85; }

			.card-body { padding: 28px 32px; }

			.site-info {
				background: #f8fafc; border: 1px solid #e2e8f0;
				border-radius: 12px; padding: 16px 18px; margin-bottom: 22px;
			}
			.site-info-row { display: flex; gap: 10px; align-items: center; margin-bottom: 6px; }
			.site-info-row:last-child { margin-bottom: 0; }
			.site-info-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; flex-shrink: 0; }
			.site-info-label { font-size: 12px; color: #94a3b8; width: 70px; flex-shrink: 0; }
			.site-info-val { font-size: 13px; color: #334155; font-weight: 500; word-break: break-all; }

			.perms { margin-bottom: 22px; }
			.perms h3 { font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; }
			.perm-item {
				display: flex; align-items: flex-start; gap: 10px;
				padding: 10px 0; border-bottom: 1px solid #f1f5f9;
			}
			.perm-item:last-child { border-bottom: none; }
			.perm-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
			.perm-text { font-size: 13px; color: #475569; line-height: 1.4; }
			.perm-text strong { color: #1e293b; }

			.user-row {
				display: flex; align-items: center; gap: 10px;
				background: #faf5ff; border: 1px solid #e9d5ff;
				border-radius: 10px; padding: 12px 14px; margin-bottom: 22px;
			}
			.user-avatar {
				width: 36px; height: 36px; border-radius: 50%; object-fit: cover;
				border: 2px solid #c4b5fd;
			}
			.user-info { font-size: 12px; }
			.user-info strong { font-size: 13px; color: #4c1d95; display: block; }
			.user-info span { color: #7c3aed; }

			.btn-row { display: flex; gap: 10px; }
			.btn {
				flex: 1; padding: 13px 20px; border-radius: 10px;
				font-size: 14px; font-weight: 700; cursor: pointer;
				border: none; transition: all 0.15s;
			}
			.btn-allow {
				background: linear-gradient(135deg, #6366f1, #8b5cf6);
				color: #fff;
			}
			.btn-allow:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); }
			.btn-deny { background: #f1f5f9; color: #64748b; }
			.btn-deny:hover { background: #fee2e2; color: #ef4444; }

			.footer {
				padding: 14px 32px;
				background: #f8fafc; border-top: 1px solid #e2e8f0;
				text-align: center; font-size: 11px; color: #94a3b8;
			}
			.footer a { color: #6366f1; text-decoration: none; }
		</style>
	</head>
	<body>
		<div class="card">
			<div class="card-header">
				<div class="logos">
					<div class="logo-box">🤖</div>
					<div class="connector">⟷</div>
					<div class="logo-box">
						<?php if ( $site_icon ) : ?>
							<img src="<?php echo esc_url( $site_icon ); ?>" alt="Site Icon">
						<?php else : ?>
							🌐
						<?php endif; ?>
					</div>
				</div>
				<h1>Claude wants to connect</h1>
				<p>to <strong><?php echo esc_html( $site_name ); ?></strong></p>
			</div>

			<div class="card-body">

				<!-- Site Info -->
				<div class="site-info">
					<div class="site-info-row">
						<div class="site-info-dot"></div>
						<div class="site-info-label">Site</div>
						<div class="site-info-val"><?php echo esc_html( $site_name ); ?></div>
					</div>
					<div class="site-info-row">
						<div class="site-info-dot" style="background:#6366f1"></div>
						<div class="site-info-label">URL</div>
						<div class="site-info-val"><?php echo esc_html( $site_url ); ?></div>
					</div>
					<div class="site-info-row">
						<div class="site-info-dot" style="background:#f59e0b"></div>
						<div class="site-info-label">WP</div>
						<div class="site-info-val">WordPress <?php echo esc_html( get_bloginfo( 'version' ) ); ?> + MCP Connector v<?php echo WMC_VERSION; ?></div>
					</div>
				</div>

				<!-- Permissions -->
				<div class="perms">
					<h3>Claude will be able to</h3>
					<div class="perm-item">
						<div class="perm-icon">📝</div>
						<div class="perm-text"><strong>Read & manage content</strong> — posts, pages, media, comments</div>
					</div>
					<div class="perm-item">
						<div class="perm-icon">🛒</div>
						<div class="perm-text"><strong>Full WooCommerce access</strong> — products, orders, coupons, customers</div>
					</div>
					<div class="perm-item">
						<div class="perm-icon">⚙️</div>
						<div class="perm-text"><strong>Site administration</strong> — plugins, themes, settings, database</div>
					</div>
					<div class="perm-item">
						<div class="perm-icon">🔐</div>
						<div class="perm-text"><strong>Security & backups</strong> — user sessions, IP blocking, database backup</div>
					</div>
				</div>

				<!-- Logged in as -->
				<div class="user-row">
					<?php $avatar = get_avatar_url( $user->ID, array( 'size' => 36 ) ); ?>
					<img src="<?php echo esc_url( $avatar ); ?>" alt="Avatar" class="user-avatar">
					<div class="user-info">
						<strong><?php echo esc_html( $user->display_name ); ?></strong>
						<span>Authorizing as <?php echo esc_html( $user->user_login ); ?> (Administrator)</span>
					</div>
				</div>

				<!-- Buttons -->
				<form method="post" action="<?php echo esc_url( $post_url ); ?>">
					<input type="hidden" name="redirect_uri" value="<?php echo esc_attr( $redirect_uri ); ?>">
					<input type="hidden" name="state"        value="<?php echo esc_attr( $state ); ?>">
					<input type="hidden" name="wmc_nonce"    value="<?php echo esc_attr( $nonce ); ?>">
					<div class="btn-row">
						<button type="submit" name="wmc_action" value="deny"  class="btn btn-deny">Deny</button>
						<button type="submit" name="wmc_action" value="allow" class="btn btn-allow">Allow Access</button>
					</div>
				</form>

			</div>

			<div class="footer">
				This authorization is secured by WordPress. <a href="<?php echo esc_url( admin_url( 'admin.php?page=wmc-settings' ) ); ?>">Manage connections →</a>
			</div>
		</div>
	</body>
	</html>
	<?php
}
