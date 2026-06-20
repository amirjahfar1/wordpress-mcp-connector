<?php
/**
 * WMC OAuth Authorization — Polling-based, no localhost server needed
 *
 * New flow (ERR_EMPTY_RESPONSE permanently fixed):
 *   1. Claude opens: https://site.com/?wmc_oauth=1&state=RANDOM
 *   2. WordPress shows "Authorize Claude?" page (normal WP request, cookies work)
 *   3. User clicks Allow → WordPress stores token in transient keyed by state
 *   4. WordPress shows beautiful success page ON THE SITE — browser never goes to localhost
 *   5. Claude polls GET /wp-json/wmc/v1/oauth/poll?state=STATE every 2s
 *   6. Poll returns token → Claude is connected. Done.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================================
//  1. Intercept ?wmc_oauth=1  (normal WP request — cookies work)
// ============================================================================

add_action( 'init', function () {
	if ( empty( $_GET['wmc_oauth'] ) ) return;

	$state  = sanitize_text_field( $_GET['state'] ?? '' );
	$action = $_POST['wmc_action'] ?? '';

	if ( empty( $state ) ) {
		wp_die( 'Missing state parameter.', 'WMC Error', array( 'response' => 400 ) );
	}

	// Not logged in → WP login → back here
	if ( ! is_user_logged_in() ) {
		$back = add_query_arg( array(
			'wmc_oauth' => '1',
			'state'     => urlencode( $state ),
		), home_url( '/' ) );
		wp_redirect( wp_login_url( $back ) );
		exit;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '<h2>Permission Denied</h2><p>Only administrators can authorize Claude.</p>', 'WMC Error', array( 'response' => 403 ) );
	}

	// ── Form submitted ────────────────────────────────────────────────────
	if ( $action ) {
		if ( ! isset( $_POST['wmc_nonce'] ) || ! wp_verify_nonce( $_POST['wmc_nonce'], 'wmc_oauth_' . $state ) ) {
			wp_die( 'Security check failed.', 'WMC Error', array( 'response' => 403 ) );
		}

		if ( $action === 'deny' ) {
			// Store denial so poll knows
			set_transient( 'wmc_oauth_' . $state, array( 'denied' => true ), 5 * MINUTE_IN_SECONDS );
			wmc_oauth_render_denied();
			exit;
		}

		// Grant — get/create secret token
		$token = get_option( 'wmc_secret_token', '' );
		if ( empty( $token ) ) {
			$token = bin2hex( random_bytes( 32 ) );
			update_option( 'wmc_secret_token', $token, false );
		}

		// Store in transient so poll endpoint can return it (expires 5 min)
		set_transient( 'wmc_oauth_' . $state, array(
			'token'     => $token,
			'site'      => get_site_url(),
			'site_name' => get_bloginfo( 'name' ),
			'version'   => WMC_VERSION,
			'username'  => wp_get_current_user()->user_login,
		), 5 * MINUTE_IN_SECONDS );

		// Log
		$log   = get_option( 'wmc_oauth_log', array() );
		$log[] = array(
			'time'  => current_time( 'Y-m-d H:i:s' ),
			'user'  => wp_get_current_user()->user_login,
			'ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
		);
		update_option( 'wmc_oauth_log', array_slice( $log, -20 ), false );

		// Show success page ON THE SITE — no localhost redirect
		wmc_oauth_render_success();
		exit;
	}

	// ── Show authorization page ───────────────────────────────────────────
	wmc_oauth_render_auth_page( $state );
	exit;

}, 1 );


// ============================================================================
//  2. REST: Poll endpoint — Claude calls this every 2s waiting for Allow
// ============================================================================

add_action( 'rest_api_init', function () {

	// Poll — returns token once user clicks Allow
	register_rest_route( 'wmc/v1', '/oauth/poll', array(
		'methods'             => 'GET',
		'callback'            => function( WP_REST_Request $req ) {
			$state = sanitize_text_field( $req->get_param( 'state' ) ?? '' );
			if ( empty( $state ) ) return new WP_REST_Response( array( 'status' => 'waiting' ), 200 );

			$data = get_transient( 'wmc_oauth_' . $state );
			if ( ! $data ) return new WP_REST_Response( array( 'status' => 'waiting' ), 200 );

			if ( ! empty( $data['denied'] ) ) {
				delete_transient( 'wmc_oauth_' . $state );
				return new WP_REST_Response( array( 'status' => 'denied' ), 200 );
			}

			delete_transient( 'wmc_oauth_' . $state );
			return new WP_REST_Response( array(
				'status'    => 'authorized',
				'token'     => $data['token'],
				'site'      => $data['site'],
				'site_name' => $data['site_name'],
				'version'   => $data['version'],
				'username'  => $data['username'],
			), 200 );
		},
		'permission_callback' => '__return_true',
	) );

	// Verify token
	register_rest_route( 'wmc/v1', '/oauth/verify', array(
		'methods'             => 'GET',
		'callback'            => 'wmc_oauth_verify',
		'permission_callback' => '__return_true',
	) );

	// Revoke token
	register_rest_route( 'wmc/v1', '/oauth/revoke', array(
		'methods'             => 'POST',
		'callback'            => 'wmc_oauth_revoke',
		'permission_callback' => function() { return current_user_can( 'manage_options' ); },
	) );
} );

function wmc_oauth_verify( WP_REST_Request $request ) {
	$token  = '';
	$header = $_SERVER['HTTP_AUTHORIZATION'] ?? ( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '' );
	if ( $header && stripos( $header, 'Bearer ' ) === 0 ) $token = trim( substr( $header, 7 ) );
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

function wmc_oauth_revoke() {
	update_option( 'wmc_secret_token', bin2hex( random_bytes( 32 ) ), false );
	return array( 'success' => true, 'message' => 'Token revoked. Re-authorize from Claude.' );
}


// ============================================================================
//  3. HTML Pages
// ============================================================================

function wmc_oauth_render_auth_page( $state ) {
	$site_name = get_bloginfo( 'name' );
	$site_url  = get_site_url();
	$site_icon = get_site_icon_url( 64 );
	$user      = wp_get_current_user();
	$nonce     = wp_create_nonce( 'wmc_oauth_' . $state );
	$post_url  = add_query_arg( array( 'wmc_oauth' => '1', 'state' => urlencode( $state ) ), home_url( '/' ) );
	?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Authorize Claude — <?php echo esc_html( $site_name ); ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:24px;box-shadow:0 30px 70px rgba(0,0,0,.22);max-width:440px;width:100%;overflow:hidden}
.card-top{background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:36px 32px 28px;text-align:center;color:#fff}
.logos{display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:20px}
.logo-box{width:56px;height:56px;border-radius:14px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:28px;border:2px solid rgba(255,255,255,.3);overflow:hidden}
.logo-box img{width:100%;height:100%;object-fit:cover}
.card-top h1{font-size:22px;font-weight:800;margin-bottom:6px}
.card-top p{font-size:14px;opacity:.85}
.card-body{padding:24px 32px}
.site-info{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 18px;margin-bottom:18px}
.sr{display:flex;gap:10px;align-items:center;padding:4px 0}
.dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.lbl{font-size:12px;color:#94a3b8;width:55px;flex-shrink:0}
.val{font-size:13px;color:#334155;font-weight:500;word-break:break-all}
.perms{margin-bottom:18px}
.perms h3{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}
.perm{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9}
.perm:last-child{border:none}
.pi{font-size:17px;flex-shrink:0}
.pt{font-size:13px;color:#475569;line-height:1.4}
.pt strong{color:#1e293b}
.user-row{display:flex;align-items:center;gap:10px;background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:12px 14px;margin-bottom:18px}
.avatar{width:36px;height:36px;border-radius:50%;border:2px solid #c4b5fd}
.uinfo strong{display:block;font-size:13px;color:#4c1d95}
.uinfo span{font-size:12px;color:#7c3aed}
.btn-row{display:flex;gap:10px}
.btn{flex:1;padding:13px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;border:none;transition:all .15s}
.btn-allow{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff}
.btn-allow:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(99,102,241,.4)}
.btn-deny{background:#f1f5f9;color:#64748b}
.btn-deny:hover{background:#fee2e2;color:#ef4444}
.footer{padding:14px 32px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:center;font-size:11px;color:#94a3b8}
.footer a{color:#6366f1;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <div class="card-top">
    <div class="logos">
      <div class="logo-box">&#129302;</div>
      <div style="font-size:22px;opacity:.7">&#8606;</div>
      <div class="logo-box"><?php echo $site_icon ? '<img src="' . esc_url( $site_icon ) . '" alt="">' : '&#127760;'; ?></div>
    </div>
    <h1>Claude wants to connect</h1>
    <p>to <strong><?php echo esc_html( $site_name ); ?></strong></p>
  </div>
  <div class="card-body">
    <div class="site-info">
      <div class="sr"><div class="dot" style="background:#10b981"></div><div class="lbl">Site</div><div class="val"><?php echo esc_html( $site_name ); ?></div></div>
      <div class="sr"><div class="dot" style="background:#6366f1"></div><div class="lbl">URL</div><div class="val"><?php echo esc_html( $site_url ); ?></div></div>
      <div class="sr"><div class="dot" style="background:#f59e0b"></div><div class="lbl">Plugin</div><div class="val">WP MCP Connector v<?php echo WMC_VERSION; ?></div></div>
    </div>
    <div class="perms">
      <h3>Claude will be able to</h3>
      <div class="perm"><div class="pi">&#128221;</div><div class="pt"><strong>Read &amp; manage content</strong> — posts, pages, media, comments</div></div>
      <div class="perm"><div class="pi">&#128722;</div><div class="pt"><strong>Full WooCommerce access</strong> — products, orders, coupons, customers</div></div>
      <div class="perm"><div class="pi">&#9881;&#65039;</div><div class="pt"><strong>Site administration</strong> — plugins, themes, settings, database</div></div>
      <div class="perm"><div class="pi">&#128272;</div><div class="pt"><strong>Security &amp; backups</strong> — sessions, IP blocking, database backup</div></div>
    </div>
    <div class="user-row">
      <img src="<?php echo esc_url( get_avatar_url( $user->ID, array( 'size' => 36 ) ) ); ?>" class="avatar" alt="">
      <div class="uinfo">
        <strong><?php echo esc_html( $user->display_name ); ?></strong>
        <span>Authorizing as <?php echo esc_html( $user->user_login ); ?> (Administrator)</span>
      </div>
    </div>
    <form method="post" action="<?php echo esc_url( $post_url ); ?>">
      <input type="hidden" name="wmc_nonce"  value="<?php echo esc_attr( $nonce ); ?>">
      <input type="hidden" name="wmc_action" value="allow">
      <div class="btn-row">
        <button type="button" onclick="document.querySelector('[name=wmc_action]').value='deny';this.closest('form').submit()" class="btn btn-deny">Deny</button>
        <button type="submit" class="btn btn-allow">&#10003; Allow Access</button>
      </div>
    </form>
  </div>
  <div class="footer">Secured by WordPress. <a href="<?php echo esc_url( admin_url( 'admin.php?page=wmc-settings' ) ); ?>">Manage connections &rarr;</a></div>
</div>
</body></html>
<?php
}

function wmc_oauth_render_success() {
	$site_name = get_bloginfo( 'name' );
	$site_url  = get_site_url();
	?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Connected — <?php echo esc_html( $site_name ); ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:24px;box-shadow:0 30px 70px rgba(0,0,0,.22);max-width:460px;width:100%;overflow:hidden}
.card-top{background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:40px 32px 32px;text-align:center;color:#fff}
.ring{width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,.15);border:3px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:36px;animation:pop .4s cubic-bezier(.34,1.56,.64,1) both}
@keyframes pop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.card-top h1{font-size:26px;font-weight:800;margin-bottom:6px}
.card-top p{font-size:14px;opacity:.85}
.card-body{padding:24px 32px 0}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px}
.box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;text-align:center}
.bi{font-size:22px;margin-bottom:6px}
.bl{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.bv{font-size:13px;color:#334155;font-weight:600;margin-top:2px;word-break:break-all}
.badge{display:flex;align-items:center;justify-content:center;gap:8px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:11px 16px;margin-bottom:22px;font-size:13px;color:#15803d;font-weight:600}
.bdot{width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0}
.dev{border-top:1px solid #f1f5f9;padding:20px 32px 22px;text-align:center}
.dl{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}
.dn{font-size:16px;font-weight:800;color:#1e293b;margin-bottom:3px}
.dt{font-size:12px;color:#64748b;margin-bottom:14px}
.slinks{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}
.sb{display:inline-flex;align-items:center;gap:7px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;transition:all .15s}
.sb:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.15)}
.sb svg{width:15px;height:15px;flex-shrink:0}
.gh{background:#0f172a;color:#fff}
.li{background:#0077b5;color:#fff}
.fb{background:#1877f2;color:#fff}
.foot{background:#f8fafc;border-top:1px solid #e2e8f0;padding:13px 32px;text-align:center;font-size:11px;color:#94a3b8;line-height:1.6}
.foot strong{color:#6366f1}
</style>
</head>
<body>
<div class="card">
  <div class="card-top">
    <div class="ring">&#10003;</div>
    <h1>Connected!</h1>
    <p>Claude is now connected to <strong><?php echo esc_html( $site_name ); ?></strong></p>
  </div>
  <div class="card-body">
    <div class="grid">
      <div class="box"><div class="bi">&#127760;</div><div class="bl">Site</div><div class="bv"><?php echo esc_html( str_replace( array('https://','http://'), '', rtrim( $site_url, '/' ) ) ); ?></div></div>
      <div class="box"><div class="bi">&#9889;</div><div class="bl">Plugin</div><div class="bv">MCP Connector<br>v<?php echo WMC_VERSION; ?></div></div>
    </div>
    <div class="badge"><div class="bdot"></div>Token verified &mdash; connection is active and ready</div>
  </div>
  <div class="dev">
    <div class="dl">Developed by</div>
    <div class="dn">Amir Ali</div>
    <div class="dt">WordPress &amp; AI Integration Specialist</div>
    <div class="slinks">
      <a href="https://github.com/amirjahfar1/" target="_blank" class="sb gh">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>GitHub
      </a>
      <a href="https://www.linkedin.com/in/scalewithaamir/" target="_blank" class="sb li">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>LinkedIn
      </a>
      <a href="https://www.facebook.com/DigitalAamirAli" target="_blank" class="sb fb">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>Facebook
      </a>
    </div>
  </div>
  <div class="foot"><strong>WordPress MCP Connector</strong> &mdash; 142 abilities for full WordPress control.<br>You can close this tab and return to Claude.</div>
</div>
</body></html>
<?php
}

function wmc_oauth_render_denied() {
	?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Denied</title>
<style>body{font-family:sans-serif;text-align:center;padding:60px;background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;align-items:center;justify-content:center}
.box{background:#fff;border-radius:20px;padding:48px 40px;max-width:380px;width:100%;box-shadow:0 20px 50px rgba(0,0,0,.2)}
h2{color:#ef4444;font-size:22px;margin:16px 0 10px}p{color:#64748b;font-size:14px}</style>
</head><body><div class="box"><div style="font-size:48px">&#10005;</div><h2>Authorization Denied</h2><p>You denied Claude access.<br>Close this tab and try again if needed.</p></div></body></html>
<?php
}
