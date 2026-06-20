import http.server
import urllib.parse
import threading
import json
import sys

PORT = 7823
received = {}

SUCCESS_HTML = u"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connected — WordPress MCP Connector</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
  background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
  min-height:100vh;display:flex;align-items:center;
  justify-content:center;padding:20px;
}
.card{
  background:#fff;border-radius:24px;
  box-shadow:0 30px 70px rgba(0,0,0,.22);
  max-width:460px;width:100%;overflow:hidden;
}
.card-top{
  background:linear-gradient(135deg,#6366f1,#8b5cf6);
  padding:40px 32px 32px;text-align:center;color:#fff;
}
.check-ring{
  width:72px;height:72px;border-radius:50%;
  background:rgba(255,255,255,.15);
  border:3px solid rgba(255,255,255,.4);
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 20px;font-size:34px;
  animation:pop .4s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes pop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.card-top h1{font-size:26px;font-weight:800;margin-bottom:6px;letter-spacing:-.3px}
.card-top p{font-size:14px;opacity:.85}
.card-top .site-name{font-weight:700;opacity:1}

.card-body{padding:28px 32px 0}

.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:24px}
.info-box{
  background:#f8fafc;border:1px solid #e2e8f0;
  border-radius:12px;padding:14px;text-align:center;
}
.info-box .ib-icon{font-size:22px;margin-bottom:6px}
.info-box .ib-label{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.info-box .ib-val{font-size:13px;color:#334155;font-weight:600;margin-top:2px;word-break:break-all}

.dev-section{
  border-top:1px solid #f1f5f9;padding:22px 32px 24px;text-align:center;
}
.dev-label{font-size:11px;color:#94a3b8;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px}
.dev-name{font-size:16px;font-weight:800;color:#1e293b;margin-bottom:4px}
.dev-title{font-size:12px;color:#64748b;margin-bottom:16px}

.social-links{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}
.social-btn{
  display:inline-flex;align-items:center;gap:7px;
  padding:8px 16px;border-radius:8px;
  font-size:12px;font-weight:700;text-decoration:none;
  transition:all .15s;border:1.5px solid transparent;
}
.social-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.12)}
.social-btn svg{width:15px;height:15px;flex-shrink:0}
.btn-gh {background:#0f172a;color:#fff;border-color:#0f172a}
.btn-li {background:#0077b5;color:#fff;border-color:#0077b5}
.btn-fb {background:#1877f2;color:#fff;border-color:#1877f2}

.footer-note{
  background:#f8fafc;border-top:1px solid #e2e8f0;
  padding:14px 32px;text-align:center;
  font-size:11px;color:#94a3b8;line-height:1.6;
}
.footer-note strong{color:#6366f1}
</style>
</head>
<body>
<div class="card">

  <div class="card-top">
    <div class="check-ring">&#10003;</div>
    <h1>Connected!</h1>
    <p>Claude is now connected to <span class="site-name">{site_name}</span></p>
  </div>

  <div class="card-body">
    <div class="info-grid">
      <div class="info-box">
        <div class="ib-icon">&#127760;</div>
        <div class="ib-label">Site</div>
        <div class="ib-val">{site_url}</div>
      </div>
      <div class="info-box">
        <div class="ib-icon">&#9889;</div>
        <div class="ib-label">Plugin</div>
        <div class="ib-val">MCP Connector<br>v{version}</div>
      </div>
    </div>
  </div>

  <div class="dev-section">
    <div class="dev-label">Developed by</div>
    <div class="dev-name">Amir Ali</div>
    <div class="dev-title">WordPress &amp; AI Integration Specialist</div>
    <div class="social-links">

      <a href="https://github.com/amirjahfar1/" target="_blank" class="social-btn btn-gh">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
        GitHub
      </a>

      <a href="https://www.linkedin.com/in/scalewithaamir/" target="_blank" class="social-btn btn-li">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        LinkedIn
      </a>

      <a href="https://www.facebook.com/DigitalAamirAli" target="_blank" class="social-btn btn-fb">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
        Facebook
      </a>

    </div>
  </div>

  <div class="footer-note">
    <strong>WordPress MCP Connector</strong> &mdash; 142 abilities for full WordPress control.<br>
    You can now close this tab and return to Claude.
  </div>

</div>
</body>
</html>"""

DENY_HTML = u"""<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Denied</title>
<style>body{font-family:sans-serif;text-align:center;padding:60px;background:#fff5f5}
h2{color:#ef4444}p{color:#64748b;margin-top:10px}</style>
</head><body>
<h2>&#10005; Authorization Denied</h2>
<p>You denied Claude access to your WordPress site.</p>
</body></html>"""


class CallbackHandler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        params = urllib.parse.parse_qs(parsed.query)

        if parsed.path == '/callback':
            token     = params.get('token',     [''])[0]
            site      = urllib.parse.unquote(params.get('site',      [''])[0])
            site_name = urllib.parse.unquote(params.get('site_name', ['My WordPress'])[0])
            version   = params.get('version',   [''])[0]
            error     = params.get('error',     [''])[0]

            if error:
                received['error'] = error
                html = DENY_HTML
            else:
                received['token']     = token
                received['site']      = site
                received['site_name'] = site_name
                received['version']   = version

                # Clean up site URL for display
                display_url = site.replace('https://', '').replace('http://', '').rstrip('/')

                html = SUCCESS_HTML.format(
                    site_name=site_name,
                    site_url=display_url,
                    version=version or '3.0.0',
                )

            self.send_response(200)
            self.send_header('Content-Type', 'text/html; charset=utf-8')
            self.end_headers()
            self.wfile.write(html.encode('utf-8'))

            threading.Thread(target=self.server.shutdown).start()

        else:
            self.send_response(404)
            self.end_headers()

    def log_message(self, format, *args):
        pass


server = http.server.HTTPServer(('localhost', PORT), CallbackHandler)
print(json.dumps({'status': 'listening', 'port': PORT}), flush=True)
server.serve_forever()
# After callback received:
print(json.dumps(received), flush=True)
