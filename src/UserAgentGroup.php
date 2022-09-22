<?php

namespace Crwlr\RobotsTxt;

use Crwlr\Url\Url;
use Exception;
use InvalidArgumentException;

final class UserAgentGroup
{
    /**
     * @var RulePattern[]
     */
    private array $disallowedPatterns = [];

    /**
     * @var RulePattern[]
     */
    private array $allowedPatterns = [];

    /**
     * @param string[] $userAgents
     */
    public function __construct(private array $userAgents)
    {
        foreach ($userAgents as $userAgent) {
            if (!is_string($userAgent)) {
                throw new InvalidArgumentException('Argument $userAgents must exclusively contain user agent strings.');
            }
        }
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

    /**
     * @throws Exception
     */
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

    /**
     * @return string[]
     */
    public function userAgents(): array
    {
        return $this->userAgents;
    }

    /**
     * @return RulePattern[]
     */
    public function disallowedPatterns(): array
    {
        return $this->disallowedPatterns;
    }

    /**
     * @return RulePattern[]
     */
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
     * @return RulePattern[]
     * @throws Exception
     */
    private function getMatchingDisallowedPatterns(Url $url): array
    {
        return $this->getMatchingPatterns($url, $this->disallowedPatterns());
    }

    /**
     * @return RulePattern[]
     * @throws Exception
     */
    private function getMatchingAllowedPatterns(Url $url): array
    {
        return $this->getMatchingPatterns($url, $this->allowedPatterns());
    }

    /**
     * @param RulePattern[] $patterns
     * @return RulePattern[]
     * @throws Exception
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
     * "The most specific match found MUST be used. The most specific match is the match that has the most octets.
     * If an allow and disallow rule is equivalent, the allow SHOULD be used."
     *
     * @param RulePattern[] $disallowedPatterns
     * @param RulePattern[] $allowedPatterns
     */
    private function isAllowedByMostSpecificMatch(array $disallowedPatterns, array $allowedPatterns): bool
    {
        $mostSpecificMatch = reset($allowedPatterns);

        if (!$mostSpecificMatch) { // There is no matching allowed pattern.
            return empty($disallowedPatterns);
        }

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
