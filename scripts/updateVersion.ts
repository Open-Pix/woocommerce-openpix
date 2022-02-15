import { exec as execCb } from 'child_process';
import util from 'util';

const exec = util.promisify(execCb);

const run = async () => {
  const [, , ...unsanitizedArgs] = process.argv;

  if (unsanitizedArgs.length !== 2) {
    //eslint-disable-next-line
    console.log(
      `
        Usage: yarn es ./scripts/updateVersion.ts <old-version> <new-version>
      `,
    );

    return;
  }

  const [latestVersion, newVersion] = unsanitizedArgs;

  // Version: 2.0.1
  const headerSedExp = `sed -i '' s/"Version: ${latestVersion}"/"Version: ${newVersion}"/g woocommerce-openpix.php`;

  // const VERSION = '2.0.1';
  const constSedExp = `sed -i '' s/"VERSION = '${latestVersion}'"/"VERSION = '${newVersion}'"/g woocommerce-openpix.php`;

  await exec(headerSedExp);
  await exec(constSedExp);
};

(async () => {
  try {
    await run();
  } catch (err) {
    // eslint-disable-next-line
    console.log(err);
    process.exit(1);
  }

  process.exit(0);
})();
