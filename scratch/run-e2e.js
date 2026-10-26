// Run E2E tests with auto-start server
// Usage: node scripts/run-e2e.js [options]

const { spawn, exec } = require('child_process');
const path = require('path');

const BASE_DIR = process.cwd();
const PORT = process.env.E2E_PORT || 8081;

console.log('==========================================' );
console.log('  Arteri E2E Test Runner');
console.log('==========================================' );
console.log(`Base Directory: ${BASE_DIR}`);
console.log(`Port: ${PORT}`);
console.log('');

let serverProcess = null;

// Function to check database connection
function checkDatabase(callback) {
    console.log('[1/3] Checking database connection...');
    
    const testConnection = spawn('php', [
        '-r',
        'try { $db = new mysqli("localhost", "root", "root", "arteri"); echo $db->host_info; } catch (Exception $e) { exit(1); }'
    ], { cwd: BASE_DIR });
    
    let output = '';
    testConnection.stdout.on('data', (data) => {
        output += data.toString();
    });
    
    testConnection.stderr.on('data', (data) => {
        output += data.toString();
    });
    
    testConnection.on('close', (code) => {
        if (code === 0) {
            console.log('  ✓ Database connection OK');
            callback(null);
        } else {
            console.log('  ✗ ERROR: Cannot connect to database');
            console.log('  Please check your database configuration in .env file');
            callback(new Error('Database connection failed'));
        }
    });
}

// Function to run migrations
function runMigrations(callback) {
    console.log('[2/3] Running migrations...');
    
    const migrate = spawn('php', ['spark', 'migrate'], {
        cwd: BASE_DIR,
        stdio: 'inherit'
    });
    
    migrate.on('close', (code) => {
        if (code === 0) {
            console.log('  ✓ Migrations complete');
            callback(null);
        } else {
            console.log('  ⚠ WARNING: Migration had issues');
            callback(null); // Continue anyway
        }
    });
}

// Function to start server
function startServer(callback) {
    console.log('[3/3] Starting development server...');
    console.log(`  Server will run on http://localhost:${PORT}`);
    
    serverProcess = spawn('php', ['spark', 'serve', '--port=' + PORT], {
        cwd: BASE_DIR,
        env: { ...process.env, CI: 'true' },
        stdio: 'pipe'
    });
    
    serverProcess.stdout.on('data', (data) => {
        process.stdout.write(data.toString());
    });
    
    serverProcess.stderr.on('data', (data) => {
        process.stderr.write(data.toString());
    });
    
    // Wait for server to start
    setTimeout(() => {
        console.log('  ✓ Server started');
        callback(null);
    }, 3000);
}

// Function to stop server
function stopServer() {
    if (serverProcess) {
        console.log('\n  Stopping server...');
        serverProcess.kill();
        serverProcess = null;
    }
}

// Function to run E2E tests
function runE2ETests() {
    console.log('\n==========================================' );
    console.log('  Running E2E Tests');
    console.log('==========================================' );
    
    const test = spawn('npx', ['playwright', 'test', '--project=chromium'], {
        cwd: BASE_DIR,
        stdio: 'inherit'
    });
    
    test.on('close', (code) => {
        console.log('\n==========================================' );
        console.log(`  Tests completed with exit code: ${code}`);
        console.log('==========================================' );
        stopServer();
        process.exit(code);
    });
}

// Main execution
async function main() {
    try {
        await new Promise((resolve, reject) => {
            checkDatabase((err) => err ? reject(err) : resolve());
        });
        
        await new Promise((resolve, reject) => {
            runMigrations((err) => err ? reject(err) : resolve());
        });
        
        await new Promise((resolve, reject) => {
            startServer((err) => err ? reject(err) : resolve());
        });
        
        runE2ETests();
        
    } catch (err) {
        console.error('Error:', err.message);
        stopServer();
        process.exit(1);
    }
}

// Handle Ctrl+C
process.on('SIGINT', () => {
    stopServer();
    process.exit(0);
});

main();