import fs from 'fs';
import { exec as execCb } from 'child_process';
import path from 'path';

import util from 'util';

import moment from 'moment';
// eslint-disable-next-line
import git from 'simple-git/promise';
// eslint-disable-next-line
import changelog from 'generate-changelog';
import dotenvSafe from 'dotenv-safe';
// eslint-disable-next-line
import { Octokit } from '@octokit/rest';

// eslint-disable-next-line
const argv = require('minimist')(process.argv.slice(1));

const exec = util.promisify(execCb);

const root = path.join.bind(this, __dirname, '../');

dotenvSafe.config({
  path: root('.env'),
  sample: root('.env.example'),
});

const owner = 'Open-Pix';
const repo = 'woocommerce-openpix';

const createPullRequest = async (branchName, tag) => {
  if (!process.env.GITHUB_TOKEN) {
    return;
  }

  const octokit = new Octokit({
    auth: process.env.GITHUB_TOKEN,
  });

  const now = moment().format('YYYY-MM-DD');

  // https://octokit.github.io/rest.js/#api-Repos-getReleases
  // https://developer.github.com/v3/repos/releases/#list-releases-for-a-repository
  const latestReleases = await octokit.repos.listReleases({
    owner,
    repo,
    per_page: 1,
  });
  const latestReleaseTag =
    latestReleases && latestReleases.data && latestReleases.data.length
      ? latestReleases.data[0].tag_name
      : 'master';

  await octokit.pulls.create({
    owner,
    repo,
    title: `Deploy Production - ${tag} - ${now}`,
    head: branchName,
    base: 'master',
    body: `https://github.com/${owner}/${repo}/compare/${latestReleaseTag}...master`,
  });
};

(async () => {
  try {
    await git().tags();

    const currentChangelog = fs.readFileSync('./CHANGELOG.md');

    const changelogContent = await changelog.generate({
      major: argv.major,
      minor: argv.minor,
      patch: argv.patch,
    });

    const rxVersion = /\d+\.\d+\.\d+/;
    const newVersion = argv.version || changelogContent.match(rxVersion)?.[0];

    const newChangelogContent =
      changelogContent.replace(rxVersion, newVersion) + currentChangelog;
    fs.writeFileSync('./CHANGELOG.md', newChangelogContent);

    await exec(`npm version --no-git-tag-version ${newVersion}`);

    const tag = `v${newVersion}`;

    const today = new Date();

    const branchName = `feature-production/${today.getFullYear()}${
      today.getMonth() + 1
    }${today.getDate()}${today.getUTCHours()}${today.getUTCMinutes()}`;

    await git().checkout(['-B', branchName]);
    await git().add(['package.json', 'CHANGELOG.md']);
    await git().commit(`build(change-log): ${tag}`, [], '-n');
    await git().addAnnotatedTag(`${tag}`, `build(tag): ${tag}`);
    await git().push(['--follow-tags', '-u', 'origin', branchName]);

    await createPullRequest(branchName, tag);
  } catch (err) {
    // eslint-disable-next-line
    console.log('err: ', err);
  }
})();
