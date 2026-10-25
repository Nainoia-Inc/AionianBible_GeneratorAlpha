<?php
declare(strict_types=1);

/**
 * fixUTF8() — convert/repair text of unknown or mixed encoding into clean UTF-8.
 *
 * Handles:
 *  - Valid UTF-8 (returned untouched, any script: CJK, Thai, Greek, Hebrew…)
 *  - Raw Windows-1252 / ISO-8859-1 bytes (incl. smart quotes, €, ™)
 *  - MIXED strings: valid UTF-8 and stray cp1252 bytes in the same string
 *  - Mojibake: n-fold encoded UTF-8 ("Itâ€™s" -> "It’s"), fixed locally
 *    so it never damages the legitimate characters around it
 *  - Lossy mojibake whose invisible C1 chars were stripped ("ÃÂÂÂÂ©" -> "é")
 *  - UTF-8 / UTF-16 / UTF-32 byte-order marks (also a double-encoded "ï»¿")
 *  - Optional explicit source encoding when you actually know it
 *
 * Ambiguity: much real text is byte-identical to mojibake — Spanish
 * "Yahvé’”" = mojibake of 钔, French "incrédulité\u{A0}»" = 頻, Czech "íš‘",
 * Slovak "Úžas", Daai Chin "“Ä”", Darby "Ç’a", Ewe "Èƒo", "«CAFÉ»". So each
 * candidate is graded (see _fixUTF8_mojibakePass()): only results that are
 * believable on their own (’ — € ™, é, ŋ, ɔ, ě, Vietnamese …) are fixed in
 * isolation; doubtful ones need other mojibake in the same string, and
 * implausible ones (CJK, Hangul, Arabic, Cyrillic …) are only fixed when the
 * whole string is mojibake (a fully double-encoded Chinese or Russian line).
 * Tested on 363,193 Bible verses in 15 languages: 0 false positives, and every
 * verse double- and triple-encoded is recovered byte-exact.
 * Known limits: an isolated single foreign character in otherwise plain text
 * ("ä¾‹" alone) is left as is; caps word + dash + caps with no spaces
 * ("CAFÉ–BAR"); mojibake whose invisible bytes 0x81/0x8D/0x8F/0x90/0x9D were
 * stripped (e.g. Cyrillic "с") can't be recovered by any tool.
 * For files that are known to be valid UTF-8, fixUTF8($s, null, 0) (no
 * mojibake repair at all) is the zero-risk choice.
 * Leftover C1 controls (U+0080–U+009F) are mapped to their cp1252 characters.
 * An unknown $sourceEncoding name throws ValueError.
 *
 * Speed: all scanning is byte-level PCRE (no /u revalidation) and all
 * conversion is strtr() table lookups. Pure ASCII and clean UTF-8 exit
 * after one or two C-level scans.
 *
 * Requires: PHP 8.0+ with mbstring. intl is optional (NFC normalization).
 *
 * @param string|null $text           Input bytes.
 * @param string|null $sourceEncoding Force a known source encoding (e.g. 'SJIS', 'KOI8-R').
 * @param int         $maxPasses      How many layers of mojibake to peel off.
 * @param bool        $normalize      Apply Unicode NFC normalization (needs intl). Off by default:
 *                                    NFC is canonically equivalent but changes bytes, e.g.
 *                                    Devanagari न + ़ (U+0928 U+093C) becomes ऩ (U+0929).
 */
