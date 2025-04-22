import fs from 'fs';
import { exec } from 'child_process';

import { findLatestProdZip, checkCurrentBranch, run } from '../svn-release';

// Increase test timeout
jest.setTimeout(30000);
jest.mock('child_process', () => ({
  exec: jest.fn().mockReturnValue({
    stdout: '',
    stderr: '',
  }),
}));

// Mock external dependencies
jest.mock('fs', () => {
  const actualFs = jest.requireActual('fs');
  return {
    ...actualFs,
    promises: {
      readdir: jest.fn().mockResolvedValue([]),
    },
    statSync: jest.fn(),
  };
});

jest.mock('util', () => ({
  promisify: (fn: unknown) => fn,
}));

jest.mock('dotenv-safe', () => ({
  config: jest.fn(),
}));

// Mock path.join for root directory
jest.mock('path', () => ({
  join: jest.fn().mockImplementation((...args) => args.join('/')),
}));

beforeEach(() => {
  jest.clearAllMocks();
  jest.resetModules();
});

it('should find the latest production zip file by version', async () => {
  process.env.SVN_USERNAME = 'test_user';
  process.env.SVN_PASSWORD = 'test_pass';
  process.env.SVN_REPO_URL = 'test_url';
  // Import the module after setting environment variables
  // Mock fs.readdir to return some test files
  const mockFiles = [
    'woocommerce-openpix-prod-v1.0.0.zip',
    'woocommerce-openpix-prod-v1.0.1.zip',
    'other-file.txt',
  ];
  (fs.promises.readdir as jest.Mock).mockResolvedValue(mockFiles);

  // Mock fs.statSync to return different modification times
  (fs.statSync as jest.Mock).mockImplementation((file) => ({
    mtime: {
      getTime: () => (file.includes('1.0.1') ? 2 : 1),
    },
  }));

  const latestZip = await findLatestProdZip();

  expect(latestZip).toBe('woocommerce-openpix-prod-v1.0.1.zip');
});

it('should find the latest production zip file by date', async () => {
  process.env.SVN_USERNAME = 'test_user';
  process.env.SVN_PASSWORD = 'test_pass';
  process.env.SVN_REPO_URL = 'test_url';
  // Import the module after setting environment variables
  // Mock fs.readdir to return some test files
  const mockFiles = [
    'woocommerce-openpix-prod-v2.13.0-2025-04-22:02:59.zip',
    'woocommerce-openpix-prod-v2.13.0-2025-04-22:03:02.zip',
    'woocommerce-openpix-prod-v2.13.0-2025-04-22:05:02.zip',
    'other-file.txt',
  ];
  (fs.promises.readdir as jest.Mock).mockResolvedValue(mockFiles);

  // Mock fs.statSync to return different modification times
  (fs.statSync as jest.Mock).mockImplementation((file) => ({
    mtime: {
      getTime: () =>
        file.includes('woocommerce-openpix-prod-v2.13.0-2025-04-22:05:02.zip')
          ? 2
          : 1,
    },
  }));

  const latestZip = await findLatestProdZip();

  expect(latestZip).toBe(
    'woocommerce-openpix-prod-v2.13.0-2025-04-22:05:02.zip',
  );
});

it('should throw error when no production zip is found', async () => {
  // Mock fs.readdir to return no production zip files
  const mockFiles = ['other-file.txt', 'another-file.txt'];
  (fs.promises.readdir as jest.Mock).mockResolvedValue(mockFiles);

  await expect(findLatestProdZip()).rejects.toThrow(
    'No production zip file found',
  );
});

it('should throw error when on main branch', async () => {
  // Mock exec to return 'main' as current branch
  const mockExec = exec as unknown as jest.Mock;
  mockExec.mockResolvedValue({ stdout: 'main' });

  await expect(checkCurrentBranch()).rejects.toThrow(
    'This script cannot be run on the main branch. Please run pnpm release:major, pnpm release:minor or pnpm release:patch.',
  );
});

it('should not throw error when not on main branch', async () => {
  // Mock exec to return 'develop' as current branch
  const mockExec = exec as unknown as jest.Mock;
  mockExec.mockResolvedValue({
    stdout: 'feature-production/2025417208',
  });

  await expect(checkCurrentBranch()).resolves.not.toThrow();
});

it('should throw error when SVN credentials are missing', async () => {
  // Clear environment variables
  delete process.env.SVN_USERNAME;
  delete process.env.SVN_PASSWORD;
  delete process.env.SVN_REPO_URL;
  // This should throw when we try to import the module
  expect(() => run()).rejects.toThrow(
    'SVN credentials not found in environment variables',
  );
});
