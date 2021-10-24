<?php

namespace Crwlr\RobotsTxt;

final class Encoding
{
    /**
     * https://datatracker.ietf.org/doc/draft-koster-rep/
     * "If a percent-encoded US-ASCII octet is encountered in the URI, it MUST be unencoded prior to comparison,
     * unless it is a reserved character in the URI as defined by RFC3986 [2] or the character is outside the
     * unreserved character range."
     *
     * If a character that doesn't have to be percent encoded, was encoded either in a robots.txt pattern or in a
     * url/path that is to be checked, comparison could falsely fail. So decode such characters before comparison.
     */
    public static function decodePercentEncodedAsciiCharactersInPath(string $path): string
    {
        return preg_replace_callback('/%[0-9A-Fa-f][0-9A-Fa-f]/', function ($match) {
            return rawurlencode(rawurldecode($match[0]));
        }, $path);
    }
}