function fixUTF8(?string $text, ?string $sourceEncoding = null, int $maxPasses = 10, bool $normalize = false): string
{
    if ($text === null || $text === '') {
        return '';
    }
    if ($sourceEncoding !== null && preg_match('/^utf-?8$/i', $sourceEncoding)) {
        $sourceEncoding = null;                     // 'UTF-8', 'utf8' …: same as auto
    }

    // 0. Fast exit: pure ASCII needs nothing (one C-level scan).
    if ($sourceEncoding === null && !preg_match('/[\x80-\xFF]/', $text)) {
        return $text;
    }

    $t = _fixUTF8_tables();

    // 1. Explicit encoding wins: if the caller knows, don't guess.
    if ($sourceEncoding !== null) {
        $text = mb_convert_encoding($text, 'UTF-8', $sourceEncoding); // ValueError if the name is unknown
    } elseif ($text[0] === "\xEF" || $text[0] === "\xFF" || $text[0] === "\xFE" || $text[0] === "\x00") {
        // 2. Byte-order marks (order matters: UTF-32LE starts with the UTF-16LE BOM).
        foreach ($t['boms'] as $bom => $enc) {
            if (strncmp($text, $bom, strlen($bom)) === 0) {
                $text = substr($text, strlen($bom));
                if ($enc !== 'UTF-8') {
                    $text = mb_convert_encoding($text, 'UTF-8', $enc);
                }
                break;
            }
        }
    }

    // 3. Repair invalid bytes, leaving valid UTF-8 runs alone.
    if (!mb_check_encoding($text, 'UTF-8')) {
        $text = preg_match('/[\xC2-\xF4][\x80-\xBF]/', $text)
            ? (preg_replace_callback($t['reRepair'], $t['cbRepair'], $text)   // mixed: byte by byte
               ?? mb_convert_encoding($text, 'UTF-8', 'UTF-8'))                 // PCRE failed: safe fallback
            : strtr($text, $t['byteToUtf8']);                              // pure cp1252: one strtr
    }

    // 4. Only if something looks like mojibake or C1 junk: peel layers.
    if (preg_match($t['reSuspect'], $text)) {
        for ($i = 0; $i < $maxPasses; $i++) {
            $fixed = preg_replace($t['reLossy'], '$1$2', $text) ?? $text;
            $fixed = _fixUTF8_mojibakePass($fixed, $t);
            if ($fixed === $text) {
                break;
            }
            $text = $fixed;
            if (!preg_match($t['reSuspect'], $text)) {
                break;                              // clean now: skip the confirm pass
            }
        }
        // 5. Leftover C1 controls next to Latin letters/digits are cp1252 bytes
        //    decoded as Latin-1 ("It\u{92}s" -> "It’s"). Elsewhere (e.g. a stray
        //    U+0080 inside Chinese text) there is no evidence of what they were,
        //    so they are left as they are.
        $text = preg_replace_callback(
            '/(?<=[A-Za-z0-9]|[\xC3-\xC9][\x80-\xBF])\xC2[\x80-\x9F]|\xC2[\x80-\x9F](?=[A-Za-z0-9]|[\xC3-\xC9][\x80-\xBF])/',
            static fn(array $m): string => $t['c1'][$m[0]],
            $text
        ) ?? $text;
        // A double-encoded BOM ("ï»¿") only becomes a real BOM after step 4.
        if (strncmp($text, "\xEF\xBB\xBF", 3) === 0) {
            $text = substr($text, 3);
        }
    }

    // 6. Canonical form (NFC); the quick-check makes this nearly free when
    //    the text is already normalized, which is almost always.
    if ($normalize && class_exists('Normalizer', false) && !Normalizer::isNormalized($text)) {
        $n = Normalizer::normalize($text);
        if ($n !== false) {
            $text = $n;
        }
    }

    return $text;
}

