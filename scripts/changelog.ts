import fs from 'fs';
import { exec as execCb } from 'child_process';
import path from 'path';

import util from 'util';

import semver from 'semver';

import moment from 'moment';
// eslint-disable-next-line
import git from 'simple-git';
// eslint-disable-next-line
import changelog from 'generate-changelog';
import dotenvSafe from 'dotenv-safe';
// eslint-disable-next-line
import { Octokit } from '@octokit/rest';
import minimist from 'minimist';

type CliArgs = {
  version?: string;
  major?: boolean;
  minor?: boolean;
  _: string[];
};

const argv: CliArgs = minimist(process.argv.slice(1));

const exec: (command: string) => Promise<{ stdout: string; stderr: string }> =
  util.promisify(execCb);
const root: (...paths: string[]) => string = path.join.bind(
  this,
  __dirname,
  '../',
);
dotenvSafe.config({
  path: root('.env'),
  sample: root('.env.example'),
});
const owner: string = 'Open-Pix';
const repo: string = 'woocommerce-openpix';
const GITHUB_TOKEN: string | undefined = process.env.GITHUB_TOKEN;

type ChangelogEntry = {
  version: string;
  date: string;
  items: string[];
};

type ExtractChangelogArgs = {
  content: string;
};

type ExtractChangelogSuccess = {
  success: true;
  data: ChangelogEntry;
};

type ExtractChangelogError = {
  success: false;
  error: string;
};

type ExtractChangelogResult = ExtractChangelogSuccess | ExtractChangelogError;

