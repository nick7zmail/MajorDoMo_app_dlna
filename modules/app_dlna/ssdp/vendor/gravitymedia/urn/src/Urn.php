<?php
/**
 * This file is part of the Urn project
 *
 * @author Daniel Schröder <daniel.schroeder@gravitymedia.de>
 */

namespace GravityMedia\Urn;

use InvalidArgumentException;

/**
 * URN
 *
 * @package GravityMedia\Urn
 */
class Urn
{
    /**
     * Valid namespace identifier pattern
     */
    const VALID_NID_PATTERN = '[a-z0-9-][a-z0-9-]{0,31}';

    /**
     * Valid namespace specific string pattern
     */
    const VALID_NSS_PATTERN = '[a-z0-9()+,\-.:=@;$_!*\'%/?#]+';

    /**
     * @var string
     */
    protected $namespaceIdentifier;

    /**
     * @var string
     */
    protected $namespaceSpecificString;

    /**
     * Return string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Return URN as string
     *
     * @return string
     */
    public function toString()
    {
        return sprintf(
            'urn:%s:%s',
            $this->getNamespaceIdentifier(),
            $this->getNamespaceSpecificString()
        );
    }

    /**
     * Check if an URN equals this one
     *
     * @param Urn $urn
     *
     * @return bool
     */
    public function equals($urn)
    {
        if (!$urn instanceof Urn) {
            return false;
        }

        return $this->toString() === $urn->toString();
    }

    /**
     * Create URN from string
     *
     * @param string $string
     *
     * @throws InvalidArgumentException
     * @return $this
     */
    public static function fromString($string)
    {
        if (!self::isValid($string)) {
            throw new InvalidArgumentException(sprintf('Invalid URN string: %s', $string));
        }
        $tuple = explode(':', preg_replace('/^urn:/i', '', $string), 2);
        /** @var Urn $urn */
        $urn = new static();
        return $urn
            ->setNamespaceIdentifier(array_shift($tuple))
            ->setNamespaceSpecificString(array_shift($tuple));
    }

    /**
     * Check if a string is a valid URN
     *
     * @param string $string
     *
     * @return bool
     */
    public static function isValid($string)
    {
        $pattern = str_replace('/', '\/', self::VALID_NID_PATTERN . ':' . self::VALID_NSS_PATTERN);
        if (preg_match('/^' . $pattern . '$/i', preg_replace('/^urn\:/i', '', $string)) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Get namespace identifier
     *
     * @return string
     */
    public function getNamespaceIdentifier()
    {
        return $this->namespaceIdentifier;
    }

    /**
     * Set namespace identifier
     *
     * @param string $namespaceIdentifier
     *
     * @throws InvalidArgumentException
     * @return $this
     */
    public function setNamespaceIdentifier($namespaceIdentifier)
    {
        $pattern = str_replace('/', '\/', self::VALID_NID_PATTERN);
        if ('urn' === strtolower($namespaceIdentifier)
            || preg_match('/^' . $pattern . '$/i', $namespaceIdentifier) < 1
        ) {
            throw new InvalidArgumentException(sprintf('Invalid namespace identifier "%s"', $namespaceIdentifier));
        }
        $this->namespaceIdentifier = $namespaceIdentifier;
        return $this;
    }

    /**
     * Get namespace specific string
     *
     * @return string
     */
    public function getNamespaceSpecificString()
    {
        return $this->namespaceSpecificString;
    }

    /**
     * Set namespace specific string
     *
     * @param string $namespaceSpecificString
     *
     * @return $this
     */
    public function setNamespaceSpecificString($namespaceSpecificString)
    {
        $pattern = str_replace('/', '\/', self::VALID_NSS_PATTERN);
        if (preg_match('/^' . $pattern . '$/i', $namespaceSpecificString) < 1) {
            throw new InvalidArgumentException(
                sprintf('Invalid namespace specific string "%s"', $namespaceSpecificString)
            );
        }
        $this->namespaceSpecificString = $namespaceSpecificString;
        return $this;
    }
}