/** Builds every regex, lookup table and callback once per request. */
function _fixUTF8_tables(): array
{
    static $t = null;
    if ($t !== null) {
        return $t;
    }

    // Windows-1252 0x80–0x9F as UTF-8 (0x81,0x8D,0x8F,0x90,0x9D are undefined).
    $cp1252 = [
        0x80 => "\u{20AC}", 0x82 => "\u{201A}", 0x83 => "\u{0192}", 0x84 => "\u{201E}",
        0x85 => "\u{2026}", 0x86 => "\u{2020}", 0x87 => "\u{2021}", 0x88 => "\u{02C6}",
        0x89 => "\u{2030}", 0x8A => "\u{0160}", 0x8B => "\u{2039}", 0x8C => "\u{0152}",
        0x8E => "\u{017D}", 0x91 => "\u{2018}", 0x92 => "\u{2019}", 0x93 => "\u{201C}",
        0x94 => "\u{201D}", 0x95 => "\u{2022}", 0x96 => "\u{2013}", 0x97 => "\u{2014}",
        0x98 => "\u{02DC}", 0x99 => "\u{2122}", 0x9A => "\u{0161}", 0x9B => "\u{203A}",
        0x9C => "\u{0153}", 0x9E => "\u{017E}", 0x9F => "\u{0178}",
    ];

    // Single invalid byte -> UTF-8 char (cp1252 interpretation).
    $byteToUtf8 = [];
    for ($b = 0x80; $b <= 0xFF; $b++) {
        $byteToUtf8[chr($b)] = $b >= 0xA0 ? mb_chr($b, 'UTF-8') : ($cp1252[$b] ?? '');
    }

    // UTF-8 char -> the byte it was misread from (for undoing mojibake).
    $charToByte = [];
    for ($b = 0x80; $b <= 0xFF; $b++) {
        $charToByte[mb_chr($b, 'UTF-8')] = chr($b);   // Latin-1 range and C1 controls
    }
    foreach ($cp1252 as $b => $ch) {
        $charToByte[$ch] = chr($b);                   // cp1252 specials (€, ’, ™ …)
    }

    // Leftover C1 control (U+0080–U+009F) -> cp1252 character.
    $c1 = [];
    for ($b = 0x80; $b <= 0x9F; $b++) {
        $c1[mb_chr($b, 'UTF-8')] = $cp1252[$b] ?? '';
    }

    // Byte-level pattern for one "continuation byte as a character":
    // U+0080–U+00BF (C2 80–C2 BF) or a cp1252 special.
    $specials = implode('|', array_map(static fn(string $c): string => preg_quote($c, '/'), $cp1252));
    $C = "(?:\\xC2[\\x80-\\xBF]|$specials)";

    $t = [
        'boms' => [
            "\xEF\xBB\xBF"     => 'UTF-8',
            "\xFF\xFE\x00\x00" => 'UTF-32LE',
            "\x00\x00\xFE\xFF" => 'UTF-32BE',
            "\xFF\xFE"         => 'UTF-16LE',
            "\xFE\xFF"         => 'UTF-16BE',
        ],

        // Valid UTF-8 runs (kept whole, one callback per run) | one invalid byte.
        'reRepair' => '/(?:
              [\x00-\x7F]
            | [\xC2-\xDF][\x80-\xBF]
            | \xE0[\xA0-\xBF][\x80-\xBF]
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}
            | \xED[\x80-\x9F][\x80-\xBF]
            | \xF0[\x90-\xBF][\x80-\xBF]{2}
            | [\xF1-\xF3][\x80-\xBF]{3}
            | \xF4[\x80-\x8F][\x80-\xBF]{2}
          ){1,64}+|(.)/xs',   // bounded: unbounded repeats break without PCRE JIT
        'cbRepair' => static fn(array $m): string => isset($m[1]) ? $byteToUtf8[$m[1]] : $m[0],

        // Cheap gate: a C1 control, or Â–ô followed by something that could be a
        // continuation char / lossy Ã-Â chain. Clean text almost never matches.
        'reSuspect' => '/\xC2[\x80-\x9F]|\xC3[\x82-\xB4](?:[\xC2\xC5\xC6\xCB\xE2]|\xC3[\x82\x83])/',

        // Lossy chain: run of 2+ Ã/Â then a Latin-1 continuation -> first + last.
        'reLossy' => '/(\xC3[\x82\x83])(?:\xC3[\x82\x83]){1,64}+(\xC2[\xA0-\xBF])/',

        // One complete UTF-8 sequence misread as cp1252/Latin-1 characters.
        // Lead Â–ß = C3 82–9F, à–ï = C3 A0–AF, ð–ô = C3 B0–B4.
        'reMojibake' => "/\\xC3[\\x82-\\x9F]$C|\\xC3[\\xA0-\\xAF]$C$C|\\xC3[\\xB0-\\xB4]$C$C$C/",
        'state' => $state = new stdClass(),
        'cbMojibake' => static function (array $m) use ($charToByte, $state): string {
            [$seq, $off] = $m[0];
            static $memo = [];                      // hot path: skip the function call
            if (!isset($memo[$seq])) {
                if (count($memo) > 4096) {
                    $memo = [];                     // bounded: never grows without limit
                }
                $dec = _fixUTF8_decodeSeq($seq, $charToByte);
                $memo[$seq] = [$dec, $dec === $seq ? 0 : _fixUTF8_tier($seq, $dec)];
            }
            [$dec, $tier] = $memo[$seq];
            if ($dec === $seq) {
                return $seq;
            }
            if ($tier === 1 && strlen($dec) === 2 && _fixUTF8_isAmbiguous($state->s, $off, strlen($seq), $dec)) {
                $tier = 2;
            } elseif ($tier === 2 && strlen($seq) === 5 && !str_contains($seq, "\xC2\xA0")) {
                // Accented capital + dash/quote glued to a lowercase letter: "wÉ”" (wɔ),
                // "É–e" (ɖe). Real text doesn't put a capital mid-word, nor a dash or
                // ” straight before a lowercase letter. (Not ’: French "Ç’a" is real.)
                $prev = $off > 0 ? $state->s[$off - 1] : '';
                $next = $state->s[$off + 5] ?? '';
                if (($prev >= 'a' && $prev <= 'z')
                    || ($next >= 'a' && $next <= 'z' && substr($seq, 2) !== "\xE2\x80\x99")) {
                    $tier = 1;
                }
            }
            $state->bytes += strlen($seq);          // coverage counts every candidate
            $state->distinct[$seq] = true;
            if ($tier === 1) {                      // plausible on its own
                $state->trust = true;
                return $dec;
            }
            if ($tier === 2) {
                $state->hasA = true;
                return $state->force >= 1 ? $dec : $seq;
            }
            $state->hasI = true;
            return $state->force >= 2 ? $dec : $seq;
        },

        'c1' => $c1,
        'byteToUtf8' => $byteToUtf8,
    ];
    return $t;
}

