"""
WMC Connect - Polling-based OAuth (no localhost server, no ERR_EMPTY_RESPONSE)
"""
import urllib.request, json, sys, os, time, secrets, subprocess

SAVE_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'wmc_connection.json')

def open_browser(url):
    if sys.platform == 'win32':
        os.startfile(url)
    else:
        subprocess.Popen(['xdg-open', url])

def poll(site_url, state, timeout=120):
    url = site_url.rstrip('/') + '/wp-json/wmc/v1/oauth/poll?state=' + state
    deadline = time.time() + timeout
    while time.time() < deadline:
        try:
            res = urllib.request.urlopen(url, timeout=5)
            data = json.loads(res.read().decode())
            if data.get('status') == 'authorized': return data
            if data.get('status') == 'denied': return None
        except: pass
        time.sleep(2)
    return None

def connect(site_url):
    state = secrets.token_hex(16)
    auth_url = site_url.rstrip('/') + '/?wmc_oauth=1&state=' + state
    print(json.dumps({'status':'opening_browser','url':auth_url}), flush=True)
    open_browser(auth_url)
    print(json.dumps({'status':'waiting'}), flush=True)
    result = poll(site_url, state)
    if not result:
        print(json.dumps({'status':'denied_or_timeout'}), flush=True); sys.exit(1)
    try:
        req = urllib.request.Request(
            site_url.rstrip('/') + '/wp-json/wmc/v1/oauth/verify',
            headers={'Authorization': 'Bearer ' + result['token']}
        )
        verify = json.loads(urllib.request.urlopen(req, timeout=10).read().decode())
    except: verify = {'success': False}
    conn = {
        'site_url': result['site'], 'site_name': result['site_name'],
        'token': result['token'], 'connected': True,
        'verified': verify.get('success', False),
        'wp_version': verify.get('wp_version',''),
        'wmc_version': result.get('version',''),
    }
    with open(SAVE_PATH, 'w') as f: json.dump(conn, f, indent=2)
    print(json.dumps({'status':'connected', **conn}), flush=True)

if __name__ == '__main__':
    connect(sys.argv[1] if len(sys.argv) > 1 else 'https://aamirali.com')
