import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL ?? 'http://localhost:8081';
const serverUrl = new URL(baseURL);

export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  retries: 1,
  workers: 1,
  timeout: 60000,
  reporter: 'html',
  use: {
    baseURL,
    trace: 'on-first-retry',
    video: 'off',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      grepInvert: /@responsive/,
      use: { browserName: 'chromium' },
    },
    {
      name: 'firefox',
      grepInvert: /@responsive/,
      use: { browserName: 'firefox' },
    },
    {
      name: 'webkit',
      grepInvert: /@responsive/,
      use: { browserName: 'webkit' },
    },
    {
      name: 'mobile-chrome',
      grep: /@responsive/,
      use: { ...devices['Pixel 5'], browserName: 'chromium' },
    },
  ],
  webServer: {
    command: `php -S ${serverUrl.hostname}:${serverUrl.port || '80'} -t public`,
    url: baseURL,
    timeout: 30000,
    reuseExistingServer: true,
    cwd: '.',
  },
});
