<?php

/**
 * EduBot Exception Classes
 * 
 * Custom exception hierarchy for consistent error handling.
 * 
 * @package EduBot_Pro
 * @subpackage Exceptions
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base EduBot Exception
 */
class EduBot_Exception extends Exception {
    
    /**
     * HTTP Status Code
     * @var int
     */
    protected $http_code = 500;

    /**
     * Additional context data
     * @var array
     */
    protected $context = array();

    /**
     * Initialize exception with context
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception $previous Previous exception
     * @param int $http_code HTTP status code
     * @param array $context Additional context
     */
    public function __construct(
        $message = "",
        $code = 0,
        Exception $previous = null,
        $http_code = 500,
        $context = array()
    ) {
        parent::__construct($message, $code, $previous);
        $this->http_code = $http_code;
        $this->context = $context;
    }

    /**
     * Get HTTP status code
     * 
     * @return int HTTP code
     */
    public function getHttpCode() {
        return $this->http_code;
    }

    /**
     * Get context data
     * 
     * @return array Context
     */
    public function getContext() {
        return $this->context;
    }
}

/**
 * Database Exception
 */
class EduBot_Database_Exception extends EduBot_Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null, $context = array()) {
        parent::__construct($message, $code, $previous, 500, $context);
    }
}

/**
 * API Exception
 */
class EduBot_API_Exception extends EduBot_Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null, $context = array()) {
        parent::__construct($message, $code, $previous, 503, $context);
    }
}

/**
 * Validation Exception
 */
class EduBot_Validation_Exception extends EduBot_Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null, $context = array()) {
        parent::__construct($message, $code, $previous, 400, $context);
    }
}

/**
 * Configuration Exception
 */
class EduBot_Configuration_Exception extends EduBot_Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null, $context = array()) {
        parent::__construct($message, $code, $previous, 500, $context);
    }
}

/**
 * Authorization Exception
 */
class EduBot_Authorization_Exception extends EduBot_Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null, $context = array()) {
        parent::__construct($message, $code, $previous, 403, $context);
    }
}

/**
 * Not Found Exception
 */
class EduBot_Not_Found_Exception extends EduBot_Exception {
    public function __construct($message = "", $code = 0, Exception $previous = null, $context = array()) {
        parent::__construct($message, $code, $previous, 404, $context);
    }
}
