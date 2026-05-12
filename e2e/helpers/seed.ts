import { execSync } from 'child_process';

export function clearRateLimits() {
  try {
    execSync('php spark test:clear-attempts', {
      cwd: process.cwd(),
      stdio: 'pipe',
      timeout: 10000,
    });
  } catch {
    // suppress errors — login_attempts table might be empty
  }
}
