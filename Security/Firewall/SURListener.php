<?php
namespace SUR\SecurityBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use SUR\SecurityBundle\Security\Authentication\Token\SURUserToken;

class SURListener implements ListenerInterface
{
	protected $securityContext;
	protected $authenticationManager;

	public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager)
	{
		$this->securityContext = $securityContext;
		$this->authenticationManager = $authenticationManager;
	}

	public function handle(GetResponseEvent $event)
	{
		$request = $event->getRequest();

		if(!$request->query->has("token")){
			return;
		}
		
		$token = new SURUserToken("ANONIMO", $request->query->get("token"));

		try {
			$authToken = $this->authenticationManager->authenticate($token);
			$this->securityContext->setToken($authToken);

			return;
		} catch (AuthenticationException $failed) {
			// ... you might log something here

			// To deny the authentication clear the token. This will redirect to the login page.
			// Make sure to only clear your token, not those of other authentication listeners.
			// $token = $this->securityContext->getToken();
			// if ($token instanceof WsseUserToken && $this->providerKey === $token->getProviderKey()) {
			//     $this->securityContext->setToken(null);
			// }
			// return;
			$this->securityContext->setToken(null);
			return;
			// Deny authentication with a '403 Forbidden' HTTP response
			$response = new Response();
			$response->setStatusCode(Response::HTTP_FORBIDDEN);
			$event->setResponse($response);

		}

		// By default deny authorization
		$response = new Response();
		$response->setStatusCode(Response::HTTP_FORBIDDEN);
		$event->setResponse($response);
	}
}