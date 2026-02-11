#!/usr/bin/env node
/* eslint-disable no-console */
import { Octokit } from '@octokit/rest';
import changelog from 'generate-changelog';
import git from 'simple-git';

import pkg from '../package.json' with { type: 'json' };

const owner = 'entria';
const repo = 'service-datasource';

// Use current version of the package by default
const versionTag = `v${pkg.version}`;

const repoUrl = `https://github.com/${owner}/${repo}.git`;

const githubToken = process.env.GITHUB_TOKEN || process.env.GITHUB_API_TOKEN;
console.log({githubToken})
const octokit = new Octokit({
  auth: githubToken,
});

async function getReleaseByTag(tagName: string): Promise<Release> {
  console.log('', 'searching for release "%s"', tagName);

  const release = await octokit.repos.getReleaseByTag({
    owner,
    repo,
    tag: tagName,
  });

  return release;
}

const createRelease = async (tagName: string): Promise<void> => {
  console.log('', 'creating release for tag "%s"', tagName);

  let lastTagName: string | null = null;

  try {
    const latestRelease = await octokit.repos.getLatestRelease({
      owner,
      repo,
    });

    lastTagName = latestRelease.data.tag_name;
  } catch (err) {
    // No previous releases found, this is the first release
    if (err?.status === 404) {
      console.log('', 'no previous releases found, creating first release');
      lastTagName = null;
    } else {
      throw err;
    }
  }

  let body = 'No changelog';

  if (lastTagName) {
    const diffPattern = `${lastTagName}..${tagName}`;

    try {
      const options = {
        tag: diffPattern,
        repoUrl,
      };

      const changelogContent = await changelog.generate(options);

      body = changelogContent.replace(/^#### (.*)\n/gm, '');
    } catch (err) {
      console.log('', 'error generating changelog:', err);
      body = 'No changelog';
    }
  }

  await octokit.repos.createRelease({
    owner,
    repo,
    tag_name: tagName,
    name: tagName,
    body,
  });
};

async function run() {
  try {
    console.log('starting script');
    await git().fetch(['--tags']);
    console.log('fetched tags');

    await getReleaseByTag(versionTag);
  } catch (err) {
    if (err?.status === 404) {
      await createRelease(versionTag);
    }
  }
}

(async () => {
  await run();

  process.exit(0);
})();