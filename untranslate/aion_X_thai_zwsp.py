#!/usr/bin/env python3
"""
thai_zwsp.py - Insert U+200B (zero-width space) at Thai word boundaries
so layout tools such as Speedata know where lines may break.

Usage:
    python thai_zwsp.py input.xml output.xml
    python thai_zwsp.py input.txt output.txt --text
    python thai_zwsp.py input.xml output.xml --dict mywords.txt
    cat input.xml | python thai_zwsp.py - - > output.xml

By default the input is treated as XML: only text between tags is changed,
so tag names and attribute values are left alone. Use --text for plain text.

Any U+200B already in the input is removed first, so the script can be
re-run safely on files that were previously marked by hand.
"""

import argparse
import re
import sys

from pythainlp.tokenize import word_tokenize
from pythainlp.corpus.common import thai_words
from pythainlp.util import dict_trie

ZWSP = "​"
THAI = re.compile(r"[฀-๿]")

# Never break BEFORE these (they must stay attached to the preceding text):
# Thai repetition mark, abbreviation marks, and closing punctuation.
NO_BREAK_BEFORE = set("ๆฯ.,;:!?)]}”’»…%")
# Never break AFTER these (opening punctuation).
NO_BREAK_AFTER = set("([{“‘«")


def load_dictionary(path):
    """Default Thai dictionary plus one-word-per-line custom entries."""
    words = set(thai_words())
    if path:
        with open(path, encoding="utf-8") as f:
            words.update(w.strip() for w in f if w.strip())
    return dict_trie(words)


def mark_text(text, trie):
    """Return text with U+200B inserted between Thai words."""
    if not THAI.search(text):
        return text
    tokens = word_tokenize(text, custom_dict=trie, engine="newmm",
                           keep_whitespace=True)
    out = [tokens[0]] if tokens else []
    for prev, cur in zip(tokens, tokens[1:]):
        if (
            prev and cur
            and not prev[-1].isspace() and not cur[0].isspace()
            and (THAI.search(prev[-1]) or THAI.search(cur[0]))
            and cur[0] not in NO_BREAK_BEFORE
            and prev[-1] not in NO_BREAK_AFTER
        ):
            out.append(ZWSP)
        out.append(cur)
    return "".join(out)


def mark_xml(xml, trie):
    """Only touch text content; leave <tags ...>, comments, PIs, CDATA intact."""
    parts = re.split(r"(<!--.*?-->|<!\[CDATA\[.*?\]\]>|<[^>]*>)", xml, flags=re.S)
    return "".join(p if p.startswith("<") else mark_text(p, trie) for p in parts)


def main():
    ap = argparse.ArgumentParser(description="Insert U+200B at Thai word boundaries.")
    ap.add_argument("input", help="input file, or - for stdin")
    ap.add_argument("output", help="output file, or - for stdout")
    ap.add_argument("--text", action="store_true", help="treat input as plain text, not XML")
    ap.add_argument("--dict", metavar="FILE", help="extra words, one per line, never to be split")
    args = ap.parse_args()

    src = sys.stdin.read() if args.input == "-" else open(args.input, encoding="utf-8").read()
    src = src.replace(ZWSP, "")  # remove any earlier hand-inserted breaks

    trie = load_dictionary(args.dict)
    result = mark_text(src, trie) if args.text else mark_xml(src, trie)

    if args.output == "-":
        sys.stdout.write(result)
    else:
        with open(args.output, "w", encoding="utf-8") as f:
            f.write(result)

    added = result.count(ZWSP)
    print(f"Inserted {added} break opportunities.", file=sys.stderr)


if __name__ == "__main__":
    main()
