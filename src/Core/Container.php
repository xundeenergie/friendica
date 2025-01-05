<?php

namespace Friendica\Core;

use Dice\Dice;
use Friendica\Core\Logger\Capability\LogChannel;
use Friendica\DI;
use Psr\Log\LoggerInterface;

final class Container
{
	private Dice $container;

	protected function __construct(Dice $container)
	{
		$this->container = $container;
	}

	public static function fromDice(Dice $container): self {
		return new static($container);
	}

	public function setup(string $logChannel = LogChannel::DEFAULT, bool $withTemplateEngine = true)
	{
		$this->setupContainerForAddons();
		$this->setupContainerForLogger($logChannel);
		$this->setupLegacyServiceLocator();
		$this->registerErrorHandler();

		if ($withTemplateEngine) {
			$this->registerTemplateEngine();
		}
	}

	public function create(string $name, array $args = [], array $share = []): object
	{
		return $this->container->create($name, $args, $share);
	}

	public function addRule(string $name, array $rule):void
	{
		$this->container = $this->container->addRule($name, $rule);
	}

	private function setupContainerForAddons(): void
	{
		/** @var \Friendica\Core\Addon\Capability\ICanLoadAddons $addonLoader */
		$addonLoader = $this->container->create(\Friendica\Core\Addon\Capability\ICanLoadAddons::class);

		$this->container = $this->container->addRules($addonLoader->getActiveAddonConfig('dependencies'));
	}

	private function setupContainerForLogger(string $logChannel): void
	{
		$this->container = $this->container->addRule(LoggerInterface::class, [
			'constructParams' => [$logChannel],
		]);
	}

	private function setupLegacyServiceLocator(): void
	{
		DI::init($this->container);
	}

	private function registerErrorHandler(): void
	{
		\Friendica\Core\Logger\Handler\ErrorHandler::register($this->container->create(LoggerInterface::class));
	}

	private function registerTemplateEngine(): void
	{
		Renderer::registerTemplateEngine('Friendica\Render\FriendicaSmartyEngine');
	}
}
