<?php

namespace Crwlr\RobotsTxt;

use Crwlr\RobotsTxt\Exceptions\EncodingException;
use Crwlr\Url\Url;
use InvalidArgumentException;

final class RulePattern
{
    private string $rawPattern;
    private ?string $preparedRegexPattern = null;

    public function __construct(string $pattern)
    {
        $this->rawPattern = $pattern;
    }

    public function pattern(): string
    {
        return $this->rawPattern;
    }

    /**
     * @param string|Url|mixed $uri
     */
    public function matches($uri): bool
    {
        if (!$uri instanceof Url && !is_string($uri)) {
            throw new InvalidArgumentException('Argument $uri must be a string or instance of Crwlr\Url.');
        }

        $path = $uri instanceof Url ? $uri->path() : Url::parse($uri)->path();

        if (!is_string($path)) {
            return false;
        }

        $path = Encoding::decodePercentEncodedAsciiCharactersInPath($path);

        return preg_match($this->preparedRegexPattern(), $path) === 1;
    }

    private function preparedRegexPattern(): string
    {
        if ($this->preparedRegexPattern === null) {
            $pattern = Encoding::decodePercentEncodedAsciiCharactersInPath($this->pattern());
            $pattern = preg_quote($pattern, '/');
            $this->preparedRegexPattern = '/' . str_replace(['\*', '\$'], ['.*', '$'], $pattern) . '/';
        }

        return $this->preparedRegexPattern;
    }
}
