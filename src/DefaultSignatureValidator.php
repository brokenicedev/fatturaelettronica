<?php
namespace Brokenice\FatturaElettronica;

class DefaultSignatureValidator implements SignatureValidator
{
    public function isValid($signature, $signingSecret, $body): bool
    {
        if (! $signature) {
            return false;
        }

        if (empty($signingSecret)) {
            throw new \Exception('Missing signature in request');
        }

        $computedSignature = hash_hmac('sha256', $body, $signingSecret);

        return hash_equals($computedSignature, $signature);
    }
}