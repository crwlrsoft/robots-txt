<?php

namespace Crwlr\RobotsTxt;

use Crwlr\RobotsTxt\Exceptions\InvalidRobotsTxtFileException;
use InvalidArgumentException;

final class RobotsTxt
{
    /**
     * @var array|UserAgentGroup[]
     */
    private array $userAgentGroups = [];

    /**
     * @param array|UserAgentGroup[] $userAgentGroups
     */
    public function __construct(array $userAgentGroups)
    {
        foreach ($userAgentGroups as $userAgentGroup) {
            if (!$userAgentGroup instanceof UserAgentGroup) {
                throw new InvalidArgumentException(
                    'Argument $userAgentGroups must exclusively contain objects of type UserAgentGroup.'
                );
            }
        }

        $this->userAgentGroups = $userAgentGroups;
    }

    /**
     * @throws InvalidRobotsTxtFileException
     */
    public static function parse(string $robotsTxtContent): RobotsTxt
    {
        return (new Parser())->parse($robotsTxtContent);
    }

    /**
     * @return array|UserAgentGroup[]
     */
    public function groups(): array
    {
        return $this->userAgentGroups;
    }

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
     * Find all groups that match a certain user agent string.
     *
     * @return array|UserAgentGroup[]
     */
    private function getGroupsMatchingUserAgent(string $userAgent): array
    {
        $matchingGroups = [];

        foreach ($this->groups() as $group) {
            if ($group->contains($userAgent)) {
                $matchingGroups[] = $group;
            }
        }

        return $matchingGroups;
    }

    /**
     * @param array|UserAgentGroup[] $groups
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
