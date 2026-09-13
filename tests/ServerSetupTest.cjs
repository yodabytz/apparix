const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const root = fs.mkdtempSync(path.join(os.tmpdir(), 'apparix-setup-test-'));
try {
    for (const dir of ['tools', 'bin', 'vendor']) fs.mkdirSync(path.join(root, dir));
    const script = path.join(root, 'tools/setup-server.sh');
    fs.copyFileSync(path.join(__dirname, '../tools/setup-server.sh'), script);
    fs.writeFileSync(path.join(root, 'version.php'), '<?php return ["version"=>"1.4.0"];');
    fs.writeFileSync(path.join(root, 'composer.lock'), '{}');
    fs.writeFileSync(path.join(root, 'vendor/autoload.php'), '<?php');
    const executable = (name, body) => fs.writeFileSync(path.join(root, 'bin', name), '#!/bin/sh\n' + body + '\n', { mode: 0o755 });
    executable('dpkg-query', 'case "$*" in *php8.3-cli*|*php8.3-common*) printf "install ok installed";; *) exit 1;; esac');
    executable('php8.3', 'exit 1');
    executable('apt-cache', 'printf "  Candidate: 8.3.1\n"');
    executable('apt-get', 'printf "CALLED\\n" >> "$SETUP_TEST_APT_LOG"; exit 99');
    const aptLog = path.join(root, 'apt.log');
    const invoke = (...args) => spawnSync('/bin/bash', [script, ...args], {
        encoding: 'utf8', input: 'NO\n', timeout: 10000,
        env: { ...process.env, PATH: path.join(root, 'bin') + ':' + process.env.PATH, SETUP_TEST_APT_LOG: aptLog },
    });
    const help = invoke('--help');
    assert.equal(help.status, 0); assert.match(help.stdout, /read-only/);
    const preview = invoke('--php=8.3');
    assert.equal(preview.status, 0, preview.stderr);
    assert.match(preview.stdout, /php8.3-mysql.*php8.3-mbstring.*php8.3-gd.*php8.3-curl.*php8.3-zip/);
    assert.doesNotMatch(preview.stdout, /Missing OS packages:.*(?:nginx|mariadb|cron)/);
    assert.match(preview.stdout, /Preview only/);
    const optional = invoke('--php=8.3', '--with-nginx', '--with-mariadb', '--with-cron');
    assert.equal(optional.status, 0, optional.stderr);
    assert.match(optional.stdout, /Missing OS packages:.*nginx.*php8.3-fpm.*mariadb-server.*cron/);
    const cancelled = invoke('--php=8.3', '--apply');
    assert.notEqual(cancelled.status, 0);
    assert.match(cancelled.stderr, /requires sudo|Cancelled/);
    assert.notEqual(invoke('--php=7.4').status, 0);
    assert.notEqual(invoke('--php=8.3;touch /tmp/no').status, 0);
    assert.notEqual(invoke('--owner=root;bad').status, 0);
    assert.notEqual(invoke('--yes').status, 0);
    assert.equal(fs.existsSync(aptLog), false, 'Preview or cancelled run invoked apt-get');
    assert.equal(fs.readFileSync(path.join(root, 'composer.lock'), 'utf8'), '{}');
    console.log('Server setup preview, package selection, cancellation and input validation tests passed.');
} finally {
    fs.rmSync(root, { recursive: true, force: true });
}
