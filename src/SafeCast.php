<?php declare(strict_types=1);

namespace Aeviiq\SafeCast;

use Aeviiq\SafeCast\Exception\CastingFailedException;

final class SafeCast
{
    /**
     * Float: Will be transformed to the string representation, while contain the decimals.
     *         e.g.: 1.0 -> "1.0" and 1.0001 -> "1.0001"
     *
     * Boolean: Will be transformed to "1" or "0".
     *
     * Integer: Will be transformed to their string value. e.g.: 55 -> "55"
     *
     * Object: Will be transformed if they have implemented the __toString() method.
     *
     * @throws CastingFailedException When the given value could not be transformed to a string.
     */
    public static function toString($value): string
    {
        if (\is_string($value)) {
            return $value;
        }

        if (\is_object($value)) {
            if (self::hasToStringMethod($value)) {
                return (string)$value;
            }
        }

        if (\is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (\is_float($value)) {
            return \number_format($value, self::getDecimalCount($value));
        }

        if (\is_int($value) || \is_resource($value)) {
            return (string)$value;
        }

        throw CastingFailedException::unableToTransformValue($value, 'string');
    }

    /**
     * String: Will be trimed and transformed to float if the value is numeric.
     *
     * Boolean: Will be transformed to its float value. e.g.: true -> 1.0 and false -> 0.0.
     *
     * Integer: Will be transformed to its float value. e.g.: 12 -> 12.0.
     *
     * Object: Will be transformed to a float the same way a string is, if the object implements the __toString() method.
     *
     * @throws CastingFailedException When the given value could not be transformed to a float.
     */
    public static function toFloat($value): float
    {
        if (\is_float($value)) {
            return $value;
        }

        if (\is_string($value)) {
            $value = \trim($value);
            if (\is_numeric($value)) {
                return (float)$value;
            }
        }

        if (\is_bool($value) || \is_int($value)) {
            return (float)$value;
        }

        if (\is_object($value)) {
            return self::toFloat(self::toString($value));
        }

        throw CastingFailedException::unableToTransformValue($value, 'float');
    }

    /**
     * String: Will be transformed to an integer if it is a numeric value that may contain trailing spaces,
     *          a + or - sign at the start. The exception on this rule is the case for floats.
     *
     * Float: Will be transformed to an integer if they only contain zeros as decimals.
     *
     * Boolean: can be transformed, true results in 1 and false in 0.
     *
     * Object: Will be transformed to an integer the same way a string is, if the object implements the __toString() method.
     *
     * @throws CastingFailedException When the given value could not be transformed to a int.
     */
    public static function toInt($value): int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_string($value)) {
            $value = \trim($value);
            if (\is_numeric($value) && \floor($value) == $value) {
                return (int)$value;
            }
        }

        if (\is_bool($value)) {
            return (int)$value;
        }

        if (\is_float($value) && \floor($value) == $value) {
            return (int)$value;
        }

        if (\is_object($value)) {
            return self::toInt(self::toString($value));
        }

        throw CastingFailedException::unableToTransformValue($value, 'int');
    }

    /**
     * String: Will be trimed and transformed to boolean if the value is '0', '1', 'true' or 'false'
     *
     * Float: Will be transformed if the value is either 1.0 (true) or 0.0 (false).
     *
     * Integer: Will be transformed if the value is either 1 (true) or 0 (false).
     *
     * Object: Will be transformed to a boolean the same way a string is, if the object implements the __toString() method.
     *
     * @throws CastingFailedException When the given value could not be transformed to a bool.
     */
    public static function toBool($value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_string($value)) {
            if ('1' === $value || 'true' === \strtolower($value)) {
                return true;
            }

            if ('0' === $value || 'false' === \strtolower($value)) {
                return false;
            }
        }

        if (\is_int($value)) {
            if (1 === $value) {
                return true;
            }

            if (0 === $value) {
                return false;
            }
        }

        if (\is_float($value)) {
            if (1.0 === $value) {
                return true;
            }

            if (0.0 === $value) {
                return false;
            }
        }

        if (\is_object($value)) {
            return self::toBool(self::toString($value));
        }

        throw CastingFailedException::unableToTransformValue($value, 'bool');
    }

    private static function hasToStringMethod(object $object): bool
    {
        return \method_exists($object, '__toString');
    }

    private static function getDecimalCount(float $value): int
    {
        $i = 0;
        while (true) {
            if ((string)$value === (string)round($value)) {
                break;
            }
            if (\is_infinite($value)) {
                break;
            }

            $value *= 10;
            $i++;
        }

        return $i === 0 ? 1 : $i;
    }
}
