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
	$site_icon = get_site_icon_url( 80 );
	$display_url = preg_replace('#^https?://#', '', rtrim( $site_url, '/' ) );
	$user      = wp_get_current_user();
	$nonce     = wp_create_nonce( 'wmc_oauth_' . $state );
	$post_url  = add_query_arg( array( 'wmc_oauth' => '1', 'state' => urlencode( $state ) ), home_url( '/' ) );
	?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Authorize Claude &#8212; <?php echo esc_html( $site_name ); ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Inter,Roboto,sans-serif;
  background:#0f0f1a;
  min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
}
body::before{
  content:'';position:fixed;inset:0;
  background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(99,102,241,.35) 0%,transparent 60%),
             radial-gradient(ellipse 60% 40% at 80% 100%,rgba(139,92,246,.2) 0%,transparent 55%);
  pointer-events:none;
}

.card{
  background:rgba(255,255,255,.03);
  border:1px solid rgba(255,255,255,.1);
  border-radius:20px;
  box-shadow:0 40px 80px rgba(0,0,0,.6),inset 0 1px 0 rgba(255,255,255,.08);
  max-width:420px;width:100%;
  backdrop-filter:blur(20px);
  animation:rise .5s cubic-bezier(.22,1,.36,1) both;
}
@keyframes rise{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}

