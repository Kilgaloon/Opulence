<?php
/**
 * Copyright (C) 2015 David Young
 *
 * Defines the CSRF token checker
 */
namespace Opulence\Framework\HTTP;
use Opulence\Cryptography\Utilities\Strings;
use Opulence\HTTP\Requests\Request;
use Opulence\Sessions\ISession;

class CSRFTokenChecker
{
    /** The token input name */
    const TOKEN_INPUT_NAME = "__OPULENCE_CSRF_TOKEN";

    /** @var Strings The string utility */
    private $strings = null;

    /**
     * @param Strings $strings The string utility
     */
    public function __construct(Strings $strings)
    {
        $this->strings = $strings;
    }

    /**
     * Checks if the token is valid
     *
     * @param Request $request The current request
     * @param ISession $session The current session
     * @return bool True if the token is valid, otherwise false
     */
    public function tokenIsValid(Request $request, ISession $session)
    {
        if(!$session->has(self::TOKEN_INPUT_NAME))
        {
            $session->set(self::TOKEN_INPUT_NAME, $this->strings->generateRandomString(32));
        }

        if($this->tokenShouldNotBeChecked($request))
        {
            return true;
        }

        // Try an input
        $token = $request->getInput(self::TOKEN_INPUT_NAME);

        // Try the X-CSRF header
        if($token === null)
        {
            $token = $request->getHeaders()->get("X-CSRF-TOKEN");
        }

        // Try the X-XSRF header
        if($token === null)
        {
            $token = $request->getHeaders()->get("X-XSRF-TOKEN");
        }

        return $this->strings->isEqual($session->get(self::TOKEN_INPUT_NAME), $token);
    }

    /**
     * Gets whether or not the token should even be checked
     *
     * @param Request $request The current request
     * @return bool True if the token should be checked, otherwise false
     */
    private function tokenShouldNotBeChecked(Request $request)
    {
        return in_array($request->getMethod(), [Request::METHOD_GET, Request::METHOD_HEAD, Request::METHOD_OPTIONS]);
    }
}