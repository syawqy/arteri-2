#!/usr/bin/env python3
"""
Browser screenshots for Arteri-2 chatbot using Python Playwright
"""
from playwright.sync_api import sync_playwright
import os, time

BASE = 'http://127.0.0.1:8082'
SS_DIR = os.path.join(os.path.dirname(__file__), '../docs/chatbot-test-screenshots')
os.makedirs(SS_DIR, exist_ok=True)

def ss(page, name):
    p = os.path.join(SS_DIR, f'{name}.png')
    page.screenshot(path=p, full_page=False)
    print(f'[SS] {name}.png ({os.path.getsize(p)} bytes)')

with sync_playwright() as p:
    browser = p.chromium.launch(
        headless=True,
        executable_path='/home/ubuntu/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome',
        args=['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu']
    )
    ctx = browser.new_context(viewport={'width': 1280, 'height': 800})
    page = ctx.new_page()

    # 1. Login page
    page.goto(f'{BASE}/login', wait_until='networkidle')
    ss(page, '01-login-page')

    # 2. Login
    page.fill('input[name="username"]', 'admin')
    page.fill('input[name="password"]', 'admin123')
    page.click('button[type="submit"]')
    page.wait_for_url('**/search**', timeout=10000)
    ss(page, '02-after-login')

    # 3. Chat page - wait for MCP to connect
    page.goto(f'{BASE}/chat', wait_until='networkidle')
    
    # Wait for input to be enabled (MCP connected)
    print('[WAIT] Waiting for MCP connection...')
    try:
        page.wait_for_selector('#userInput:not([disabled])', timeout=30000)
        print('[OK] MCP connected, input enabled')
    except:
        print('[WARN] Input still disabled after 30s, taking screenshot anyway')
    
    page.wait_for_timeout(2000)
    ss(page, '03-chat-page')

    # Check MCP status text
    status = page.text_content('.mcp-status') or 'unknown'
    print(f'[MCP] Status: {status}')

    # 4. Test: Count Archives
    if page.is_enabled('#userInput'):
        page.fill('#userInput', 'Berapa total arsip di sistem ini?')
        page.click('#sendBtn')
        page.wait_for_timeout(20000)
        ss(page, '04-count-archives')

        # 5. Test: Search Penghapusan
        page.fill('#userInput', '')
        page.fill('#userInput', 'Cari arsip "Penghapusan"')
        page.click('#sendBtn')
        page.wait_for_timeout(20000)
        ss(page, '05-search-penghapusan')

        # 6. Test: Kode 44
        page.fill('#userInput', '')
        page.fill('#userInput', 'Tampilkan arsip kode 44')
        page.click('#sendBtn')
        page.wait_for_timeout(20000)
        ss(page, '06-kode-44')

        # 7. Test: Metadata
        page.fill('#userInput', '')
        page.fill('#userInput', 'Cek kelengkapan metadata arsip')
        page.click('#sendBtn')
        page.wait_for_timeout(20000)
        ss(page, '07-metadata-completeness')

        # 8. Test: Suggest Classification
        page.fill('#userInput', '')
        page.fill('#userInput', 'Suggest klasifikasi untuk surat keputusan pegawai negeri')
        page.click('#sendBtn')
        page.wait_for_timeout(20000)
        ss(page, '08-suggest-classification')

        # 9. Test: Compliance Report
        page.fill('#userInput', '')
        page.fill('#userInput', 'Buat laporan compliance arsip')
        page.click('#sendBtn')
        page.wait_for_timeout(20000)
        ss(page, '09-compliance-report')

        # 10. Session sidebar
        ss(page, '10-session-history')
    else:
        print('[SKIP] Input disabled, skipping chat tests')
        ss(page, '04-input-disabled')

    browser.close()
    print('\n[DONE] All screenshots saved')
