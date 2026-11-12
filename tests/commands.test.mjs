import test from 'node:test';
import assert from 'node:assert/strict';
import { parseCommand, mediaUrl } from '../src/commands.mjs';

test('image commands support mentions and Arabic', () => assert.deepEqual(parseCommand('/image@MyBot فندق عربي'), {kind:'generate',media:'image',prompt:'فندق عربي'}));
test('video command accepts detailed prompts', () => assert.deepEqual(parseCommand('/video drone shot'), {kind:'generate',media:'video',prompt:'drone shot'}));
test('no prompt is rejected', () => assert.equal(parseCommand('/video').kind, 'missing'));
test('other messages ignored', () => assert.equal(parseCommand('Hi').kind,'ignore'));
test('unsafe unbounded input rejected', () => assert.equal(parseCommand('/image ' + 'x'.repeat(1601)).kind,'too_long'));
test('URL encoding and duration', () => {const url = new URL(mediaUrl('https://gen.pollinations.ai', 'video', 'مكة & sea', 'google/veo-3.1-fast', 4));assert.equal(url.searchParams.get('model'),'google/veo-3.1-fast');assert.equal(url.searchParams.get('duration'),'4');assert.ok(url.pathname.includes('%'));});