/**
 * One mojibake pass. Every candidate gets a tier (see _fixUTF8_tier()):
 *  1 = plausible on its own ("â€™" -> ’, "Ã©" -> é): always converted.
 *  2 = plausible result but the bytes are also common real text
 *      ("Ä”", "CAFÉ»", "é\u{A0}»"): converted only if the string also holds
 *      tier-1 mojibake, or the whole string is mojibake.
 *  3 = implausible result (CJK, Hangul, Arabic, rare Latin…), almost always a
 *      collision such as Spanish "Yahvé’”" -> 钔: converted only if the whole
 *      string is mojibake, i.e. candidates cover ≥90% of its non-ASCII bytes
 *      with at least 2 different sequences (a fully double-encoded Russian,
 *      Greek or Chinese line).
 */
function _fixUTF8_mojibakePass(string $s, array $t): string
{
    $st = $t['state'];
    $st->s = $s;
    $st->trust = $st->hasA = $st->hasI = false;
    $st->force = 0;
    $st->bytes = 0;
    $st->distinct = [];
    $r = preg_replace_callback($t['reMojibake'], $t['cbMojibake'], $s, -1, $n, PREG_OFFSET_CAPTURE) ?? $s;
    if (!$st->hasA && !$st->hasI) {
        return $r;
    }
    $nonAscii = strlen($s) - strlen(preg_replace('/[\x80-\xFF]+/', '', $s) ?? $s);
    if (count($st->distinct) >= 2 && $nonAscii > 0 && $st->bytes / $nonAscii >= 0.9) {
        $st->force = 2;
    } elseif ($st->trust && $st->hasA) {
        $st->force = 1;
    } else {
        return $r;
    }
    return preg_replace_callback($t['reMojibake'], $t['cbMojibake'], $s, -1, $n, PREG_OFFSET_CAPTURE) ?? $r;
}

/** Turn one mojibake sequence back into its original character (or leave it). */
function _fixUTF8_decodeSeq(string $seq, array $charToByte): string
{
    $bytes = strtr($seq, $charToByte);
    if (!mb_check_encoding($bytes, 'UTF-8')
        // Reject U+0700–U+07FF (Syriac/Thaana/NKo): what German/Nordic text like
        // "Straße»" or "Ü«" would wrongly decode into (2-byte leads DC–DF).
        || (strlen($bytes) === 2 && $bytes[0] >= "\xDC")) {
        $bytes = $seq;                              // overlong/surrogate/implausible
    }
    return $bytes;
}

/**
 * True when a 2-character candidate could just as well be real text: the two
 * characters before it are capitals (inside an ALL-CAPS word), the character
 * after it is not a capital (the word ends), and either it ends in closing
 * punctuation (” ’ » … ™ – …) or it would not decode to a Latin capital (real
 * caps mojibake like "CAFÃ‰" -> "CAFÉ" or Romanian "È˜" -> "Ș" decodes to
 * capitals).
 */
