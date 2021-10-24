<?php

declare(strict_types=1);

use Crwlr\RobotsTxt\Encoding;
use PHPUnit\Framework\TestCase;

final class EncodingTest extends TestCase
{
    public function test_decodes_unnecessarily_encoded_characters(): void
    {
        $this->assertEquals('/foo/bar', Encoding::decodePercentEncodedAsciiCharactersInPath('/f%6F%6f/b%61r'));
    }

    public function test_not_decodes_non_ascii_characters(): void
    {
        $this->assertEquals('/f%F6%F6/b%E4r', Encoding::decodePercentEncodedAsciiCharactersInPath('/f%F6%F6/b%E4r'));
    }

    public function test_not_decodes_encoded_reserved_characters(): void
    {
        $this->assertEquals(
            '%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D',
            Encoding::decodePercentEncodedAsciiCharactersInPath(
                '%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D'
            )
        );
    }
}
