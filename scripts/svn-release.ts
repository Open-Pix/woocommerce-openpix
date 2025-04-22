import fs from 'fs';
import { exec as execCb } from 'child_process';
import path from 'path';
import util from 'util';

import dotenvSafe from 'dotenv-safe';

const exec = util.promisify(execCb);

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
    const statA = fs.statSync(a);
    const statB = fs.statSync(b);
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
    await checkCurrentBranch();

    // Generate production build
    console.log('Generating production build...');
    await exec('./pack.sh prod');

    // Clone the SVN repository
    console.log('Cloning SVN repository...');
    await exec(
      `svn checkout ${SVN_REPO_URL} --username ${SVN_USERNAME} --password "${SVN_PASSWORD}"`,
    );

    // Change to the repository directory
    process.chdir('openpix-for-woocommerce');

    // Get the latest version from package.json
    const packageJson = JSON.parse(fs.readFileSync('../package.json', 'utf8'));
    const newVersion = packageJson.version;

    // Create new tag directory
    console.log(`Creating new tag for version ${newVersion}...`);
    await exec(`mkdir -p tags/${newVersion}`);

    // Find the latest production zip file
    console.log('Finding latest production zip file...');
    const latestZip = await findLatestProdZip();
    console.log(`Found zip file: ${latestZip}`);

    // Extract to tag directory
    console.log('Extracting to tag directory...');
    await exec(`unzip -o ../${latestZip} -d tags/${newVersion}`);

    // Copy new version to trunk
    console.log('Copy new version to trunk...');
    await exec(`cp -r tags/${newVersion}/* trunk/`);

    // Add the new tag to version control
    console.log('Adding new tag to version control...');
    await exec(`svn add tags/${newVersion}`);

    // Add trunk changes
    console.log('Adding trunk changes...');
    await exec('svn add trunk/*');

    // Commit the new version
    console.log('Committing new version...');
    await exec(
      `svn ci -m "version ${newVersion}" --username ${SVN_USERNAME} --password ${SVN_PASSWORD}`,
    );

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
    await exec('rm -rf openpix-for-woocommerce temp *.zip');

    console.log('SVN release completed successfully!');
    console.log(
      `Open PR with name "build(change-log): v${newVersion}" and merge it.`,
    );
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
