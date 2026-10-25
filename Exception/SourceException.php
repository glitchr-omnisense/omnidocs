<?php

namespace Omnidocs\Exception;

/** What a source reads cannot be read: a file that is not the tool's output, a JSON or XML that does not parse. */
final class SourceException extends \RuntimeException implements ExceptionInterface
{
}
