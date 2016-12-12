<?php
namespace Germania\Authentication;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Aura\Session\SegmentInterface;

class LogoutController
{

    /**
     * @var SegmentInterface
     */
    public $session;

    /**
     * @var Callable
     */
    public $cookie_setter;

    /**
     * @var Callable
     */
    public $deleter;

    /**
     * @var string
     */
    public $cookie_name;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var integer
     */
    public $status_code = 204;



    /**
     * @param SegmentInterface     $session       Aura.Session Segment
     * @param Callable             $cookie_setter Callable cookie setter that accepts cookie name, value and lifetime
     * @param string               $cookie_name   Name of Persistent Login Cookie
     * @param Callable             $deleter       Callable Persistent Login Deleter that accepts User ID
     * @param LoggerInterface|null $logger        Optional: PSR-3 Logger
     */
    public function __construct( SegmentInterface $session, Callable $cookie_setter, $cookie_name, Callable $deleter, LoggerInterface $logger = null )
    {
        $this->session               = $session;
        $this->cookie_setter         = $cookie_setter;
        $this->deleter               = $deleter;
        $this->cookie_name           = $cookie_name;
        $this->logger                = $logger ?: new NullLogger;
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
            throw new \RuntimeException("Attribute 'user' on PSR-7 ServerRequest: Instance of " . AuthUserInterface::class ." expected.");
        endif;


	    // --------------------------------------------
	    // 1. Delete all Cookies
	    // --------------------------------------------
        $cookie_setter = $this->cookie_setter;
        $cookie_key    = $this->cookie_name;

	    $this->logger->info("Delete Remember cookie", [
	        'cookie' => $cookie_key
	    ]);
        $cookie_setter($cookie_key, null, time()-3600);



	    // --------------------------------------------
	    // 2. Delete all stored Logins from database
	    // --------------------------------------------
        $user_id = $user->getId();

        $deleter = $this->deleter;
        $deleter($user_id);

        $this->logger->info("Deleted permanent logins", [
            'user_id' => $user_id
        ]);



	    // --------------------------------------------
	    // 3. Unset User ID, destroy session
	    // --------------------------------------------
        $session->clear();
        $user->setId( null );

        // --------------------------------------------
        // 4. Logoff success:
        //    Add 204 No Content to Response
        // --------------------------------------------
        return $response->withStatus( $this->status_code );


	}
}
