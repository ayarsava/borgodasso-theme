const fs = require('node:fs');
const path = require('node:path');

const certFile = path.join(__dirname, '.certs/localhost.pem');
const keyFile = path.join(__dirname, '.certs/localhost-key.pem');

/**
 * Snippet mode on https://borgodasso.ddev.site
 * localOnly + socket.domain force the client to talk to localhost:3000
 * (not borgodasso.ddev.site:3000, which breaks TLS).
 *
 * The client script is enqueued in WordPress (inc/enqueue.php) while .browsersync
 * exists — no manual snippet paste required.
 */
module.exports = {
	port: 3000,
	open: false,
	notify: false,
	logSnippet: false,
	ghostMode: false,
	ui: false,
	localOnly: true,
	https:
		fs.existsSync(certFile) && fs.existsSync(keyFile)
			? { cert: certFile, key: keyFile }
			: true,
	socket: {
		domain: 'https://localhost:3000',
	},
	files: ['dist/**/*.{css,js}', '**/*.php'],
	ignore: ['node_modules/**'],
	injectChanges: true,
};
