<?php
// Regression suite for fixUTF8(). Usage: php regress_fixUTF8.php   (next to aion_common_encoding_claude.php)
require __DIR__ . '/aion_common_encoding_claude.php';
$dbl = static function (string $s): string {           // UTF-8 bytes misread as cp1252, re-encoded
    static $m = null;
    if ($m === null) { for ($b = 0; $b < 256; $b++) { $m[chr($b)] = $b < 0x80 ? chr($b) : (mb_convert_encoding(chr($b), 'UTF-8', 'Windows-1252') ?: mb_chr($b)); }
        foreach ([0x81, 0x8D, 0x8F, 0x90, 0x9D] as $u) { $m[chr($u)] = mb_chr($u); } }
    return strtr($s, $m);
};
$lat = static fn(string $s): string => mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1');
$N = "\u{A0}";
$cases = [
 // [name, input, expected]
 ['plain UTF-8', 'café naïve', 'café naïve'],
 ['Japanese', '日本語テキスト', '日本語テキスト'],
 ['Thai', 'ภาษาไทย', 'ภาษาไทย'],
 ['Greek', 'Ἐν ἀρχῇ ἦν ὁ λόγος', 'Ἐν ἀρχῇ ἦν ὁ λόγος'],
 ['raw cp1252 bytes', "\x93Hi\x94 caf\xE9 \x80", '“Hi” café €'],
 ['double-encoded quote', 'Itâ€™s', 'It’s'],
 ['double-encoded accents', "Ã©tÃ© Ã{$N} Paris", 'été à Paris'],
 ['triple-encoded', $dbl($dbl('It’s café')), 'It’s café'],
 ['mojibake next to Japanese', '日本 Itâ€™s', '日本 It’s'],
 ['mixed UTF-8 + cp1252 byte', "café caf\xE9", 'café café'],
 ['German guillemets', 'Straße» und «Über»', 'Straße» und «Über»'],
 ['UTF-8 BOM', "\xEF\xBB\xBFhello", 'hello'],
 ['UTF-16LE BOM', "\xFF\xFE" . mb_convert_encoding('héllo', 'UTF-16LE', 'UTF-8'), 'héllo'],
 ['double-encoded BOM', 'ï»¿hello', 'hello'],
 ['explicit SJIS', mb_convert_encoding('日本語', 'SJIS', 'UTF-8'), '日本語', 'SJIS'],
 ["'utf8' alias", "caf\xE9", 'café', 'utf8'],
 ['ForceUTF8 lossy x4', "F\u{C3}\u{C2}\u{C2}\u{C2}\u{C2}\u{A9}dration", 'Fédration'],
 ['ForceUTF8 lossy x1', 'FÃÂ©dÃ©ration', 'Fédération'],
 ['5-layer latin1 chain', $lat($lat($lat($lat($lat('Fédération'))))), 'Fédération'],
 ['5-layer cp1252 chain', $dbl($dbl($dbl($dbl($dbl('Fédération’s'))))), 'Fédération’s'],
 ['caps mojibake', 'CAFÃ‰', 'CAFÉ'],
 ['Romanian caps', 'BUCUREÈ˜TI', 'BUCUREȘTI'],
 ['Polish', 'PÅ‚ock ZÅ‚oty', 'Płock Złoty'],
 ['Czech ě', 'nevÄ›dÄ›li', 'nevěděli'],
 ['Ewe ɔ ɖ ƒ', $dbl('Mawu wɔ dziƒo, ɖe ŋu'), 'Mawu wɔ dziƒo, ɖe ŋu'],
 ['Russian line', $dbl('и в Москва'), 'и в Москва'],
 ['Chinese line', $dbl('起初神创造天地'), '起初神创造天地'],
 ['Vietnamese', $dbl('Việt Nam'), 'Việt Nam'],
 ['© double-encoded', 'Â© 2026', '© 2026'],
 // real text that looks like mojibake — must be left alone
 ['«CAFÉ» JOSÉ’s', '«CAFÉ» et JOSÉ’s PERÚ™', '«CAFÉ» et JOSÉ’s PERÚ™'],
 ['French NBSP »', "incrédulité{$N}»", "incrédulité{$N}»"],
 ['French NBSP «', "voilà{$N}«{$N}oui{$N}»", "voilà{$N}«{$N}oui{$N}»"],
 ['Spanish é’”', '‘Yo soy Yahvé’”.', '‘Yo soy Yahvé’”.'],
 ['Spanish á»»', '«Este nació allá»».', '«Este nació allá»».'],
 ['Portuguese ê––', 'por que você–– vir', 'por que você–– vir'],
 ['Czech íš‘', '‚nezcizoložíš‘;', '‚nezcizoložíš‘;'],
 ['Slovak Úžas', 'Úžas sa zmocnil', 'Úžas sa zmocnil'],
 ['Daai Chin “Ä”', 'Jesuh naw “Ä” ti lü', 'Jesuh naw “Ä” ti lü'],
 ['Darby Ç’a', 'Ç’a été devant', 'Ç’a été devant'],
 ['Ewe Èƒo', 'Èƒo wo gake', 'Èƒo wo gake'],
 ['Rohingya hãšše', 'fori hãšše edde', 'fori hãšše edde'],
 ['Shi è”»', 'embere afè”».', 'embere afè”».'],
 ['NUL bytes kept', "a\0b", "a\0b"],
 ['"0"', '0', '0'],
];
$pass = 0;
foreach ($cases as $c) {
    $got = fixUTF8($c[1], $c[3] ?? null);
    $ok = $got === $c[2];
    $pass += $ok;
    if (!$ok) { printf("FAIL  %-26s got: %s\n", $c[0], $got); }
}
printf("%d/%d passed\n", $pass, count($cases));
if (class_exists('Normalizer')) {
    echo fixUTF8("e\u{301}", null, 10, true) === "é" ? "NFC option: PASS\n" : "NFC option: FAIL\n";
}
// C1 control characters (added after the Chinese Sigao report)
foreach ([
    ['C1 in Chinese kept', "直到+?\u{80}+直到", "直到+?\u{80}+直到"],
    ['C1 ’ between letters', "It\u{92}s", 'It’s'],
    ['C1 quotes around word', "\u{93}Hi\u{94}", '“Hi”'],
    ['C1 € after digits', "50\u{80}", '50€'],
] as $c) { $g = fixUTF8($c[1]); printf("%s  %s%s\n", $g === $c[2] ? 'PASS' : 'FAIL', $c[0], $g === $c[2] ? '' : "  got: $g"); }
