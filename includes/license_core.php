<?php
/**
 * Compact activation helper with minimal surface.
 */
class LicenseCore
{
    /**
     * Encoded activation endpoint (base64).
     */
    private const EP = 'aHR0cHM6Ly9kaXp6eXNjcmlwdHMuY29tL2FjdGl2YXRlLw==';

    public static function endpoint(): string
    {
        return base64_decode(self::EP);
    }
}
