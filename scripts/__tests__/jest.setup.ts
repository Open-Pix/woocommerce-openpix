// Mock console.log to prevent output during tests
global.console.log = jest.fn();
global.console.error = jest.fn();

// Mock process.exit to prevent test process from exiting
process.exit = jest.fn() as unknown as typeof process.exit; 