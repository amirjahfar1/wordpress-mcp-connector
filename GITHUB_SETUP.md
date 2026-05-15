# GitHub Setup Instructions

## Step 1: Create GitHub Repository

1. **Go to GitHub**
   - Visit https://github.com/new
   - Or click "+" → "New repository" in top-right

2. **Create Repository**
   - **Repository name:** `wordpress-mcp-connector`
   - **Description:** "WordPress MCP Connector - Comprehensive Model Context Protocol integration for WordPress"
   - **Visibility:** Public (for open-source)
   - **DO NOT** initialize with README (we have one)
   - Click "Create repository"

3. **Copy Repository URL**
   - Copy: `https://github.com/amirjahfar1/wordpress-mcp-connector.git`

## Step 2: Setup Git Authentication

### Option A: HTTPS with Personal Access Token (Recommended)

1. **Create Personal Access Token**
   - Go to GitHub → Settings → Developer settings → Personal access tokens
   - Click "Generate new token"
   - Name: "WordPress MCP Connector"
   - Select scopes:
     - ✅ `repo` (full control of private repositories)
   - Click "Generate token"
   - **Copy the token** (you won't see it again!)

2. **Configure Git**
   ```bash
   git config --global credential.helper store
   ```

### Option B: SSH Key (Advanced)

1. **Generate SSH Key**
   ```bash
   ssh-keygen -t ed25519 -C "amirjahfar@gmail.com"
   ```

2. **Add to GitHub**
   - Go to GitHub → Settings → SSH and GPG keys
   - Click "New SSH key"
   - Paste your public key

3. **Update Remote URL**
   ```bash
   cd C:\Users\DELL\Desktop\Claude\Wordpress\Test\Wordpress-MCP-Connector
   git remote set-url origin git@github.com:amirjahfar1/wordpress-mcp-connector.git
   ```

## Step 3: Push Repository

### Using HTTPS Token

```bash
cd C:\Users\DELL\Desktop\Claude\Wordpress\Test\Wordpress-MCP-Connector

# Push to GitHub
git push -u origin main

# When prompted:
# Username: your_github_username
# Password: your_personal_access_token
```

### Using SSH

```bash
cd C:\Users\DELL\Desktop\Claude\Wordpress\Test\Wordpress-MCP-Connector

# Push to GitHub (no authentication needed if SSH key is configured)
git push -u origin main
```

## Step 4: Verify Upload

1. **Go to your repository**
   - https://github.com/amirjahfar1/wordpress-mcp-connector

2. **Check files are uploaded**
   - Should see all files listed
   - README.md should be displayed

3. **Check commits**
   - Click "Commits" tab
   - Should see initial commit

## Step 5: Add GitHub Topics

1. **Go to repository**
2. **Click Settings**
3. **Scroll to "Topics"**
4. **Add these topics:**
   - `wordpress`
   - `mcp`
   - `wordpress-plugin`
   - `automation`
   - `rest-api`
   - `abilities-api`
   - `wordpress-development`

## Step 6: Setup Repository Description

1. **Go to repository main page**
2. **Click "Edit" next to repository description**
3. **Add:**
   - Description: "WordPress MCP Connector - Comprehensive Model Context Protocol integration"
   - Website: `https://github.com/amirjahfar1/wordpress-mcp-connector`

## Step 7: Add License Badge (Optional)

The repository already includes:
- GPL-2.0-or-later license
- Version badge
- WordPress compatibility badge

## Troubleshooting

### "fatal: 'origin' does not appear to be a 'git' repository"

```bash
# Verify remote
git remote -v

# If empty, add remote
git remote add origin https://github.com/amirjahfar1/wordpress-mcp-connector.git
```

### "fatal: The remote end hung up unexpectedly"

```bash
# Update git
git update-git-index --refresh

# Try again
git push -u origin main
```

### "Permission denied (publickey)"

- Check SSH key is added to GitHub
- Or use HTTPS with personal access token instead

### Authentication Failed

```bash
# Clear stored credentials
git config --global --unset credential.helper

# Try again with personal access token
git push -u origin main
```

## Future Pushes

After initial setup, push changes with:

```bash
git add .
git commit -m "your commit message"
git push origin main
```

---

**Once pushed, your plugin will be:**
- ✅ Available on GitHub
- ✅ Ready for collaborators
- ✅ Ready for issue tracking
- ✅ Ready for pull requests
- ✅ Discoverable on GitHub search
