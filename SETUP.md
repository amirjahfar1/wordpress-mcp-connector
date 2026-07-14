# Setup Guide — WordPress MCP Connector

This guide walks you through connecting Claude to your WordPress site, step by step. It assumes you've never used a terminal before — every command is explained before you run it.

There are two things you're setting up:
1. **The WordPress plugin** — installed on your website, gives Claude 171 abilities to control it.
2. **Node.js** — installed on *your computer* (the one running Claude), so Claude can talk to your site using the `@automattic/mcp-wordpress-remote` connector.

No Python, no Composer, no database setup is required for normal use — those only matter if you're contributing code to this plugin (see the note at the end).

---

## What's a terminal, and how do I open one?

A terminal is a text window where you type commands instead of clicking buttons.

- **Windows:** Press the **Start** key, type `PowerShell`, press Enter.
- **Mac:** Press `Cmd + Space`, type `Terminal`, press Enter.

Every command below is meant to be typed (or copy-pasted) into this window, followed by Enter.

---

## Step 1 — Check if Node.js is installed

Claude connects to your site through a small connector package (`@automattic/mcp-wordpress-remote`) that runs via `npx`, a tool that comes bundled with Node.js. You need Node.js installed once on your computer.

**Check first:**

```powershell
node -v
npm -v
```

**What success looks like:** two version numbers, e.g.

```
v20.11.1
10.2.4
```

