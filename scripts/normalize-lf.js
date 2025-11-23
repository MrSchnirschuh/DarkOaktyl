const fs = require('fs');

const paths = process.argv.slice(2);

if (!paths.length) {
    console.error('No paths provided to normalize-lf.js');
    process.exit(1);
}

paths.forEach(target => {
    const resolved = target;
    if (!fs.existsSync(resolved)) {
        console.warn(`Skipping missing path: ${resolved}`);
        return;
    }

    const data = fs.readFileSync(resolved, 'utf8').replace(/\r\n/g, '\n');
    fs.writeFileSync(resolved, data, { encoding: 'utf8' });
});
