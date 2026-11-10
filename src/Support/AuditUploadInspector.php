<?php

namespace CXEngine\ExpertStatistics\Support;

use CXEngine\ExpertStatistics\Exceptions\AuditUploadRejected;
use XMLReader;
use ZipArchive;

/**
 * Checks a file of the AI Audit "files" tab before it is forwarded to the
 * Expert Statistics API. The page reads the 3CX backup in the browser
 * (resources/js/backup-reader.js), but the Livewire upload endpoint can be
 * called directly, so nothing the browser did is trusted here.
 *
 *  - configuration: one .xml, ≤ 50 MB, root <PhoneSystem>, no DTD / entity, no NUL
 *    byte, well-formed (parsed without network nor entity substitution);
 *  - prompts: a .zip ≤ 20 MB of .wav files only — safe paths (no absolute, "..",
 *    backslash, control characters, symlinks), no encryption, store/deflate only,
 *    entry count, sizes and compression ratio limits; every entry is decompressed
 *    in a stream (nothing written to disk): real size = declared size, CRC32, and
 *    RIFF/WAVE content.
 *
 * The limits mirror the API's (AuditSourceFiles), which checks again.
 * Refusals throw AuditUploadRejected with a translated message.
 */
class AuditUploadInspector
{
    public const MAX_XML_BYTES = 50 * 1048576;

    public const MAX_PROMPTS_ZIP_BYTES = 20 * 1048576;

    private const MAX_PROMPTS = 2000;

    private const MAX_PROMPTS_EXPANDED = 200 * 1048576;

    private const MAX_RATIO = 100;

    private const CHUNK = 1048576;

    public function inspectConfiguration(string $path, string $originalName): void
    {
        $this->assertFile($path, self::MAX_XML_BYTES);
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'xml') {
            throw AuditUploadRejected::because('not_xml');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw AuditUploadRejected::because('unreadable');
        }
        $head = '';
        $tail = '';
        try {
            while (! feof($handle)) {
                $chunk = (string) fread($handle, self::CHUNK);
                if (strlen($head) < 8192) {
                    $head .= substr($chunk, 0, 8192 - strlen($head));
                }
                $window = $tail.$chunk;
                if (str_contains($window, "\0") || preg_match('/<!(DOCTYPE|ENTITY)/i', $window)) {
                    throw AuditUploadRejected::because('not_3cx');
                }
                $tail = substr($window, -16);
            }
        } finally {
            fclose($handle);
        }

