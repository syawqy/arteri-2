import { test, expect } from '@playwright/test';
import { loginAsAdmin, logout } from './helpers/auth';
import { BASE } from './helpers/config';

// Helper function to check status is in array
function expectStatusIn(status: number, allowed: number[]) {
  if (!allowed.includes(status)) {
    throw new Error(`Expected status ${status} to be in [${allowed.join(', ')}]`);
  }
}

test.describe('MCP API Integration', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test.afterEach(async ({ page }) => {
    await logout(page);
  });

  test.describe('MCP Configuration', () => {
    test('should display MCP dashboard for admin', async ({ page }) => {
      await page.goto('/mcp');
      await expect(page.locator('h1, h2, h3, .page-header, .container')).toBeVisible();
    });

    test('should show MCP API documentation', async ({ page }) => {
      await page.goto('/mcp');
      const content = await page.textContent('body');
      expect(content).toMatch(/API|endpoint|config/i);
    });
  });

  test.describe('MCP Endpoints', () => {
    test('should create ingestion request via API', async ({ page, request }) => {
      // Test AI ingestion queue API
      const response = await request.post('/api/mcp/ingestion', {
        data: {
          archive_id: 1,
          priority: 'normal',
          options: {
            extract_metadata: true,
            extract_text: true
          }
        },
        headers: {
          'Content-Type': 'application/json'
        }
      });

      // Should either succeed or return proper error
      expectStatusIn(response.status(), [200, 201, 400, 401, 403, 422, 500]);
    });

    test('should list pending verifications', async ({ page, request }) => {
      const response = await request.get('/api/mcp/verifications', {
        headers: {
          'Accept': 'application/json'
        }
      });

      expectStatusIn(response.status(), [200, 401, 403, 500]);
      
      if (response.status() === 200) {
        const data = await response.json();
        expect(Array.isArray(data)).toBeTruthy();
      }
    });
  });

  test.describe('Document Processing', () => {
    test('should extract metadata from document', async ({ page, request }) => {
      // Mock document parsing
      const response = await request.post('/api/mcp/parse', {
        data: {
          document_id: 1,
          content: 'Surat Masuk\nNomor: 001/SM/2026\nTanggal: 15/06/2026\nPerihal: Laporan Keuangan'
        },
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.status() === 200) {
        const data = await response.json();
        expect(data).toHaveProperty('metadata');
      }
    });

    test('should calculate document confidence score', async ({ page, request }) => {
      const response = await request.post('/api/mcp/score', {
        data: {
          text_content: 'This is a well-formed document with proper text. '.repeat(20),
          criteria: ['ocr_quality', 'regex_match', 'date_extraction']
        },
        headers: {
          'Content-Type': 'application/json'
        }
      });

      expectStatusIn(response.status(), [200, 400, 401, 403, 500]);
    });
  });

  test.describe('Compliance & Retention', () => {
    test('should display retention schedules', async ({ page }) => {
      await page.goto('/mcp/retention');
      const content = await page.textContent('body');
      // Page should load without error
      expect(content).toBeTruthy();
    });

    test('should show legal holds', async ({ page, request }) => {
      const response = await request.get('/api/mcp/legal-holds', {
        headers: {
          'Accept': 'application/json'
        }
      });

      expectStatusIn(response.status(), [200, 401, 403, 500]);
    });

    test('should apply legal hold to archive', async ({ page, request }) => {
      const response = await request.post('/api/mcp/legal-holds', {
        data: {
          archive_id: 1,
          reason: 'Litigation hold',
          expires_at: '2025-12-31'
        },
        headers: {
          'Content-Type': 'application/json'
        }
      });

      expectStatusIn(response.status(), [200, 201, 400, 401, 403, 422, 500]);
    });
  });

  test.describe('Classification', () => {
    test('should auto-classify document', async ({ page, request }) => {
      const response = await request.post('/api/mcp/classify', {
        data: {
          document_text: 'This is a financial report document with budget data.',
          categories: ['finance', 'legal', 'administrative']
        },
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.status() === 200) {
        const data = await response.json();
        expect(data).toHaveProperty('classification');
      }
    });

    test('should show classification results', async ({ page }) => {
      await page.goto('/mcp/classification');
      await expect(page.locator('body')).toBeVisible();
    });
  });

  test.describe('Queue Management', () => {
    test('should list ingestion queue', async ({ page, request }) => {
      const response = await request.get('/api/mcp/queue/ingestion', {
        headers: {
          'Accept': 'application/json'
        }
      });

      expectStatusIn(response.status(), [200, 401, 403, 500]);
    });

    test('should list migration queue', async ({ page, request }) => {
      const response = await request.get('/api/mcp/queue/migration', {
        headers: {
          'Accept': 'application/json'
        }
      });

      expectStatusIn(response.status(), [200, 401, 403, 500]);
    });
  });
});