<?php
/**
 * WMC OAuth Authorization Server
 *
 * OAuth 2.0 Authorization Code flow. Authorization page runs as a normal
 * WordPress request (not REST API) so session cookies and login redirects
 * work correctly — no double login prompt.
 *
 * Authorization URL:  https://site.com/?wmc_oauth=1&redirect_uri=http://localhost:PORT/callback&state=XYZ
 * After Allow click:  redirects to http://localhost:PORT/callback?token=...&site=...
 * REST verify/revoke: /wp-json/wmc/v1/oauth/verify  and  /wp-json/wmc/v1/oauth/revoke
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================================
//  1. Intercept ?wmc_oauth=1 on ANY WordPress URL (normal request, not REST)
//     This ensures wp_get_current_user() / is_user_logged_in() use the
//     browser's session cookie exactly as the admin dashboard does.
// ============================================================================

add_action( 'init', function () {
	if ( empty( $_GET['wmc_oauth'] ) ) return;

	$redirect_uri = $_GET['redirect_uri'] ?? '';
	$state        = sanitize_text_field( $_GET['state'] ?? '' );
	$action       = $_POST['wmc_action'] ?? ''; // 'allow' or 'deny' on form submit

	// Validate redirect_uri — localhost only
	if ( ! wmc_oauth_valid_redirect( $redirect_uri ) ) {
		wp_die( '<h2>Invalid redirect_uri</h2><p>Only localhost redirect URIs are allowed.</p>', 'WMC Error', array( 'response' => 400 ) );
	}

	// ── Not logged in → send to WP login, come back here after ────────────
	if ( ! is_user_logged_in() ) {
		$return_url = add_query_arg( array(
			'wmc_oauth'    => '1',
			'redirect_uri' => urlencode( $redirect_uri ),
			'state'        => urlencode( $state ),
		), home_url( '/' ) );
		wp_redirect( wp_login_url( $return_url ) );
		exit;
	}

	// ── Logged in but not admin ────────────────────────────────────────────
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '<h2>Permission Denied</h2><p>Only site administrators can authorize Claude.</p>', 'WMC Error', array( 'response' => 403 ) );
	}

	// ── Handle form submission (POST) ──────────────────────────────────────
	if ( $action ) {
		if ( ! isset( $_POST['wmc_nonce'] ) || ! wp_verify_nonce( $_POST['wmc_nonce'], 'wmc_oauth_authorize' ) ) {
			wp_die( 'Security check failed. Please go back and try again.', 'WMC Error', array( 'response' => 403 ) );
		}

		if ( $action === 'deny' ) {
			wp_redirect( add_query_arg( array(
				'error'             => 'access_denied',
				'error_description' => urlencode( 'User denied the authorization request.' ),
				'state'             => $state,
			), $redirect_uri ) );
			exit;
		}

		// ── Grant: get or create the secret token ─────────────────────────
		$token = get_option( 'wmc_secret_token', '' );
		if ( empty( $token ) ) {
			$token = bin2hex( random_bytes( 32 ) );
			update_option( 'wmc_secret_token', $token, false );
		}

		// Log authorization
		$log   = get_option( 'wmc_oauth_log', array() );
		$log[] = array(
			'time'     => current_time( 'Y-m-d H:i:s' ),
			'user'     => wp_get_current_user()->user_login,
			'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
			'redirect' => $redirect_uri,
		);
		update_option( 'wmc_oauth_log', array_slice( $log, -20 ), false );

		// Redirect to Claude's local server with the token
		wp_redirect( add_query_arg( array(
			'token'     => $token,
			'state'     => $state,
			'site'      => urlencode( get_site_url() ),
			'site_name' => urlencode( get_bloginfo( 'name' ) ),
			'username'  => urlencode( wp_get_current_user()->user_login ),
			'version'   => WMC_VERSION,
		), $redirect_uri ) );
		exit;
	}

	// ── GET: show authorization page ──────────────────────────────────────
	wmc_oauth_render_page( $redirect_uri, $state );
	exit;
}, 1 );


// ============================================================================
//  2. REST endpoints: verify and revoke (these don't need browser session)
// ============================================================================

add_action( 'rest_api_init', function () {

	register_rest_route( 'wmc/v1', '/oauth/verify', array(
		'methods'             => 'GET',
		'callback'            => 'wmc_oauth_verify',
		'permission_callback' => '__return_true',
	) );

	register_rest_route( 'wmc/v1', '/oauth/revoke', array(
		'methods'             => 'POST',
		'callback'            => 'wmc_oauth_revoke',
		'permission_callback' => function() { return current_user_can( 'manage_options' ); },
	) );
} );

function wmc_oauth_verify( WP_REST_Request $request ) {
	$token  = '';
	$header = $_SERVER['HTTP_AUTHORIZATION'] ?? ( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '' );
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

function wmc_oauth_revoke( WP_REST_Request $request ) {
	$new_token = bin2hex( random_bytes( 32 ) );
	update_option( 'wmc_secret_token', $new_token, false );
	return array( 'success' => true, 'message' => 'Token revoked. Re-authorize from Claude.' );
}


// ============================================================================
//  3. Validate redirect_uri — localhost only
// ============================================================================

function wmc_oauth_valid_redirect( $uri ) {
	if ( empty( $uri ) ) return false;
	$host = parse_url( $uri, PHP_URL_HOST );
	return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true );
}


// ============================================================================
//  4. Authorization Page HTML
// ============================================================================

function wmc_oauth_render_page( $redirect_uri, $state ) {
	$site_name = get_bloginfo( 'name' );
	$site_url  = get_site_url();
	$site_icon = get_site_icon_url( 64 );
	$user      = wp_get_current_user();
	$nonce     = wp_create_nonce( 'wmc_oauth_authorize' );
	$post_url  = add_query_arg( array(
		'wmc_oauth'    => '1',
		'redirect_uri' => urlencode( $redirect_uri ),
		'state'        => urlencode( $state ),
	), home_url( '/' ) );
	?><!DOCTYPE html>
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
			display: flex; align-items: center; justify-content: center; padding: 20px;
		}
		.card {
			background: #fff; border-radius: 20px;
			box-shadow: 0 25px 60px rgba(0,0,0,.2);
			max-width: 440px; width: 100%; overflow: hidden;
		}
		.card-header {
			background: linear-gradient(135deg, #6366f1, #8b5cf6);
			padding: 32px 32px 28px; text-align: center; color: #fff;
		}
		.logos {
			display: flex; align-items: center; justify-content: center;
			gap: 16px; margin-bottom: 20px;
		}
		.logo-box {
			width: 56px; height: 56px; border-radius: 14px;
			background: rgba(255,255,255,.2);
			display: flex; align-items: center; justify-content: center;
			font-size: 28px; border: 2px solid rgba(255,255,255,.3);
			overflow: hidden;
		}
		.logo-box img { width: 100%; height: 100%; object-fit: cover; }
		.connector { font-size: 22px; opacity: .7; }
		.card-header h1 { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
		.card-header p  { font-size: 14px; opacity: .85; }

		.card-body { padding: 28px 32px; }

		.site-info {
			background: #f8fafc; border: 1px solid #e2e8f0;
			border-radius: 12px; padding: 14px 18px; margin-bottom: 20px;
		}
		.site-row { display: flex; gap: 10px; align-items: center; padding: 5px 0; }
		.dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
		.lbl { font-size: 12px; color: #94a3b8; width: 55px; flex-shrink: 0; }
		.val { font-size: 13px; color: #334155; font-weight: 500; word-break: break-all; }

		.perms { margin-bottom: 20px; }
		.perms h3 {
			font-size: 11px; font-weight: 700; color: #94a3b8;
			text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px;
		}
		.perm {
			display: flex; align-items: flex-start; gap: 10px;
			padding: 9px 0; border-bottom: 1px solid #f1f5f9;
		}
		.perm:last-child { border: none; }
		.perm-icon { font-size: 17px; flex-shrink: 0; }
		.perm-text { font-size: 13px; color: #475569; line-height: 1.4; }
		.perm-text strong { color: #1e293b; }

		.user-row {
			display: flex; align-items: center; gap: 10px;
			background: #faf5ff; border: 1px solid #e9d5ff;
			border-radius: 10px; padding: 12px 14px; margin-bottom: 20px;
		}
		.avatar { width: 36px; height: 36px; border-radius: 50%; border: 2px solid #c4b5fd; }
		.uinfo { font-size: 12px; }
		.uinfo strong { display: block; font-size: 13px; color: #4c1d95; }
		.uinfo span { color: #7c3aed; }

		.btn-row { display: flex; gap: 10px; }
		.btn {
			flex: 1; padding: 13px; border-radius: 10px;
			font-size: 14px; font-weight: 700; cursor: pointer;
			border: none; transition: all .15s;
		}
		.btn-allow { background: linear-gradient(135deg,#6366f1,#8b5cf6); color: #fff; }
		.btn-allow:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,.4); }
		.btn-deny  { background: #f1f5f9; color: #64748b; }
		.btn-deny:hover  { background: #fee2e2; color: #ef4444; }

		.footer {
			padding: 14px 32px; background: #f8fafc; border-top: 1px solid #e2e8f0;
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
				<?php echo $site_icon ? '<img src="' . esc_url( $site_icon ) . '" alt="">' : '🌐'; ?>
			</div>
		</div>
		<h1>Claude wants to connect</h1>
		<p>to <strong><?php echo esc_html( $site_name ); ?></strong></p>
	</div>

	<div class="card-body">

		<div class="site-info">
			<div class="site-row">
				<div class="dot" style="background:#10b981"></div>
				<div class="lbl">Site</div>
				<div class="val"><?php echo esc_html( $site_name ); ?></div>
			</div>
			<div class="site-row">
				<div class="dot" style="background:#6366f1"></div>
				<div class="lbl">URL</div>
				<div class="val"><?php echo esc_html( $site_url ); ?></div>
			</div>
			<div class="site-row">
				<div class="dot" style="background:#f59e0b"></div>
				<div class="lbl">Plugin</div>
				<div class="val">WP MCP Connector v<?php echo WMC_VERSION; ?></div>
			</div>
		</div>

		<div class="perms">
			<h3>Claude will be able to</h3>
			<div class="perm"><div class="perm-icon">📝</div><div class="perm-text"><strong>Read & manage content</strong> — posts, pages, media, comments</div></div>
			<div class="perm"><div class="perm-icon">🛒</div><div class="perm-text"><strong>Full WooCommerce access</strong> — products, orders, coupons, customers</div></div>
			<div class="perm"><div class="perm-icon">⚙️</div><div class="perm-text"><strong>Site administration</strong> — plugins, themes, settings, database</div></div>
			<div class="perm"><div class="perm-icon">🔐</div><div class="perm-text"><strong>Security & backups</strong> — sessions, IP blocking, database backup</div></div>
		</div>

		<div class="user-row">
			<img src="<?php echo esc_url( get_avatar_url( $user->ID, array( 'size' => 36 ) ) ); ?>" class="avatar" alt="">
			<div class="uinfo">
				<strong><?php echo esc_html( $user->display_name ); ?></strong>
				<span>Authorizing as <?php echo esc_html( $user->user_login ); ?> (Administrator)</span>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( $post_url ); ?>">
			<input type="hidden" name="wmc_nonce"    value="<?php echo esc_attr( $nonce ); ?>">
			<input type="hidden" name="redirect_uri" value="<?php echo esc_attr( $redirect_uri ); ?>">
			<input type="hidden" name="state"        value="<?php echo esc_attr( $state ); ?>">
			<div class="btn-row">
				<button type="submit" name="wmc_action" value="deny"  class="btn btn-deny">Deny</button>
				<button type="submit" name="wmc_action" value="allow" class="btn btn-allow">✓ Allow Access</button>
			</div>
		</form>
	</div>

	<div class="footer">
		Secured by WordPress. <a href="<?php echo esc_url( admin_url( 'admin.php?page=wmc-settings' ) ); ?>">Manage connections →</a>
	</div>
</div>
</body>
</html>
<?php
}
