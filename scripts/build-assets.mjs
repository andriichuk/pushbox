import { compile, optimize } from '@tailwindcss/node';
import { Scanner } from '@tailwindcss/oxide';
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';

const input = resolve('resources/css/pushbox.css');
const compiler = await compile(await readFile(input, 'utf8'), { base: dirname(input), onDependency() {} });
const scanner = new Scanner({ sources: compiler.sources });
const css = compiler.build(scanner.scan());
await mkdir('resources/dist', { recursive: true });
await writeFile('resources/dist/pushbox.css', optimize(css, { minify: true }).code);
