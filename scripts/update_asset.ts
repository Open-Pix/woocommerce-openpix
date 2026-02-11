#!/usr/bin/env node

import path from 'path';
import fs from 'fs';
import https from 'https';

import dotenvSafe from 'dotenv-safe';

const octonode = require('octonode');
const log = require('npmlog');

const root = path.join.bind(this, __dirname, '../');

dotenvSafe.config({
  path: root('.env'),
  sample: root('.env.example'),
});

const args = process.argv.splice(2);

log.heading = 'ci@entria';

if (args.length < 2) {
  log.error('', 'Usage: pnpm es ./scripts/update_asset.ts -- <tag> --asset=<path>');
  process.exit(-1);
}

const tagArg = args[0];
const assetArg = args.find((arg) => arg.startsWith('--asset='));

if (!tagArg.startsWith('v')) {
  log.error('', 'Tag must start with "v" (e.g., v2.13.4)');
  process.exit(-1);
}

if (!assetArg) {
  log.error('', 'Asset path is required (--asset=<path>)');
  process.exit(-1);
}

const tagName = tagArg;
const assetPath = assetArg.replace('--asset=', '');

const username = process.env.CIRCLE_PROJECT_USERNAME || 'Open-Pix';
const reponame = process.env.CIRCLE_PROJECT_REPONAME || 'woocommerce-openpix';
const GITHUB_TOKEN = process.env.GITHUB_TOKEN || process.env.GITHUB_API_TOKEN;

const octo = octonode.client(GITHUB_TOKEN);
const repo = octo.repo(`${username}/${reponame}`);

function getReleaseByTag(tagName: string): Promise<any> {
  log.info('', 'searching for release "%s"', tagName);

  return new Promise((resolve, reject) => {
    repo.release(`tags/${tagName}`).infoAsync((err: any, data: any) => {
      if (err) {
        if (err.statusCode === 404) {
          log.error('', 'Release for tag "%s" not found.', tagName);
          reject(new Error(`Release not found: ${tagName}`));
          return;
        }
        reject(err);
        return;
      }
      log.info('', 'release for tag "%s" found: %s', tagName, data.url);
      resolve(data);
    });
  });
}

function uploadAssetToRelease(uploadUrl: string, filePath: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const resolvedPath = path.resolve(filePath);
    if (!fs.existsSync(resolvedPath)) {
      reject(new Error(`Asset file not found: ${resolvedPath}`));
      return;
    }

    const fileContent = fs.readFileSync(resolvedPath);
    const fileName = path.basename(resolvedPath);

    // Parse upload URL (remove {?name,label} placeholder)
    const parsedUrl = uploadUrl.replace('{?name,label}', `?name=${encodeURIComponent(fileName)}`);

    log.info('', 'uploading asset to URL: %s', parsedUrl);

    const url = new URL(parsedUrl);
    const options = {
      hostname: url.hostname,
      path: url.pathname + url.search,
      method: 'POST',
      headers: {
        'Authorization': `token ${GITHUB_TOKEN}`,
        'Content-Type': 'application/zip',
        'Content-Length': fileContent.length,
      },
    };

    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', (chunk) => data += chunk);
      res.on('end', () => {
        if (res.statusCode && res.statusCode >= 200 && res.statusCode < 300) {
          log.info('', 'asset "%s" uploaded successfully', fileName);
          resolve();
        } else {
          log.error('', 'failed to upload asset: %s', data);
          reject(new Error(`Upload failed with status ${res.statusCode}: ${data}`));
        }
      });
    });

    req.on('error', (err) => {
      log.error('', 'request error: %s', err.message);
      reject(err);
    });

    req.write(fileContent);
    req.end();
  });
}

function doSomethingWithError(err: Error) {
  log.error('', err.message || err);
  process.exit(-1);
}

async function main() {
  try {
    const release = await getReleaseByTag(tagName);
    const uploadUrl = release.upload_url;
    await uploadAssetToRelease(uploadUrl, assetPath);

    const fileName = path.basename(assetPath);
    const downloadUrl = `https://github.com/${username}/${reponame}/releases/download/${tagName}/${fileName}`;

    log.info('', 'Asset upload completed successfully!');
    log.info('', 'Download URL: %s', downloadUrl);
    console.log('\nDownload URL:', downloadUrl);
  } catch (err) {
    doSomethingWithError(err as Error);
  }
}

main();
