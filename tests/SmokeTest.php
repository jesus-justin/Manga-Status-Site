<?php
use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testHealthCheckEndpointExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../health_check.php');
    }

    public function testTemplatesExist(): void
    {
        $this->assertFileExists(__DIR__ . '/../templates/header.php');
        $this->assertFileExists(__DIR__ . '/../templates/footer.php');
    }
}
