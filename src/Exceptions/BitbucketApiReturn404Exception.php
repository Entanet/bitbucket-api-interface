<?php

namespace Bitbucket\Exceptions;



class BitbucketApiReturn404Exception extends \Exception
{
    public function __construct($message = 'Bitbucket API returned with a status code of 404',
                                $code = 0,
                                \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