const extractLatestChangelogEntry = (
  args: ExtractChangelogArgs,
): ExtractChangelogResult => {
  const headerMatch: RegExpMatchArray | null = args.content.match(/^####\s+(\d+\.\d+\.\d+)\s+\(([^)]+)\)/m);
  if (!headerMatch || headerMatch.index === undefined) {
    return { success: false, error: 'Unable to find latest changelog entry' };
  }
  const headerEndIndex: number = headerMatch.index + headerMatch[0].length;
  const afterHeader: string = args.content.slice(headerEndIndex);
  const nextHeaderIndex: number = afterHeader.search(/\n####\s+\d+\.\d+\.\d+/);
  const body: string = nextHeaderIndex === -1 ? afterHeader : afterHeader.slice(0, nextHeaderIndex);
  const items: string[] = body
    .split('\n')
    .map((line: string): string => line.trim())
    .filter((line: string): boolean => line.startsWith('*') || line.startsWith('-'))
    .map((line: string): string => line.replace(/^[-*]+\s*/, ''))
    .filter((line: string): boolean => line.length > 0);
  return { success: true, data: { version: headerMatch[1], date: headerMatch[2], items } };
};

type BuildReadmeSectionArgs = {
  entry: ChangelogEntry;
};

type BuildReadmeSectionSuccess = {
  success: true;
  data: string;
};

type BuildReadmeSectionError = {
  success: false;
  error: string;
};

type BuildReadmeSectionResult =
  | BuildReadmeSectionSuccess
  | BuildReadmeSectionError;

const buildReadmeSection = (
  args: BuildReadmeSectionArgs,
): BuildReadmeSectionResult => {
  const entries: string[] =
    args.entry.items.length === 0
      ? ['- Atualizações diversas']
      : args.entry.items.map((item: string): string => `- ${item}`);
  const section: string = `= ${args.entry.version} - ${args.entry.date} =\n\n${entries.join(
    '\n',
  )}\n\n`;
  return { success: true, data: section };
};

type UpdateReadmeArgs = {
  readmePath: string;
  entry: ChangelogEntry;
  section: string;
};

type UpdateReadmeSuccess = {
  success: true;
};

type UpdateReadmeError = {
  success: false;
  error: string;
};

type UpdateReadmeResult = UpdateReadmeSuccess | UpdateReadmeError;

const updateReadmeFile = (args: UpdateReadmeArgs): UpdateReadmeResult => {
  if (!fs.existsSync(args.readmePath)) {
    return { success: false, error: `readme not found at ${args.readmePath}` };
  }
  const readmeContent: string = fs.readFileSync(args.readmePath, 'utf-8');
  if (readmeContent.includes(`= ${args.entry.version} -`)) {
    return { success: true };
  }
  const changelogHeader: string = '== Changelog ==';
  const headerIndex: number = readmeContent.indexOf(changelogHeader);
  if (headerIndex < 0) {
    return { success: false, error: 'Changelog section not found in readme.txt' };
  }
  const headerEndIndex: number = readmeContent.indexOf('\n\n', headerIndex);
  const insertionIndex: number = headerEndIndex > -1 ? headerEndIndex + 2 : readmeContent.length;
  const updatedContent: string = `${readmeContent.slice(0, insertionIndex)}${args.section}${readmeContent.slice(insertionIndex)}`;
  fs.writeFileSync(args.readmePath, updatedContent);
  return { success: true };
};

const createPullRequest = async (
  branchName: string,
  tag: string,
): Promise<void> => {
  if (!GITHUB_TOKEN) {
    return;
  }
  const octokit = new Octokit({ auth: GITHUB_TOKEN });
  const now: string = moment().format('YYYY-MM-DD');
  const latestReleases = await octokit.repos.listReleases({
    owner,
    repo,
    per_page: 1,
  });
  const latestReleaseTag: string =
    latestReleases && latestReleases.data && latestReleases.data.length
      ? latestReleases.data[0].tag_name
      : 'main';
  await octokit.pulls.create({
    owner,
    repo,
    title: `Deploy Production - ${tag} - ${now}`,
    head: branchName,
    base: 'main',
    body: `https://github.com/${owner}/${repo}/compare/${latestReleaseTag}...main`,
  });
};

const updatePhp = async (
  latestVersion: string,
  newVersion: string,
): Promise<void> => {
  const blankParamForMac: string = process.platform === 'darwin' ? "''" : '';
  const headerSedExp: string = `sed -i ${blankParamForMac} s/"Version: ${latestVersion}"/"Version: ${newVersion}"/g woocommerce-openpix.php`;
  const constSedExp: string = `sed -i ${blankParamForMac} s/"VERSION = '${latestVersion}'"/"VERSION = '${newVersion}'"/g woocommerce-openpix.php`;
  const readmeSedExp: string = `sed -i ${blankParamForMac} s/"Stable tag: ${latestVersion}"/"Stable tag: ${newVersion}"/g readme.txt`;
  const pluginReadmeSedExp: string = `sed -i ${blankParamForMac} s/"Stable tag: ${latestVersion}"/"Stable tag: ${newVersion}"/g ../plugins/openpix-for-woocommerce/readme.txt`;
  await exec(headerSedExp);
  await exec(constSedExp);
  await exec(readmeSedExp);
  await exec(pluginReadmeSedExp);
};

const run = async (): Promise<void> => {
  const resultTag: { latest: string } = await git().tags();
  const latestTag: string = resultTag.latest;
  const currentChangelog: string = fs.readFileSync('./CHANGELOG.md', 'utf-8');
  const diffPattern: string = `${latestTag}..main`;
  const changelogContent: string = await changelog.generate({ tag: diffPattern });
  const rxVersion: RegExp = /\d+\.\d+\.\d+/;
  const versionMatch: RegExpMatchArray | null = changelogContent.match(rxVersion);
  const latestVersion: string = argv.version || versionMatch?.[0] || '';
  if (!latestVersion) {
    throw new Error('Unable to determine latest version from changelog');
  }
  const getReleaseType = (): semver.ReleaseType => {
    if (argv.major) {
      return 'major';
    }
    if (argv.minor) {
      return 'minor';
    }
    return 'patch';
  };
  const semverResult: string | null = semver.inc(latestVersion, getReleaseType());
  if (!semverResult) {
    throw new Error('Unable to increment version');
  }
  const newChangelogContent: string =
    changelogContent.replace(rxVersion, semverResult) + currentChangelog;
  const changelogEntryResult: ExtractChangelogResult = extractLatestChangelogEntry({
    content: newChangelogContent,
  });
  if (!changelogEntryResult.success) {
    throw new Error(changelogEntryResult.error);
  }
  const readmeSectionResult: BuildReadmeSectionResult = buildReadmeSection({
    entry: changelogEntryResult.data,
  });
  if (!readmeSectionResult.success) {
    throw new Error(readmeSectionResult.error);
  }
  const readmePaths: string[] = [
    root('readme.txt'),
    root('../plugins/openpix-for-woocommerce/readme.txt'),
  ];
  readmePaths.forEach((readmePath: string): void => {
    const updateResult: UpdateReadmeResult = updateReadmeFile({
      readmePath,
      entry: changelogEntryResult.data,
      section: readmeSectionResult.data,
    });
    if (!updateResult.success) {
      throw new Error(updateResult.error);
    }
  });
  fs.writeFileSync('./CHANGELOG.md', newChangelogContent);
  await exec(`npm version --no-git-tag-version ${semverResult}`);
  const tag: string = `v${semverResult}`;
  const today: Date = new Date();
  const branchName: string = `feature-production/${today.getFullYear()}${today.getMonth() + 1}${today.getDate()}${today.getUTCHours()}${today.getUTCMinutes()}`;
  await updatePhp(latestVersion, semverResult);
  await git().checkout(['-B', branchName]);
  await git().add([
    'package.json',
    'CHANGELOG.md',
    'woocommerce-openpix.php',
    'readme.txt',
    '../plugins/openpix-for-woocommerce/readme.txt',
  ]);
  await git().commit(`build(change-log): ${tag}`, [], {
    '--no-verify': true,
  });
  await git().addAnnotatedTag(`${tag}`, `build(tag): ${tag}`);
  await git().push(['--follow-tags', '-u', 'origin', branchName]);
  await createPullRequest(branchName, tag);
};

(async () => {
  try {
    await run();
  } catch (err) {
    // eslint-disable-next-line
    console.log('err: ', err);
  }
})();
