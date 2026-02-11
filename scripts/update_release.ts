#!/usr/bin/env node

import path from 'path';
import fs from 'fs';
import https from 'https';

import dotenvSafe from 'dotenv-safe';
import git from 'simple-git';

const octonode = require('octonode');
const log = require('npmlog');
const changelog = require('generate-changelog');

const root = path.join.bind(this, __dirname, '../');

dotenvSafe.config({
  path: root('.env'),
  sample: root('.env.example'),
});

// Use current version of the package by default
const versionTag =
  process.env.CIRCLE_TAG || `v${require('../package.json').version}`;

const args = process.argv.splice(2);
const validArgs = ['--publish'];

log.heading = 'ci@entria';

if (args.length < 1) {
  log.error(
    '',
    'invalid number of arguments passed to module-packaging script',
  );
  process.exit(-1);
}

const command = args[0];
const extraArgs = args.slice(1);

if (validArgs.indexOf(command) === -1) {
  log.error(
    '',
    'invalid argument "%s" passed to module-packaging script.',
    command,
  );
  process.exit(-1);
}

const username = process.env.CIRCLE_PROJECT_USERNAME || 'Open-Pix';
const reponame = process.env.CIRCLE_PROJECT_REPONAME || 'woocommerce-openpix';
const GITHUB_TOKEN = process.env.GITHUB_TOKEN || process.env.GITHUB_API_TOKEN;

const octo = octonode.client(GITHUB_TOKEN);
const repo = octo.repo(`${username}/${reponame}`);

function parseAssetArg(arg: string): string | undefined {
  if (arg.startsWith('--asset=')) {
    return arg.replace('--asset=', '');
  }
  return undefined;
}

function getAssetPath(): string | undefined {
  for (const arg of extraArgs) {
    const asset = parseAssetArg(arg);
    if (asset) return asset;
  }
  return undefined;
}

function getReleaseByTag(tagName) {
  log.info('', 'searching for release "%s"', tagName);

  return repo
    .release(`tags/${tagName}`)
    .infoAsync()
    .then(([data, headers]) => {
      log.info('', 'release for tag "%s" found: %s', tagName, data.url);
      return [data, headers];
    })
    .catch((err) => {
      if (err && err.statusCode && err.statusCode === 404) {
        log.info('', 'release for tag "%s" not found.', tagName);
      }
      throw err;
    });
}

const createRelease = async (tagName) => {
  log.info('', 'creating release for tag "%s"', tagName);

  const resultTag = await git().tags();
  const currentTag = resultTag.all[resultTag.all.length - 2];

  const diffPattern = `${currentTag}..main`;

  const changelogContent = await changelog.generate({ tag: diffPattern });

  const body = changelogContent.replace(/^#### (.*)\n/gm, '');

  return repo.releaseAsync({
    tag_name: tagName,
    name: tagName,
    body,
  });
};

function uploadAsset(uploadUrl: string, assetPath: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const resolvedPath = path.resolve(assetPath);
    if (!fs.existsSync(resolvedPath)) {
      reject(new Error(`Asset file not found: ${resolvedPath}`));
      return;
    }

    const fileContent = fs.readFileSync(resolvedPath);
    const fileName = path.basename(resolvedPath);

    const parsedUrl = uploadUrl.replace('{?name,label}', `?name=${encodeURIComponent(fileName)}`);

    log.info('', 'uploading asset to URL: %s', parsedUrl);

    // Parse URL manually to avoid op_mini compatibility issues
    const urlMatch = parsedUrl.match(/https?:\/\/([^/]+)(\/.*)/);
    if (!urlMatch) {
      reject(new Error(`Invalid URL: ${parsedUrl}`));
      return;
    }

    const hostname = urlMatch[1];
    const urlPath = urlMatch[2];

    const options = {
      hostname,
      path: urlPath,
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

function doSomethingWithError(err) {
  log.error('', err);
  process.exit(-1);
}

async function publish() {
  const assetPath = getAssetPath();

  return getReleaseByTag(versionTag)
    .catch((err) => {
      if (err.statusCode && err.statusCode === 404) {
        return createRelease(versionTag);
      }
      throw err;
    })
    .then(async (releaseData: any) => {
      if (assetPath) {
        // Se o release já existia, releaseData contém os dados
        // Se foi criado agora, precisamos buscar novamente
        let release: any = releaseData;
        if (!release || !release.upload_url) {
          const result = await getReleaseByTag(versionTag);
          release = result[0];
        }
        await uploadAsset(release.upload_url, assetPath);
      }
    });
}

const commands = {
  publish,
};

commands[command.replace('--', '')]().catch((err) => {
  doSomethingWithError(err);
});