/* ── Header ── */
.hd{padding:32px 32px 24px;text-align:center}
.logos{display:flex;align-items:center;justify-content:center;gap:18px;margin-bottom:22px}
.logo{
  width:52px;height:52px;border-radius:14px;
  background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);
  display:flex;align-items:center;justify-content:center;overflow:hidden;
}
.logo img{width:100%;height:100%;object-fit:cover;border-radius:12px}
.logo svg{width:28px;height:28px}
.arrow{display:flex;align-items:center;gap:5px;opacity:.4}
.arrow span{width:6px;height:6px;border-radius:50%;background:#a5b4fc}
.arrow line-seg{width:28px;height:1.5px;background:rgba(165,180,252,.4);border-radius:2px;display:block}
.hd h1{font-size:20px;font-weight:700;color:#f1f5f9;letter-spacing:-.3px;margin-bottom:5px}
.hd p{font-size:13.5px;color:#94a3b8}
.hd p strong{color:#c4b5fd}

/* ── Info strip ── */
.strip{
  margin:0 20px;
  background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.08);
  border-radius:12px;padding:4px 0;
}
.row{display:flex;align-items:center;gap:12px;padding:9px 16px}
.row+.row{border-top:1px solid rgba(255,255,255,.05)}
.rdot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.rlbl{font-size:11.5px;color:#64748b;width:52px;flex-shrink:0;font-weight:500}
.rval{font-size:12.5px;color:#cbd5e1;font-weight:500;word-break:break-all}

/* ── Permissions ── */
.perms{padding:20px 20px 0}
.perms-hd{font-size:10.5px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.08em;margin-bottom:10px;padding-left:4px}
.perm{
  display:flex;align-items:flex-start;gap:12px;
  padding:10px 14px;border-radius:10px;margin-bottom:4px;
  background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);
  transition:background .15s;
}
.perm:hover{background:rgba(99,102,241,.08)}
.perm-icon{
  width:30px;height:30px;border-radius:8px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
}
.perm-icon svg{width:15px;height:15px}
.perm-text{padding-top:1px}
.perm-text strong{display:block;font-size:12.5px;color:#e2e8f0;font-weight:600;margin-bottom:2px}
.perm-text span{font-size:11.5px;color:#64748b;line-height:1.4}

/* ── User row ── */
.user-row{
  display:flex;align-items:center;gap:12px;
  margin:16px 20px 0;
  padding:11px 14px;
  background:rgba(139,92,246,.08);
  border:1px solid rgba(139,92,246,.2);
  border-radius:10px;
}
.avatar{width:34px;height:34px;border-radius:50%;border:2px solid rgba(196,181,253,.4)}
.uinfo strong{display:block;font-size:12.5px;color:#ddd6fe;font-weight:600}
.uinfo span{font-size:11.5px;color:#7c6fba}

/* ── Buttons ── */
.btns{display:flex;gap:8px;padding:20px}
.btn{
  flex:1;padding:13px 0;border-radius:10px;
  font-size:13.5px;font-weight:700;cursor:pointer;border:none;
  transition:all .15s;letter-spacing:-.1px;
}
.btn-allow{
  background:linear-gradient(135deg,#6366f1,#8b5cf6);
  color:#fff;box-shadow:0 4px 16px rgba(99,102,241,.4);
}
.btn-allow:hover{transform:translateY(-1px);box-shadow:0 8px 24px rgba(99,102,241,.5)}
.btn-allow:active{transform:none;box-shadow:0 2px 8px rgba(99,102,241,.3)}
.btn-deny{
  background:rgba(255,255,255,.05);
  color:#64748b;border:1px solid rgba(255,255,255,.08);
}
.btn-deny:hover{background:rgba(239,68,68,.1);color:#f87171;border-color:rgba(239,68,68,.2)}

/* ── Footer ── */
.foot{
  border-top:1px solid rgba(255,255,255,.06);
  padding:12px 20px;text-align:center;
  font-size:11px;color:#374151;
  display:flex;align-items:center;justify-content:center;gap:6px;
}
.foot svg{opacity:.5}
.foot a{color:#6366f1;text-decoration:none;font-weight:500}
.foot a:hover{color:#818cf8}
</style>
</head>
<body>
<div class="card">

  <div class="hd">
    <div class="logos">
      <div class="logo">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect width="24" height="24" rx="6" fill="#CC785C"/>
          <path d="M12 4C7.582 4 4 7.582 4 12C4 16.418 7.582 20 12 20C16.418 20 20 16.418 20 12C20 7.582 16.418 4 12 4ZM12 7.5C13.38 7.5 14.5 8.62 14.5 10C14.5 11.38 13.38 12.5 12 12.5C10.62 12.5 9.5 11.38 9.5 10C9.5 8.62 10.62 7.5 12 7.5ZM12 17.5C10.07 17.5 8.36 16.57 7.28 15.13C7.31 13.65 10.33 12.85 12 12.85C13.66 12.85 16.69 13.65 16.72 15.13C15.64 16.57 13.93 17.5 12 17.5Z" fill="white"/>
        </svg>
      </div>
      <div class="arrow">
        <span></span>
        <line-seg></line-seg>
        <svg width="16" height="10" viewBox="0 0 16 10" fill="none"><path d="M1 5H15M15 5L11 1M15 5L11 9" stroke="#a5b4fc" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M1 5H15M15 5L11 1M15 5L11 9" stroke="#a5b4fc" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" transform="scale(-1,1) translate(-16,0)"/></svg>
        <line-seg></line-seg>
        <span></span>
      </div>
      <div class="logo">
        <?php if ( $site_icon ) : ?>
          <img src="<?php echo esc_url( $site_icon ); ?>" alt="<?php echo esc_attr( $site_name ); ?>">
        <?php else : ?>
          <svg viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="6" fill="#1e293b"/><path d="M12 3C7.032 3 3 7.032 3 12C3 16.968 7.032 21 12 21C16.968 21 21 16.968 21 12C21 7.032 16.968 3 12 3ZM4.68 12.744L11.256 19.32C7.632 18.948 4.752 16.212 4.68 12.744ZM12.744 19.32L19.32 12.744C19.248 16.212 16.368 18.948 12.744 19.32ZM12 4.68C14.868 4.68 17.412 6.012 19.044 8.1H4.956C6.588 6.012 9.132 4.68 12 4.68ZM4.5 9.78H19.5C19.788 10.5 19.956 11.232 19.956 12C19.956 12.408 19.908 12.804 19.848 13.2H4.152C4.092 12.804 4.044 12.408 4.044 12C4.044 11.232 4.212 10.5 4.5 9.78Z" fill="#6366f1"/></svg>
        <?php endif; ?>
      </div>
    </div>
    <h1>Claude wants to connect</h1>
    <p>Requesting access to <strong><?php echo esc_html( $site_name ); ?></strong></p>
  </div>

  <div class="strip">
    <div class="row">
      <div class="rdot" style="background:#22c55e"></div>
      <div class="rlbl">Site</div>
      <div class="rval"><?php echo esc_html( $site_name ); ?></div>
    </div>
    <div class="row">
      <div class="rdot" style="background:#6366f1"></div>
      <div class="rlbl">URL</div>
      <div class="rval"><?php echo esc_html( $display_url ); ?></div>
    </div>
    <div class="row">
      <div class="rdot" style="background:#f59e0b"></div>
      <div class="rlbl">Plugin</div>
      <div class="rval">WP MCP Connector v<?php echo WMC_VERSION; ?></div>
    </div>
  </div>

  <div class="perms">
    <div class="perms-hd">Claude will be able to</div>

    <div class="perm">
      <div class="perm-icon" style="background:rgba(99,102,241,.15)">
        <svg viewBox="0 0 24 24" fill="none" stroke="#818cf8" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      </div>
      <div class="perm-text">
        <strong>Read &amp; manage content</strong>
        <span>Posts, pages, media, comments</span>
      </div>
    </div>

    <div class="perm">
      <div class="perm-icon" style="background:rgba(34,197,94,.12)">
        <svg viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 001.96-1.61L23 6H6"/></svg>
      </div>
      <div class="perm-text">
        <strong>Full WooCommerce access</strong>
        <span>Products, orders, coupons, customers</span>
      </div>
    </div>

    <div class="perm">
      <div class="perm-icon" style="background:rgba(251,191,36,.12)">
        <svg viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/><path d="M15.54 8.46a5 5 0 010 7.07M8.46 8.46a5 5 0 000 7.07"/></svg>
      </div>
      <div class="perm-text">
        <strong>Site administration</strong>
        <span>Plugins, themes, settings, database</span>
      </div>
    </div>

    <div class="perm">
      <div class="perm-icon" style="background:rgba(239,68,68,.1)">
        <svg viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      </div>
      <div class="perm-text">
        <strong>Security &amp; backups</strong>
        <span>Sessions, IP blocking, database backup</span>
      </div>
    </div>
  </div>

  <div class="user-row">
    <img src="<?php echo esc_url( get_avatar_url( $user->ID, array( 'size' => 68 ) ) ); ?>" class="avatar" alt="">
    <div class="uinfo">
      <strong><?php echo esc_html( $user->display_name ); ?></strong>
      <span>Authorizing as <?php echo esc_html( $user->user_login ); ?> &middot; Administrator</span>
    </div>
  </div>

  <form method="post" action="<?php echo esc_url( $post_url ); ?>">
    <input type="hidden" name="wmc_nonce"  value="<?php echo esc_attr( $nonce ); ?>">
    <input type="hidden" name="wmc_action" value="allow">
    <div class="btns">
      <button type="button" onclick="document.querySelector('[name=wmc_action]').value='deny';this.closest('form').submit()" class="btn btn-deny">Deny</button>
      <button type="submit" class="btn btn-allow">
        <svg style="display:inline;vertical-align:-2px;margin-right:6px" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>Allow Access
      </button>
    </div>
  </form>

  <div class="foot">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
    Secured by WordPress &mdash; <a href="<?php echo esc_url( admin_url( 'admin.php?page=wmc-settings' ) ); ?>">Manage connections</a>
  </div>

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
