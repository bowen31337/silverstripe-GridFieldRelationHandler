<?php

declare(strict_types=1);

namespace Arillo\GridFieldRelationHandler\Tests;

use SilverStripe\Dev\SapphireTest;

/**
 * Base test case for GridFieldRelationHandler tests
 *
 * Provides common setup and helper methods for all tests
 */
class BaseTestCase extends SapphireTest
{
    /**
     * Whether to use a test database for this test
     *
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Common setup for all tests can go here
    }

    /**
     * Clean up test environment after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        // Common cleanup for all tests can go here
    }
}
