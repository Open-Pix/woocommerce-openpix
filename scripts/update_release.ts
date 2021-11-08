#!/usr/bin/env node
import git from 'simple-git/promise';

const octonode = require('octonode');
const log = require('npmlog');
const changelog = require('generate-changelog');

// Use current version of the package by default
const versionTag =
  process.env.CIRCLE_TAG || `v${require('../package.json').version}`;

const args = process.argv.splice(2, 2);
const validArgs = ['--publish'];

log.heading = 'ci@entria';

if (args.length !== 1) {
  log.error(
    '',
    'invalid number of arguments passed to module-packaging script',
  );
  process.exit(-1);
}

if (validArgs.indexOf(args[0]) === -1) {
  log.error(
    '',
    'invalid argument "%s" passed to module-packaging script.',
    args[0],
  );
  process.exit(-1);
}

const username = process.env.CIRCLE_PROJECT_USERNAME || 'Open-Pix';
const reponame = process.env.CIRCLE_PROJECT_REPONAME || 'woocommerce-openpix';

const octo = octonode.client(process.env.GITHUB_API_TOKEN);
const repo = octo.repo(`${username}/${reponame}`);

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

  const diffPattern = `${currentTag}..master`;

  const changelogContent = await changelog.generate({ tag: diffPattern });

  const body = changelogContent.replace(/^#### (.*)\n/gm, '');

  return repo.releaseAsync({
    tag_name: tagName,
    name: tagName,
    body,
  });
};

function doSomethingWithError(err) {
  log.error('', err);
  process.exit(-1);
}

function publish() {
  return getReleaseByTag(versionTag).catch((err) => {
    if (err.statusCode && err.statusCode === 404) {
      return createRelease(versionTag);
    }
    throw err;
  });
}

const commands = {
  publish,
};

commands[args[0].replace('--', '')](args[1]).catch((err) => {
  doSomethingWithError(err);
});
