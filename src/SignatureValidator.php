<?php

namespace Brokenice\FatturaElettronica;

interface SignatureValidator
{
    public function isValid($signature, $signingSecret, $body): bool;
}