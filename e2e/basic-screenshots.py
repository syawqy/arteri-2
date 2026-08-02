#!/usr/bin/env python3
"""
Simple browser screenshots - login + dashboard + chat page
"""
from playwright.sync_api import sync_playwright
import os

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
    ss(page, '02-dashboard')

    # 3. Chat page (MCP may not connect in headless, that's OK)
    page.goto(f'{BASE}/chat', wait_until='networkidle')
    page.wait_for_timeout(3000)
    ss(page, '03-chat-page')

    browser.close()
    print('\n[DONE] Basic screenshots saved')
