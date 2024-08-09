<?php

namespace OCA\Metrics\Middleware;

use OC\AppFramework\Utility\ControllerMethodReflector;
use OC\ForbiddenException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Middleware;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;

class SharedSecretMiddleware extends Middleware {

	/**
	 * @var IRequest
	 */
	private $request;

	/**
	 * @var ControllerMethodReflector
	 */
	private $reflector;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var ILogger
	 */
	private $logger;

	/**
	 * SharedSecretMiddleware constructor.
	 *
	 * @param IRequest $request
	 * @param ControllerMethodReflector $reflector
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(
		IRequest $request,
		ControllerMethodReflector $reflector,
		IConfig $config,
		ILogger $logger
	) {
		$this->request = $request;
		$this->reflector = $reflector;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * @param Controller $controller
	 * @param string $methodName
	 *
	 * @return void
	 * @throws ForbiddenException
	 */
	public function beforeController($controller, $methodName) {
		if ($this->reflector->hasAnnotation('SharedSecretRequired')) {
			$this->validateSharedSecret();
			return;
		}
	}

	/**
	 * Validates the shared secret given in the request.
	 * If the shared secret is not set by the admin, no data is accessible.
	 * If the shared secret is set by the admin and it doesn't match the one provided in the request, no data is accesible.
	 *
	 * @return void
	 * @throws ForbiddenException
	 */
	private function validateSharedSecret() {
		$systemSharedSecret = $this->config->getSystemValue('metrics_shared_secret', null);
		if ($systemSharedSecret === null) {
			$this->logger->error("Metrics is not configured properly.");
			throw new ForbiddenException('You do not have sufficient permissions to access this endpoint. Please consult the System Administrator for access.');
		}
		$providedSharedSecret = $this->request->getHeader('OC-MetricsApiKey');
		if ($systemSharedSecret !== $providedSharedSecret) {
			throw new ForbiddenException('You do not have sufficient permissions to access this endpoint. Please consult the System Administrator for access.');
		}
	}
}
