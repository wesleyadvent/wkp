const {test} = require('node:test');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const {BitArray, Code128Reader} = require('@zxing/library');

test('generated SVG labels decode to their exact product code with the camera decoder', () => {
    const fixtures = JSON.parse(execFileSync('php', [], {encoding: 'utf8', input: `<?php
        require 'vendor/autoload.php';
        $codes = ['WK-'.Illuminate\\Support\\Str::ulid(), 'CINCIN-001', 'LEGACY-001', '2026-09-12-1-1', '2026-09-12-12-1000'];
        echo json_encode(array_map(fn ($code) => ['code' => $code, 'svg' => App\\Support\\ProductCode::svg($code)], $codes));
    `}));
    for (const {code, svg} of fixtures) {
        const width = Number(svg.match(/<svg[^>]*width="(\d+)"/)[1]);
        const offset = Number(svg.match(/transform="translate\((\d+),/)[1]);
        const scanline = new BitArray(width);
        for (const rect of svg.matchAll(/<rect\s+x="([\d.]+)"\s+y="[\d.]+"\s+width="([\d.]+)"/g)) {
            const start = offset + Number(rect[1]);
            const end = start + Number(rect[2]);
            for (let x = start; x < end; x++) scanline.set(x);
        }
        assert.equal(new Code128Reader().decodeRow(0, scanline).getText(), code);
    }
});
