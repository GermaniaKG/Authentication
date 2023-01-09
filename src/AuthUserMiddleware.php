<?php
namespace Germania\Authentication;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Aura\Session\SegmentInterface;


/**
 * This PSR-style Middleware injects a AuthUserInterface user instance into the current request,
 * and, after calling next middleware, stores the User ID in the Aura.Session segment.
 */
class AuthUserMiddleware implements MiddlewareInterface
{

    /**
     * @var AuthUserInterface
     */
    public $user;

    /**
     * @var SegmentInterface
     */
    public $session;


    /**
     * @var string
     */
    public $session_user_id_field;

    /**
     * @var LoggerInterface
     */
    public $logger;



    /**
     * @param AuthUserInterface    $user    User object, providing getId and setId methods.
     * @param SegmentInterface     $session Aura.Session Segment
     * @param LoggerInterface|null $logger  Optional: PSR-3 Logger
     */
    public function __construct (AuthUserInterface $user, SegmentInterface $session, $session_user_id_field, LoggerInterface $logger = null)
    {
        $this->user                  = $user;
        $this->session               = $session;
        $this->session_user_id_field = $session_user_id_field;
        $this->logger                = $logger ?: new NullLogger;
    }



    public function process(Request $request, RequestHandlerInterface $handler) : Response
    {

        // ---------------------------------------
        //  1. Store user in Request
        // ---------------------------------------

        $this->logger->info("Before Route: Inject user to request", [
            'user_id' => $this->user->getId()
        ]);
        $request = $request->withAttribute('user', $this->user) ;



        // ---------------------------------------
        //  2. Call next middleware.
        // ---------------------------------------
        $response = $handler->handle($request);



        // ---------------------------------------
        // 3. Store User ID in session.
        //    Get user from Request, just in case it should have changed.
        // ---------------------------------------
        $user = $request->getAttribute('user');

        if ($user instanceOf AuthUserInterface):
            $user_id = $user->getId();
            $this->logger->info("After Route: Store User ID in session", [
                'user_id' => $user_id ?: "(none)"
            ]);
            $this->session->set( $this->session_user_id_field, $user_id);

        else:
            $this->logger->warning("After Route: User is not instance of '".AuthUserInterface::class."'");
        endif;




        // ---------------------------------------
        // Finish
        // ---------------------------------------

        return $response;
    }



    /**
     * @param  \Psr\Http\Message\ServerRequestInterface  $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface       $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, $next)
    {

        // ---------------------------------------
        //  1. Store user in Request
        // ---------------------------------------

        $this->logger->info("Before Route: Inject user to request", [
            'user_id' => $this->user->getId()
        ]);
        $request = $request->withAttribute('user', $this->user) ;


        // ---------------------------------------
        //  2. Call next middleware.
        // ---------------------------------------
        $response = $next($request, $response);


        // ---------------------------------------
        // 3. Store User ID in session.
        //    Get user from Request, just in case it should have changed.
        // ---------------------------------------
        $user = $request->getAttribute('user');

        if ($user instanceOf AuthUserInterface):
            $user_id = $user->getId();
            $this->logger->info("After Route: Store User ID in session", [
                'user_id' => $user_id ?: "(none)"
            ]);
            $this->session->set( $this->session_user_id_field, $user_id);

        else:
            $this->logger->warning("After Route: User is not instance of '".AuthUserInterface::class."'");
        endif;




        // ---------------------------------------
        // Finish
        // ---------------------------------------

        return $response;
    }
}
