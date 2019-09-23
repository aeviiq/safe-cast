<?php declare(strict_types=1);

namespace Aeviiq\SafeCast;

use Aeviiq\Collection\Collection;
use Aeviiq\Collection\CollectionInterface;
use Aeviiq\Collection\FloatCollection;
use Aeviiq\Collection\IntCollection;
use Aeviiq\Collection\ObjectCollection;
use Aeviiq\Collection\StringCollection;
use Aeviiq\SafeCast\Exception\TransformationFailedException;

final class Transformer
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
     * @throws TransformationFailedException When the given value could not be transformed to a string.
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

        throw TransformationFailedException::unableToTransformValue($value, 'string');
    }

    /**
     * String: Will be trimed and transformed to integer if the value is numeric.
     *
     * Boolean: Will be transformed to its float value. e.g.: true -> 1.0 and false -> 0.0.
     *
     * Integer: Will be transformed to its float value. e.g.: 12 -> 12.0.
     *
     * Object: Will be transformed to a float the same way a string is, if the object implements the __toString() method.
     *
     * @throws TransformationFailedException When the given value could not be transformed to a float.
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

        throw TransformationFailedException::unableToTransformValue($value, 'float');
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
     * @throws TransformationFailedException When the given value could not be transformed to a int.
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

        throw TransformationFailedException::unableToTransformValue($value, 'int');
    }

    /**
     * String: Will be trimed and transformed to integer if the value is numeric.
     *
     * Float: Will be transformed if the value is either 1.0 (true) or 0.0 (false).
     *
     * Integer: Will be transformed if the value is either 1 (true) or 0 (false).
     *
     * Object: Will be transformed to a boolean the same way a string is, if the object implements the __toString() method.
     *
     * @throws TransformationFailedException When the given value could not be transformed to a bool.
     */
    public static function toBool($value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_string($value)) {
            if ('1' === $value || 'true' === $value) {
                return true;
            }

            if ('0' === $value || 'false' === $value) {
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

        throw TransformationFailedException::unableToTransformValue($value, 'bool');
    }

    /**
     * String: Will be transformed to a StringCollection
     *
     * Float: Will be transformed to a FloatCollection
     *
     * Boolean: Can not be transformed.
     *
     * Integer: Will be transformed to an IntegerCollection
     *
     * Object: Will be transformed to an ObjectCollection
     *
     * Array: If all values in the array are of the same type, e.g. object, an ObjectCollection will be returned.
     *        The same rule applies for strings, integers and floats.
     *        If the array contains mixed values, a (mixed) Collection will be returned.
     *
     * @throws TransformationFailedException When the given value could not be transformed to a CollectionInterface.
     */
    public static function toCollection($value): CollectionInterface
    {
        if (\is_string($value)) {
            return new StringCollection([$value]);
        }

        if (\is_float($value)) {
            return new FloatCollection([$value]);
        }

        if (\is_int($value)) {
            return new IntCollection([$value]);
        }

        if (\is_object($value)) {
            return new ObjectCollection([$value]);
        }

        if (\is_array($value)) {
            $types = [];
            foreach ($value as $item) {
                $type = \gettype($item);
                if (!isset($types[$type])) {
                    $types[$type] = true;
                }

                if (\count($types) > 1) {
                    return new Collection($value);
                }
            }

            if (1 === \count($types)) {
                switch (\key($types)) {
                    case ('string' === $type):
                        return new StringCollection($value);
                    case ('double' === $type):
                        return new FloatCollection($value);
                    case ('integer' === $type):
                        return new IntCollection($value);
                    case ('object' === $type):
                        return new ObjectCollection($value);
                }
            }
        }

        throw TransformationFailedException::unableToTransformValue($value, CollectionInterface::class);
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
