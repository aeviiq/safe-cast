<?php declare(strict_types=1);

namespace Aeviiq\SafeCast\Exception;

class TransformationFailedException extends InvalidArgumentException
{
    public static function unableToTransformValue($subject, string $to): TransformationFailedException
    {
        $msg = 'Unable to transform (%s) "%s" to "%s".';
        $type = \gettype($subject);
        if (\is_object($subject)) {
            $subject = \get_class($subject);
            $msg .= 'In order to cast an object, ensure you implement the __toString() method.';
        }

        return new static(\sprintf($msg, $type, $subject, $to));
    }
}
