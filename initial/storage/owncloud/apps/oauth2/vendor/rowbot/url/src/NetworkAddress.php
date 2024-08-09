<?php
namespace Rowbot\URL;

interface NetworkAddress
{
    /**
     * Checks to see if two NetworkAddresses are equal.
     *
     * @param self|string|null $address Another NetworkAddress, string, or null.
     *
     * @return bool
     */
    public function equals($address);

    /**
     * Serializes a network address.
     *
     * @return string
     */
    public function __toString();
}