If you see numbers like that, skip to [Step 2](#step-2--get-the-plugin-onto-your-wordpress-site). Node 18 or newer is fine.

**If you see `'node' is not recognized as an internal or external command`:** Node.js isn't installed yet.

1. Go to [nodejs.org](https://nodejs.org) and download the **LTS** version (the button labeled "Recommended for Most Users").
2. Run the installer, clicking "Next" through the default options.
3. **Close and completely reopen PowerShell** — this is required for Windows to pick up the new `node`/`npm`/`npx` commands. Reopening the same window is not enough.
4. Run `node -v` and `npm -v` again to confirm.

---

## Step 2 — Get the plugin onto your WordPress site

Pick whichever of these you're comfortable with:

### Option A — Upload a ZIP from WordPress Admin (easiest, no terminal needed)

1. Go to [github.com/amirjahfar1/wordpress-mcp-connector/releases](https://github.com/amirjahfar1/wordpress-mcp-connector/releases) and download the ZIP for the latest release.
2. In your WordPress admin, go to **Plugins → Add New → Upload Plugin**.
3. Choose the ZIP file you downloaded, click **Install Now**, then **Activate**.

**What success looks like:** the plugin opens its own Settings page automatically right after activation.

### Option B — Upload via FTP / your host's File Manager

1. Download and unzip the plugin (or use the folder you already cloned with Git).
2. Upload the whole `wordpress-mcp-connector` folder into `/wp-content/plugins/` on your server, using FTP (FileZilla, etc.) or your hosting provider's File Manager.
3. In WordPress admin, go to **Plugins → Installed Plugins**, find "WordPress MCP Connector", click **Activate**.

### Option C — Git clone directly on the server (for developers with SSH access)

```bash
cd /path/to/wp-content/plugins
git clone https://github.com/amirjahfar1/wordpress-mcp-connector.git
```

Then activate it from **Plugins → Installed Plugins** as in Option B.

**Requirements this site must already meet** (these are about your WordPress *hosting*, nothing to install locally):

| Requirement | Minimum |
|---|---|
| WordPress | 6.9+ |
| PHP | 7.4+ |
| WooCommerce | 5.0+ (optional — only if you want WooCommerce abilities) |

If activation fails with a version error, ask your hosting provider to update WordPress/PHP — most hosts do this from their control panel with no code involved.

---

## Step 3 — Generate your connection credentials

1. After activating, you should land on the plugin's **Settings** page (or go to **WP Admin → MCP Connector**).
2. Find the **"Connect Claude Code to Your Site"** card.
3. Select your administrator account from the dropdown.
4. Click **⚡ Generate**.

**What success looks like:** a JSON block appears on screen, looking like this (with your real site details filled in):

```json
{
  "mcpServers": {
    "wordpress-yoursite-com": {
      "command": "npx",
      "args": ["-y", "@automattic/mcp-wordpress-remote@latest"],
      "env": {
        "WP_API_URL": "https://yoursite.com/wp-json/mcp/mcp-adapter-default-server",
        "WP_API_USERNAME": "your-username",
        "WP_API_PASSWORD": "xxxx xxxx xxxx xxxx xxxx xxxx"
      }
    }
  }
}
```

Copy this whole block — **the password shown here cannot be viewed again**, so don't close this page until you've saved it or pasted it into Claude.

---

## Step 4 — Add the config to Claude

### If you're using Claude Code

Paste the JSON block into your chat with Claude and say *"add this to my MCP config"* — Claude will create or update `.mcp.json` in your project root for you.

To do it manually instead, create/edit `.mcp.json` in your project's root folder and paste the JSON exactly as copied.

### If you're using Claude Desktop

1. Open this file in a text editor (Notepad is fine):
   ```
   Windows:  C:\Users\<YourName>\AppData\Roaming\Claude\claude_desktop_config.json
   Mac:      ~/Library/Application Support/Claude/claude_desktop_config.json
   ```
2. Paste the JSON block in (merge it with any existing `mcpServers` entries — don't delete what's already there).
3. Save the file.

**Important:** always use the full path shown above, not a shortcut or relative path — Claude Desktop doesn't read your terminal's PATH settings, so it needs the exact command (`npx`) and full config path to work.

---

## Step 5 — Restart and test

1. Fully quit Claude Code / Claude Desktop and reopen it — don't just close the window, make sure the app isn't still running in the background.
2. Ask Claude something simple, e.g. *"List the last 5 posts on my WordPress site."*

**What success looks like:** Claude returns real data from your site. The first run may take a few extra seconds — `npx` is downloading the connector package the first time.

---

## Troubleshooting

**"Abilities not showing up in Claude"**
- Restart Claude Code/Desktop after any change to the config file — it only reads it on startup.
- Confirm the plugin is Active at **WP Admin → Plugins**.

**`npx` hangs or fails to download the package**
- Check your internet connection, or a corporate firewall/proxy may be blocking `registry.npmjs.org`.
- Try running the command by hand to see the real error:
  ```powershell
  npx -y @automattic/mcp-wordpress-remote@latest
  ```

**"Authentication failed" / "401 Unauthorized"**
- The Application Password shown at generation time can't be retrieved again — go back to the Settings page and click **⚡ Generate** for a new one.
- Make sure `WP_API_USERNAME` matches your WordPress username exactly (it's case-sensitive).

**"'node' is not recognized..." even after installing Node.js**
- You likely still have the old PowerShell window open. Close it completely and open a new one.
- If it still fails, restart your computer once — this fully refreshes Windows' PATH.

**"WooCommerce abilities not working"**
- Confirm WooCommerce is installed and active.
- Check **WP Admin → MCP Connector** that the WooCommerce ability group is toggled on.

**Plugin activation fails with a version error**
- Your host's WordPress or PHP version is below the minimum in the table above — this is a hosting setting, not something to fix locally. Contact your host or use their control panel to update.

**Quick diagnostic endpoint**

Visit this in a browser (replace with your domain) to confirm the plugin is wired up correctly:
```
https://yoursite.com/wp-json/wmc/v1/diagnose
```
It returns plugin version, ability count, WordPress version, and PHP version as JSON.

---

## Note for contributors (not needed for normal use)

If you're modifying the plugin's PHP code itself, `composer.json` lists optional dev tools (`phpcs`, `phpstan`) for linting/static analysis:

```bash
composer install
```

This is entirely optional and only relevant if you're editing the plugin's source — it is **not** required to install or use the plugin on a WordPress site.