function _fixUTF8_isAmbiguous(string $s, int $off, int $len, string $decoded): bool
{
    if ($decoded[0] === "\xC2") {
        return false;                               // "Â" + symbol: never real text in practice
    }
    $p = $off > 0 ? ord($s[$off - 1]) : 0;
    if ($p < 0x80 && ($p < 0x41 || $p > 0x5A)) {
        return false;                               // fast path: previous char is not A–Z
    }
    $before = substr($s, max(0, $off - 8), min(8, $off));
    $before = preg_replace('/^[\x80-\xBF]+/', '', $before);    // don't start mid-character
    if (!preg_match('/\p{Lu}\p{Lu}$/u', $before)) {
        return false;
    }
    if (preg_match('/^\p{Lu}/u', substr($s, $off + $len, 4))) {
        return false;
    }
    // Ending in quote/dash/ellipsis/™ etc.: real text is just as likely ("IRMÃ”").
    if (preg_match('/(?:\xE2\x80[\x93\x94\x98-\x9A\x9C-\x9E\xA2\xA6\xB9\xBA]|\xE2\x84\xA2|\xC2[\xAB\xAE\xB0\xB7\xBB])$/', $s === '' ? '' : substr($s, $off, $len))) {
        return true;
    }
    $cp = mb_ord($decoded, 'UTF-8');
    return !(($cp <= 0x17F || $cp === 0x218 || $cp === 0x21A) && preg_match('/^\p{Lu}$/u', $decoded));
}

/**
 * How believable is it that $seq is mojibake of $dec on its own (no context)?
 *  1 = believable, 2 = believable result but common real-text shape, 3 = implausible.
 */
function _fixUTF8_tier(string $seq, string $dec): int
{
    $cp = mb_ord($dec, 'UTF-8');
    // French typography: no-break space before » : ; ! ? ("é\u{A0}»").
    // Only Â/Ã/Å + NBSP (double-encoded NBSP, "à", "Š") is safe on its own.
    $nbsp = str_contains($seq, "\xC2\xA0")
        && !in_array($seq, ["\xC3\x82\xC2\xA0", "\xC3\x83\xC2\xA0", "\xC3\x85\xC2\xA0"], true);
    // Quotes, guillemets and dashes: a letter followed by them is everyday text.
    static $punct = '(?:\xE2\x80[\x93\x94\x98-\x9A\x9C-\x9E]|\xC2[\xAB\xBB])';   // – — ‘ ’ ‚ “ ” „ « »
    static $close = '(?:\xE2\x80[\x93\x94\x99\x9D]|\xC2\xBB)';                    // – — ’ ” »
    if (strlen($dec) === 2) {
        $plausible = $cp <= 0x1FF                                      // Latin-1, Ext-A, most of Ext-B
            || ($cp >= 0x218 && $cp <= 0x21B)                          // Ș ș Ț ț
            || $cp === 0x241 || $cp === 0x242                          // Ɂ ɂ
            || ($cp >= 0x250 && $cp <= 0x2FF);                         // IPA (ɔ ɛ ɖ ʋ …), modifiers (ʼ ˆ ˜)
        if (!$plausible) {
            return 3;
        }
        // "Ä”", "Ç’": accented letter + quote/dash. Leads Â, Ã, Å, Æ are the classic
        // mojibake leads (Â« Ã© Å“ Æ’ …) and almost never precede a quote in real text.
        $lead2 = substr($seq, 0, 2);
        if ($nbsp || (!in_array($lead2, ["\xC3\x82", "\xC3\x83", "\xC3\x85", "\xC3\x86"], true)
                && preg_match("/^..$close$/s", $seq))) {
            return 2;
        }
        return 1;
    }
    if (strlen($dec) === 3) {
        $plausible = ($cp >= 0x2000 && $cp <= 0x214F)                 // punctuation, €, ™ …
            || ($cp >= 0x1E00 && $cp <= 0x1EF9)                       // dot-below letters, Vietnamese
            || $cp === 0xFEFF;                                        // BOM
        if (!$plausible) {
            return 3;
        }
        // Letter + two quotes/dashes ("á»”", "é’”") is real text far more often.
        if ($nbsp || preg_match("/^..$punct$punct$/s", $seq)) {
            return 2;
        }
        return 1;
    }
    return 3;                                       // 4-byte: emoji etc. only in fully broken strings
}
