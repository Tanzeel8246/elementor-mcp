#!/usr/bin/env node
import { existsSync, readFileSync } from 'node:fs';
import { spawn } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = dirname(fileURLToPath(import.meta.url));
const configPath = process.env.MINDCRAFTS_MCP_CONFIG || join(scriptDir, 'mindcrafts-ai-mcp.local.json');

function fail(message) {
  process.stderr.write(`[MindCrafts MCP] ${message}\n`);
  process.exit(1);
}

if (!existsSync(configPath)) {
  fail(`Missing config file: ${configPath}`);
}

let config;
try {
  config = JSON.parse(readFileSync(configPath, 'utf8'));
} catch (error) {
  fail(`Could not read config JSON: ${error.message}`);
}

function required(name) {
  const value = String(config[name] || '').trim();
  if (!value || value.startsWith('PUT_')) {
    fail(`Please set ${name} in ${configPath}`);
  }
  return value;
}

const password = required('WP_API_PASSWORD');
if (password.includes(':') || password.startsWith('Basic ')) {
  fail('WP_API_PASSWORD must contain only the WordPress Application Password, not username:password or Basic auth.');
}

const env = {
  ...process.env,
  WP_API_URL: required('WP_API_URL'),
  WP_API_USERNAME: required('WP_API_USERNAME'),
  WP_API_PASSWORD: password,
  OAUTH_ENABLED: String(config.OAUTH_ENABLED || 'false'),
  LOG_FILE: String(config.LOG_FILE || 'C:/tmp/mindcrafts-mcp.log'),
  LOG_LEVEL: String(config.LOG_LEVEL || '3')
};

const command = process.platform === 'win32' ? 'npx.cmd' : 'npx';
const args = ['-y', '@automattic/mcp-wordpress-remote@latest'];

const child = spawn(command, args, {
  env,
  stdio: 'inherit',
  windowsHide: true
});

child.on('error', (error) => {
  fail(`Could not launch ${command}: ${error.message}`);
});

child.on('exit', (code, signal) => {
  if (signal) {
    process.kill(process.pid, signal);
    return;
  }
  process.exit(code ?? 0);
});
