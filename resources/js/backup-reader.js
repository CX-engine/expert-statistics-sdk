/*
 * Expert Statistics — reads a 3CX backup on the user's computer (AI Audit files tab).
 *
 * The user drops the backup .zip (any size: tens of GB is common), its extracted
 * folder, or the configuration .xml alone. Nothing is uploaded at that point: a
 * File is a handle on the user's disk. Only the zip's index and two kinds of
 * entries are read — the configuration <n>Db.xml and the audio prompts
 * httpprompts/**.wav. Call tables, chats, recordings and voicemails are never read
 * (the call statistics of bluerocktel-cx come from ClickHouse).
 *
 * Before anything is sent, the credentials of the configuration are blanked (the
 * audit never reads them) and the prompts are packed in "stored" zips small enough
 * for the upload limit. The server checks everything again (AuditUploadInspector).
 *
 * Dependency-free (the package has no JS build): minimal zip reader (Zip64, stored
 * and deflate via the native DecompressionStream, CRC32 checked) and writer
 * (stored entries). Loaded once per page by the config-audit-files view (@assets).
 * Design: cockpit docs/browser-backup-extraction.md (same idea, cockpit side).
 */
(function (global) {
    'use strict';

    /** Configuration elements whose text is a credential (blanked before sending). */
    var SECRET_ELEMENTS = ['AuthID', 'AuthPassword', 'VMPIN', 'mailServerPassword', 'licensekey'];
    var CONFIG_ROOT = /^﻿?\s*(<\?xml[^>]*\?>\s*)?(<!--[\s\S]*?-->\s*)*<PhoneSystem[\s>]/;
    /** Folders of a backup never worth walking when a folder is dropped. */
    var SKIPPED_FOLDERS = ['chatfiles', 'voicemails', 'recordings', 'dbtables', 'provisioning', 'profilepictures', 'crmintegration', 'fax'];

    function ReaderError(code, cause) {
        var error = new Error(code);
        error.name = 'BackupReaderError';
        error.code = code;
        if (cause) error.cause = cause;
        return error;
    }

    // ------------------------------------------------------------------ CRC32

    var CRC_TABLE = (function () {
        var table = new Uint32Array(256);
        for (var n = 0; n < 256; n++) {
            var c = n;
            for (var k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
            table[n] = c >>> 0;
        }
        return table;
    })();

    function crc32(bytes, crc) {
        crc = crc === undefined ? 0xffffffff : crc;
        for (var i = 0; i < bytes.length; i++) crc = CRC_TABLE[(crc ^ bytes[i]) & 0xff] ^ (crc >>> 8);
        return crc;
    }

    // ------------------------------------------------------------------ zip reading

    async function bytesOf(source, start, end) {
        return new Uint8Array(await source.slice(start, end).arrayBuffer());
    }

    function u16(view, offset) { return view.getUint16(offset, true); }
    function u32(view, offset) { return view.getUint32(offset, true); }
    function u64(view, offset) {
        var value = view.getBigUint64(offset, true);
        if (value > BigInt(Number.MAX_SAFE_INTEGER)) throw ReaderError('corrupted');
        return Number(value);
    }

    /** Central directory of a zip (only the end of the file is read). */
    async function zipEntries(source) {
        var size = source.size;
        if (size < 22) throw ReaderError(size === 0 ? 'empty' : 'not_zip');

        var head = await bytesOf(source, 0, 4);
        var startsLikeZip = head[0] === 0x50 && head[1] === 0x4b && head[2] === 0x03 && head[3] === 0x04;

        var tailStart = Math.max(0, size - 65557);
        var tail = await bytesOf(source, tailStart, size);
        var tailView = new DataView(tail.buffer);
        var eocd = -1;
        for (var i = tail.length - 22; i >= 0; i--) {
            if (u32(tailView, i) === 0x06054b50) { eocd = i; break; }
        }
        if (eocd < 0) throw ReaderError(startsLikeZip ? 'corrupted' : 'not_zip');

        var count = u16(tailView, eocd + 10);
        var cdSize = u32(tailView, eocd + 12);
        var cdOffset = u32(tailView, eocd + 16);

        if (count === 0xffff || cdSize === 0xffffffff || cdOffset === 0xffffffff) {
            // Zip64: locator right before the end record.
            var locator = eocd - 20;
            if (locator < 0 || u32(tailView, locator) !== 0x07064b50) throw ReaderError('corrupted');
            var zip64At = u64(tailView, locator + 8);
            var zip64 = await bytesOf(source, zip64At, zip64At + 56);
            var zip64View = new DataView(zip64.buffer);
            if (zip64.length < 56 || u32(zip64View, 0) !== 0x06064b50) throw ReaderError('corrupted');
            count = u64(zip64View, 32);
            cdSize = u64(zip64View, 40);
            cdOffset = u64(zip64View, 48);
        }
        if (cdOffset + cdSize > size) throw ReaderError('corrupted');

        var cd = await bytesOf(source, cdOffset, cdOffset + cdSize);
        var view = new DataView(cd.buffer);
        var utf8 = new TextDecoder('utf-8');
        var entries = [];
        var at = 0;
        for (var n = 0; n < count; n++) {
            if (at + 46 > cd.length || u32(view, at) !== 0x02014b50) throw ReaderError('corrupted');
            var flags = u16(view, at + 8);
            var entry = {
                method: u16(view, at + 10),
                crc: u32(view, at + 16),
                compressedSize: u32(view, at + 20),
                size: u32(view, at + 24),
                offset: u32(view, at + 42),
                encrypted: (flags & 1) === 1,
            };
            var nameLength = u16(view, at + 28);
            var extraLength = u16(view, at + 30);
            var commentLength = u16(view, at + 32);
            entry.path = utf8.decode(cd.subarray(at + 46, at + 46 + nameLength)).replace(/\\/g, '/');

            // Zip64 extended information: only the fields saturated in the record, in this order.
            var extra = at + 46 + nameLength;
            var extraEnd = extra + extraLength;
            while (extra + 4 <= extraEnd) {
                var id = u16(view, extra);
                var length = u16(view, extra + 2);
                if (id === 0x0001) {
                    var field = extra + 4;
                    if (entry.size === 0xffffffff) { entry.size = u64(view, field); field += 8; }
                    if (entry.compressedSize === 0xffffffff) { entry.compressedSize = u64(view, field); field += 8; }
                    if (entry.offset === 0xffffffff) { entry.offset = u64(view, field); }
                }
                extra += 4 + length;
            }

            at += 46 + nameLength + extraLength + commentLength;
            if (!entry.path.endsWith('/')) entries.push(entry);
        }

        return entries.map(function (entry) {
            return {
                path: entry.path,
                size: entry.size,
                encrypted: entry.encrypted,
                read: function () { return readZipEntry(source, entry); },
            };
        });
    }

    /** Bytes of one entry: decompressed, size and CRC32 checked. */
    async function readZipEntry(source, entry) {
        if (entry.encrypted) throw ReaderError('encrypted');
        var local = await bytesOf(source, entry.offset, entry.offset + 30);
        var localView = new DataView(local.buffer);
        if (local.length < 30 || u32(localView, 0) !== 0x04034b50) throw ReaderError('corrupted');
        var start = entry.offset + 30 + u16(localView, 26) + u16(localView, 28);
        var raw = source.slice(start, start + entry.compressedSize);

        var bytes;
        if (entry.method === 0) {
            bytes = new Uint8Array(await raw.arrayBuffer());
        } else if (entry.method === 8) {
            try {
                var stream = raw.stream().pipeThrough(new DecompressionStream('deflate-raw'));
                bytes = new Uint8Array(await new Response(stream).arrayBuffer());
            } catch (error) {
                throw ReaderError('corrupted', error);
            }
        } else {
            throw ReaderError('corrupted');
        }

        if (bytes.length !== entry.size || (crc32(bytes) ^ 0xffffffff) >>> 0 !== entry.crc) {
            throw ReaderError('corrupted');
        }
        return bytes;
    }

    // ------------------------------------------------------------------ folders

    /** Files of an <input webkitdirectory>: [{path, size, read}] relative to the chosen folder. */
    function listEntries(files) {
        return files.map(function (file) {
            var path = (file.webkitRelativePath || file.relativePath || file.name).replace(/\\/g, '/');
            return {
                path: path.indexOf('/') >= 0 && file.webkitRelativePath ? path.slice(path.indexOf('/') + 1) : path,
                size: file.size,
                encrypted: false,
                read: async function () { return new Uint8Array(await file.arrayBuffer()); },
            };
        });
    }

    /** A folder dropped on the page (FileSystemDirectoryEntry): only what can matter is walked. */
    async function folderEntries(directory) {
        var out = [];
        async function children(dir) {
            var reader = dir.createReader();
            var all = [];
            for (;;) {
                var batch = await new Promise(function (resolve, reject) { reader.readEntries(resolve, reject); });
                if (!batch.length) return all;
                all = all.concat(batch);
            }
        }
        async function walk(dir, prefix, depth, inPrompts) {
            var items = await children(dir);
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                var path = prefix + item.name;
                if (item.isDirectory) {
                    var lower = item.name.toLowerCase();
                    if (inPrompts || lower === 'httpprompts') {
                        await walk(item, path + '/', depth + 1, true);
                    } else if (depth < 2 && SKIPPED_FOLDERS.indexOf(lower) < 0) {
                        await walk(item, path + '/', depth + 1, false);
                    }
                } else if (inPrompts ? /\.wav$/i.test(item.name) : /Db\.xml$/i.test(item.name)) {
                    var file = await new Promise(function (resolve, reject) { item.file(resolve, reject); });
                    out.push({ path: path, size: file.size, encrypted: false, read: async function (f) { return new Uint8Array(await f.arrayBuffer()); }.bind(null, file) });
                }
            }
        }
        await walk(directory, '', 0, false);
        return out;
    }

    // ------------------------------------------------------------------ picking

    function depth(path) { return path.split('/').length; }

    function pick(entries) {
        var usable = entries.filter(function (e) { return e.path.indexOf('__MACOSX/') !== 0; });
        var xml = usable
            .filter(function (e) { return /(^|\/)[^/]*Db\.xml$/.test(e.path); })
            .sort(function (a, b) { return depth(a.path) - depth(b.path); })[0];

        var dirs = usable
            .map(function (e) { var m = e.path.match(/^(.*?(?:^|\/)httpprompts\/)/i); return m ? m[1] : null; })
            .filter(Boolean)
            .sort(function (a, b) { return depth(a) - depth(b); });
        var prompts = [];
        if (dirs.length) {
            var prefix = dirs[0];
            prompts = usable
                .filter(function (e) { return e.path.indexOf(prefix) === 0 && /\.wav$/i.test(e.path); })
                .map(function (e) { return Object.assign({}, e, { target: e.path.slice(prefix.length) }); });
        }
        return { xml: xml, prompts: prompts };
    }

    // ------------------------------------------------------------------ configuration

    function redactSecrets(xml) {
        var redacted = 0;
        var elements = new RegExp('<(' + SECRET_ELEMENTS.join('|') + ')>([^<]+)</\\1>', 'g');
        xml = xml.replace(elements, function (_, name) { redacted++; return '<' + name + '></' + name + '>'; });
        // Phone provisioning secrets, inside the escaped <Settings> of the devices.
        xml = xml.replace(/(\sSecret=)("[^"]+"|&quot;.+?&quot;)/g, function (_, attribute) { redacted++; return attribute + '""'; });
        return { xml: xml, redacted: redacted };
    }

    function configuration(bytes, name) {
        var text = new TextDecoder('utf-8', { ignoreBOM: true }).decode(bytes);
        if (!CONFIG_ROOT.test(text.slice(0, 8192)) || /<!(DOCTYPE|ENTITY)/i.test(text) || text.indexOf('\0') >= 0) {
            throw ReaderError('not_3cx');
        }
        var result = redactSecrets(text);
        return {
            file: new File([result.xml], name.split('/').pop(), { type: 'application/xml' }),
            redacted: result.redacted,
        };
    }

    // ------------------------------------------------------------------ zip writing (stored)

    function storedZip(files) {
        var encoder = new TextEncoder();
        var parts = [];
        var central = [];
        var offset = 0;
        files.forEach(function (f) {
            var name = encoder.encode(f.name);
            var crc = (crc32(f.bytes) ^ 0xffffffff) >>> 0;
            var local = new DataView(new ArrayBuffer(30));
            local.setUint32(0, 0x04034b50, true);
            local.setUint16(4, 20, true);
            local.setUint16(6, 0x0800, true); // UTF-8 names
            local.setUint32(14, crc, true);
            local.setUint32(18, f.bytes.length, true);
            local.setUint32(22, f.bytes.length, true);
            local.setUint16(26, name.length, true);
            parts.push(local, name, f.bytes);

            var entry = new DataView(new ArrayBuffer(46));
            entry.setUint32(0, 0x02014b50, true);
            entry.setUint16(4, 20, true);
            entry.setUint16(6, 20, true);
            entry.setUint16(8, 0x0800, true);
            entry.setUint32(16, crc, true);
            entry.setUint32(20, f.bytes.length, true);
            entry.setUint32(24, f.bytes.length, true);
            entry.setUint16(28, name.length, true);
            entry.setUint32(42, offset, true);
            central.push(entry, name);
            offset += 30 + name.length + f.bytes.length;
        });
        var centralSize = central.reduce(function (sum, p) { return sum + p.byteLength; }, 0);
        var end = new DataView(new ArrayBuffer(22));
        end.setUint32(0, 0x06054b50, true);
        end.setUint16(8, files.length, true);
        end.setUint16(10, files.length, true);
        end.setUint32(12, centralSize, true);
        end.setUint32(16, offset, true);
        return new Blob(parts.concat(central, [end]), { type: 'application/zip' });
    }

    function isWave(bytes) {
        return bytes.length >= 12
            && String.fromCharCode(bytes[0], bytes[1], bytes[2], bytes[3]) === 'RIFF'
            && String.fromCharCode(bytes[8], bytes[9], bytes[10], bytes[11]) === 'WAVE';
    }

    // ------------------------------------------------------------------ main

    /**
     * @param input File (.zip backup or .xml), FileList/File[] (extracted folder), or a dropped
     *              FileSystemDirectoryEntry
     * @param options {batchBytes, maxXmlBytes, onProgress({step, percent})}
     * @returns {xml: File|null, batches: File[], summary}
     */
    async function read(input, options) {
        options = options || {};
        var batchBytes = options.batchBytes || 11 * 1048576;
        var maxXmlBytes = options.maxXmlBytes || 50 * 1048576;
        var progress = function (step, percent) { (options.onProgress || function () {})({ step: step, percent: Math.round(percent) }); };

        try {
            if (typeof DecompressionStream === 'undefined' || typeof TextDecoder === 'undefined') throw ReaderError('unsupported');
            if (!input || (input.length !== undefined && !(input instanceof Blob) && input.length === 0)) throw ReaderError('empty');
            progress('index', 0);

            var entries;
            var sourceBytes;
            if (input && input.isDirectory) {
                entries = await folderEntries(input);
                sourceBytes = null;
            } else if (input instanceof Blob || (input && typeof input.slice === 'function' && typeof input.size === 'number' && input.length === undefined)) {
                if (input.size === 0) throw ReaderError('empty');
                sourceBytes = input.size;
                if (/\.xml$/i.test(input.name || '')) {
                    if (input.size > maxXmlBytes) throw ReaderError('xml_too_big');
                    var alone = configuration(new Uint8Array(await input.arrayBuffer()), input.name);
                    progress('done', 100);
                    return { xml: alone.file, batches: [], summary: { sourceBytes: sourceBytes, readBytes: input.size, prompts: 0, skipped: 0, redacted: alone.redacted, batches: 0 } };
                }
                entries = await zipEntries(input);
            } else {
                entries = listEntries(Array.prototype.slice.call(input));
                sourceBytes = entries.reduce(function (sum, e) { return sum + e.size; }, 0);
            }

            var picked = pick(entries);
            if (!picked.xml) throw ReaderError('no_xml');
            if (picked.xml.encrypted || picked.prompts.some(function (p) { return p.encrypted; })) throw ReaderError('encrypted');
            if (picked.xml.size > maxXmlBytes) throw ReaderError('xml_too_big');

            progress('xml', 5);
            var config = configuration(await picked.xml.read(), picked.xml.path);
            var readBytes = picked.xml.size;

            // Prompts, packed in batches under the upload limit.
            var batches = [];
            var current = [];
            var currentBytes = 0;
            var kept = 0;
            var skipped = 0;
            for (var i = 0; i < picked.prompts.length; i++) {
                var prompt = picked.prompts[i];
                progress('prompts', 10 + (85 * (i + 1)) / picked.prompts.length);
                var bytes = await prompt.read();
                readBytes += bytes.length;
                // Only real WAVE files that fit in one upload; anything else is left out.
                if (!isWave(bytes) || bytes.length + 200 > batchBytes) { skipped++; continue; }
                var cost = bytes.length + 2 * (76 + prompt.target.length);
                if (current.length && currentBytes + cost + 22 > batchBytes) {
                    batches.push(current);
                    current = [];
                    currentBytes = 0;
                }
                current.push({ name: prompt.target, bytes: bytes });
                currentBytes += cost;
                kept++;
            }
            if (current.length) batches.push(current);

            var files = batches.map(function (batch, index) {
                return new File([storedZip(batch)], 'prompts-' + (index + 1) + '.zip', { type: 'application/zip' });
            });
            progress('done', 100);

            return {
                xml: config.file,
                batches: files,
                summary: { sourceBytes: sourceBytes, readBytes: readBytes, prompts: kept, skipped: skipped, redacted: config.redacted, batches: files.length },
            };
        } catch (error) {
            throw asReaderError(error);
        }
    }

    function asReaderError(error) {
        if (error && error.name === 'BackupReaderError') return error;
        var name = (error && error.name) || '';
        var message = String((error && error.message) || '');
        if (['NotReadableError', 'NotFoundError', 'SecurityError', 'NotAllowedError'].indexOf(name) >= 0) return ReaderError('unreadable', error);
        if (name === 'RangeError' || /allocation|out of memory/i.test(message)) return ReaderError('memory', error);
        return ReaderError('generic', error);
    }

    global.expertStatsBackupReader = { read: read, redactSecrets: redactSecrets, storedZip: storedZip };
})(typeof window !== 'undefined' ? window : globalThis);
