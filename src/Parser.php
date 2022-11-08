<?php

namespace Crwlr\RobotsTxt;

use Crwlr\RobotsTxt\Exceptions\InvalidRobotsTxtFileException;

final class Parser
{
    /**
     * @throws InvalidRobotsTxtFileException
     */
    public function parse(string $robotsTxtContent): RobotsTxt
    {
        $lines = explode("\n", $robotsTxtContent);
        $userAgentGroups = $sitemaps = [];

        for ($lineNumber = 0; $lineNumber < count($lines); $lineNumber++) {
            $line = $this->getLine($lines, $lineNumber);

            if ($this->isUserAgentLine($line)) {
                $userAgentGroup = $this->makeUserAgentGroup($lines, $line, $lineNumber);
                $userAgentGroups[] = $userAgentGroup;
            }

            if ($this->isRuleLine($line)) {
                if (!isset($userAgentGroup)) {
                    throw new InvalidRobotsTxtFileException('Rule (allow/disallow) line before any user-agent line.');
                }

                $this->addRuleToUserAgentGroup($line, $userAgentGroup);
            }

            if ($this->isSitemapLine($line)) {
                $sitemaps[] = $this->getSitemapFromLine($line);
            }
        }

        return new RobotsTxt($userAgentGroups, $sitemaps);
    }

    /**
     * @param string[] $lines
     */
    private function getLine(array $lines, int $lineNumber): string
    {
        return trim(
            $lines[$lineNumber],
            " \n\r\t\v\x00 ­﻿  ⁠​           ⠀͏‌"
        );
    }

    /**
     * @param string[] $lines
     */
    private function getNextLine(array $lines, int $lineNumber): ?string
    {
        if (array_key_exists(($lineNumber + 1), $lines)) {
            return $this->getLine($lines, ($lineNumber + 1));
        }

        return null;
    }

    private function isUserAgentLine(string $line): bool
    {
        return preg_match('/^\s?user-agent\s?:/i', $line) === 1;
    }

    private function isRuleLine(string $line): bool
    {
        return $this->isDisallowLine($line) || $this->isAllowLine($line);
    }

    private function isSitemapLine(string $line): bool
    {
        return preg_match('/^\s?sitemap\s?:/i', $line) === 1;
    }

    private function isDisallowLine(string $line): bool
    {
        return preg_match('/^\s?disallow\s?:/i', $line) === 1;
    }

    private function isAllowLine(string $line): bool
    {
        return preg_match('/^\s?allow\s?:/i', $line) === 1;
    }

    /**
     * @param string[] $lines
     */
    private function makeUserAgentGroup(array $lines, string $line, int &$lineNumber): UserAgentGroup
    {
        $userAgents = [$this->getUserAgentFromLine($line)];

        while (($nextLine = $this->getNextLine($lines, $lineNumber)) && $this->isUserAgentLine($nextLine)) {
            $userAgents[] = $this->getUserAgentFromLine($nextLine);
            $lineNumber++;
        }

        return new UserAgentGroup($userAgents);
    }

    private function addRuleToUserAgentGroup(string $line, UserAgentGroup $userAgentGroup): void
    {
        $rulePattern = $this->getPatternFromRuleLine($line);

        if (empty($rulePattern)) {
            return;
        }

        if ($this->isDisallowLine($line)) {
            $rulePattern = new RulePattern($rulePattern);
            $userAgentGroup->addDisallowedPattern($rulePattern);
        } elseif ($this->isAllowLine($line)) {
            $rulePattern = new RulePattern($rulePattern);
            $userAgentGroup->addAllowedPattern($rulePattern);
        }
    }

    private function getUserAgentFromLine(string $line): string
    {
        return $this->getStringAfterFirstColon($line);
    }

    private function getSitemapFromLine(string $line): string
    {
        return $this->getStringAfterFirstColon($line);
    }

    private function getPatternFromRuleLine(string $line): string
    {
        $lineAfterFirstColon = $this->getStringAfterFirstColon($line);

        return explode(' ', $lineAfterFirstColon)[0];
    }

    private function getStringAfterFirstColon(string $string): string
    {
        return trim(explode(':', $string, 2)[1]);
    }
}
