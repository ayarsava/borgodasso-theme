import { existsSync, mkdirSync, writeFileSync, unlinkSync } from 'node:fs';
import { spawn, spawnSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const themeDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const certsDir = path.join(themeDir, '.certs');
const certFile = path.join(certsDir, 'localhost.pem');
const keyFile = path.join(certsDir, 'localhost-key.pem');
const flagFile = path.join(themeDir, '.browsersync');

function ensureCerts() {
	if (existsSync(certFile) && existsSync(keyFile)) {
		return;
	}

	mkdirSync(certsDir, { recursive: true });
	const result = spawnSync(
		'mkcert',
		['-cert-file', certFile, '-key-file', keyFile, 'localhost', '127.0.0.1', '::1'],
		{ stdio: 'inherit' }
	);

	if (result.status !== 0) {
		console.error(
			'\nCould not create HTTPS certs for BrowserSync. Install mkcert, run `mkcert -install`, then retry.\n'
		);
		process.exit(1);
	}
}

function cleanup() {
	try {
		unlinkSync(flagFile);
	} catch {
		// Flag may already be gone.
	}
}

ensureCerts();
writeFileSync(flagFile, '1');

console.log(
	'[reload] BrowserSync watching on https://localhost:3000 — client loaded by WordPress (inc/enqueue.php)'
);

const child = spawn(
	process.platform === 'win32' ? 'npx.cmd' : 'npx',
	['browser-sync', 'start', '--config', 'bs-config.cjs'],
	{
		cwd: themeDir,
		stdio: 'inherit',
		shell: true,
	}
);

const exit = (code = 0) => {
	cleanup();
	process.exit(code);
};

process.on('exit', cleanup);
process.on('SIGINT', () => exit(0));
process.on('SIGTERM', () => exit(0));
child.on('exit', (code) => exit(code ?? 0));