        if (! preg_match('/^(\xEF\xBB\xBF)?\s*(<\?xml[^>]*\?>\s*)?(<!--.*?-->\s*)*<PhoneSystem[\s>]/s', $head)) {
            throw AuditUploadRejected::because('not_3cx');
        }
        $this->assertWellFormed($path);
    }

    /**
     * @return int number of prompts
     */
    public function inspectPrompts(string $path, string $originalName): int
    {
        $this->assertFile($path, self::MAX_PROMPTS_ZIP_BYTES);
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip') {
            throw AuditUploadRejected::because('not_zip');
        }

        $zip = new ZipArchive;
        $opened = $zip->open($path, ZipArchive::CHECKCONS | ZipArchive::RDONLY);
        if ($opened !== true) {
            $startsLikeZip = file_get_contents($path, false, null, 0, 4) === "PK\x03\x04";
            throw AuditUploadRejected::because($opened === ZipArchive::ER_NOZIP && ! $startsLikeZip ? 'not_zip' : 'corrupted');
        }

        try {
            if ($zip->numFiles === 0) {
                throw AuditUploadRejected::because('no_prompt');
            }
            if ($zip->numFiles > self::MAX_PROMPTS) {
                throw AuditUploadRejected::because('too_many_files');
            }

            $prompts = 0;
            $expanded = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index, ZipArchive::FL_UNCHANGED);
                if ($stat === false) {
                    throw AuditUploadRejected::because('corrupted');
                }
                $name = (string) $stat['name'];
                $this->assertSafePath($zip, $index, $name);
                if (str_ends_with($name, '/')) {
                    continue;
                }
                if (! preg_match('/\.wav$/i', $name)) {
                    throw AuditUploadRejected::because('unexpected_file', ['name' => $this->shorten($name)]);
                }
                if (($stat['encryption_method'] ?? 0) !== ZipArchive::EM_NONE) {
                    throw AuditUploadRejected::because('encrypted');
                }
                if (! in_array($stat['comp_method'] ?? -1, [ZipArchive::CM_STORE, ZipArchive::CM_DEFLATE], true)) {
                    throw AuditUploadRejected::because('corrupted');
                }
                $expanded += (int) $stat['size'];
                if ($expanded > self::MAX_PROMPTS_EXPANDED
                    || ($stat['size'] > 1048576 && $stat['size'] > self::MAX_RATIO * max(1, (int) $stat['comp_size']))) {
                    throw AuditUploadRejected::because('too_large_content');
                }

                $this->verifyPrompt($zip, $index, $name, (int) $stat['size'], (int) $stat['crc']);
                $prompts++;
            }

            if ($prompts === 0) {
                throw AuditUploadRejected::because('no_prompt');
            }

            return $prompts;
        } finally {
            $zip->close();
        }
    }

    private function assertFile(string $path, int $max): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw AuditUploadRejected::because('unreadable');
        }
        $size = (int) filesize($path);
        if ($size === 0) {
            throw AuditUploadRejected::because('empty');
        }
        if ($size > $max) {
            throw AuditUploadRejected::because('too_big', ['max' => $max / 1048576]);
        }
    }

    private function assertSafePath(ZipArchive $zip, int $index, string $name): void
    {
        $unsafe = $name === ''
            || strlen($name) > 255
            || ! mb_check_encoding($name, 'UTF-8')
            || str_contains($name, '\\')
            || str_starts_with($name, '/')
            || preg_match('/^[A-Za-z]:/', $name)
            || preg_match('/[\x00-\x1F\x7F]/', $name)
            || preg_match('#(^|/)\.{1,2}(/|$)#', $name)
            || str_contains(rtrim($name, '/'), '//');

        // Unix symlinks (S_IFLNK in the external attributes).
        if (! $unsafe && $zip->getExternalAttributesIndex($index, $opsys, $attributes) && $opsys === ZipArchive::OPSYS_UNIX) {
            $unsafe = (($attributes >> 16) & 0170000) === 0120000;
        }

        if ($unsafe) {
            throw AuditUploadRejected::because('unsafe_path', ['name' => $this->shorten($name)]);
        }
    }

    private function verifyPrompt(ZipArchive $zip, int $index, string $name, int $declared, int $crc): void
    {
        $stream = $zip->getStreamIndex($index);
        if ($stream === false) {
            throw AuditUploadRejected::because('corrupted');
        }

        $hash = hash_init('crc32b');
        $read = 0;
        $head = '';
        try {
            while (! feof($stream)) {
                $chunk = fread($stream, self::CHUNK);
                if ($chunk === false) {
                    throw AuditUploadRejected::because('corrupted');
                }
                $read += strlen($chunk);
                if ($read > $declared) {
                    throw AuditUploadRejected::because('corrupted');
                }
                hash_update($hash, $chunk);
                if (strlen($head) < 12) {
                    $head .= substr($chunk, 0, 12 - strlen($head));
                }
            }
        } finally {
            fclose($stream);
        }

        if ($read !== $declared || hexdec(hash_final($hash)) !== ($crc & 0xFFFFFFFF)) {
            throw AuditUploadRejected::because('corrupted');
        }
        if (strlen($head) < 12 || ! str_starts_with($head, 'RIFF') || substr($head, 8, 4) !== 'WAVE') {
            throw AuditUploadRejected::because('not_wave', ['name' => $this->shorten($name)]);
        }
    }

    private function assertWellFormed(string $path): void
    {
        $previous = libxml_use_internal_errors(true);
        $reader = new XMLReader;
        try {
            if (! @$reader->open($path, null, LIBXML_NONET | LIBXML_COMPACT)) {
                throw AuditUploadRejected::because('not_3cx');
            }
            while (@$reader->read()) {
                // Reading every node is the check.
            }
            if (libxml_get_errors() !== []) {
                throw AuditUploadRejected::because('not_3cx');
            }
        } finally {
            $reader->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function shorten(string $name): string
    {
        $name = mb_check_encoding($name, 'UTF-8') ? $name : mb_convert_encoding($name, 'UTF-8', 'UTF-8');
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/', '?', $name);

        return mb_strlen($name) > 80 ? '…'.mb_substr($name, -79) : $name;
    }
}
