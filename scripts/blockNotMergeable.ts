import fs from 'fs/promises';

(async () => {
  const [, , file] = process.argv;

  const content = await fs.readFile(file);

  const notMergelable = content.includes('@woovi/do-not-merge');

  if (notMergelable) {
    // eslint-disable-next-line no-console
    console.log('Do not merge');

    process.exit(1);
  }
})();
