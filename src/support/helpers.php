<?php

namespace Sotvokun\Webman\Dfx;

use Webman\Http\Response;

function dfx_path(): string
{
    return base_path() . '/vendor/sotvokun/webman-dfx';
}

/**
 * Response
 * @param int $code
 * @param string $message
 * @param bool $acceptJson
 * @param array $jsonData
 * @param int $jsonOption
 * @return Response
 */
function response(int $code, string $message, bool $acceptJson = false, array $jsonData = [], int $jsonOption = JSON_UNESCAPED_UNICODE): Response
{
    if (!$acceptJson) {
        return \response($message, $code);
    }
    return new Response(
        $code,
        ['Content-Type' => 'application/json'],
        json_encode($jsonData, $jsonOption)
    );
}


/**
 * Checks value to find if it was serialized.
 * **NOTE** This function is refered from wordpress.
 * https://github.com/WordPress/wordpress-develop/blob/6.2/src/wp-includes/functions.php#L668
 *
 * @param string $data
 * @param bool $strict
 * @return bool
 */
function is_serialized(string $data, bool $strict = true): bool
{
    // If it isn't a string, it isn't serialized.
    if (!is_string($data)) {
        return false;
    }
    $data = trim($data);
    if ('N;' === $data) {
        return true;
    }
    if (strlen($data) < 4) {
        return false;
    }
    if (':' !== $data[1]) {
        return false;
    }
    if ($strict) {
        $lastc = substr($data, -1);
        if (';' !== $lastc && '}' !== $lastc) {
            return false;
        }
    } else {
        $semicolon = strpos($data, ';');
        $brace     = strpos($data, '}');
        // Either ; or } must exist.
        if (false === $semicolon && false === $brace) {
            return false;
        }
        // But neither must be in the first X characters.
        if (false !== $semicolon && $semicolon < 3) {
            return false;
        }
        if (false !== $brace && $brace < 4) {
            return false;
        }
    }
    $token = $data[0];
    switch ($token) {
        case 's':
            if ($strict) {
                if ('"' !== substr($data, -2, 1)) {
                    return false;
                }
            } elseif (false === strpos($data, '"')) {
                return false;
            }
            // Or else fall through.
        case 'a':
        case 'O':
        case 'E':
            return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
        case 'b':
        case 'i':
        case 'd':
            $end = $strict ? '$' : '';
            return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
    }
    return false;
}
