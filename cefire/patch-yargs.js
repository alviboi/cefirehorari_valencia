const fs = require('fs');
const path = require('path');

const target = path.join(__dirname, 'node_modules/yargs/package.json');

if (fs.existsSync(target)) {
    try {
        const pkg = JSON.parse(fs.readFileSync(target, 'utf8'));
        if (pkg.type === 'module') {
            pkg.type = 'commonjs';
            fs.writeFileSync(target, JSON.stringify(pkg, null, 2), 'utf8');
            console.log('Successfully patched yargs package.json: changed type from "module" to "commonjs"');
        } else {
            console.log('yargs package.json is already patched or does not need patching.');
        }
    } catch (e) {
        console.error('Failed to patch yargs package.json:', e);
    }
} else {
    console.log('yargs package.json not found.');
}
