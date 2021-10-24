<?php

namespace Crwlr\RobotsTxt;

use Crwlr\Url\Url;
use InvalidArgumentException;

final class UserAgentGroup
{
    /**
     * @var array|string[]
     */
    private array $userAgents;

    /**
     * @var array|RulePattern[]
     */
    private array $disallowedPatterns = [];

    /**
     * @var array|RulePattern[]
     */
    private array $allowedPatterns = [];

    /**
     * @param array|string[] $userAgents
     */
    public function __construct(array $userAgents)
    {
        foreach ($userAgents as $userAgent) {
            if (!is_string($userAgent)) {
                throw new InvalidArgumentException('Argument $userAgents must exclusively contain user agent strings.');
            }
        }

        $this->userAgents = $userAgents;
    }

    public function contains(string $userAgent): bool
    {
        foreach ($this->userAgents as $groupUserAgent) {
            if ($groupUserAgent === '*' || strtolower($groupUserAgent) === strtolower($userAgent)) {
                return true;
            }
        }

        return false;
    }

    public function isAllowed(string $uri): bool
    {
        $uri = Url::parse($uri);
        $matchingDisallowedPatterns = $this->getMatchingDisallowedPatterns($uri);

        if (count($matchingDisallowedPatterns) === 0) {
            return true;
        }

        $matchingAllowedPatterns = $this->getMatchingAllowedPatterns($uri);

        if (count($matchingAllowedPatterns) === 0) {
            return false;
        }

        return $this->isAllowedByMostSpecificMatch($matchingDisallowedPatterns, $matchingAllowedPatterns);
    }

    public function userAgents(): array
    {
        return $this->userAgents;
    }

    public function disallowedPatterns(): array
    {
        return $this->disallowedPatterns;
    }

    public function allowedPatterns(): array
    {
        return $this->allowedPatterns;
    }

    public function addDisallowedPattern(RulePattern $pattern): void
    {
        $this->disallowedPatterns[] = $pattern;
    }

    public function addAllowedPattern(RulePattern $pattern): void
    {
        $this->allowedPatterns[] = $pattern;
    }

    /**
     * @return array|RulePattern[]
     */
    private function getMatchingDisallowedPatterns(Url $url): array
    {
        return $this->getMatchingPatterns($url, $this->disallowedPatterns());
    }

    /**
     * @return array|RulePattern[]
     */
    private function getMatchingAllowedPatterns(Url $url): array
    {
        return $this->getMatchingPatterns($url, $this->allowedPatterns());
    }

    /**
     * @param array|RulePattern[] $patterns
     * @return array|RulePattern[]
     */
    private function getMatchingPatterns(Url $url, array $patterns): array
    {
        $matches = [];

        foreach ($patterns as $pattern) {
            if ($pattern->matches($url)) {
                $matches[] = $pattern;
            }
        }

        return $matches;
    }

    /**
     * https://datatracker.ietf.org/doc/draft-koster-rep/
     * 2.2.2.
     * "The most specific match found MUST be used.  The most specific match is the match that has the most octets.
     * If an allow and disallow rule is equivalent, the allow SHOULD be used."
     */
    private function isAllowedByMostSpecificMatch(array $disallowedPatterns, array $allowedPatterns): bool
    {
        $mostSpecificMatch = reset($allowedPatterns);
        $maxStrLen = strlen($mostSpecificMatch->pattern());

        foreach ($allowedPatterns as $pattern) {
            if (strlen($pattern->pattern()) > $maxStrLen) {
                $maxStrLen = strlen($pattern->pattern());
            }
        }

        foreach ($disallowedPatterns as $pattern) {
            if (strlen($pattern->pattern()) > $maxStrLen) {
                return false;
            }
        }

        return true;
    }
}
