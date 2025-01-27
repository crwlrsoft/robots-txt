<?php

namespace Crwlr\RobotsTxt;

use Crwlr\Url\Url;
use Exception;

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
     * @throws Exception
     */
    public function matches(string|Url $uri): bool
    {
        $pathQueryFragment = $uri instanceof Url ? $uri->relative() : Url::parse($uri)->relative();

        $pathQueryFragment = Encoding::decodePercentEncodedAsciiCharactersInPath($pathQueryFragment);

        return preg_match($this->preparedRegexPattern(), $pathQueryFragment) === 1;
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
