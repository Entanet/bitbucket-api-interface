<?php


namespace Bitbucket\Exceptions;


class BitbucketApiReturnUnknownException extends \Exception
{
    public function __construct($httpStatusCode,
                                $message = 'Bitbucket API returned with a status code of: ',
                                $code = 0,
                                \Exception $previous = null)
    {
        parent::__construct(($message . $httpStatusCode), $code, $previous);
    }
}