import fs from 'fs';
import { exec as execCb, spawn } from 'child_process';
import path from 'path';
import util from 'util';

import dotenvSafe from 'dotenv-safe';

const exec = util.promisify(execCb);
const svnDefaultArgs: string[] = [
  '--config-option',
  'servers:global:http-compression=yes',
  '--config-option',
  'servers:global:http-timeout=0',
];
const runSvnCommand = async (args: string[]): Promise<void> =>
  new Promise((resolve, reject) => {
    const child = spawn('svn', [...svnDefaultArgs, ...args], { stdio: 'inherit' });
    child.on('error', reject);
    child.on('close', (code) => {
      if (code === 0) {
        resolve();
        return;
      }
      reject(new Error(`svn ${args.join(' ')} exited with code ${code}`));
    });
  });

const root = path.join.bind(this, __dirname, '../');

dotenvSafe.config({
  path: root('.env'),
  sample: root('.env.example'),
});

// SVN credentials from environment variables
const SVN_USERNAME = process.env.SVN_USERNAME;
const SVN_PASSWORD = process.env.SVN_PASSWORD;
const SVN_REPO_URL = process.env.SVN_REPO_URL;

export const findLatestProdZip = async () => {
  const files = await fs.promises.readdir('..');
  const prodZips = files.filter(
    (file) =>
      file.startsWith('woocommerce-openpix-prod') && file.endsWith('.zip'),
  );

  if (prodZips.length === 0) {
    throw new Error('No production zip file found');
  }

  // Sort by modification time to get the latest
  const latestZip = prodZips.sort((a, b) => {
    const dir = (file: string) => path.resolve(process.cwd(), '..', file);

    const statA = fs.statSync(dir(a));
    const statB = fs.statSync(dir(b));
    return statB.mtime.getTime() - statA.mtime.getTime();
  })[0];

  return latestZip;
};

export const checkCurrentBranch = async () => {
  const { stdout: currentBranch } = await exec('git branch --show-current');
  if (currentBranch.trim() === 'main') {
    throw new Error(
      'This script cannot be run on the main branch. Please run pnpm release:major, pnpm release:minor or pnpm release:patch.',
    );
  }
};

export const run = async () => {
  if (!SVN_USERNAME || !SVN_PASSWORD || !SVN_REPO_URL) {
    throw new Error('SVN credentials not found in environment variables');
  }

  try {
    // Check if we're on main branch
    // await checkCurrentBranch();

    // Generate production build
    console.log('Generating production build...');
    await exec('./pack.sh prod');

    // Clone the SVN repository
    console.log('Cloning SVN repository...');

    const svnDirectory = 'openpix-for-woocommerce';

    if (!fs.existsSync(svnDirectory)) {
      await exec(
        `svn checkout ${SVN_REPO_URL} ${svnDirectory} --username ${SVN_USERNAME} --password "${SVN_PASSWORD}"`,
      );
    } else {
      await exec(
        `svn update ${svnDirectory} --username ${SVN_USERNAME} --password "${SVN_PASSWORD}"`,
      );
    }

    // Change to the repository directory
    process.chdir(svnDirectory);

    // Get the latest version from package.json
    const packageJson = JSON.parse(fs.readFileSync('../package.json', 'utf8'));
    const newVersion = packageJson.version;
    const tagDirectory = path.posix.join('tags', newVersion);
    const trunkDirectory = 'trunk';
    const authArgs = [
      '--username',
      SVN_USERNAME as string,
      '--password',
      SVN_PASSWORD as string,
    ];

    if (fs.existsSync(tagDirectory)) {
      console.log(`Removing existing tag directory for version ${newVersion}...`);
      await exec(`svn delete ${tagDirectory} --force`);
      await fs.promises.rm(tagDirectory, { recursive: true, force: true });
    }

    // Create new tag directory
    console.log(`Creating new tag for version ${newVersion}...`);
    await fs.promises.mkdir(tagDirectory, { recursive: true });

    // Find the latest production zip file
    console.log('Finding latest production zip file...');
    const latestZip = await findLatestProdZip();
    console.log(`Found zip file: ${latestZip}`);

    // Extract to tag directory
    console.log('Extracting to tag directory...');
    await exec(`unzip -o ../${latestZip} -d ${tagDirectory}`);

    console.log('Cleaning trunk directory...');
    const trunkEntries = await fs.promises.readdir(trunkDirectory);
    await Promise.all(
      trunkEntries.map(async (entry) =>
        fs.promises.rm(path.join(trunkDirectory, entry), {
          recursive: true,
          force: true,
        }),
      ),
    );

    // Copy new tag to trunk
    console.log('Copy new tag to trunk...');
    await exec(`cp -R ${tagDirectory}/. ${trunkDirectory}/`);

    console.log('Staging deletions...');
    const { stdout: statusBeforeAdd } = await exec('svn status');
    const removedPaths = statusBeforeAdd
      .split('\n')
      .map((line) => line.trim())
      .filter((line) => line.startsWith('!'))
      .map((line) => line.replace('!', '').trim())
      .filter(Boolean);
    for (const removedPath of removedPaths) {
      await exec(`svn delete ${removedPath} --force`);
    }

    // Add the new svn tag to version control
    console.log('Adding new svn tag to version control...');
    await exec(`svn add ${tagDirectory} --force`);

    // Add trunk changes
    console.log('Adding trunk changes...');
    await exec(`svn add ${trunkDirectory} --force`);

    // Commit the new version
    const commitMessage = `version ${newVersion}`;
    console.log('Committing tag changes first...');
    await runSvnCommand(['ci', tagDirectory, '-m', commitMessage, ...authArgs]);
    console.log('Committing trunk changes...');
    await runSvnCommand([
      'ci',
      trunkDirectory,
      '-m',
      commitMessage,
      ...authArgs,
    ]);

    // Verify status
    console.log('Verifying status...');

    const { stdout: status } = await exec('svn status');

    if (status.trim() === '') {
      console.log('All files committed successfully!');
    } else {
      console.log('Warning: Some files still need to be committed:');
      console.log(status);
    }

    // Clean up
    process.chdir('..');
    // await exec('rm -rf openpix-for-woocommerce temp *.zip');

    console.log('SVN release completed successfully!');
  } catch (error) {
    console.error('Error during SVN operations:', error);
    process.exit(1);
  }
};

// Only run if this file is being executed directly (not imported as a module)
if (require.main === module) {
  (async () => {
    await run();
  })();
}
