<?php

namespace OCA\windows_network_drive\lib\custom_loggers;

use Symfony\Component\Console\Output\OutputInterface;
use OCP\Util;

class ConsoleLogger implements \OCP\ILogger {
	private $levelMap = [
		Util::DEBUG => OutputInterface::VERBOSITY_DEBUG,
		Util::INFO => OutputInterface::VERBOSITY_VERBOSE,
		Util::WARN => OutputInterface::VERBOSITY_NORMAL,
		Util::ERROR => OutputInterface::VERBOSITY_QUIET,
		Util::FATAL => OutputInterface::VERBOSITY_QUIET
	];

	private $simpleColorMap = [
		Util::FATAL => '<error>%s</error>',
		Util::ERROR => '<error>%s</error>',
		Util::WARN => '<info>%s</info>',
		Util::DEBUG => '<comment>%s</comment>'
	];

	private $outputInterface;
	public function __construct(OutputInterface $output) {
		$this->outputInterface = $output;
	}

	/**
	 * Get the wrapped OutputInterface or null if it isn't set
	 * @return OutputInterface|null
	 */
	public function getWrappedOutput() {
		return $this->outputInterface;
	}

	private static $globalConsoleLogger = null;

	public static function setGlobalConsoleLogger(ConsoleLogger $logger = null) {
		self::$globalConsoleLogger = $logger;
	}

	public static function getGlobalConsoleLogger() {
		return self::$globalConsoleLogger;
	}

	public function log($level, $message, array $context = []) {
		// handle verbosity level
		if (isset($this->levelMap[$level])) {
			$verbosity = $this->levelMap[$level];
		} else {
			$verbosity = OutputInterface::VERBOSITY_NORMAL;
		}
		// handle style
		if (isset($this->simpleColorMap[$level])) {
			$realMessage = \sprintf($this->simpleColorMap[$level], $message);
		} else {
			$realMessage = $message;
		}
		$this->outputInterface->writeln($realMessage, $verbosity);
	}

	public function emergency($message, array $context = []) {
		$this->log(Util::FATAL, $message, $context);
	}

	public function alert($message, array $context = []) {
		$this->log(Util::ERROR, $message, $context);
	}

	public function critical($message, array $context = []) {
		$this->log(Util::ERROR, $message, $context);
	}

	public function error($message, array $context = []) {
		$this->log(Util::ERROR, $message, $context);
	}

	public function warning($message, array $context = []) {
		$this->log(Util::WARN, $message, $context);
	}

	public function notice($message, array $context = []) {
		$this->log(Util::INFO, $message, $context);
	}

	public function info($message, array $context = []) {
		$this->log(Util::INFO, $message, $context);
	}

	public function debug($message, array $context = []) {
		$this->log(Util::DEBUG, $message, $context);
	}

	public function logException($exception, array $context = []) {
		$this->log(Util::ERROR, $exception->getMessage() . ' in file ' . $exception->getFile() . '#' . $exception->getLine(), $context);
	}
}
