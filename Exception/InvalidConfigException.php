<?php

namespace Omnidocs\Exception;

/** A source asked for with options it cannot work with: a folder that is not there, an option missing. */
final class InvalidConfigException extends \InvalidArgumentException implements ExceptionInterface
{
}
