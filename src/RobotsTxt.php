<?php

namespace Crwlr\RobotsTxt;

use Crwlr\RobotsTxt\Exceptions\InvalidRobotsTxtFileException;
use Exception;
use InvalidArgumentException;

final class RobotsTxt
{
    /**
     * @param UserAgentGroup[] $userAgentGroups
     * @param string[] $sitemaps
     */
    public function __construct(private array $userAgentGroups, private array $sitemaps = [])
    {
        foreach ($userAgentGroups as $userAgentGroup) {
            if (!$userAgentGroup instanceof UserAgentGroup) {
                throw new InvalidArgumentException(
                    'Argument $userAgentGroups must exclusively contain objects of type UserAgentGroup.'
                );
            }
        }
    }

    /**
     * @throws InvalidRobotsTxtFileException
     */
    public static function parse(string $robotsTxtContent): RobotsTxt
    {
        return (new Parser())->parse($robotsTxtContent);
    }

    /**
     * @return UserAgentGroup[]
     */
    public function groups(): array
    {
        return $this->userAgentGroups;
    }

    /**
     * @return string[]
     */
    public function sitemaps(): array
    {
        return $this->sitemaps;
    }

    /**
     * @throws Exception
     */
    public function isAllowed(string $uri, string $userAgent): bool
    {
        $matchingGroups = $this->getGroupsMatchingUserAgent($userAgent);
        $groupCount = count($matchingGroups);

        if ($groupCount === 0) {
            return true;
        }

        $group = $groupCount === 1 ? $matchingGroups[0] : $this->combineGroups($matchingGroups);

        return $group->isAllowed($uri);
    }

    /**
     * @throws Exception
     */
    public function isExplicitlyNotAllowedFor(string $uri, string $userAgent): bool
    {
        $matchingGroups = $this->getGroupsMatchingUserAgent($userAgent, false);

        $groupCount = count($matchingGroups);

        if ($groupCount === 0) {
            return false;
        }

        $group = $groupCount === 1 ? $matchingGroups[0] : $this->combineGroups($matchingGroups);

        return !$group->isAllowed($uri);
    }

    /**
     * Find all groups that match a certain user agent string.
     *
     * @param bool $includeWildcard  Set to false if wildcard (*) should not count (user agent explicitly in group)
     * @return UserAgentGroup[]
     */
    private function getGroupsMatchingUserAgent(string $userAgent, bool $includeWildcard = true): array
    {
        $matchingGroups = [];

        foreach ($this->groups() as $group) {
            if ($group->contains($userAgent, $includeWildcard)) {
                $matchingGroups[] = $group;
            }
        }

        return $matchingGroups;
    }

    /**
     * @param UserAgentGroup[] $groups
     */
    private function combineGroups(array $groups): UserAgentGroup
    {
        $combinedGroup = new UserAgentGroup(['*']);

        foreach ($groups as $group) {
            foreach ($group->disallowedPatterns() as $disallowedPattern) {
                $combinedGroup->addDisallowedPattern($disallowedPattern);
            }

            foreach ($group->allowedPatterns() as $allowedPattern) {
                $combinedGroup->addAllowedPattern($allowedPattern);
            }
        }

        return $combinedGroup;
    }
}
