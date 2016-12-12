<?php
namespace Germania\Authentication;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Aura\Session\SegmentInterface;

class LoginController
{

    /**
     * @var array
     */
    public  $user_input = [
                'username' => null,
                'password' => null,
                'remember' => null
            ];

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var Callable
     */
    public $authenticator;

    /**
     * @var Callable
     */
    public $persistent_login_creator;


    /**
     * HTTP Status Code for "Unauthorized". Usually 401.
     * @var string
     */
    public $auth_required_status_code = 401;

    /**
     * HTTP Status Code for Responses after successful login. Usually 204.
     * @var string
     */
    public $authorized_status_code = 204;


    /**
     * @param SegmentInterface  $session                  Aura.Session Segment
     * @param array             $user_input               User input array with keys username, password, and (optional) remember
     * @param Callable          $authenticator            Callable that takes username and password and returns User ID.
     * @param Callable          $persistent_login_creator Callable for creating a persistent login for the User ID.
     * @param LoggerInterface   $logger                   Optional: PSR-3 Log
     */
    public function __construct( SegmentInterface $session, array $user_input, Callable $authenticator, Callable $persistent_login_creator, LoggerInterface $logger )
    {
        $this->session                  = $session;
        $this->user_input               = array_merge($this->user_input, $user_input);
        $this->authenticator            = $authenticator;
        $this->persistent_login_creator = $persistent_login_creator;
        $this->logger                   = $logger ?: new NullLogger;
    }



    /**
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
	public function __invoke(Request $request, Response $response) {


        // --------------------------------------------
        // Prerequisites
        // --------------------------------------------
        $session = $this->session;

        if (!$user = $request->getAttribute('user')
        or  !$user instanceOf AuthUserInterface) :
            throw new \RuntimeException("Attribute 'user' on PSR-7 ServerRequest: Instance of " . AuthUserInterface::class . " expected.");
        endif;


        // --------------------------------------------
        // 1. Retrieve user input
        // --------------------------------------------
        $user_input = filter_var_array($this->user_input, \FILTER_SANITIZE_STRING);
        extract ( $user_input );


        // --------------------------------------------
        // 2. Evaluate input
        // --------------------------------------------

        if (empty($username)
        and empty($password)):
            $this->logger->debug("No credentials given.");


        // Configure falsch messages
        elseif (empty($username)):
            $this->logger->warning("No username given.");
            $session->setFlash('username_help', 'Bitte geben Sie Ihren Anmeldenamen an!');

        elseif (empty($password)):
            $this->logger->warning("No password given.");
            $session->setFlash('password_help', 'Bitte geben Sie ein Passwort an!');
        else:
            // Both fields are set
        endif;


        // Set response status
        if (empty($username) or empty($password)):
            $this->logger->debug("Incomplete user input; Set 'Unauthorized' header", [
                'status' => $this->auth_required_status_code
            ]);
            return $response->withStatus( $this->auth_required_status_code );
        endif;


        // --------------------------------------------
        // 3. Authenticate and set User's ID
        // --------------------------------------------
        $authenticator = $this->authenticator;
        $user_id = ($username and $password)
        ? $authenticator($username, $password)
        : null;

        $user->setId( $user_id );


        // --------------------------------------------
        // 4. User ID empty? Authentication failed!
        //    Add "401 Unauthorized" Header to Response
        // --------------------------------------------
        if (!$user_id):
            $session->setFlash('form_message', 'Mit den Zugangsdaten stimmt etwas nicht. Liegt vielleicht ein Tippfehler vor?');
            $this->logger->warning("Authentication failed; Set 'Unauthorized' Header", [
                'status' => $this->auth_required_status_code
            ]);
            return $response->withStatus( $this->auth_required_status_code );
        endif;


        // --------------------------------------------
        // 5. Create persistent Login if needed
        // --------------------------------------------
        if ($remember):
             $this->logger->info("Create persistent login");
             $persistent_login_creator = $this->persistent_login_creator;
             $persistent_login_creator( $user_id );

        else:
            $this->logger->debug("No persistent login requested");
        endif;


        // --------------------------------------------
        // 6. Authentication success:
        //    Add 204 No Content to Response
        // --------------------------------------------
	    return $response->withStatus( $this->authorized_status_code );

	}
}
