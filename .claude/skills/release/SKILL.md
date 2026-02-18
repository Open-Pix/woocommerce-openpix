---
name: release
description: Execute a complete release for the OpenPix WooCommerce plugin (GitHub tag, PR, merge, SVN, GitHub Release)
disable-model-invocation: true
argument-hint: "[patch|minor|major]"
allowed-tools: Read, Grep, Glob, Bash, Edit, Write
---

# WooCommerce OpenPix - Complete Release

Execute a full release cycle for the OpenPix WooCommerce plugin. This includes bumping the version, creating a GitHub PR, merging it, publishing to WordPress.org SVN, and creating a GitHub Release with the zip asset.

## Arguments

- `$ARGUMENTS` determines the release type: `patch` (default), `minor`, or `major`
- If no argument is provided, default to `patch`

## Known Issue: package.json version drift

The `package.json` version is NOT updated by the release scripts and may lag behind the actual release version. Both `scripts/changelog.ts` and `scripts/svn-release.ts` and `scripts/update_release.ts` read version from `package.json`, which causes incorrect version numbers.

**Workarounds required:**
1. **changelog.ts**: Run directly with `--version <current-release-version>` instead of using `pnpm release:patch`
2. **svn-release.ts**: After it runs, manually create the correct SVN tag with `svn copy`
3. **update_release.ts**: Set `CIRCLE_TAG=v<new-version>` env var when running

## Prerequisites

Before starting, verify:
1. On `main` branch with clean working tree (`git status`)
2. `.env` file exists with `SVN_USERNAME`, `SVN_PASSWORD`, `SVN_REPO_URL`, `GITHUB_TOKEN`
3. `svn`, `gh`, `pnpm` are installed
4. There are unreleased commits on main since the last tag (`git log $(git describe --tags --abbrev=0)..HEAD --oneline`)

## Release Steps

### Step 1: Determine versions

1. Get the latest git tag: `git describe --tags --abbrev=0` (e.g., `v2.13.7`)
2. Strip the `v` prefix to get `CURRENT_VERSION` (e.g., `2.13.7`)
3. Calculate `NEW_VERSION` by incrementing `CURRENT_VERSION` based on the release type argument (patch/minor/major)
4. Show the user: "Releasing: CURRENT_VERSION -> NEW_VERSION"
5. List the unreleased commits that will be included

### Step 2: Generate changelog, tag, and PR

Run the changelog script directly (bypassing pnpm to pass `--version` correctly):

```bash
node -r esbuild-register ./scripts/changelog --<release-type> --version <CURRENT_VERSION>
```

This will:
- Update `CHANGELOG.md`, `readme.txt`, `openpix-for-woocommerce.php`
- Create a `feature-production/<date>` branch
- Create annotated git tag `v<NEW_VERSION>`
- Push branch + tag to origin
- Open a PR on GitHub

Verify the output shows the correct NEW_VERSION in the PR title.

### Step 3: Merge the PR

```bash
gh pr merge --squash
```

Then pull latest main:

```bash
git checkout main && git pull origin main
```

Verify version files are updated:
- `openpix-for-woocommerce.php` has `Version: <NEW_VERSION>`
- `readme.txt` has `Stable tag: <NEW_VERSION>`
- `CHANGELOG.md` has `<NEW_VERSION>` entry

### Step 4: SVN Release (WordPress.org)

Run the SVN release script:

```bash
pnpm release:svn
```

**IMPORTANT:** This script reads version from `package.json` which is likely wrong. After it completes:

1. Check what SVN tag directory was created (it will be the package.json version, not NEW_VERSION)
2. Create the correct tag by copying:

```bash
cd <svn-working-directory> && svn copy tags/<wrong-version> tags/<NEW_VERSION>
```

3. Read SVN credentials from `.env` (be careful with special chars - use `grep` + `cut` instead of `source`):

```bash
SVN_USERNAME=$(grep '^SVN_USERNAME=' .env | cut -d= -f2)
SVN_PASSWORD=$(grep '^SVN_PASSWORD=' .env | cut -d= -f2)
```

4. Commit the correct tag:

```bash
svn ci tags/<NEW_VERSION> -m "version <NEW_VERSION>" --username "$SVN_USERNAME" --password "$SVN_PASSWORD" --config-option servers:global:http-compression=yes --config-option servers:global:http-timeout=0
```

5. Verify: `svn ls https://plugins.svn.wordpress.org/openpix-for-woocommerce/tags/<NEW_VERSION>/`

**Note:** SVN commits can take several minutes due to the vendor/ directory.

### Step 5: Publish GitHub Release with asset

1. Find the generated zip: `ls woocommerce-openpix-prod-v*.zip`
2. Identify the latest v<NEW_VERSION> zip file
3. Create the GitHub Release with the asset:

```bash
CIRCLE_TAG=v<NEW_VERSION> pnpm publish:tag -- --asset=./<zip-filename>
```

4. Verify: `gh release view v<NEW_VERSION>`

### Step 6: Final Verification

Run all verification checks and present a summary table:

| Check | Status |
|-------|--------|
| GitHub PR merged | |
| Git tag `v<NEW_VERSION>` | |
| GitHub Release with zip | |
| SVN tag on WordPress.org | |
| SVN trunk updated | |
| PHP Version header | |
| readme.txt Stable tag | |
| CHANGELOG.md entry | |

## Troubleshooting

- **"Tag already published on GitHub"**: `gh release delete v<VERSION> --yes && git tag -d v<VERSION> && git push --delete origin v<VERSION>`
- **SVN credentials error**: Check `.env` has `SVN_USERNAME`, `SVN_PASSWORD`, `SVN_REPO_URL`
- **eslint pre-commit hook fails**: Scripts use `--no-verify` flag already
- **zip not found**: Ensure `pack.sh prod` ran successfully (Step 4 does this automatically)
- **Wrong version generated**: The root cause is `package.json` version drift. Always pass `--version` explicitly to the changelog script

## SVN Working Directory

The SVN working copy is located at `../openpix-for-woocommerce` relative to the project root (i.e., `/home/hallexcosta/woovi-wordpress-dev/openpix-for-woocommerce`).
